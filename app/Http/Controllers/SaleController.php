<?php

namespace App\Http\Controllers;

use App\Models\{
    Sale, SaleItem, Product, Customer, Transaction, StockMovement, SalePayment
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SaleController extends Controller
{
    /** Old single-channel on Sale (kept for compatibility) */
    private const CHANNELS = ['cash', 'bank', 'momo', 'mobile', 'mobile_money'];

    /** New payment methods for split payments */
    private const PAYMENT_METHODS = ['cash', 'bank', 'momo', 'mobile', 'mobile_money'];

    /** ===================== INDEX ===================== */
    public function index(Request $request)
    {
        $perPage = $this->sanitizePerPage((int) $request->get('per_page', 15));

        $sales = $this->filteredSalesQuery($request)
            ->paginate($perPage)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    /** ===================== CREATE ===================== */
    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'price', 'cost_price']);

        return view('sales.create', compact('customers', 'products'));
    }

    /** ===================== STORE ===================== */
    public function store(Request $request)
{
    // Normalize product lines (remove empties/zero qty)
    $cleanProducts = collect($request->input('products', []))
        ->filter(fn ($p) => !empty($p['product_id']) && floatval($p['quantity']) > 0)
        ->values()
        ->toArray();
    $request->merge(['products' => $cleanProducts]);

    // -------- Validate (mode-aware) --------
    $mode = $request->input('cust_mode', 'walkin'); // walkin|existing|new

    $rules = [
        'cust_mode'             => 'required|in:walkin,existing,new',
        'sale_date'             => 'required|date',
        'products'              => 'required|array|min:1',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.quantity'   => 'required|numeric|min:0.01',
        'products.*.unit_price' => 'required|numeric|min:0',

        // Legacy single-payment fallback
        'amount_paid'           => 'nullable|numeric|min:0',
        'payment_channel'       => 'nullable|in:cash,bank,momo,mobile,mobile_money',
        'method'                => 'nullable|string|max:50',
        'notes'                 => 'nullable|string|max:500',

        // Split payments (optional)
        'payments'              => 'nullable|array|min:1',
        'payments.*.method'     => 'required_with:payments|in:cash,bank,momo,mobile,mobile_money',
        'payments.*.amount'     => 'required_with:payments|numeric|min:0.01',
        'payments.*.reference'  => 'nullable|string|max:100',
        'payments.*.phone'      => 'nullable|string|max:30',
        'payments.*.paid_at'    => 'nullable|date',
    ];

    if ($mode === 'existing') {
        $rules['customer_id'] = 'required|exists:customers,id';
    } elseif ($mode === 'new') {
        // Accept both modern customer_* and legacy new_customer_* field names
        $rules['customer_name']    = 'required_without:new_customer_name|string|min:2|max:120';
        $rules['new_customer_name'] = 'required_without:customer_name|string|min:2|max:120';
        $rules['customer_phone']   = 'nullable|string|max:30';
        $rules['customer_email']   = 'nullable|email|max:120';
        $rules['customer_address'] = 'nullable|string|max:200';
    }

    $request->validate($rules);

    // Abort early if no products after cleaning
    if (empty($request->products)) {
        return back()->withErrors(['products' => 'Please add at least one product.'])->withInput();
    }

    try {
        DB::beginTransaction();

        // -------- Resolve customer id by mode --------
        $customerId = null;
        if ($mode === 'existing') {
            $customerId = (string) $request->customer_id;
        } elseif ($mode === 'new') {
            $customerId = $this->quickCreateCustomerFromRequest($request);
            if (!$customerId) {
                throw new \RuntimeException('Failed to create customer (missing name).');
            }
        } // walkin => null

        // -------- Create base Sale (amounts/status set later) --------
        $sale = Sale::create([
            'customer_id'     => $customerId,
            'user_id'         => Auth::id(),
            'sale_date'       => $request->sale_date,
            'payment_channel' => $this->normalizeChannel($request->payment_channel, $request->method),
            'method'          => $request->method ?: null, // reference/batch
            'amount_paid'     => 0,
            'total_amount'    => 0,
            'status'          => 'pending',
            'notes'           => $request->notes,
        ]);

        // -------- Items + stock movements --------
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

        // -------- Payments (prefer split) --------
        $payments = collect($request->input('payments', []))
            ->filter(fn ($p) => isset($p['method'], $p['amount']) && (float)$p['amount'] > 0);

        $sumPaid  = 0.0;
        $dominant = null;

        if ($payments->isNotEmpty()) {
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
            // legacy single payment
            $sumPaid  = round((float)($request->amount_paid ?? 0), 2);
            $dominant = $this->normalizeChannel($request->payment_channel, $request->method);
        }

        // -------- Finalize sale --------
        $sale->update([
            'total_amount'    => $totalAmount,
            'amount_paid'     => $sumPaid,
            'payment_channel' => $dominant ?: 'cash',
            'status'          => ($totalAmount <= $sumPaid) ? 'completed' : 'pending',
        ]);

        // -------- Check for Low Stock & Alert Admins --------
        try {
            $lowStockProducts = collect();
            foreach ($request->products as $item) {
                $product = Product::find($item['product_id']);
                if ($product && $product->isLowStock()) {
                    $lowStockProducts->push($product);
                }
            }

            if ($lowStockProducts->isNotEmpty()) {
                // Find admins (users with 'admin' role)
                // Assuming Spatie Permission: User::role('admin')->get()
                // Or fallback to checking a specific email or permission if roles aren't strictly named 'admin'
                $admins = \App\Models\User::role('admin')->get(); 
                
                // If no role 'admin', maybe try 'Super Admin' or just the current user if they are admin?
                // Let's stick to 'admin' role for now, or fallback to a config email.
                if ($admins->isEmpty()) {
                     $admins = \App\Models\User::where('email', 'admin@stockmanagement.com')->get();
                }

                if ($admins->isNotEmpty()) {
                    \Illuminate\Support\Facades\Mail::to($admins)->send(new \App\Mail\LowStockAlertMail($lowStockProducts));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send low stock alert: ' . $e->getMessage());
        }

        DB::commit();

        return redirect()->route('sales.show', $sale->id)
            ->with('success', 'Sale recorded successfully.');
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Sale creation failed', ['error' => $e->getMessage()]);
        return back()->withErrors(['error' => 'Failed to create sale: '.$e->getMessage()])->withInput();
    }
}

    /** ===================== SHOW ===================== */
    public function show(Sale $sale)
    {
        $sale->load([
            'customer:id,name',
            'user:id,name',
            'items.product:id,name,cost_price,price',
            'returns.items.product:id,name',
            'payments.user:id,name',
            'loan' => function ($q) {
                $q->with(['payments' => fn ($p) => $p->orderBy('payment_date', 'desc')->with('user:id,name')]);
            },
        ])->loadSum('returns as returns_total', 'amount');

        return view('sales.show', compact('sale'));
    }

    /** ===================== EDIT ===================== */
    public function edit(Sale $sale)
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'price', 'cost_price']);
        $sale->load(['items.product', 'payments']);

        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    /** ===================== UPDATE ===================== */
    public function update(Request $request, Sale $sale)
    {
        // Quick customer create if inline fields provided (and no id)
        if (!$request->filled('customer_id')) {
            $cid = $this->quickCreateCustomerFromRequest($request);
            if ($cid) { $request->merge(['customer_id' => $cid]); }
        }

        $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'sale_date'             => 'required|date',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',

            'amount_paid'           => 'nullable|numeric|min:0',
            'payment_channel'       => 'nullable|in:cash,bank,momo,mobile,mobile_money',
            'method'                => 'nullable|string|max:50',
            'notes'                 => 'nullable|string|max:500',

            // split payments
            'payments'              => 'nullable|array|min:1',
            'payments.*.method'     => 'required_with:payments|in:cash,bank,momo,mobile,mobile_money',
            'payments.*.amount'     => 'required_with:payments|numeric|min:0.01',
            'payments.*.reference'  => 'nullable|string|max:100',
            'payments.*.phone'      => 'nullable|string|max:30',
            'payments.*.paid_at'    => 'nullable|date',
        ]);

        $products = collect($request->products)
            ->filter(fn ($p) => !empty($p['product_id']) && $p['quantity'] > 0)
            ->values();

        try {
            DB::beginTransaction();

            // Reset items + stock movements
            StockMovement::where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->delete();
            $sale->items()->delete();

            $totalAmount = 0; $totalProfit = 0;

            foreach ($products as $item) {
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

            // Reset and re-create payments
            $sumPaid = 0.0; $dominant = null;
            $sale->payments()->delete();

            $payments = collect($request->input('payments', []))
                ->filter(fn ($p) => isset($p['method'], $p['amount']) && (float)$p['amount'] > 0);

            if ($payments->isNotEmpty()) {
                foreach ($payments as $p) {
                    $amount = round((float)$p['amount'], 2);
                    $sumPaid += $amount;

                    SalePayment::create([
                        'sale_id'   => $sale->id,
                        'method'    => $this->normalizePaymentMethod($p['method']),
                        'amount'    => $amount,
                        'reference' => $p['reference'] ?? null,
                        'phone'     => $p['phone'] ?? null,
                        'paid_at'   => $p['paid_at'] ?? $request->sale_date ?? now(),
                        'user_id'   => Auth::id(),
                    ]);
                }

                $byMethod = $payments->groupBy(fn($p) => $this->normalizePaymentMethod($p['method']))
                                     ->map(fn($gs) => collect($gs)->sum('amount'));
                $dominant = $byMethod->sortDesc()->keys()->first();
            } else {
                $sumPaid  = round((float)($request->amount_paid ?? 0), 2);
                $dominant = $this->normalizeChannel($request->payment_channel, $request->method, $sale->payment_channel);
            }

            $sale->update([
                'customer_id'     => $request->customer_id,
                'sale_date'       => $request->sale_date,
                'payment_channel' => $dominant ?: 'cash',
                'method'          => $request->method ?? $sale->method,
                'amount_paid'     => $sumPaid,
                'total_amount'    => $totalAmount,
                'status'          => ($totalAmount <= $sumPaid) ? 'completed' : 'pending',
                'notes'           => $request->notes,
            ]);

            DB::commit();
            return redirect()->route('sales.show', $sale->id)
                ->with('success', 'Sale updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sale update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update sale: '.$e->getMessage()]);
        }
    }

    /** ===================== DESTROY ===================== */
    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            $sale->payments()->delete();
            StockMovement::where('source_type', Sale::class)->where('source_id', $sale->id)->delete();
            Transaction::where('sale_id', $sale->id)->delete(); // keep if you have this FK
            $sale->items()->delete();
            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }

    /** ===================== INVOICE ===================== */
    public function invoice(Sale $sale)
    {
        $sale->load([
            'customer', 'items.product', 'user', 'returns', 'payments.user', 'loan.payments.user',
        ])->loadSum('returns as returns_total', 'amount');

        $pdf = Pdf::loadView('sales.invoice', compact('sale'))->setPaper('a4');
        return $pdf->stream('sale-invoice-' . $sale->id . '.pdf');
    }

    /** ===================== PAYMENTS PDF ===================== */
    public function exportPaymentsPdf(Request $request)
    {
        $period = $request->input('period'); // daily|weekly|monthly|custom
        $start  = $request->input('from');
        $end    = $request->input('to');

        if ($period === 'daily') {
            $start = Carbon::today(); $end = Carbon::today()->endOfDay();
        } elseif ($period === 'weekly') {
            $start = Carbon::now()->startOfWeek(); $end = Carbon::now()->endOfWeek();
        } elseif ($period === 'monthly') {
            $start = Carbon::now()->startOfMonth(); $end = Carbon::now()->endOfMonth();
        } else {
            $start = Carbon::parse($start ?? Carbon::today()->toDateString())->startOfDay();
            $end   = Carbon::parse($end   ?? Carbon::today()->toDateString())->endOfDay();
        }

        $rows = SalePayment::query()
            ->select([
                'sale_payments.*',
                'sales.sale_date',
                'sales.id as sale_id',
                'sales.total_amount',
                'customers.name as customer_name',
            ])
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->whereBetween('sales.sale_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('sales.sale_date', 'desc')
            ->get();

        $totalsByMethod = $rows->groupBy('method')->map->sum('amount');

        $saleLines = $rows->groupBy('sale_id')->map(function ($grp) {
            $base = [
                'sale_id'       => $grp->first()->sale_id,
                'sale_date'     => $grp->first()->sale_date,
                'customer_name' => $grp->first()->customer_name ?? 'Walk-in',
                'total_amount'  => (float)($grp->first()->total_amount ?? 0),
                'cash'   => 0, 'bank' => 0, 'momo' => 0, 'mobile' => 0, 'mobile_money' => 0,
            ];
            foreach ($grp as $p) {
                $m = $p->method;
                $base[$m] += (float)$p->amount;
            }
            $base['paid']    = $base['cash'] + $base['bank'] + $base['momo'] + $base['mobile_money'];
            $base['balance'] = max(0, $base['total_amount'] - $base['paid']);
            return $base;
        })->values();

        $pdf = Pdf::loadView('sales.pdf.payments', [
            'start'          => $start,
            'end'            => $end,
            'saleLines'      => $saleLines,
            'totalsByMethod' => $totalsByMethod,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("sales-payments_{$start->toDateString()}_to_{$end->toDateString()}.pdf");
    }

    /** ===================== HELPERS ===================== */

    private function filteredSalesQuery(Request $request)
    {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $q = Sale::query()
            ->with(['customer', 'user'])
            ->withSum('returns as returns_total', 'amount');

        if (($search = trim((string) $request->get('search'))) !== '') {
            $q->where(function ($w) use ($search, $like) {
                if (is_numeric($search)) { $w->orWhere('id', (int) $search); }
                $w->orWhere('payment_channel', $like, "%{$search}%")
                  ->orWhere('status',           $like, "%{$search}%")
                  ->orWhere('method',           $like, "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', $like, "%{$search}%"));
            });
        }

        if ($request->filled('channel') && in_array($request->channel, self::CHANNELS, true)) {
            $q->where('payment_channel', $request->channel);
        }

        if ($request->filled('status')) { $q->where('status', $request->status); }
        if ($from = $request->get('from')) { $q->whereDate('sale_date', '>=', $from); }
        if ($to   = $request->get('to'))   { $q->whereDate('sale_date', '<=', $to); }
        if ($request->boolean('has_returns')) { $q->whereHas('returns'); }

        $sortable = ['sale_date', 'total_amount', 'amount_paid', 'id'];
        $sort = in_array($request->get('sort'), $sortable, true) ? $request->get('sort') : 'sale_date';
        $dir  = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        return $q->orderBy($sort, $dir)->orderBy('id', 'desc');
    }

    private function normalizeChannel(?string $paymentChannel, ?string $method, ?string $fallback = 'cash'): string
    {
        $channel = $paymentChannel
            ?? (in_array(strtolower((string) $method), self::CHANNELS, true) ? strtolower($method) : $fallback);
        return in_array($channel, self::CHANNELS, true) ? $channel : 'cash';
    }

    private function normalizePaymentMethod(string $m): string
    {
        $m = strtolower(trim($m));
        return in_array($m, self::PAYMENT_METHODS, true) ? $m : 'cash';
    }

    private function sanitizePerPage(int $n): int
    {
        $allowed = [10, 15, 25, 50, 100];
        return in_array($n, $allowed, true) ? $n : 15;
    }

    /**
     * Create a customer on-the-fly from inline fields:
     * accepts: customer_name, customer_phone, customer_email, customer_address
     * returns customer id or null
     */
    private function quickCreateCustomerFromRequest(Request $request): ?string
{
    $name    = trim((string) ($request->input('customer_name') ?? $request->input('new_customer_name') ?? ''));
    $phone   = $request->input('customer_phone')   ?? $request->input('new_customer_phone');
    $email   = $request->input('customer_email')   ?? $request->input('new_customer_email');
    $address = $request->input('customer_address') ?? $request->input('new_customer_address');

    if ($name === '') {
        return null;
    }

    $data = [
        'name'    => $name,
        'phone'   => $phone,
        'email'   => $email,
        'address' => $address,
    ];

    // Prefer phone as lookup if present, else name
    $lookup = $phone ? ['phone' => $phone] : ['name' => $name];

    $customer = \App\Models\Customer::firstOrCreate(
        $lookup,
        ['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address]
    );

    // Optionally fill missing fields on existing record
    $dirty = false;
    foreach (['name','phone','email','address'] as $f) {
        if (!$customer->$f && !empty($data[$f])) {
            $customer->$f = $data[$f];
            $dirty = true;
        }
    }
    if ($dirty) {
        $customer->save();
    }

    return (string) $customer->id;
}
}
