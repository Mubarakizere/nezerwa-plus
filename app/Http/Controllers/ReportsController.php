<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\DebitCredit;
use App\Models\Loan;
use App\Models\SaleItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PDF; // barryvdh/laravel-dompdf

class ReportsController extends Controller
{
    /**
     * Main Reports Dashboard — analytics, summaries & insights (with P&L).
     */
    public function index(Request $request)
    {
        // -------- Dates (safe defaults) --------
        $start = $request->input('start_date', now()->subMonth()->toDateString());
        $end   = $request->input('end_date',   now()->toDateString());
        $q     = trim((string) $request->input('q', '')); // global optional search

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate   = Carbon::parse($end)->endOfDay();

        $like = $this->like();

        // =========================
        // SALES SUMMARY (date-range)
        // =========================
        $salesBase = Sale::query()
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('customer:id,name');

        if ($q !== '') {
            $salesBase->where(function ($xx) use ($q, $like) {
                $xx->where('notes', $like, "%{$q}%")
                   ->orWhereHas('customer', fn($c) => $c->where('name', $like, "%{$q}%"));
            });
        }

        $totalSales = (float) $salesBase->sum('total_amount');

        // Profit from sale items within the same date-range
        $siBase = SaleItem::query()
            ->whereHas('sale', function ($s) use ($startDate, $endDate) {
                $s->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()]);
            });

        if ($q !== '') {
            // match product name OR sale customer name OR sale notes
            $siBase->where(function ($w) use ($q, $like) {
                $w->whereHas('product', fn($p) => $p->where('name', $like, "%{$q}%"))
                  ->orWhereHas('sale.customer', fn($c) => $c->where('name', $like, "%{$q}%"))
                  ->orWhereHas('sale', fn($s) => $s->where('notes', $like, "%{$q}%"));
            });
        }

        $totalProfit   = (float) $siBase->sum('profit');
        $revenueByItem = (float) $siBase->sum('subtotal'); // more precise for P&L

        // Pending balances within range (keep your original formula)
        $pendingBalances = (float) Sale::whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', 'pending')
            ->sum(DB::raw('COALESCE(total_amount,0) - COALESCE(amount_paid,0) - COALESCE(returns_total,0)'));

        // =========================
        // PURCHASE SUMMARY (date-range)
        // =========================
        $purchaseBase = Purchase::query()
            ->whereBetween('purchase_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($q !== '') {
            $purchaseBase->where(function ($w) use ($q, $like) {
                $w->where('notes', $like, "%{$q}%")
                  ->orWhereHas('supplier', fn($s) => $s->where('name', $like, "%{$q}%"));
            });
        }

        $totalPurchases = (float) $purchaseBase->sum('total_amount');

        // =========================
        // FINANCE SUMMARY (date-range) — use DC.date (not created_at)
        // =========================
        $credits = (float) DebitCredit::where('type', 'credit')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $debits = (float) DebitCredit::where('type', 'debit')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $netBalance = $credits - $debits;

        // =========================
        // LOANS SUMMARY (date-range) — keep as cash view
        // =========================
        $loansGiven = (float) Loan::where('type', 'given')
            ->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        $loansTaken = (float) Loan::where('type', 'taken')
            ->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        // =========================
        // TOP ENTITIES (date-range)
        // =========================
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(subtotal) as total_sales'))
            ->whereHas('sale', function ($qSale) use ($startDate, $endDate, $q, $like) {
                $qSale->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()]);
                if ($q !== '') {
                    $qSale->where(function ($xx) use ($q, $like) {
                        $xx->where('notes', $like, "%{$q}%")
                           ->orWhereHas('customer', fn($c) => $c->where('name', $like, "%{$q}%"));
                    });
                }
            })
            ->when($q !== '', function ($qry) use ($q, $like) {
                $qry->whereHas('product', fn($p) => $p->where('name', $like, "%{$q}%"));
            })
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        $topCustomers = Sale::select('customer_id', DB::raw('SUM(total_amount) as total_spent'))
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotNull('customer_id')
            ->when($q !== '', function ($qry) use ($q, $like) {
                $qry->where(function ($xx) use ($q, $like) {
                    $xx->where('notes', $like, "%{$q}%")
                       ->orWhereHas('customer', fn($c) => $c->where('name', $like, "%{$q}%"));
                });
            })
            ->with('customer:id,name')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        // =========================
        // MONTHLY SALES (last 6 months, gap-safe)
        // =========================
        $months       = collect();
        $monthlySales = collect();
        for ($i = 5; $i >= 0; $i--) {
            $mStart = now()->copy()->subMonths($i)->startOfMonth();
            $mEnd   = $mStart->copy()->endOfMonth();

            $mmBase = Sale::whereBetween('sale_date', [$mStart->toDateString(), $mEnd->toDateString()]);
            $months->push($mStart->format('M'));
            $monthlySales->push((float) $mmBase->sum('total_amount'));
        }

        // =========================
        // ADVANCED METRICS
        // =========================
        $lastMonthStart    = now()->subMonth()->startOfMonth();
        $lastMonthEnd      = now()->subMonth()->endOfMonth();
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd   = now()->endOfMonth();

        $lastMonthSales = (float) Sale::whereBetween('sale_date', [$lastMonthStart->toDateString(), $lastMonthEnd->toDateString()])
            ->sum('total_amount');

        $currentMonthSales = (float) Sale::whereBetween('sale_date', [$currentMonthStart->toDateString(), $currentMonthEnd->toDateString()])
            ->sum('total_amount');

        $revenueGrowth = $lastMonthSales > 0
            ? (($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100
            : 0.0;

        // Use sales item–based revenue for margin accuracy
        $profitMargin = $revenueByItem > 0 ? ($totalProfit / $revenueByItem) * 100 : 0.0;

        $totalExpenses = $totalPurchases + $debits; // simple proxy for expense on dashboard card
        $expenseRatio  = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0.0;

        // =========================
        // REVENUE vs EXPENSES (last 6 months)
        // =========================
        $revenueExpensesChart = collect();
        for ($i = 5; $i >= 0; $i--) {
            $mStart = now()->copy()->subMonths($i)->startOfMonth();
            $mEnd   = $mStart->copy()->endOfMonth();

            $sales    = (float) Sale::whereBetween('sale_date', [$mStart->toDateString(), $mEnd->toDateString()])->sum('total_amount');
            $expenses = (float) Purchase::whereBetween('purchase_date', [$mStart->toDateString(), $mEnd->toDateString()])->sum('total_amount');

            $revenueExpensesChart->push([
                'month'    => $mStart->format('M'),
                'sales'    => $sales,
                'expenses' => $expenses,
            ]);
        }

        // =========================
        // LIVE SNAPSHOT: AR/AP & Inventory
        // =========================
        $arBalance = (float) Sale::select('total_amount', 'amount_paid', 'returns_total')
            ->get()
            ->sum(function ($s) {
                $due = (float)$s->total_amount - (float)$s->amount_paid - (float)($s->returns_total ?? 0);
                return $due > 0 ? $due : 0;
            });

        $apBalance = (float) Purchase::select('total_amount', 'amount_paid')
            ->get()
            ->sum(function ($p) {
                $due = (float)$p->total_amount - (float)$p->amount_paid;
                return $due > 0 ? $due : 0;
            });

        $productsForInv = Product::select('id', 'cost_price', 'price')
            ->withSum(['stockMovements as qty_in'  => fn($q) => $q->where('type', 'in')],  'quantity')
            ->withSum(['stockMovements as qty_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->get();

        $inventoryUnits = 0;
        $inventoryValue = 0.0;
        foreach ($productsForInv as $p) {
            $in  = (float)($p->qty_in  ?? 0);
            $out = (float)($p->qty_out ?? 0);
            $stk = max(0, $in - $out);
            $inventoryUnits += $stk;
            $inventoryValue += $stk * (float)($p->cost_price ?? 0);
        }

        // =========================
        // PROFIT & LOSS (date-range)
        // =========================
        // Revenue: sum of sale_items.subtotal in range (more accurate, aligns with profit column)
        $plRevenue = $revenueByItem;

        // COGS: revenue - profit (guards against negative with max)
        $plCogs    = max(0.0, $plRevenue - $totalProfit);

        // Other operating expenses: conservative default = DC debits with no linked transaction (tune if needed)
        $plOtherExpenses = (float) DebitCredit::where('type', 'debit')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNull('transaction_id') // <- keeps it from double-counting payment entries
            ->sum('amount');

        $plGrossProfit = $plRevenue - $plCogs;
        $plNetProfit   = $plGrossProfit - $plOtherExpenses;

        // -------- Return view --------
        return view('reports.index', [
            'start' => $start,
            'end' => $end,
            'q' => $q,

            'totalSales'      => number_format($totalSales, 2),
            'totalProfit'     => number_format($totalProfit, 2),
            'pendingBalances' => number_format($pendingBalances, 2),
            'totalPurchases'  => number_format($totalPurchases, 2),
            'credits'         => number_format($credits, 2),
            'debits'          => number_format($debits, 2),
            'netBalance'      => number_format($netBalance, 2),

            'loansGiven'      => number_format($loansGiven, 2),
            'loansTaken'      => number_format($loansTaken, 2),

            'months'          => $months,
            'monthlySales'    => $monthlySales,

            'revenueGrowth'   => $revenueGrowth,
            'profitMargin'    => $profitMargin,
            'expenseRatio'    => $expenseRatio,

            'revenueExpensesChart' => $revenueExpensesChart,

            'arBalance'       => $arBalance,
            'apBalance'       => $apBalance,
            'inventoryUnits'  => $inventoryUnits,
            'inventoryValue'  => $inventoryValue,

            'topProducts'     => $topProducts,
            'topCustomers'    => $topCustomers,

            // P&L block
            'plRevenue'       => $plRevenue,
            'plCogs'          => $plCogs,
            'plGrossProfit'   => $plGrossProfit,
            'plOtherExpenses' => $plOtherExpenses,
            'plNetProfit'     => $plNetProfit,
        ]);
    }

    /**
     * Export Sales Report (CSV)
     */
    public function exportSalesCsv(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', now()->subMonth()->toDateString()))->toDateString();
        $endDate   = Carbon::parse($request->input('end_date', now()->toDateString()))->toDateString();
        $like      = $this->like();
        $q         = trim((string) $request->input('q', ''));

        $sales = Sale::with('customer:id,name')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->when($q !== '', function ($qry) use ($q, $like) {
                $qry->where(function ($xx) use ($q, $like) {
                    $xx->where('notes', $like, "%{$q}%")
                       ->orWhereHas('customer', fn($c) => $c->where('name', $like, "%{$q}%"));
                });
            })
            ->orderBy('sale_date', 'desc')
            ->get(['id', 'customer_id', 'total_amount', 'amount_paid', 'status', 'sale_date']);

        $csv  = "Sale ID,Customer,Total Amount,Amount Paid,Status,Date\n";
        foreach ($sales as $s) {
            $customer = $s->customer->name ?? 'Walk-in';
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $s->id,
                str_replace(',', ' ', $customer),
                $s->total_amount,
                $s->amount_paid,
                ucfirst((string)$s->status),
                $s->sale_date
            );
        }

        $filename = "sales_report_{$startDate}_to_{$endDate}.csv";
        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export Finance Report (PDF)
     * View (to create later): resources/views/reports/pdf/finance.blade.php
     */
    public function exportFinancePdf(Request $request)
    {
        $start = Carbon::parse($request->input('start_date', now()->subMonth()->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->input('end_date',   now()->toDateString()))->endOfDay();

        // Overall summary (use DC.date)
        $credits    = (float) DebitCredit::where('type', 'credit')->whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $debits     = (float) DebitCredit::where('type', 'debit')->whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $netBalance = $credits - $debits;

        // Profit via items
        $totalProfit = (float) SaleItem::whereHas('sale', function ($q) use ($start, $end) {
                $q->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]);
            })->sum('profit');

        // Category buckets (simple)
        $salesCredits    = (float) Sale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');
        $salesDebits     = 0.0;

        $purchaseCredits = 0.0;
        $purchaseDebits  = (float) Purchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');

        $loanCredits = (float) Loan::where('type', 'taken')->whereBetween('created_at', [$start, $end])->sum('amount');
        $loanDebits  = (float) Loan::where('type', 'given')->whereBetween('created_at', [$start, $end])->sum('amount');

        $otherCredits = (float) DebitCredit::where('type', 'credit')
            ->whereNull('customer_id')->whereNull('supplier_id')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $otherDebits = (float) DebitCredit::where('type', 'debit')
            ->whereNull('customer_id')->whereNull('supplier_id')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $totalSales     = $salesCredits;
        $totalPurchases = $purchaseDebits;
        $totalExpenses  = $totalPurchases + $debits;

        $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0.0;
        $expenseRatio = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0.0;

        $lastMonthSales = (float) Sale::whereBetween('sale_date', [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()])
            ->sum('total_amount');

        $currentMonthSales = (float) Sale::whereBetween('sale_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('total_amount');

        $revenueGrowth = $lastMonthSales > 0 ? (($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100 : 0.0;

        $pdf = PDF::loadView('reports.pdf.finance', compact(
            'start', 'end',
            'credits', 'debits', 'netBalance', 'totalProfit',
            'salesCredits', 'salesDebits',
            'purchaseCredits', 'purchaseDebits',
            'loanCredits', 'loanDebits',
            'otherCredits', 'otherDebits',
            'profitMargin', 'expenseRatio', 'revenueGrowth'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("finance_report_{$start->toDateString()}_to_{$end->toDateString()}.pdf");
    }

    /**
     * Export Business Insights Report (PDF)
     * View (to create later): resources/views/reports/pdf/insights.blade.php
     */
    public function exportInsightsPdf(Request $request)
    {
        $start = Carbon::parse($request->input('start_date', now()->subMonth()->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->input('end_date',   now()->toDateString()))->endOfDay();

        $totalSales     = (float) Sale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');
        $totalProfit    = (float) SaleItem::whereHas('sale', fn($q) => $q->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]))->sum('profit');
        $totalPurchases = (float) Purchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');
        $debits         = (float) DebitCredit::where('type','debit')->whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $credits        = (float) DebitCredit::where('type','credit')->whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $netBalance     = $credits - $debits;

        $totalExpenses = $totalPurchases + $debits;
        $profitMargin  = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0.0;
        $expenseRatio  = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0.0;

        $topProducts = SaleItem::select('product_id', DB::raw('SUM(subtotal) as total_sales'))
            ->whereHas('sale', fn($q) => $q->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        $topCustomers = Sale::select('customer_id', DB::raw('SUM(total_amount) as total_spent'))
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('customer_id')
            ->with('customer:id,name')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        $pdf = PDF::loadView('reports.pdf.insights', compact(
            'start', 'end',
            'totalSales', 'totalProfit', 'totalPurchases',
            'debits', 'credits', 'netBalance',
            'profitMargin', 'expenseRatio',
            'topProducts', 'topCustomers'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("business_insights_{$start->toDateString()}_to_{$end->toDateString()}.pdf");
    }

    /**
     * Export Profit & Loss (PDF)
     * View (to create later): resources/views/reports/pdf/pl.blade.php
     */
    public function exportProfitLossPdf(Request $request)
    {
        $start = Carbon::parse($request->input('start_date', now()->subMonth()->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->input('end_date',   now()->toDateString()))->endOfDay();

        // Revenue / COGS / Profit from sale items
        $siBase = SaleItem::query()
            ->whereHas('sale', fn($s) => $s->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]));

        $plRevenue       = (float) $siBase->sum('subtotal');
        $plTotalProfit   = (float) $siBase->sum('profit');
        $plCogs          = max(0.0, $plRevenue - $plTotalProfit);

        // Other operating expenses (tune this predicate to your workflow)
        $plOtherExpenses = (float) DebitCredit::where('type', 'debit')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('transaction_id')
            ->sum('amount');

        $plGrossProfit = $plRevenue - $plCogs;
        $plNetProfit   = $plGrossProfit - $plOtherExpenses;

        $pdf = PDF::loadView('reports.pdf.pl', [
            'start'            => $start,
            'end'              => $end,
            'plRevenue'        => $plRevenue,
            'plCogs'           => $plCogs,
            'plGrossProfit'    => $plGrossProfit,
            'plOtherExpenses'  => $plOtherExpenses,
            'plNetProfit'      => $plNetProfit,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("profit_loss_{$start->toDateString()}_to_{$end->toDateString()}.pdf");
    }

    // =========================
    // Helpers
    // =========================
    private function like(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
