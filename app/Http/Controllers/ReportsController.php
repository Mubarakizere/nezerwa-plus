<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\DebitCredit;
use App\Models\Loan;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PDF; // barryvdh/laravel-dompdf

class ReportsController extends Controller
{
    /**
     * 📊 Main Reports Dashboard — analytics, summaries & insights
     */
    public function index(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->subMonth()->toDateString());
        $end   = $request->input('end_date', Carbon::now()->toDateString());

        // =========================
        // SALES SUMMARY
        // =========================
        $salesQuery = Sale::whereBetween('sale_date', [$start, $end]);
        $totalSales = $salesQuery->sum('total_amount');
        $totalProfit = SaleItem::sum('profit');
        $pendingBalances = Sale::where('status', 'pending')
            ->sum(DB::raw('total_amount - amount_paid'));

        // =========================
        // PURCHASE SUMMARY
        // =========================
        $purchasesQuery = Purchase::whereBetween('purchase_date', [$start, $end]);
        $totalPurchases = $purchasesQuery->sum('total_amount');

        // =========================
        // FINANCE SUMMARY
        // =========================
        $credits = DebitCredit::where('type', 'credit')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $debits = DebitCredit::where('type', 'debit')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $netBalance = $credits - $debits;

        // =========================
        // LOANS SUMMARY
        // =========================
        $loansGiven = Loan::where('type', 'given')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $loansTaken = Loan::where('type', 'taken')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        // =========================
        // INSIGHTS SECTION
        // =========================
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(subtotal) as total_sales'))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        $topCustomers = Sale::select('customer_id', DB::raw('SUM(total_amount) as total_spent'))
            ->with('customer:id,name')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        // 📈 Monthly Sales Trend (Last 6 Months)
        $months = collect(range(1, 6))
            ->map(fn($i) => now()->subMonths(6 - $i)->format('M'));

        $monthlySales = Sale::selectRaw('EXTRACT(MONTH FROM sale_date) AS month, SUM(total_amount) AS total')
            ->where('sale_date', '>=', Carbon::now()->subMonths(6))
            ->groupByRaw('EXTRACT(MONTH FROM sale_date)')
            ->orderByRaw('MIN(sale_date)')
            ->pluck('total');

        // ADVANCED METRICS
        $lastMonthSales = Sale::whereBetween('sale_date', [
            Carbon::now()->subMonths(1)->startOfMonth(),
            Carbon::now()->subMonths(1)->endOfMonth(),
        ])->sum('total_amount');

        $currentMonthSales = Sale::whereBetween('sale_date', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->sum('total_amount');

        $revenueGrowth = $lastMonthSales > 0
            ? (($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100
            : 0;

        $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;

        $totalExpenses = $totalPurchases + $debits;
        $expenseRatio = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0;

        $revenueExpensesChart = collect(range(1, 6))->map(function ($i) {
            $monthStart = now()->subMonths(6 - $i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $sales = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->sum('total_amount');
            $expenses = Purchase::whereBetween('purchase_date', [$monthStart, $monthEnd])->sum('total_amount');

            return [
                'month' => $monthStart->format('M'),
                'sales' => $sales,
                'expenses' => $expenses,
            ];
        });

        // Return view
        return view('reports.index', compact(
            'start', 'end', 'totalSales', 'totalProfit', 'pendingBalances',
            'totalPurchases', 'credits', 'debits', 'netBalance',
            'loansGiven', 'loansTaken', 'topProducts', 'topCustomers',
            'months', 'monthlySales', 'revenueGrowth', 'profitMargin',
            'expenseRatio', 'revenueExpensesChart'
        ));
    }

    /**
     * 💾 Export Sales Report (CSV)
     */
    public function exportSalesCsv(Request $request)
    {
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        $sales = Sale::with('customer')
            ->whereBetween('sale_date', [$start, $end])
            ->orderBy('sale_date', 'desc')
            ->get(['id', 'customer_id', 'total_amount', 'amount_paid', 'status', 'sale_date']);

        $csvData = "Sale ID,Customer,Total Amount,Amount Paid,Status,Date\n";
        foreach ($sales as $sale) {
            $csvData .= "{$sale->id}," . ($sale->customer->name ?? 'Walk-in') . ",{$sale->total_amount},{$sale->amount_paid}," . ucfirst($sale->status) . ",{$sale->sale_date}\n";
        }

        $filename = "sales_report_{$start}_to_{$end}.csv";
        return Response::make($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * 📄 Export Finance Report (PDF)
     */
    /**
 * 📄 Export Finance Report (PDF)
 */
public function exportFinancePdf(Request $request)
{
    $start = $request->input('start_date');
    $end   = $request->input('end_date');

    // =========================
    // 🔹 Overall Summary
    // =========================
    $credits = DebitCredit::where('type', 'credit')
        ->whereBetween('created_at', [$start, $end])
        ->sum('amount');

    $debits = DebitCredit::where('type', 'debit')
        ->whereBetween('created_at', [$start, $end])
        ->sum('amount');

    $netBalance = $credits - $debits;
    $totalProfit = SaleItem::whereBetween('created_at', [$start, $end])->sum('profit');

    // =========================
    // 🔹 Category Breakdown
    // =========================
    // Sales (from transactions or debit/credit)
    $salesCredits = Sale::whereBetween('sale_date', [$start, $end])->sum('total_amount');
    $salesDebits = 0; // sales usually don’t have direct debits

    // Purchases
    $purchaseCredits = 0; // normally no credit inflow from purchases
    $purchaseDebits = Purchase::whereBetween('purchase_date', [$start, $end])->sum('total_amount');

    // Loans
    $loanCredits = Loan::where('type', 'taken')
        ->whereBetween('created_at', [$start, $end])
        ->sum('amount');
    $loanDebits = Loan::where('type', 'given')
        ->whereBetween('created_at', [$start, $end])
        ->sum('amount');

    // Other transactions (debit/credit records not tied to sales or purchases)
    $otherCredits = DebitCredit::where('type', 'credit')
        ->whereNull('customer_id')
        ->whereNull('supplier_id')
        ->whereBetween('created_at', [$start, $end])
        ->sum('amount');

    $otherDebits = DebitCredit::where('type', 'debit')
        ->whereNull('customer_id')
        ->whereNull('supplier_id')
        ->whereBetween('created_at', [$start, $end])
        ->sum('amount');

    // =========================
    // 🔹 Ratios
    // =========================
    $totalSales = Sale::whereBetween('sale_date', [$start, $end])->sum('total_amount');
    $totalPurchases = Purchase::whereBetween('purchase_date', [$start, $end])->sum('total_amount');
    $totalExpenses = $totalPurchases + $debits;

    $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
    $expenseRatio = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0;

    $lastMonthSales = Sale::whereBetween('sale_date', [
        Carbon::now()->subMonths(1)->startOfMonth(),
        Carbon::now()->subMonths(1)->endOfMonth(),
    ])->sum('total_amount');

    $currentMonthSales = Sale::whereBetween('sale_date', [
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth(),
    ])->sum('total_amount');

    $revenueGrowth = $lastMonthSales > 0
        ? (($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100
        : 0;

    // =========================
    // 🔹 Generate PDF
    // =========================
    $pdf = PDF::loadView('reports.pdf.finance', compact(
        'start', 'end',
        'credits', 'debits', 'netBalance', 'totalProfit',
        'salesCredits', 'salesDebits',
        'purchaseCredits', 'purchaseDebits',
        'loanCredits', 'loanDebits',
        'otherCredits', 'otherDebits',
        'profitMargin', 'expenseRatio', 'revenueGrowth'
    ))->setPaper('a4', 'portrait');

    return $pdf->download("finance_report_{$start}_to_{$end}.pdf");
}


    /**
     * 📈 Export Business Insights Report (PDF)
     */
    public function exportInsightsPdf(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->subMonth()->toDateString());
        $end   = $request->input('end_date', Carbon::now()->toDateString());

        // Core data
        $totalSales = Sale::whereBetween('sale_date', [$start, $end])->sum('total_amount');
        $totalProfit = SaleItem::sum('profit');
        $totalPurchases = Purchase::whereBetween('purchase_date', [$start, $end])->sum('total_amount');
        $debits = DebitCredit::where('type', 'debit')->sum('amount');
        $credits = DebitCredit::where('type', 'credit')->sum('amount');
        $netBalance = $credits - $debits;

        // Ratios
        $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
        $totalExpenses = $totalPurchases + $debits;
        $expenseRatio = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0;

        // Top entities
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(subtotal) as total_sales'))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        $topCustomers = Sale::select('customer_id', DB::raw('SUM(total_amount) as total_spent'))
            ->with('customer:id,name')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        // Generate PDF
        $pdf = PDF::loadView('reports.pdf.insights', compact(
            'start', 'end', 'totalSales', 'totalProfit', 'totalPurchases',
            'debits', 'credits', 'netBalance', 'profitMargin', 'expenseRatio',
            'topProducts', 'topCustomers'
        ));

        return $pdf->download("business_insights_{$start}_to_{$end}.pdf");
    }
}
