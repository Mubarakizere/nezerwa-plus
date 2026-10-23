<?php

namespace App\Http\Controllers;

use App\Models\{
    Sale, SaleItem, Product, Customer, Supplier, StockMovement, SalePayment, PaymentChannel, Category
};
use App\Services\SupplierLoanSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log};
use Carbon\Carbon;

class PosController extends Controller
{
    /**
     * Display the POS terminal.
     */
    public function index(Request $request)
    {
        // 1. Categories for filtering (active products categories)
        $categories = Category::active()
            ->forProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        // 2. Products with calculated stock in a single efficient query
        $products = Product::query()
            ->with(['category' => fn($q) => $q->withTrashed()])
            ->withSum(['stockMovements as qty_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockMovements as qty_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $stock = max(0, (float)($p->qty_in ?? 0) - (float)($p->qty_out ?? 0));
                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'category_id'   => $p->category_id,
                    'category_name' => $p->category?->name ?? 'Uncategorized',
                    'price'         => (float) $p->price,
                    'cost_price'    => (float) ($p->cost_price ?? 0),
                    'stock'         => $stock,
                    'is_low'        => $stock <= (int) config('inventory.low_stock', 5),
                ];
            });

        // 3. Customers for fast lookup
        $customers = Customer::orderBy('name')
            ->get(['id', 'name', 'phone', 'email']);

        // 4. Suppliers with pending taken loans for debt offset
        $suppliers = Supplier::orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'phone'       => $s->phone ?? '',
                    'email'       => $s->email ?? '',
                    'total_owed'  => (float) $s->total_owed,
                    'loans_count' => (int) $s->pending_taken_loans_count,
                ];
            });

        // 5. Payment channels
        $paymentChannels = PaymentChannel::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('pos.terminal', compact('categories', 'products', 'customers', 'suppliers', 'paymentChannels'));
    }


    /**
     * Return live products list with current stock (useful for search or sync).
     */
    public function products(Request $request)
    {
        $products = Product::query()
            ->with(['category' => fn($q) => $q->withTrashed()])
            ->withSum(['stockMovements as qty_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockMovements as qty_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $stock = max(0, (float)($p->qty_in ?? 0) - (float)($p->qty_out ?? 0));
                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'category_id'   => $p->category_id,
                    'category_name' => $p->category?->name ?? 'Uncategorized',
                    'price'         => (float) $p->price,
                    'cost_price'    => (float) ($p->cost_price ?? 0),
                    'stock'         => $stock,
                    'is_low'        => $stock <= (int) config('inventory.low_stock', 5),
                ];
            });

        return response()->json([
            'success'  => true,
            'products' => $products,
        ]);
    }

    /**
     * Process POS checkout via AJAX.
     * Uses the exact business logic and validations as SaleController::store.
     */
    public function checkout(Request $request)
    {
        // 1. Clean products array
        $cleanProducts = collect($request->input('products', []))
            ->filter(fn ($p) => !empty($p['product_id']) && floatval($p['quantity']) > 0)
            ->values()
            ->toArray();
        $request->merge(['products' => $cleanProducts]);

        // Default sale_date to today if not supplied
        if (!$request->filled('sale_date')) {
            $request->merge(['sale_date' => now()->toDateString()]);
        }

        // 2. Validate
        $mode = $request->input('cust_mode', 'walkin');

        $rules = [
            'cust_mode'             => 'required|in:walkin,existing,new,supplier',
            'sale_date'             => 'required|date',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',

            'is_gift'               => 'nullable|boolean',
            'gift_reason'           => 'nullable|string|max:255',

            // Split payments
            'payments'              => 'nullable|array',
            'payments.*.method'     => 'required_with:payments|string',
            'payments.*.amount'     => 'required_with:payments|numeric|min:0.01',
            'payments.*.reference'  => 'nullable|string|max:100',
            'payments.*.phone'      => 'nullable|string|max:30',

            // Legacy / Single payment fallback
            'amount_paid'           => 'nullable|numeric|min:0',
            'payment_channel'       => 'nullable|string',
            'method'                => 'nullable|string|max:50',
            'notes'                 => 'nullable|string|max:500',
            'amount_tendered'       => 'nullable|numeric|min:0',
        ];

        if ($mode === 'existing') {
            $rules['customer_id'] = 'required|exists:customers,id';
        } elseif ($mode === 'new') {
            $rules['customer_name']     = 'required_without:new_customer_name|string|min:2|max:120';
            $rules['new_customer_name'] = 'required_without:customer_name|string|min:2|max:120';
            $rules['customer_phone']    = 'nullable|string|max:30';
            $rules['customer_email']    = 'nullable|email|max:120';
            $rules['customer_address']  = 'nullable|string|max:200';
        } elseif ($mode === 'supplier') {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
        }

        $validated = $request->validate($rules);

        if (empty($request->products)) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty. Please add at least one product.'
            ], 422);
        }

        // 3. Stock availability verification
        $stockErrors = [];
        foreach ($request->products as $index => $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $requestedQty = (float) $item['quantity'];
                $available    = $product->currentStock();
                
                if ($requestedQty > $available) {
                    $stockErrors[] = "Only {$available} units available for '{$product->name}' (Requested: {$requestedQty}).";
                }
            }
        }

        if (!empty($stockErrors)) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $stockErrors),
                'errors'  => $stockErrors,
            ], 422);
        }

        // 4. Execute Sale in Transaction
        try {
            DB::beginTransaction();

            // Resolve Customer or Supplier
            $customerId = null;
            $supplierId = null;
            if ($mode === 'existing') {
                $customerId = (string) $request->customer_id;
            } elseif ($mode === 'new') {
                $customerId = $this->quickCreateCustomerFromRequest($request);
                if (!$customerId) {
                    throw new \RuntimeException('Failed to register new customer: missing valid name.');
                }
            } elseif ($mode === 'supplier') {
                $supplierId = (int) $request->supplier_id;
            }

            $isGift = $request->boolean('is_gift') || $request->input('payment_channel') === 'boss_gift';

            // Create base Sale record
            $sale = Sale::create([
                'customer_id'     => $customerId,
                'supplier_id'     => $supplierId,
                'user_id'         => Auth::id(),
                'sale_date'       => $request->sale_date,
                'payment_channel' => $isGift ? 'boss_gift' : $this->normalizeChannel($request->payment_channel, $request->method),
                'method'          => $request->method ?: null,
                'amount_paid'     => 0,
                'total_amount'    => 0,
                'status'          => $isGift ? 'completed' : 'pending',
                'is_gift'         => $isGift,
                'gift_reason'     => $request->gift_reason,
                'notes'           => $request->notes,
            ]);


            // Create Sale Items & Stock Movements
            $totalAmount = 0.0;
            $totalProfit = 0.0;

            foreach ($request->products as $item) {
                $product = Product::findOrFail($item['product_id']);

                $qty       = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $subtotal  = round($qty * $unitPrice, 2);
                $cost      = (float) ($product->cost_price ?? 0);
                $profit    = round(($unitPrice - $cost) * $qty, 2);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                    'cost_price' => $cost,
                    'profit'     => $profit,
                ]);

                StockMovement::create([
                    'product_id'  => $product->id,
                    'type'        => 'out',
                    'quantity'    => $qty,
                    'unit_cost'   => $cost,
                    'total_cost'  => round($cost * $qty, 2),
                    'source_type' => Sale::class,
                    'source_id'   => $sale->id,
                    'user_id'     => Auth::id(),
                ]);

                $totalAmount += $subtotal;
                $totalProfit += $profit;
            }

            // Handle Payments (Split or Single)
            $payments = collect($request->input('payments', []))
                ->filter(fn ($p) => isset($p['method'], $p['amount']) && (float)$p['amount'] > 0);

            $sumPaid  = 0.0;
            $dominant = null;

            if ($isGift) {
                // Complimentary Gift given by Boss
                SalePayment::create([
                    'sale_id'   => $sale->id,
                    'method'    => 'boss_gift',
                    'amount'    => $totalAmount,
                    'reference' => $request->gift_reason ?: 'Gift authorized by Boss',
                    'phone'     => null,
                    'paid_at'   => $request->sale_date ?? now(),
                    'user_id'   => Auth::id(),
                ]);

                $sumPaid  = $totalAmount;
                $dominant = 'boss_gift';
            } elseif ($payments->isNotEmpty()) {
                foreach ($payments as $p) {
                    $amount = round((float)$p['amount'], 2);

                    SalePayment::create([
                        'sale_id'   => $sale->id,
                        'method'    => $this->normalizePaymentMethod($p['method']),
                        'amount'    => $amount,
                        'reference' => $p['reference'] ?? null,
                        'phone'     => $p['phone'] ?? null,
                        'paid_at'   => $p['paid_at'] ?? $request->sale_date ?? now(),
                        'user_id'   => Auth::id(),
                    ]);

                    $sumPaid += $amount;
                }

                $byMethod = $payments
                    ->groupBy(fn($p) => $this->normalizePaymentMethod($p['method']))
                    ->map(fn($gs) => collect($gs)->sum('amount'));

                $dominant = $byMethod->sortDesc()->keys()->first();
            } else {
                // Single payment fallback
                $singleAmount = round((float)($request->amount_paid ?? $totalAmount), 2);
                $channelSlug  = $this->normalizeChannel($request->payment_channel, $request->method);

                SalePayment::create([
                    'sale_id'   => $sale->id,
                    'method'    => $channelSlug,
                    'amount'    => $singleAmount,
                    'reference' => $request->method ?? null,
                    'phone'     => null,
                    'paid_at'   => $request->sale_date ?? now(),
                    'user_id'   => Auth::id(),
                ]);

                $sumPaid  = $singleAmount;
                $dominant = $channelSlug;
            }

            // Finalize Sale
            $sale->update([
                'total_amount'    => $totalAmount,
                'amount_paid'     => $isGift ? $totalAmount : $sumPaid,
                'payment_channel' => $isGift ? 'boss_gift' : ($dominant ?: 'cash'),
                'status'          => ($isGift || $totalAmount <= $sumPaid) ? 'completed' : 'pending',
                'is_gift'         => $isGift,
                'gift_reason'     => $request->gift_reason,
            ]);

            // Settle Supplier Loans FIFO if Supplier Offset was used
            $settledLoans = [];
            $remainingSupplierOwed = 0.0;
            if ($supplierId) {
                $offsetAmount = 0.0;
                if ($sale->payments()->count() > 0) {
                    $offsetAmount = (float) $sale->payments()->whereIn('method', ['supplier_offset', 'goods_exchange'])->sum('amount');
                } elseif (in_array($dominant, ['supplier_offset', 'goods_exchange'], true)) {
                    $offsetAmount = (float) $sumPaid;
                }

                if ($offsetAmount > 0.005) {
                    $supplier = Supplier::find($supplierId);
                    if ($supplier) {
                        $settledLoans = app(SupplierLoanSettlementService::class)->settleFifo($sale, $supplier, $offsetAmount, Auth::id());
                        $remainingSupplierOwed = (float) $supplier->fresh()->total_owed;
                    }
                }
            }

            // Low Stock Alert check
            try {
                $lowStockProducts = collect();
                foreach ($request->products as $item) {
                    $p = Product::find($item['product_id']);
                    if ($p && $p->isLowStock()) {
                        $lowStockProducts->push($p);
                    }
                }

                if ($lowStockProducts->isNotEmpty()) {
                    $admins = \App\Models\User::role('admin')->get();
                    if ($admins->isEmpty()) {
                        $admins = \App\Models\User::where('email', 'admin@stockmanagement.com')->get();
                    }
                    if ($admins->isNotEmpty()) {
                        \Illuminate\Support\Facades\Mail::to($admins)->send(new \App\Mail\LowStockAlertMail($lowStockProducts));
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Low stock alert mail failed in POS: ' . $e->getMessage());
            }

            DB::commit();

            $tendered = (float) ($request->input('amount_tendered') ?? $sumPaid);
            $change   = max(0.0, round($tendered - $totalAmount, 2));

            return response()->json([
                'success'                => true,
                'message'                => 'Sale #' . $sale->id . ' completed successfully.',
                'sale_id'                => $sale->id,
                'sale_number'            => '#' . $sale->id,
                'total_amount'           => $totalAmount,
                'amount_paid'            => $sumPaid,
                'change'                 => $change,
                'status'                 => $sale->status,
                'receipt_url'            => route('pos.receipt', $sale->id),
                'supplier_settled'       => !empty($settledLoans),
                'settled_loans'          => $settledLoans,
                'remaining_supplier_owed'=> $remainingSupplierOwed,
            ]);


        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('POS Checkout Failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thermal Receipt View (printable 80mm/58mm).
     */
    public function receipt(Sale $sale)
    {
        $sale->load([
            'customer',
            'user',
            'items.product.category',
            'payments',
        ]);

        return view('pos.receipt', compact('sale'));
    }

    /* -------------------------------------------------------------------------
     | Helper Methods
     | ------------------------------------------------------------------------ */

    private function normalizeChannel(?string $paymentChannel, ?string $method, ?string $fallback = 'cash'): string
    {
        if ($paymentChannel) {
            if (in_array($paymentChannel, ['boss_gift', 'supplier_offset', 'supplier-debt-offset'])) {
                return $paymentChannel;
            }
            $exists = PaymentChannel::where('slug', $paymentChannel)->orWhere('name', $paymentChannel)->exists();
            if ($exists) return $paymentChannel;
        }

        if ($method) {
            $m = strtolower($method);
            if (in_array($m, ['boss_gift', 'supplier_offset', 'supplier-debt-offset', 'goods_exchange'])) {
                return $m;
            }
            $exists = PaymentChannel::where('slug', $m)->orWhere('name', $method)->exists();
            if ($exists) return $m;
        }

        return $fallback;
    }

    private function normalizePaymentMethod(string $m): string
    {
        $m = strtolower(trim($m));
        if (in_array($m, ['boss_gift', 'supplier_offset', 'supplier-debt-offset', 'goods_exchange'])) {
            return $m;
        }
        $exists = PaymentChannel::where('slug', $m)->orWhere('name', $m)->exists();
        return $exists ? $m : 'cash';
    }

    private function quickCreateCustomerFromRequest(Request $request): ?string
    {
        $name    = trim((string) ($request->input('customer_name') ?? $request->input('new_customer_name') ?? ''));
        $phone   = $request->input('customer_phone')   ?? $request->input('new_customer_phone');
        $email   = $request->input('customer_email')   ?? $request->input('new_customer_email');
        $address = $request->input('customer_address') ?? $request->input('new_customer_address');

        if ($name === '') {
            return null;
        }

        $customer = Customer::firstOrCreate(
            $phone ? ['phone' => $phone] : ['name' => $name],
            ['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address]
        );

        return (string) $customer->id;
    }
}
