<?php

namespace Tests\Feature;

use App\Models\{Customer, Category, Product, StockMovement, Supplier, Sale, SalePayment, Loan, LoanPayment, Transaction, DebitCredit, PaymentChannel, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SupplierDebtOffsetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $productA;
    protected Product $productB;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        PaymentChannel::firstOrCreate(
            ['slug' => 'cash'],
            ['name' => 'Cash', 'is_active' => true]
        );
        PaymentChannel::firstOrCreate(
            ['slug' => 'supplier_offset'],
            ['name' => 'Supplier Debt Offset', 'is_active' => true]
        );

        $this->user = User::factory()->create();
        Gate::before(fn () => true);

        $category = Category::create([
            'name' => 'Wines',
            'kind' => 'product',
        ]);

        $this->productA = Product::create([
            'name'        => 'Red Wine Reserve',
            'sku'         => 'WINE-RED',
            'quantity'    => 50,
            'price'       => 15000,
            'cost_price'  => 10000,
            'category_id' => $category->id,
        ]);

        StockMovement::create([
            'product_id'  => $this->productA->id,
            'type'        => 'in',
            'quantity'    => 50,
            'unit_cost'   => 10000,
            'total_cost'  => 500000,
            'source_type' => Product::class,
            'source_id'   => $this->productA->id,
            'reason'      => 'Initial Stock',
            'date'        => Carbon::today(),
        ]);

        $this->productB = Product::create([
            'name'        => 'White Wine Vintage',
            'sku'         => 'WINE-WHT',
            'quantity'    => 50,
            'price'       => 10000,
            'cost_price'  => 7000,
            'category_id' => $category->id,
        ]);

        StockMovement::create([
            'product_id'  => $this->productB->id,
            'type'        => 'in',
            'quantity'    => 50,
            'unit_cost'   => 7000,
            'total_cost'  => 350000,
            'source_type' => Product::class,
            'source_id'   => $this->productB->id,
            'reason'      => 'Initial Stock',
            'date'        => Carbon::today(),
        ]);

        $this->supplier = Supplier::create([
            'name'    => 'Kigali Beverage Distributors',
            'phone'   => '0789001122',
            'email'   => 'supplier@kigalibev.com',
            'address' => 'Gikondo Industrial Zone',
        ]);
    }

    public function test_supplier_can_exchange_products_to_offset_taken_loans_fifo(): void
    {
        // 1. Create two pending 'taken' loans representing store debt to this supplier
        // Loan 1 (older by date): 30,000 RWF
        $oldLoan = Loan::create([
            'supplier_id' => $this->supplier->id,
            'type'        => 'taken',
            'amount'      => 30000,
            'loan_date'   => Carbon::today()->subDays(5),
            'status'      => 'pending',
            'notes'       => 'Purchase credit batch #1',
        ]);

        // Loan 2 (newer by date): 50,000 RWF
        $newLoan = Loan::create([
            'supplier_id' => $this->supplier->id,
            'type'        => 'taken',
            'amount'      => 50000,
            'loan_date'   => Carbon::today()->subDays(2),
            'status'      => 'pending',
            'notes'       => 'Purchase credit batch #2',
        ]);

        $this->assertEquals(80000, $this->supplier->fresh()->total_owed);
        $this->assertEquals(2, $this->supplier->fresh()->pending_taken_loans_count);

        // 2. Supplier takes products totaling 45,000 RWF (3 bottles of Red Wine @ 15,000)
        $response = $this->actingAs($this->user)->post(route('sales.store'), [
            'cust_mode'       => 'supplier',
            'supplier_id'     => $this->supplier->id,
            'sale_date'       => Carbon::today()->format('Y-m-d'),
            'payment_channel' => 'supplier_offset',
            'amount_paid'     => 45000,
            'products'        => [
                [
                    'product_id' => $this->productA->id,
                    'quantity'   => 3,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $sale = Sale::where('supplier_id', $this->supplier->id)->latest()->first();
        $this->assertNotNull($sale);
        $this->assertEquals(45000, (float) $sale->total_amount);
        $this->assertEquals(45000, (float) $sale->amount_paid);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(0.0, (float) $sale->balance);

        // 3. Verify stock movement was registered
        $this->assertDatabaseHas('stock_movements', [
            'product_id'  => $this->productA->id,
            'type'        => 'out',
            'quantity'    => 3,
            'source_type' => Sale::class,
            'source_id'   => $sale->id,
        ]);
        $this->assertEquals(47, $this->productA->currentStock());

        // 4. Verify NO false customer loan was created
        $this->assertEquals(0, Loan::where('sale_id', $sale->id)->count());

        // 5. Verify NO false monetary inflow drawer transaction was created
        $this->assertEquals(0, Transaction::where('sale_id', $sale->id)->count());

        // 6. Verify FIFO Allocation on Loans:
        // Old loan (30,000): should be 100% paid and marked 'paid'!
        $oldLoan->refresh();
        $this->assertEquals('paid', $oldLoan->status);
        $this->assertEquals(30000, $oldLoan->paid);
        $this->assertEquals(0, $oldLoan->remaining);

        // New loan (50,000): should have 15,000 paid and 35,000 remaining, status 'pending'
        $newLoan->refresh();
        $this->assertEquals('pending', $newLoan->status);
        $this->assertEquals(15000, $newLoan->paid);
        $this->assertEquals(35000, $newLoan->remaining);

        // Store now owes 35,000 RWF total
        $this->assertEquals(35000, $this->supplier->fresh()->total_owed);
        $this->assertEquals(1, $this->supplier->fresh()->pending_taken_loans_count);

        // 7. Verify LoanPayments have sale_id linked
        $this->assertEquals(2, LoanPayment::where('sale_id', $sale->id)->count());
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $oldLoan->id,
            'sale_id' => $sale->id,
            'amount'  => 30000,
            'method'  => 'supplier_offset',
        ]);
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $newLoan->id,
            'sale_id' => $sale->id,
            'amount'  => 15000,
            'method'  => 'supplier_offset',
        ]);
    }

    public function test_pos_terminal_checkout_with_supplier_offset(): void
    {
        $loan = Loan::create([
            'supplier_id' => $this->supplier->id,
            'type'        => 'taken',
            'amount'      => 20000,
            'loan_date'   => Carbon::today(),
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('pos.checkout'), [
            'cust_mode'       => 'supplier',
            'supplier_id'     => $this->supplier->id,
            'sale_date'       => Carbon::today()->format('Y-m-d'),
            'payment_channel' => 'supplier_offset',
            'amount_paid'     => 20000,
            'products'        => [
                [
                    'product_id' => $this->productB->id,
                    'quantity'   => 2,
                    'unit_price' => 10000,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'          => true,
            'supplier_settled' => true,
            'total_amount'     => 20000,
        ]);

        $loan->refresh();
        $this->assertEquals('paid', $loan->status);
        $this->assertEquals(0, $this->supplier->fresh()->total_owed);
    }

    public function test_split_payment_with_supplier_offset_and_cash(): void
    {
        // Store owes 15,000
        $loan = Loan::create([
            'supplier_id' => $this->supplier->id,
            'type'        => 'taken',
            'amount'      => 15000,
            'loan_date'   => Carbon::today(),
            'status'      => 'pending',
        ]);

        // Supplier takes products worth 25,000 (15,000 offset + 10,000 cash)
        $response = $this->actingAs($this->user)->post(route('sales.store'), [
            'cust_mode'   => 'supplier',
            'supplier_id' => $this->supplier->id,
            'sale_date'   => Carbon::today()->format('Y-m-d'),
            'products'    => [
                [
                    'product_id' => $this->productA->id,
                    'quantity'   => 1,
                    'unit_price' => 15000,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity'   => 1,
                    'unit_price' => 10000,
                ],
            ],
            'payments'    => [
                [
                    'method' => 'supplier_offset',
                    'amount' => 15000,
                ],
                [
                    'method' => 'cash',
                    'amount' => 10000,
                ],
            ],
        ]);

        $sale = Sale::where('supplier_id', $this->supplier->id)->latest()->first();
        $this->assertNotNull($sale);
        $this->assertEquals(25000, (float) $sale->total_amount);
        $this->assertEquals(25000, (float) $sale->amount_paid);

        $loan->refresh();
        $this->assertEquals('paid', $loan->status);

        // Monetary transaction should ONLY be for the 10,000 cash!
        $this->assertDatabaseHas('transactions', [
            'sale_id' => $sale->id,
            'amount'  => 10000,
        ]);
        $this->assertDatabaseMissing('transactions', [
            'sale_id' => $sale->id,
            'amount'  => 25000,
        ]);
    }

    public function test_deleting_sale_reverses_supplier_loan_payments_and_reopens_loan(): void
    {
        $loan = Loan::create([
            'supplier_id' => $this->supplier->id,
            'type'        => 'taken',
            'amount'      => 15000,
            'loan_date'   => Carbon::today(),
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user)->post(route('sales.store'), [
            'cust_mode'       => 'supplier',
            'supplier_id'     => $this->supplier->id,
            'sale_date'       => Carbon::today()->format('Y-m-d'),
            'payment_channel' => 'supplier_offset',
            'amount_paid'     => 15000,
            'products'        => [
                [
                    'product_id' => $this->productA->id,
                    'quantity'   => 1,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $sale = Sale::where('supplier_id', $this->supplier->id)->latest()->first();
        $loan->refresh();
        $this->assertEquals('paid', $loan->status);

        // Delete the sale
        $this->actingAs($this->user)->delete(route('sales.destroy', $sale));

        // Loan must be reopened to 'pending' with full 15,000 remaining!
        $loan->refresh();
        $this->assertEquals('pending', $loan->status);
        $this->assertEquals(15000, $loan->remaining);
        $this->assertEquals(0, $loan->paid);
        $this->assertEquals(0, LoanPayment::where('sale_id', $sale->id)->count());
    }
}
