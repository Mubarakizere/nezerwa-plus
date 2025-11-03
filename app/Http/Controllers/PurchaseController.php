<?php

namespace App\Http\Controllers;

use App\Models\{
    Purchase,
    PurchaseItem,
    Product,
    Supplier,
    StockMovement,
    Loan
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log};
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    /**
     * List all purchases.
     */
    public function index()
    {
        $purchases = Purchase::with('supplier')->latest()->paginate(10);
        return view('purchases.index', compact('purchases'));
    }

    /**
     * Show form to create a new purchase.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $products  = Product::all();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    /**
     * Store a new purchase.
     * Automatically creates a Loan (taken) when supplier not fully paid.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'             => 'required|exists:suppliers,id',
            'purchase_date'           => 'required|date',
            'products'                => 'required|array|min:1',
            'products.*.product_id'   => 'required|exists:products,id',
            'products.*.quantity'     => 'required|numeric|min:1',
            'products.*.unit_cost'    => 'required|numeric|min:0',
            'amount_paid'             => 'nullable|numeric|min:0',
            'method'                  => 'nullable|string|max:50',
            'notes'                   => 'nullable|string|max:500',
            'tax'                     => 'nullable|numeric|min:0|max:100',
            'discount'                => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();
            Log::info('🧾 Creating Purchase...', ['user' => Auth::id(), 'payload' => $request->all()]);

            // 1️⃣ Create Purchase shell
            $purchase = Purchase::create([
                'supplier_id'   => $request->supplier_id,
                'user_id'       => Auth::id(),
                'purchase_date' => $request->purchase_date,
                'subtotal'      => 0,
                'tax'           => $request->tax ?? 0,
                'discount'      => $request->discount ?? 0,
                'total_amount'  => 0,
                'amount_paid'   => $request->amount_paid ?? 0,
                'notes'         => $request->notes ?? null,
            ]);

            // 2️⃣ Loop through products
            $subtotal = 0;
            foreach ($request->products as $item) {
                $quantity  = (float) $item['quantity'];
                $unitCost  = (float) $item['unit_cost'];
                $totalCost = round($quantity * $unitCost, 2);
                $subtotal += $totalCost;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $quantity,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $totalCost,
                ]);

                StockMovement::create([
                    'product_id'  => $item['product_id'],
                    'type'        => 'in',
                    'quantity'    => $quantity,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => $totalCost,
                    'source_type' => Purchase::class,
                    'source_id'   => $purchase->id,
                    'user_id'     => Auth::id(),
                ]);

                $product = Product::find($item['product_id']);
                $this->updateWeightedAverageCost($product, $quantity, $unitCost);
            }

            // 3️⃣ Finalize totals
            $taxValue      = ($subtotal * ($purchase->tax ?? 0)) / 100;
            $discountValue = ($subtotal * ($purchase->discount ?? 0)) / 100;
            $totalAmount   = ($subtotal + $taxValue) - $discountValue;
            $balanceDue    = $totalAmount - ($purchase->amount_paid ?? 0);

            $purchase->update([
                'subtotal'      => $subtotal,
                'tax'           => $taxValue,
                'discount'      => $discountValue,
                'total_amount'  => $totalAmount,
                'balance_due'   => $balanceDue,
            ]);




            DB::commit();
            Log::info('✅ Purchase stored successfully', ['purchase_id' => $purchase->id]);

            return redirect()
                ->route('purchases.index')
                ->with('success', 'Purchase recorded successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Purchase store failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Purchase failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Weighted Average Cost Calculation
     */
    private function updateWeightedAverageCost(Product $product, float $qtyIn, float $costIn): void
    {
        $currentStock = $product->currentStock();
        $oldCost      = $product->cost_price ?? 0;

        $oldValue = $currentStock * $oldCost;
        $newValue = $qtyIn * $costIn;
        $newQty   = $currentStock + $qtyIn;

        if ($newQty > 0) {
            $product->cost_price = round(($oldValue + $newValue) / $newQty, 2);
        }

        $product->save();
    }

    /**
     * Show purchase details.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'transaction']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Edit purchase.
     */
    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::all();
        $products  = Product::all();
        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    /**
     * Delete purchase (and related stock, loan).
     */
    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            StockMovement::where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->delete();

            Loan::where('notes', "Auto-created for Purchase #{$purchase->id} (Unpaid supplier balance)")->delete();

            $purchase->items()->delete();
            $purchase->delete();
        });

        Log::info('🗑️ Purchase deleted', ['purchase_id' => $purchase->id]);

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase deleted successfully.');
    }

    /**
     * Generate PDF Invoice.
     */
    public function invoice(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'transaction', 'user']);

        $pdf = Pdf::loadView('purchases.invoice', compact('purchase'))
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->stream('purchase-invoice-' . $purchase->id . '.pdf');
    }
}
