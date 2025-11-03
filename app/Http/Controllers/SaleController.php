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
     * Display all sales.
     */
    public function index()
    {
        $sales = Sale::with(['customer', 'user'])
            ->latest('sale_date')
            ->latest('id')
            ->paginate(15);

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
        // Clean empty product rows first
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
            'payment_channel'       => 'nullable|in:cash,bank,momo', // new field
            'method'                => 'nullable|string|max:50',     // optional detail (e.g., POS ref)
            'notes'                 => 'nullable|string|max:500',
        ]);

        if (empty($request->products)) {
            return back()->withErrors(['products' => 'Please add at least one product.'])->withInput();
        }

        // Prefer payment_channel; fall back to method if it matches a channel; default cash
        $channel = $request->payment_channel
            ?? (in_array(strtolower((string) $request->method), self::CHANNELS, true) ? strtolower($request->method) : 'cash');

        try {
            DB::beginTransaction();

            // 1) Create Sale shell
            $sale = Sale::create([
                'customer_id'     => $request->customer_id,
                'user_id'         => Auth::id(),
                'sale_date'       => $request->sale_date,
                'payment_channel' => $channel,                      // << NEW
                'method'          => $request->method ?? null,      // optional extra info
                'amount_paid'     => $request->amount_paid ?? 0,
                'total_amount'    => 0,
                'status'          => 'pending',
                'notes'           => $request->notes,
            ]);

            $totalAmount = 0;
            $totalProfit = 0;

            // 2) Add items + stock movements
            foreach (collect($request->products) as $item) {
                $product = Product::findOrFail($item['product_id']);

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
            Log::info('✅ Sale stored successfully', ['sale_id' => $sale->id, 'channel' => $sale->payment_channel]);

            return redirect()->route('sales.show', $sale->id)
                ->with('success', 'Sale recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Sale creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Update a sale (Observers handle loan/transaction sync).
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
            'payment_channel'       => 'nullable|in:cash,bank,momo', // new field
            'method'                => 'nullable|string|max:50',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $products = collect($request->products)
            ->filter(fn ($p) => !empty($p['product_id']) && $p['quantity'] > 0)
            ->values();

        // Normalize channel
        $channel = $request->payment_channel
            ?? (in_array(strtolower((string) $request->method), self::CHANNELS, true) ? strtolower($request->method) : ($sale->payment_channel ?? 'cash'));

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
                'payment_channel' => $channel,                     // << NEW
                'method'          => $request->method ?? $sale->method,
                'amount_paid'     => $request->amount_paid ?? 0,
                'total_amount'    => $totalAmount,
                'status'          => $status,
                'notes'           => $request->notes,
            ]);

            DB::commit();
            Log::info('✅ Sale updated successfully', ['sale_id' => $sale->id, 'channel' => $sale->payment_channel]);

            return redirect()
                ->route('sales.show', $sale->id)
                ->with('success', 'Sale updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Sale update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update sale: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a sale (and related stock + transaction + loan).
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

        Log::info('🗑️ Sale deleted', ['sale_id' => $sale->id]);

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
}
