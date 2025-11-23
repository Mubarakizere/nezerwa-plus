<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\ItemLoan;
use App\Models\ItemLoanReturn;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovementController extends Controller
{
    /**
     * Display filterable stock history.
     */
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->get();

        // Build the filtered query (+ eager loads)
        $query = $this->buildQuery($request);

        // Paginate results
        $movements = $query->paginate(20);

        // Totals for current filter
        $inTotal  = (clone $query)->where('type', 'in')->sum('quantity');
        $outTotal = (clone $query)->where('type', 'out')->sum('quantity');
        $totals = [
            'in'  => $inTotal,
            'out' => $outTotal,
            'net' => $inTotal - $outTotal,
        ];

        // OUT/IN breakdown by origin for current filter
        $breakdown = [
            'out_sales'       => (clone $query)->where('type','out')->where('source_type', Sale::class)->sum('quantity'),
            'out_returns'     => (clone $query)->where('type','out')->where('source_type', PurchaseReturn::class)->sum('quantity'),
            'in_purchases'    => (clone $query)->where('type','in')->where('source_type', Purchase::class)->sum('quantity'),
            'in_purchases'    => (clone $query)->where('type','in')->where('source_type', Purchase::class)->sum('quantity'),
            'in_sale_returns' => (clone $query)->where('type','in')->where('source_type', SaleReturn::class)->sum('quantity'),
            'out_loans'       => (clone $query)->where('type','out')->where('source_type', ItemLoan::class)->sum('quantity'),
            'in_loan_returns' => (clone $query)->where('type','in')->where('source_type', ItemLoanReturn::class)->sum('quantity'),
        ];

        return view('stock_movements.index', compact('movements', 'products', 'totals', 'breakdown'));
    }

    /**
     * Build query (shared by web, CSV, PDF, API).
     */
    private function buildQuery(Request $request)
    {
        // Eager-load morph source safely. If your PurchaseReturn has `purchase()`
        // or SaleReturn has `sale()`, morphWith will try to preload them; if not, it’s still safe.
        $query = StockMovement::query()
            ->with([
                'product:id,name',
                'user:id,name',
                'source' => function (MorphTo $m) {
                    // These nested eager-loads are best-effort; adjust to match your actual relation names.
                    $m->morphWith([
                        PurchaseReturn::class => ['purchase:id'],
                        PurchaseReturn::class => ['purchase:id'],
                        SaleReturn::class     => ['sale:id'],
                        ItemLoanReturn::class => ['loan:id'],
                        // Purchase::class, Sale::class, ItemLoan::class typically don't need nested loads here.
                    ]);
                },
            ])
            ->latest();

        // Filters
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $type = strtolower($request->type);
            if (in_array($type, ['in', 'out'], true)) {
                $query->where('type', $type);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Origin filter: purchase / sale / purchase_return / sale_return
        if ($request->filled('origin')) {
            $map = [
                'purchase'        => Purchase::class,
                'sale'            => Sale::class,
                'purchase_return' => PurchaseReturn::class,
                'purchase_return' => PurchaseReturn::class,
                'sale_return'     => SaleReturn::class,
                'item_loan'       => ItemLoan::class,
                'item_loan_return'=> ItemLoanReturn::class,
            ];
            $val = $request->origin;
            if (isset($map[$val])) {
                $query->where('source_type', $map[$val]);
            }
        }

        // Role-based visibility (optional; adapt to your Spatie setup)
        $user = auth()->user();
        if ($user) {
            if (method_exists($user, 'hasRole') && $user->hasRole('cashier')) {
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
        $filename  = 'stock_history_' . now()->format('Ymd_His') . '.csv';
        $movements = $this->buildQuery($request)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($movements) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date', 'Product', 'Type', 'Quantity', 'Unit Cost', 'Total Cost',
                'Reference / Note', 'Recorded By', 'Source'
            ]);

            foreach ($movements as $m) {
                // Compose "Source" label (now includes SaleReturn)
                $source = 'N/A';
                if ($m->source_type === Purchase::class) {
                    $source = 'Purchase #' . $m->source_id;
                } elseif ($m->source_type === Sale::class) {
                    $source = 'Sale #' . $m->source_id;
                } elseif ($m->source_type === PurchaseReturn::class) {
                    $purchaseId = optional($m->source)->purchase_id ?? null;
                    $source = 'Return to Supplier #' . $m->source_id . ($purchaseId ? " (Purchase #$purchaseId)" : '');
                } elseif ($m->source_type === SaleReturn::class) {
                } elseif ($m->source_type === SaleReturn::class) {
                    $saleId = optional($m->source)->sale_id ?? null;
                    $source = 'Customer Return #' . $m->source_id . ($saleId ? " (Sale #$saleId)" : '');
                } elseif ($m->source_type === ItemLoan::class) {
                    $source = 'Item Loan #' . $m->source_id;
                } elseif ($m->source_type === ItemLoanReturn::class) {
                    $loanId = optional($m->source)->item_loan_id ?? null;
                    $source = 'Loan Return #' . $m->source_id . ($loanId ? " (Loan #$loanId)" : '');
                }

                // Reference / Note: prefer movement fields, then source fallbacks
                $reference = $m->reference
                    ?? $m->note
                    ?? optional($m->source)->reference
                    ?? optional($m->source)->note
                    ?? optional($m->source)->remarks
                    ?? optional($m->source)->return_reason
                    ?? null;

                fputcsv($handle, [
                    optional($m->created_at)->format('Y-m-d H:i'),
                    optional($m->product)->name,
                    strtoupper($m->type),
                    $m->quantity,
                    $m->unit_cost,
                    $m->total_cost,
                    $reference,
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
     * (If you want Reference/Note in the PDF too, update the Blade at: resources/views/stock_movements/report.blade.php)
     */
    public function exportPdf(Request $request)
    {
        $movements = $this->buildQuery($request)->get();

        // Optional: pass totals/breakdown to the report view if needed
        $inTotal  = (clone $this->buildQuery($request))->where('type', 'in')->sum('quantity');
        $outTotal = (clone $this->buildQuery($request))->where('type', 'out')->sum('quantity');
        $totals = ['in' => $inTotal, 'out' => $outTotal, 'net' => $inTotal - $outTotal];

        $breakdown = [
            'out_sales'       => (clone $this->buildQuery($request))->where('type','out')->where('source_type', Sale::class)->sum('quantity'),
            'out_returns'     => (clone $this->buildQuery($request))->where('type','out')->where('source_type', PurchaseReturn::class)->sum('quantity'),
            'in_purchases'    => (clone $this->buildQuery($request))->where('type','in')->where('source_type', Purchase::class)->sum('quantity'),
            'in_purchases'    => (clone $this->buildQuery($request))->where('type','in')->where('source_type', Purchase::class)->sum('quantity'),
            'in_sale_returns' => (clone $this->buildQuery($request))->where('type','in')->where('source_type', SaleReturn::class)->sum('quantity'),
            'out_loans'       => (clone $this->buildQuery($request))->where('type','out')->where('source_type', ItemLoan::class)->sum('quantity'),
            'in_loan_returns' => (clone $this->buildQuery($request))->where('type','in')->where('source_type', ItemLoanReturn::class)->sum('quantity'),
        ];

        $pdf = Pdf::loadView('stock_movements.report', compact('movements', 'totals', 'breakdown'))
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
