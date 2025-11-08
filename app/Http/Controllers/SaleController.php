<?php

namespace App\Http\Controllers;

use App\Models\{
    Sale,
    SaleItem,
    Product,
    Customer,
    Transaction,
    StockMovement
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log};
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    /** Allowed payment channels */
    private const CHANNELS = ['cash', 'bank', 'momo'];

    /**
     * List sales with search, filters, sorting, and per-page control.
     *
     * Query params:
     * - search: free text (customer name, channel, status, method, numeric id)
     * - channel: cash|bank|momo
     * - status: completed|pending|cancelled
     * - from / to: YYYY-MM-DD (sale_date range)
     * - has_returns: 1 to require at least one return
     * - sort: sale_date|total_amount|amount_paid|id
     * - dir: asc|desc
     * - per_page: 10|15|25|50|100
     */
    public function index(Request $request)
    {
        $perPage = $this->sanitizePerPage((int) $request->get('per_page', 15));

        $sales = $this->filteredSalesQuery($request)
            ->paginate($perPage)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    /**
     * Show form for creating a sale.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'price', 'cost_price']);

        return view('sales.create', compact('customers', 'products'));
    }

    /**
     * Store a new sale.
     */
    public function store(Request $request)
    {
        // Remove empty product rows from the request before validating
        $cleanProducts = collect($request->input('products', []))
            ->filter(fn ($p) => !empty($p['product_id']) && floatval($p['quantity']) > 0)
            ->values()
            ->toArray();
        $request->merge(['products' => $cleanProducts]);

        $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'sale_date'             => 'required|date',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            'amount_paid'           => 'nullable|numeric|min:0',
            'payment_channel'       => 'nullable|in:cash,bank,momo',
            'method'                => 'nullable|string|max:50',
            'notes'                 => 'nullable|string|max:500',
        ]);

        if (empty($request->products)) {
            return back()->withErrors(['products' => 'Please add at least one product.'])->withInput();
        }

        $channel = $this->normalizeChannel($request->payment_channel, $request->method);

        try {
            DB::beginTransaction();

            // 1) Create Sale
            $sale = Sale::create([
                'customer_id'     => $request->customer_id,
                'user_id'         => Auth::id(),
                'sale_date'       => $request->sale_date,
                'payment_channel' => $channel,
                'method'          => $request->method ?? null,
                'amount_paid'     => $request->amount_paid ?? 0,
                'total_amount'    => 0,
                'status'          => 'pending',
                'notes'           => $request->notes,
            ]);

            $totalAmount = 0;
            $totalProfit = 0;

            // 2) Items + stock movements
            foreach (collect($request->products) as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Optional live-stock guard if using movements-derived stock
                if (method_exists($product, 'currentStock') && $product->currentStock() < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

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

            // 3) Totals + status
            $sale->update([
                'total_amount' => $totalAmount,
                'status'       => ($totalAmount <= ($sale->amount_paid ?? 0)) ? 'completed' : 'pending',
            ]);

            DB::commit();
            Log::info('Sale stored successfully', ['sale_id' => $sale->id, 'channel' => $sale->payment_channel]);

            return redirect()->route('sales.show', $sale->id)
                ->with('success', 'Sale recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sale creation failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to create sale: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display a sale.
     */
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'items.product', 'transaction', 'user']);
        return view('sales.show', compact('sale'));
    }

    /**
     * Edit a sale.
     */
    public function edit(Sale $sale)
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'price', 'cost_price']);
        $sale->load('items.product');

        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    /**
     * Update a sale.
     */
    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'sale_date'             => 'required|date',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            'amount_paid'           => 'nullable|numeric|min:0',
            'payment_channel'       => 'nullable|in:cash,bank,momo',
            'method'                => 'nullable|string|max:50',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $products = collect($request->products)
            ->filter(fn ($p) => !empty($p['product_id']) && $p['quantity'] > 0)
            ->values();

        $channel = $this->normalizeChannel($request->payment_channel, $request->method, $sale->payment_channel);

        try {
            DB::beginTransaction();

            // Clear old stock + items
            StockMovement::where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->delete();
            $sale->items()->delete();

            $totalAmount = 0;
            $totalProfit = 0;

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

            $status = ($totalAmount <= ($request->amount_paid ?? 0)) ? 'completed' : 'pending';

            $sale->update([
                'customer_id'     => $request->customer_id,
                'sale_date'       => $request->sale_date,
                'payment_channel' => $channel,
                'method'          => $request->method ?? $sale->method,
                'amount_paid'     => $request->amount_paid ?? 0,
                'total_amount'    => $totalAmount,
                'status'          => $status,
                'notes'           => $request->notes,
            ]);

            DB::commit();
            Log::info('Sale updated successfully', ['sale_id' => $sale->id, 'channel' => $sale->payment_channel]);

            return redirect()
                ->route('sales.show', $sale->id)
                ->with('success', 'Sale updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sale update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update sale: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a sale (and related stock + transaction).
     */
    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            StockMovement::where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->delete();

            Transaction::where('sale_id', $sale->id)->delete();
            $sale->items()->delete();
            $sale->delete();
        });

        Log::info('Sale deleted', ['sale_id' => $sale->id]);

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale deleted successfully.');
    }

    /**
     * Generate printable PDF invoice.
     */
    public function invoice(Sale $sale)
    {
        $sale->load(['customer', 'items.product', 'transaction', 'user']);

        $pdf = Pdf::loadView('sales.invoice', compact('sale'))
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->stream('sale-invoice-' . $sale->id . '.pdf');
    }

    /**
     * Export filtered sales to CSV (uses same filters as index).
     */
    public function export(Request $request)
    {
        $rows = $this->filteredSalesQuery($request)
            ->get(['id','sale_date','customer_id','payment_channel','method','status','total_amount','amount_paid'])
            ->load('customer');

        $filename = 'sales-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#','Date','Customer','Channel','Ref/Method','Status','Total','Paid','Balance','Returns','Net After Returns']);

            foreach ($rows as $s) {
                $returns   = (float) ($s->returns_total ?? 0);
                $total     = (float) ($s->total_amount ?? 0);
                $paid      = (float) ($s->amount_paid ?? 0);
                $netAfter  = max(0, $total - $returns);
                $balance   = max(0, round($netAfter - $paid, 2));

                $date = '';
                if ($s->sale_date) {
                    $date = is_string($s->sale_date)
                        ? $s->sale_date
                        : $s->sale_date->format('Y-m-d');
                }

                fputcsv($out, [
                    $s->id,
                    $date,
                    $s->customer->name ?? 'Walk-in',
                    strtoupper($s->payment_channel ?? 'cash'),
                    $s->method ?? '',
                    ucfirst($s->status ?? ''),
                    number_format($total, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format($balance, 2, '.', ''),
                    number_format($returns, 2, '.', ''),
                    number_format($netAfter, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Build the filtered, sortable base query for listings and export.
     */
    private function filteredSalesQuery(Request $request)
    {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $q = Sale::query()
            ->with(['customer', 'user'])
            ->withSum('returns as returns_total', 'amount');

        // Free-text search
        if (($search = trim((string) $request->get('search'))) !== '') {
            $q->where(function ($w) use ($search, $like) {
                if (is_numeric($search)) {
                    $w->orWhere('id', (int) $search);
                }
                $w->orWhere('payment_channel', $like, "%{$search}%")
                  ->orWhere('status',           $like, "%{$search}%")
                  ->orWhere('method',           $like, "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', $like, "%{$search}%"));
            });
        }

        // Channel
        if ($request->filled('channel') && in_array($request->channel, self::CHANNELS, true)) {
            $q->where('payment_channel', $request->channel);
        }

        // Status
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        // Date range
        if ($from = $request->get('from')) {
            $q->whereDate('sale_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $q->whereDate('sale_date', '<=', $to);
        }

        // Only sales that have returns
        if ($request->boolean('has_returns')) {
            $q->whereHas('returns');
        }

        // Sorting
        $sortable = ['sale_date', 'total_amount', 'amount_paid', 'id'];
        $sort = in_array($request->get('sort'), $sortable, true) ? $request->get('sort') : 'sale_date';
        $dir  = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        // Primary sort + tiebreaker
        $q->orderBy($sort, $dir)->orderBy('id', 'desc');

        return $q;
    }

    /**
     * Normalize a payment channel using either explicit channel or method fallback.
     */
    private function normalizeChannel(?string $paymentChannel, ?string $method, ?string $fallback = 'cash'): string
    {
        $channel = $paymentChannel
            ?? (in_array(strtolower((string) $method), self::CHANNELS, true) ? strtolower($method) : $fallback);

        return in_array($channel, self::CHANNELS, true) ? $channel : 'cash';
    }

    /**
     * Bound per-page to a safe whitelist.
     */
    private function sanitizePerPage(int $n): int
    {
        $allowed = [10, 15, 25, 50, 100];
        return in_array($n, $allowed, true) ? $n : 15;
    }
}
