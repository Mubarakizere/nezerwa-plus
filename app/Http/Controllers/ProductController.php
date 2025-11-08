<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display list of products with category and stock summary.
     */
    public function index(Request $request)
    {
        $threshold = 5;
        $perPage   = (int) $request->input('per_page', 20);

        $categories = Category::orderBy('name')->get();

        $query = Product::query()
            ->with('category')
            ->withSum(['stockMovements as qty_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockMovements as qty_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->withSum(['stockMovements as qty_returned' => function ($q) {
                $q->where('type', 'out')->where('source_type', PurchaseReturn::class);
            }], 'quantity')
            ->withMax('stockMovements as last_moved_at', 'created_at');

        //  Search (name / SKU)
        if ($s = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        //  Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        //  Stock status filter (Postgres-safe: use correlated subquery in WHERE)
        if ($status = $request->input('stock_status')) {
            $stockExpr = "
                (
                    SELECT
                        COALESCE(SUM(CASE WHEN type = 'in'  THEN quantity ELSE 0 END), 0)
                      - COALESCE(SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END), 0)
                    FROM stock_movements sm
                    WHERE sm.product_id = products.id
                )
            ";

            // Expose computed_stock for display/sorting if needed
            $query->select('products.*')->selectRaw("$stockExpr AS computed_stock");

            if ($status === 'out') {
                $query->whereRaw("$stockExpr <= 0");
            } elseif ($status === 'low') {
                $query->whereRaw("$stockExpr > 0 AND $stockExpr <= ?", [$threshold]);
            } elseif ($status === 'in') {
                $query->whereRaw("$stockExpr > ?", [$threshold]);
            }
        }

        $query->orderBy('name');

        $products = $query->paginate($perPage);

        return view('products.index', [
            'products'   => $products,
            'categories' => $categories,
            'threshold'  => $threshold,
        ]);
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

            // Record initial stock as StockMovement (type: in)
            if ($request->stock > 0) {
                $uc = $product->cost_price ?? $product->price ?? 0;
                StockMovement::create([
                    'product_id'  => $product->id,
                    'type'        => 'in',
                    'quantity'    => $request->stock,
                    'unit_cost'   => $uc,
                    'total_cost'  => $uc * $request->stock,
                    'source_type' => Product::class,
                    'source_id'   => $product->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            DB::commit();
            Log::info(' Product created', ['product_id' => $product->id]);

            return redirect()
                ->route('products.index')
                ->with('success', 'Product created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error(' Product create failed', ['error' => $e->getMessage()]);
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

            $newStock   = (float) $request->stock;
            $difference = $newStock - (float) $oldStock;

            // Record stock adjustment
            if ($difference != 0) {
                $uc = $product->cost_price ?? $product->price ?? 0;
                StockMovement::create([
                    'product_id'  => $product->id,
                    'type'        => $difference > 0 ? 'in' : 'out',
                    'quantity'    => abs($difference),
                    'unit_cost'   => $uc,
                    'total_cost'  => abs($difference) * $uc,
                    'source_type' => Product::class,
                    'source_id'   => $product->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            DB::commit();
            Log::info(' Product updated', ['product_id' => $product->id]);

            return redirect()
                ->route('products.index')
                ->with('success', 'Product updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error(' Product update failed', ['error' => $e->getMessage()]);
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

            Log::info(' Product deleted', ['product_id' => $product->id]);

            return redirect()
                ->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Throwable $e) {
            Log::error(' Product delete failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete product: ' . $e->getMessage()]);
        }
    }
}
