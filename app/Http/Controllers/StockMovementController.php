<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
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

    // 🔍 Build the filtered query
    $query = $this->buildQuery($request);

    // ✅ Paginate results
    $movements = $query->paginate(20);

    // ✅ Compute totals for the current filter
    $totals = [
        'in'  => (clone $query)->where('type', 'in')->sum('quantity'),
        'out' => (clone $query)->where('type', 'out')->sum('quantity'),
    ];
    $totals['net'] = $totals['in'] - $totals['out'];

    return view('stock_movements.index', compact('movements', 'products', 'totals'));
}

    /**
     * Build query (shared by web, CSV, PDF, API).
     */
    private function buildQuery(Request $request)
{
    $query = StockMovement::with(['product', 'user'])
        ->latest();

    // 🔍 Filters
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

    // 🔐 Role-based access
    $user = auth()->user();
    if ($user->role === 'cashier') {
        // Cashier only sees their own movements
        $query->where('user_id', $user->id);
    } elseif ($user->role === 'manager') {
        // Manager sees all movements for their managed team (future)
        // For now, same as admin but we can refine with branch filtering later
    } elseif ($user->role === 'admin') {
        // Admin sees everything
    }

    return $query;
}


    /**
     * Export filtered data to CSV (API-ready).
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
                fputcsv($handle, [
                    $m->created_at->format('Y-m-d H:i'),
                    $m->product->name,
                    strtoupper($m->type),
                    $m->quantity,
                    $m->unit_cost,
                    $m->total_cost,
                    $m->user->name ?? 'System',
                    class_basename($m->source_type) . ' #' . $m->source_id,
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
