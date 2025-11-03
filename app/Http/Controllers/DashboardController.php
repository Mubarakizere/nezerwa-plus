<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\DebitCredit;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $role = strtolower($user->getRoleNames()->first() ?? 'guest');

        // =========================
        // Role-based Sections
        // =========================
        $sections = [
            'kpis'               => in_array($role, ['admin', 'manager']),
            'finance'            => in_array($role, ['admin']),
            'loans'              => in_array($role, ['admin', 'manager']),
            'charts'             => in_array($role, ['admin', 'manager']),
            'recentTransactions' => in_array($role, ['admin', 'manager']),
            'cashierDaily'       => in_array($role, ['cashier']),
            'insights'           => in_array($role, ['admin', 'manager']),
        ];

        // =========================
        // 📊 KPI & Financial Overview
        // =========================
        $today = Carbon::today();
        $monthStart = now()->startOfMonth();

        // Totals
        $totalSales     = Sale::sum('total_amount');
        $totalPurchases = Purchase::sum('total_amount');
        $totalProfit    = SaleItem::sum(DB::raw('COALESCE(profit, (unit_price - cost_price) * quantity)'));
        $pendingBalances = Sale::where('status', 'pending')
            ->sum(DB::raw('total_amount - amount_paid'));

        // Debits & Credits
        $totalCredits = DebitCredit::where('type', 'credit')->sum('amount');
        $totalDebits  = DebitCredit::where('type', 'debit')->sum('amount');
        $netBalance   = $totalCredits - $totalDebits;

        // Loans
        $totalLoansGiven   = Loan::where('type', 'given')->sum('amount');
        $totalLoansTaken   = Loan::where('type', 'taken')->sum('amount');
        $activeLoans       = Loan::where('status', 'active')->count();
        $paidLoans         = Loan::where('status', 'paid')->count();
        $totalLoanPayments = LoanPayment::sum('amount');

        // =========================
        // 🕒 Time-Based KPIs
        // =========================
        $todaySales   = Sale::whereDate('created_at', $today)->sum('total_amount');
        $monthSales   = Sale::where('created_at', '>=', $monthStart)->sum('total_amount');
        $weekSales    = Sale::whereBetween('created_at', [now()->startOfWeek(), now()])->sum('total_amount');

        $yesterdaySales = Sale::whereDate('created_at', $today->copy()->subDay())->sum('total_amount');
        $salesChange = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : 0;

        // =========================
        // 💰 Stock & Assets
        // =========================
        $totalStockValue = Product::all()->sum(fn($p) => $p->stockValue());

        // =========================
        // 👤 User Daily Performance (Cashier / Manager)
        // =========================
        $myTodaySalesTotal = Sale::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $myTodaySalesCount = Sale::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->count();

        $myLatestSales = Sale::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // =========================
        // 💳 Recent Transactions
        // =========================
        $recentTransactions = DebitCredit::with(['user', 'customer', 'supplier'])
            ->latest()
            ->take(10)
            ->get();

        // =========================
        // 📈 Chart Data (30-Day Trend)
        // =========================
        $chartData = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        $chartLabels = $chartData->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'));
        $chartSales  = $chartData->pluck('total_sales');

        // =========================
        // 📅 Monthly Trends (6-month view)
        // =========================
        $months = collect(range(1, 6))
            ->map(fn($m) => now()->subMonths(6 - $m)->format('M'));

        $salesTrend = Sale::selectRaw('EXTRACT(MONTH FROM created_at) AS month, SUM(total_amount) AS total')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('MIN(created_at)')
            ->pluck('total');

        $purchaseTrend = Purchase::selectRaw('EXTRACT(MONTH FROM created_at) AS month, SUM(total_amount) AS total')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('MIN(created_at)')
            ->pluck('total');

        // =========================
        // 🧠 Advanced Insights
        // =========================
        // Top Products
        $topProducts = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('product:id,name')
            ->get();

        // Top Customers
        $topCustomers = Sale::select(
                'customer_id',
                DB::raw('SUM(total_amount) as total_spent')
            )
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->with('customer:id,name')
            ->get();

        // =========================
        // Return View
        // =========================
        return view('dashboard', compact(
            'role',
            'sections',
            'totalSales',
            'totalPurchases',
            'totalProfit',
            'pendingBalances',
            'totalCredits',
            'totalDebits',
            'netBalance',
            'totalLoansGiven',
            'totalLoansTaken',
            'activeLoans',
            'paidLoans',
            'totalLoanPayments',
            'todaySales',
            'monthSales',
            'weekSales',
            'salesChange',
            'totalStockValue',
            'myTodaySalesTotal',
            'myTodaySalesCount',
            'myLatestSales',
            'recentTransactions',
            'months',
            'salesTrend',
            'purchaseTrend',
            'chartLabels',
            'chartSales',
            'topProducts',
            'topCustomers'
        ));
    }

    /**
     * 🔄 Sales Chart Data API (for AJAX updates)
     */
    public function salesChartData()
    {
        $chartData = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'labels' => $chartData->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d')),
            'sales'  => $chartData->pluck('total_sales'),
        ]);
    }
}
