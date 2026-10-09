<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StatementController;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DebitCredit;
use App\Models\Loan;
use App\Models\PaymentChannel;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BossGiftSaleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure payment channels exist
        PaymentChannel::firstOrCreate(
            ['slug' => 'boss_gift'],
            ['name' => 'Boss Gift', 'is_active' => true]
        );
        PaymentChannel::firstOrCreate(
            ['slug' => 'cash'],
            ['name' => 'Cash', 'is_active' => true]
        );

        $this->user = User::factory()->create();
        Gate::before(fn () => true); // Authorize all actions for testing

        $category = Category::create([
            'name' => 'Wines',
            'kind' => 'product',
        ]);

        $this->product = Product::create([
            'name'        => 'Test Wine',
            'sku'         => 'WINE-001',
            'quantity'    => 20,
            'price'       => 10000,
            'cost_price'  => 7000,
            'category_id' => $category->id,
        ]);

        // Add opening stock movement so Product::currentStock() is 20
        StockMovement::create([
            'product_id'  => $this->product->id,
            'type'        => 'in',
            'quantity'    => 20,
            'unit_cost'   => 7000,
            'total_cost'  => 140000,
            'source_type' => Product::class,
            'source_id'   => $this->product->id,
            'reason'      => 'Initial Stock',
            'date'        => Carbon::today(),
        ]);

        $this->customer = Customer::create([
            'name'  => 'VIP Customer',
            'phone' => '0780000000',
        ]);
    }

    public function test_boss_gift_sale_model_and_observer_prevents_loan_and_monetary_ledger(): void
    {
        // Create a boss gift sale directly
        $sale = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today(),
            'total_amount'    => 20000,
            'amount_paid'     => 20000,
            'payment_channel' => 'boss_gift',
            'is_gift'         => true,
            'gift_reason'     => 'Special Boss Gift for VIP Partnership',
            'status'          => 'completed',
        ]);

        SaleItem::create([
            'sale_id'    => $sale->id,
            'product_id' => $this->product->id,
            'quantity'   => 2,
            'unit_price' => 10000,
            'subtotal'   => 20000,
            'profit'     => 6000,
        ]);

        SalePayment::create([
            'sale_id' => $sale->id,
            'method'  => 'boss_gift',
            'amount'  => 20000,
            'paid_at' => Carbon::today(),
        ]);

        $sale->refresh();

        // 1. Verify model helper & balance
        $this->assertTrue($sale->isGift());
        $this->assertEquals(0.0, (float) $sale->balance);
        $this->assertEquals('Special Boss Gift for VIP Partnership', $sale->gift_reason);

        // 2. Verify NO LOAN was created
        $this->assertEquals(0, Loan::where('sale_id', $sale->id)->count());

        // 3. Verify NO false monetary ledger / drawer transaction was created
        $this->assertEquals(0, Transaction::where('sale_id', $sale->id)->count());
        $this->assertEquals(0, DebitCredit::where('sale_id', $sale->id)->count());
    }

    public function test_boss_gift_sale_can_be_stored_via_http_post(): void
    {
        $response = $this->actingAs($this->user)->post(route('sales.store'), [
            'cust_mode'   => 'existing',
            'customer_id' => $this->customer->id,
            'sale_date'   => Carbon::today()->format('Y-m-d'),
            'is_gift'     => '1',
            'gift_reason' => 'Given by Boss to foreign guest',
            'products'    => [
                [
                    'product_id' => $this->product->id,
                    'quantity'   => 3,
                    'unit_price' => 10000,
                ],
            ],
        ]);

        $sale = Sale::where('gift_reason', 'Given by Boss to foreign guest')->first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionHas('success');
        $this->assertTrue($sale->is_gift);
        $this->assertEquals('boss_gift', $sale->payment_channel);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(30000, (float) $sale->total_amount);
        $this->assertEquals(30000, (float) $sale->amount_paid);

        // Verify stock movement out was generated
        $this->assertDatabaseHas('stock_movements', [
            'product_id'  => $this->product->id,
            'type'        => 'out',
            'quantity'    => 3,
            'source_type' => Sale::class,
            'source_id'   => $sale->id,
        ]);

        // Verify current stock decreased from 20 to 17
        $this->assertEquals(17, $this->product->currentStock());

        // Verify NO LOAN
        $this->assertEquals(0, Loan::where('sale_id', $sale->id)->count());

        // Verify sale payment method is boss_gift
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'method'  => 'boss_gift',
            'amount'  => 30000,
        ]);
    }

    public function test_boss_gift_sale_can_be_updated_via_http_put(): void
    {
        $sale = Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today()->format('Y-m-d'),
            'total_amount'    => 10000,
            'amount_paid'     => 10000,
            'payment_channel' => 'boss_gift',
            'is_gift'         => true,
            'gift_reason'     => 'Initial Reason',
            'status'          => 'completed',
        ]);

        SaleItem::create([
            'sale_id'    => $sale->id,
            'product_id' => $this->product->id,
            'quantity'   => 1,
            'unit_price' => 10000,
            'subtotal'   => 10000,
        ]);

        StockMovement::create([
            'product_id'  => $this->product->id,
            'type'        => 'out',
            'quantity'    => 1,
            'source_type' => Sale::class,
            'source_id'   => $sale->id,
        ]);

        SalePayment::create([
            'sale_id' => $sale->id,
            'method'  => 'boss_gift',
            'amount'  => 10000,
            'paid_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->user)->put(route('sales.update', $sale), [
            'customer_id' => $this->customer->id,
            'sale_date'   => Carbon::today()->format('Y-m-d'),
            'is_gift'     => '1',
            'gift_reason' => 'Updated Reason: VIP Event Promotion',
            'products'    => [
                [
                    'product_id' => $this->product->id,
                    'quantity'   => 2,
                    'unit_price' => 10000,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $this->assertTrue($sale->is_gift);
        $this->assertEquals('Updated Reason: VIP Event Promotion', $sale->gift_reason);
        $this->assertEquals(20000, (float) $sale->total_amount);
        $this->assertEquals(20000, (float) $sale->amount_paid);
        $this->assertEquals(0, Loan::where('sale_id', $sale->id)->count());

        // Verify stock movement was updated to 2 units
        $this->assertEquals(18, $this->product->currentStock());
    }

    public function test_customer_statement_excludes_boss_gift_from_customer_debt(): void
    {
        // 1. Regular unpaid credit sale (customer genuinely owes 10,000)
        Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today()->subDays(2),
            'total_amount'    => 10000,
            'amount_paid'     => 0,
            'payment_channel' => 'cash',
            'is_gift'         => false,
            'status'          => 'pending',
        ]);

        // 2. Boss Gift sale for 50,000
        Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today(),
            'total_amount'    => 50000,
            'amount_paid'     => 50000,
            'payment_channel' => 'boss_gift',
            'is_gift'         => true,
            'gift_reason'     => 'Complimentary gift from boss',
            'status'          => 'completed',
        ]);

        $controller = app(StatementController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getCustomerData');
        $method->setAccessible(true);

        $request = new Request(['customer_id' => $this->customer->id]);
        $data = $method->invoke($controller, $request);

        // Sales total in KPIs must ONLY be the regular 10,000!
        $this->assertEquals(10000, $data['kpis']['sales_total']);
        // Balance due must be exactly 10,000 (NOT 60,000!)
        $this->assertEquals(10000, $data['kpis']['balance']);

        // Timeline events must include the boss gift with 0.00 amount impact
        $giftEvent = collect($data['events'])->firstWhere('type', 'Boss Gift (Complimentary)');
        $this->assertNotNull($giftEvent);
        $this->assertEquals(0.0, $giftEvent['amount']);
        $this->assertStringContainsString('Gift Reason: Complimentary gift from boss', $giftEvent['note']);
    }

    public function test_reports_daily_collection_records_gift_sales_cleanly(): void
    {
        // Regular cash sale
        Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today()->format('Y-m-d'),
            'total_amount'    => 15000,
            'amount_paid'     => 15000,
            'payment_channel' => 'cash',
            'is_gift'         => false,
            'status'          => 'completed',
        ]);

        // Boss Gift sale
        Sale::create([
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->user->id,
            'sale_date'       => Carbon::today()->format('Y-m-d'),
            'total_amount'    => 25000,
            'amount_paid'     => 25000,
            'payment_channel' => 'boss_gift',
            'is_gift'         => true,
            'gift_reason'     => 'Boss gift for sponsor',
            'status'          => 'completed',
        ]);

        $reportsController = app(ReportsController::class);
        $reflection = new \ReflectionClass($reportsController);
        $method = $reflection->getMethod('dailyCollectionData');
        $method->setAccessible(true);

        $request = new Request([
            'mode'       => 'single',
            'start_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $data = $method->invoke($reportsController, $request);

        // ledgerGiftSales must capture the 25,000
        $this->assertEquals(25000, $data['ledgerGiftSales']);
        // ledgerCreditSales must NOT contain the gift sale
        $this->assertEquals(0, $data['ledgerCreditSales']);
    }
}
