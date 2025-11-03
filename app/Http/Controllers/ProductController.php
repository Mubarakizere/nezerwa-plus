<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display list of products with category and stock summary.
     */
    public function index()
    {
        $products = Product::with(['category'])
            ->withCount([
                'stockMovements as total_in' => fn($q) => $q->where('type', 'in'),
                'stockMovements as total_out' => fn($q) => $q->where('type', 'out'),
            ])
            ->get();

        return view('products.index', compact('products'));
    }

    /**
     * Show form to create a new product.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created product and optionally record an initial stock movement.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $product = Product::create([
                'name'        => $request->name,
                'category_id' => $request->category_id,
                'price'       => $request->price,
                'stock'       => $request->stock,
            ]);

            // ✅ Record initial stock as StockMovement (type: in)
            if ($request->stock > 0) {
                StockMovement::create([
                    'product_id'  => $product->id,
                    'type'        => 'in',
                    'quantity'    => $request->stock,
                    'unit_cost'   => $product->cost_price ?? $product->price,
                    'total_cost'  => ($product->cost_price ?? $product->price) * $request->stock,
                    'source_type' => Product::class,
                    'source_id'   => $product->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            DB::commit();
            Log::info('✅ Product created successfully', ['product_id' => $product->id]);

            return redirect()
                ->route('products.index')
                ->with('success', 'Product created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Product creation failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Display a single product with recent stock movements.
     */
    public function show(Product $product)
    {
        $product->load(['category', 'stockMovements' => function ($q) {
            $q->latest()->take(10)->with('user');
        }]);

        $totalIn  = $product->stockMovements()->where('type', 'in')->sum('quantity');
        $totalOut = $product->stockMovements()->where('type', 'out')->sum('quantity');
        $current  = $product->currentStock();

        return view('products.show', compact('product', 'totalIn', 'totalOut', 'current'));
    }

    /**
     * Show form to edit an existing product.
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update product details and adjust stock movement if stock changes.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $oldStock = $product->currentStock();

            $product->update([
                'name'        => $request->name,
                'category_id' => $request->category_id,
                'price'       => $request->price,
                'stock'       => $request->stock,
            ]);

            $newStock = $request->stock;
            $difference = $newStock - $oldStock;

            // ✅ Record stock adjustment movement
            if ($difference != 0) {
                StockMovement::create([
                    'product_id'  => $product->id,
                    'type'        => $difference > 0 ? 'in' : 'out',
                    'quantity'    => abs($difference),
                    'unit_cost'   => $product->cost_price ?? $product->price,
                    'total_cost'  => abs($difference) * ($product->cost_price ?? $product->price),
                    'source_type' => Product::class,
                    'source_id'   => $product->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            DB::commit();
            Log::info('♻️ Product updated successfully', ['product_id' => $product->id]);

            return redirect()
                ->route('products.index')
                ->with('success', 'Product updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Product update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a product safely (stock movements remain for history).
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();

            Log::info('🗑️ Product deleted', ['product_id' => $product->id]);

            return redirect()
                ->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('❌ Product delete failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete product: ' . $e->getMessage()]);
        }
    }
}
