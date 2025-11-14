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
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /** List products */
    public function index(Request $request)
    {
        $threshold = 5;
        $perPage   = (int) $request->input('per_page', 20);
        $op        = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        // Only show categories that are usable for products in filters
        $categories = Category::active()->forProducts()->orderBy('name')->get();

        $query = Product::query()
            ->with(['category' => fn($q) => $q->withTrashed()])
            ->withSum(['stockMovements as qty_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockMovements as qty_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->withSum(['stockMovements as qty_returned' => function ($q) {
                $q->where('type', 'out')->where('source_type', PurchaseReturn::class);
            }], 'quantity')
            ->withMax('stockMovements as last_moved_at', 'created_at');

        // Case-insensitive search (supports ?q= or ?search=)
        if ($s = trim((string) ($request->input('q', $request->input('search', ''))))) {
            $pattern = "%{$s}%";
            $query->where(function ($w) use ($op, $pattern, $s) {
                $w->where('name', $op, $pattern)
                  ->orWhere('sku', $op, $pattern);
                if (is_numeric($s)) $w->orWhere('id', (int) $s);
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // Stock status filter
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
            $query->select('products.*')->selectRaw("$stockExpr AS computed_stock");

            if ($status === 'out') {
                $query->whereRaw("$stockExpr <= 0");
            } elseif ($status === 'low') {
                $query->whereRaw("$stockExpr > 0 AND $stockExpr <= ?", [$threshold]);
            } elseif ($status === 'in') {
                $query->whereRaw("$stockExpr > ?", [$threshold]);
            }
        }

        $products = $query->orderBy('name')->paginate($perPage)->appends($request->query());

        return view('products.index', compact('products', 'categories', 'threshold'));
    }

    /** Create form */
    public function create()
    {
        $categories = Category::active()->forProducts()->orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    /** Store */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required','string','max:255'],
            'category_id' => [
                'required','integer',
                Rule::exists('categories','id')->where(fn($q) =>
                    $q->whereNull('deleted_at')->where('is_active', true)->whereIn('kind',['product','both'])
                ),
            ],
            'price'       => ['required','numeric','min:0'],
            'stock'       => ['required','numeric','min:0'],
        ], [
            'category_id.exists' => 'The selected category is invalid or inactive.',
        ]);

        try {
            DB::beginTransaction();

            $product = Product::create([
                'name'        => $request->name,
                'category_id' => $request->integer('category_id'),
                'price'       => $request->input('price'),
                'stock'       => $request->input('stock'),
            ]);

            // Initial stock movement
            if ($request->float('stock', 0) > 0) {
                $uc = $product->cost_price ?? $product->price ?? 0;
                StockMovement::create([
                    'product_id'  => $product->id,
                    'type'        => 'in',
                    'quantity'    => $request->float('stock'),
                    'unit_cost'   => $uc,
                    'total_cost'  => $uc * $request->float('stock'),
                    'source_type' => Product::class,
                    'source_id'   => $product->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            DB::commit();
            Log::info('Product created', ['product_id' => $product->id]);

            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product create failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()])->withInput();
        }
    }

    /** Show */
    public function show(Product $product)
    {
        $product->load(['category' => fn($q) => $q->withTrashed(), 'stockMovements' => function ($q) {
            $q->latest()->take(10)->with('user');
        }]);

        $totalIn  = $product->stockMovements()->where('type', 'in')->sum('quantity');
        $totalOut = $product->stockMovements()->where('type', 'out')->sum('quantity');
        $current  = $product->currentStock();

        return view('products.show', compact('product', 'totalIn', 'totalOut', 'current'));
    }

    /** Edit form */
    public function edit(Product $product)
    {
        $categories = Category::active()->forProducts()->orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    /** Update */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'        => ['required','string','max:255'],
            'category_id' => [
                'required','integer',
                Rule::exists('categories','id')->where(fn($q) =>
                    $q->whereNull('deleted_at')->where('is_active', true)->whereIn('kind',['product','both'])
                ),
            ],
            'price'       => ['required','numeric','min:0'],
            'stock'       => ['required','numeric','min:0'],
        ], [
            'category_id.exists' => 'The selected category is invalid or inactive.',
        ]);

        try {
            DB::beginTransaction();

            $oldStock = $product->currentStock();

            $product->update([
                'name'        => $request->name,
                'category_id' => $request->integer('category_id'),
                'price'       => $request->input('price'),
                'stock'       => $request->input('stock'),
            ]);

            $newStock   = (float) $request->input('stock');
            $difference = $newStock - (float) $oldStock;

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
            Log::info('Product updated', ['product_id' => $product->id]);

            return redirect()->route('products.index')->with('success', 'Product updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()])->withInput();
        }
    }

    /** Delete (soft or hard depending on your model setup) */
    public function destroy(Product $product)
    {
        try {
            $product->delete();
            Log::info('Product deleted', ['product_id' => $product->id]);
            return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Product delete failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete product: ' . $e->getMessage()]);
        }
    }
}
