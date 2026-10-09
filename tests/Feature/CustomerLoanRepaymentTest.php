<?php

namespace Tests\Feature;

use App\Models\{Customer, Category, Product, StockMovement, Sale, SalePayment, Loan, LoanPayment, Transaction, DebitCredit, PaymentChannel, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CustomerLoanRepaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        PaymentChannel::firstOrCreate(
            ['slug' => 'cash'],
            ['name' => 'Cash', 'is_active' => true]
        );
        PaymentChannel::firstOrCreate(
            ['slug' => 'momo'],
            ['name' => 'Mobile Money', 'is_active' => true]
        );

        $this->user = User::factory()->create();
        Gate::before(fn () => true);

        $category = Category::create([
            'name' => 'Beverages',
            'kind' => 'product',
        ]);

        $this->product = Product::create([
            'name'        => 'Champagne Brut',
            'sku'         => 'CHAMP-01',
            'quantity'    => 50,
            'price'       => 10000,
            'cost_price'  => 6000,
            'category_id' => $category->id,
        ]);

        StockMovement::create([
            'product_id'  => $this->product->id,
            'type'        => 'in',
            'quantity'    => 50,
            'unit_cost'   => 6000,
            'total_cost'  => 300000,
            'source_type' => Product::class,
            'source_id'   => $this->product->id,
            'reason'      => 'Opening Stock',
            'date'        => Carbon::today(),
        ]);

        $this->customer = Customer::create([
            'name'    => 'David Mugisha',
            'phone'   => '0788123456',
            'email'   => 'david@example.com',
            'address' => 'Kigali, Rwanda',
        ]);
    }

    public function test_credit_sale_creates_pending_loan_and_updates_customer_debt(): void
    {
        // 1. Create a credit sale: Total 20,000, paid 5,000 upfront, unpaid 15,000
        $sale = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today(),
            'total_amount'    => 20000,
            'amount_paid'     => 5000,
            'payment_channel' => 'cash',
            'status'          => 'pending',
            'is_gift'         => false,
        ]);

        SalePayment::create([
            'sale_id' => $sale->id,
            'method'  => 'cash',
            'amount'  => 5000,
            'paid_at' => Carbon::today(),
            'user_id' => $this->user->id,
        ]);

        // SaleObserver automatically creates the loan for the unpaid 15,000
        $loan = Loan::where('sale_id', $sale->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals(15000, $loan->amount);

        $this->customer->refresh();

        $this->assertEquals(15000, $this->customer->total_debt);
        $this->assertEquals(1, $this->customer->pending_loans_count);
        $this->assertEquals(15000, $loan->remaining);
    }

    public function test_partial_loan_repayment_reduces_debt_and_correctly_updates_sale_amount_paid(): void
    {
        // Sale: 20,000 total, 5,000 upfront, loan = 15,000
        $sale = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today(),
            'total_amount'    => 20000,
            'amount_paid'     => 5000,
            'payment_channel' => 'cash',
            'status'          => 'pending',
            'is_gift'         => false,
        ]);

        SalePayment::create([
            'sale_id' => $sale->id,
            'method'  => 'cash',
            'amount'  => 5000,
            'paid_at' => Carbon::today(),
            'user_id' => $this->user->id,
        ]);

        $loan = Loan::where('sale_id', $sale->id)->first();
        $this->assertNotNull($loan);

        // Pay a partial installment of 6,000 via customers.pay-loans
        $response = $this->actingAs($this->user)->post(route('customers.pay-loans', $this->customer), [
            'amount'       => 6000,
            'payment_date' => Carbon::today()->toDateString(),
            'method'       => 'momo',
            'notes'        => 'Partial installment #1',
        ]);

        $response->assertRedirect(route('customers.show', $this->customer));

        $loan->refresh();
        $sale->refresh();
        $this->customer->refresh();

        // 1. Verify Loan was partially paid: 15,000 - 6,000 = 9,000 remaining
        $this->assertEquals('pending', $loan->status);
        $this->assertEquals(9000, $loan->remaining);
        $this->assertEquals(6000, $loan->paid);

        // 2. CRITICAL BUGFIX TEST: Sale amount_paid should be 5,000 + 6,000 = 11,000 (NOT just 6,000!)
        $this->assertEquals(11000, $sale->amount_paid);
        $this->assertEquals('pending', $sale->status);

        // 3. Customer total debt should now be 9,000
        $this->assertEquals(9000, $this->customer->total_debt);

        // 4. Verify accounting records were created
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'amount'  => 6000,
            'method'  => 'momo',
        ]);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'loan_id'     => $loan->id,
            'amount'      => 6000,
            'type'        => 'credit',
        ]);
    }

    public function test_final_installment_settles_loan_and_completes_sale(): void
    {
        $sale = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today(),
            'total_amount'    => 20000,
            'amount_paid'     => 5000,
            'payment_channel' => 'cash',
            'status'          => 'pending',
            'is_gift'         => false,
        ]);

        $loan = Loan::where('sale_id', $sale->id)->first();
        $this->assertNotNull($loan);

        // First installment: 5,000
        $this->actingAs($this->user)->post(route('customers.pay-loans', $this->customer), [
            'amount'       => 5000,
            'payment_date' => Carbon::today()->toDateString(),
            'method'       => 'cash',
        ]);

        // Second installment: 10,000 (clearing remaining 10,000)
        $this->actingAs($this->user)->post(route('customers.pay-loans', $this->customer), [
            'amount'       => 10000,
            'payment_date' => Carbon::today()->toDateString(),
            'method'       => 'momo',
        ]);

        $loan->refresh();
        $sale->refresh();
        $this->customer->refresh();

        // 1. Loan should now be paid in full
        $this->assertEquals('paid', $loan->status);
        $this->assertEquals(0, $loan->remaining);
        $this->assertEquals(15000, $loan->paid);

        // 2. Sale should now be completed in full
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(20000, $sale->amount_paid);

        // 3. Customer total debt should be 0
        $this->assertEquals(0, $this->customer->total_debt);
        $this->assertEquals(0, $this->customer->pending_loans_count);
    }

    public function test_fifo_payment_across_multiple_customer_loans(): void
    {
        // Loan 1 (Oldest): 10,000
        $sale1 = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today()->subDays(5),
            'total_amount'    => 10000,
            'amount_paid'     => 0,
            'payment_channel' => 'cash',
            'status'          => 'pending',
        ]);

        $loan1 = Loan::where('sale_id', $sale1->id)->first();
        $this->assertNotNull($loan1);

        // Loan 2 (Newer): 25,000
        $sale2 = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today()->subDays(1),
            'total_amount'    => 25000,
            'amount_paid'     => 0,
            'payment_channel' => 'cash',
            'status'          => 'pending',
        ]);

        $loan2 = Loan::where('sale_id', $sale2->id)->first();
        $this->assertNotNull($loan2);

        $this->customer->refresh();
        $this->assertEquals(35000, $this->customer->total_debt);
        $this->assertEquals(2, $this->customer->pending_loans_count);

        // Customer pays 18,000 in a single transaction:
        // Loan 1 should get 10,000 (fully paid)
        // Loan 2 should get 8,000 (17,000 remaining)
        $response = $this->actingAs($this->user)->post(route('customers.pay-loans', $this->customer), [
            'amount'       => 18000,
            'payment_date' => Carbon::today()->toDateString(),
            'method'       => 'momo',
            'notes'        => 'Lump sum installment payment',
        ]);

        $response->assertRedirect(route('customers.show', $this->customer));

        $loan1->refresh();
        $loan2->refresh();
        $sale1->refresh();
        $sale2->refresh();
        $this->customer->refresh();

        // Loan 1 fully paid
        $this->assertEquals('paid', $loan1->status);
        $this->assertEquals(0, $loan1->remaining);
        $this->assertEquals('completed', $sale1->status);
        $this->assertEquals(10000, $sale1->amount_paid);

        // Loan 2 partially paid
        $this->assertEquals('pending', $loan2->status);
        $this->assertEquals(17000, $loan2->remaining);
        $this->assertEquals(8000, $loan2->paid);
        $this->assertEquals('pending', $sale2->status);
        $this->assertEquals(8000, $sale2->amount_paid);

        // Overall Customer debt is now 17,000
        $this->assertEquals(17000, $this->customer->total_debt);
        $this->assertEquals(1, $this->customer->pending_loans_count);
    }

    public function test_customer_pages_render_successfully(): void
    {
        $sale = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today(),
            'total_amount'    => 15000,
            'amount_paid'     => 5000,
            'payment_channel' => 'cash',
            'status'          => 'pending',
        ]);

        $loan = Loan::where('sale_id', $sale->id)->first();
        $this->assertNotNull($loan);

        // 1. Customers list page
        $resIndex = $this->actingAs($this->user)->get(route('customers.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('David Mugisha');
        $resIndex->assertSee('10,000.00');

        // 2. Customer show/account page
        $resShow = $this->actingAs($this->user)->get(route('customers.show', $this->customer));
        $resShow->assertOk();
        $resShow->assertSee('David Mugisha');
        $resShow->assertSee('10,000.00');
        $resShow->assertSee('Active Credit Sales');
    }
}
