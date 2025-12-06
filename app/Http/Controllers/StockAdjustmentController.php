<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{
    /**
     * Show form to adjust stock for a specific product.
     */
    public function create(Product $product)
    {
        return view('stock_adjustments.create', compact('product'));
    }

    /**
     * Store the adjustment.
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'actual_stock' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Lock product to prevent race conditions
            $product = Product::where('id', $product->id)->lockForUpdate()->first();
            
            $currentStock = $product->currentStock();
            $actualStock  = (float) $request->input('actual_stock');
            $difference   = $actualStock - $currentStock;

            if (abs($difference) < 0.001) {
                return back()->with('info', 'No adjustment needed. Stock is already ' . $actualStock);
            }

            $type = $difference > 0 ? 'in' : 'out';
            $qty  = abs($difference);
            
            // Use weighted average cost for the value of the adjustment
            $unitCost = $product->weightedAverageCost();

            StockMovement::create([
                'product_id'  => $product->id,
                'type'        => $type,
                'quantity'    => $qty,
                'unit_cost'   => $unitCost,
                'total_cost'  => round($qty * $unitCost, 2),
                'source_type' => 'App\Models\StockAdjustment', // Virtual model or just string
                'source_id'   => 0, // No specific parent model for ad-hoc adjustment, or could create one
                'user_id'     => Auth::id(),
                'notes'       => $request->input('notes') ?? 'Manual Stock Adjustment (Survey)',
            ]);

            // Update product cost price if needed? 
            // Usually adjustments shouldn't change unit cost unless it's a value adjustment.
            // We keep WAC as is, just adjusting quantity.

            DB::commit();
            
            return redirect()->route('products.show', $product)
                ->with('success', "Stock adjusted from {$currentStock} to {$actualStock}.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Stock adjustment failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Adjustment failed: ' . $e->getMessage()]);
        }
    }
}
