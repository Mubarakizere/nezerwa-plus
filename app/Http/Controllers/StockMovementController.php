<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class StockMovementController extends Controller
{
    /**
     * Display filterable stock history.
     */
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->get();

        // Build the filtered query
        $query = $this->buildQuery($request);

        // Paginate results
        $movements = $query->paginate(20);

        // Totals for current filter
        $totals = [
            'in'  => (clone $query)->where('type', 'in')->sum('quantity'),
            'out' => (clone $query)->where('type', 'out')->sum('quantity'),
        ];
        $totals['net'] = $totals['in'] - $totals['out'];

        // OUT/IN breakdown by origin for current filter
        $breakdown = [
            'out_sales'     => (clone $query)->where('type','out')->where('source_type', Sale::class)->sum('quantity'),
            'out_returns'   => (clone $query)->where('type','out')->where('source_type', PurchaseReturn::class)->sum('quantity'),
            'in_purchases'  => (clone $query)->where('type','in')->where('source_type', Purchase::class)->sum('quantity'),
        ];

        return view('stock_movements.index', compact('movements', 'products', 'totals', 'breakdown'));
    }

    /**
     * Build query (shared by web, CSV, PDF, API).
     */
    private function buildQuery(Request $request)
    {
        // If your StockMovement model defines: public function source() { return $this->morphTo(); }
        // this will eager-load the morph so we can deep-link (e.g., a PurchaseReturn knows its purchase_id).
        $query = StockMovement::with(['product', 'user', 'source'])
            ->latest();

        // Filters
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Origin filter: purchase / sale / purchase_return
        if ($request->filled('origin')) {
            $map = [
                'purchase'        => Purchase::class,
                'sale'            => Sale::class,
                'purchase_return' => PurchaseReturn::class,
            ];
            $val = $request->origin;
            if (isset($map[$val])) {
                $query->where('source_type', $map[$val]);
            }
        }

        // Role-based visibility (optional; keep your logic)
        $user = auth()->user();
        if ($user && property_exists($user, 'role')) {
            if ($user->role === 'cashier') {
                $query->where('user_id', $user->id);
            }
            // manager/admin see all (add branch filters later if needed)
        }

        return $query;
    }

    /**
     * Export filtered data to CSV.
     */
    public function exportCsv(Request $request)
    {
        $filename = 'stock_history_' . now()->format('Ymd_His') . '.csv';
        $movements = $this->buildQuery($request)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($movements) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Product', 'Type', 'Quantity', 'Unit Cost', 'Total Cost', 'Recorded By', 'Source']);

            foreach ($movements as $m) {
                $source = 'N/A';
                if ($m->source_type === Purchase::class) {
                    $source = 'Purchase #' . $m->source_id;
                } elseif ($m->source_type === Sale::class) {
                    $source = 'Sale #' . $m->source_id;
                } elseif ($m->source_type === PurchaseReturn::class) {
                    $purchaseId = optional($m->source)->purchase_id ?? null;
                    $source = 'Purchase Return #' . $m->source_id . ($purchaseId ? " (Purchase #$purchaseId)" : '');
                }

                fputcsv($handle, [
                    $m->created_at->format('Y-m-d H:i'),
                    optional($m->product)->name,
                    strtoupper($m->type),
                    $m->quantity,
                    $m->unit_cost,
                    $m->total_cost,
                    optional($m->user)->name ?: 'System',
                    $source,
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export filtered data to PDF.
     */
    public function exportPdf(Request $request)
    {
        $movements = $this->buildQuery($request)->get();
        $pdf = Pdf::loadView('stock_movements.report', compact('movements'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('stock_history_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * JSON API endpoint for future integrations.
     */
    public function api(Request $request)
    {
        $movements = $this->buildQuery($request)->paginate(50);
        return response()->json($movements);
    }
}
