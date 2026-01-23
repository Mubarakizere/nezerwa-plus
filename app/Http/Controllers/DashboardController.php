<?php

namespace App\Http\Controllers;

use App\Models\{Sale, SaleItem, Purchase, Loan, LoanPayment, DebitCredit, Product, Expense, Category};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Auth, Schema, Log};
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        try {
            $user = Auth::user();

            // Determine data access levels based on permissions
            $canViewAllSales = $user->can('sales.view-all') || ($user->can('sales.view') && !$user->can('sales.view-own'));
            $canViewOwnSales = $user->can('sales.view-own') || ($user->can('sales.view') && !$canViewAllSales);
            $canViewSales = $canViewAllSales || $canViewOwnSales;
            
            $canViewAllTransactions = $user->can('transactions.view-all') || ($user->can('transactions.view') && !$user->can('transactions.view-own'));
            $canViewTransactions = $canViewAllTransactions || $user->can('transactions.view-own') || $user->can('transactions.view');

            // Check module permissions
            $permissions = [
                'sales' => $canViewSales,
                'purchases' => $user->can('purchases.view'),
                'loans' => $user->can('loans.view'),
                'expenses' => $user->can('expenses.view'),
                'stock' => $user->can('stock.view'),
                'debits_credits' => $user->can('debits-credits.view'),
                'customers' => $user->can('customers.view'),
                'products' => $user->can('products.view'),
                'reports' => $user->can('reports.view') || $user->can('reports.view-own') || $user->can('reports.view-all'),
            ];

            $today      = Carbon::today();
            $monthStart = now()->startOfMonth();
            $weekStart  = now()->startOfWeek();

            // Determine if user should see only their own sales data
            $filterOwnSales = $canViewOwnSales && !$canViewAllSales;
            $filterOwnTransactions = $user->can('transactions.view-own') && !$canViewAllTransactions;
            $userId = $user->id;

            // ========== TOTALS ==========
            $totalSales = 0;
            $totalProfit = 0;
            $pendingBalances = 0;
            
            if ($permissions['sales']) {
                $totalSales = $this->safeSum(
                    $filterOwnSales ? Sale::where('user_id', $userId) : Sale::class,
                    'total_amount'
                );
                $totalProfit = $this->safeSum(
                    $filterOwnSales ? SaleItem::whereHas('sale', fn($q) => $q->where('user_id', $userId)) : SaleItem::class,
                    DB::raw('COALESCE(profit, (unit_price - cost_price) * quantity)')
                );
                $pendingBalances = $this->safeSum(
                    $filterOwnSales ? Sale::where('status', 'pending')->where('user_id', $userId) : Sale::where('status', 'pending'),
                    DB::raw('total_amount - COALESCE(amount_paid, 0)')
                );
            }

            $totalPurchases = $permissions['purchases'] ? $this->safeSum(Purchase::class, 'total_amount') : 0;

            // ========== LEDGER ==========
            $totalCredits = 0;
            $totalDebits = 0;
            $netBalance = 0;
            
            if ($permissions['debits_credits']) {
                $totalCredits = $this->safeSum(DebitCredit::where('type', 'credit'), 'amount');
                $totalDebits  = $this->safeSum(DebitCredit::where('type', 'debit'), 'amount');
                $netBalance   = $totalCredits - $totalDebits;
            }

            // ========== EXPENSES ==========
            $totalExpenses = 0;
            $todayExpenses = 0;
            $weekExpenses = 0;
            $monthExpenses = 0;
            
            if ($permissions['expenses']) {
                $totalExpenses = $this->safeSum(Expense::class, 'amount');
                $todayExpenses = $this->safeSum(Expense::whereDate('date', $today), 'amount');
                $weekExpenses  = $this->safeSum(Expense::where('date', '>=', $weekStart), 'amount');
                $monthExpenses = $this->safeSum(Expense::where('date', '>=', $monthStart), 'amount');
            }

            // ========== LOANS ==========
            $totalLoansGiven = 0;
            $totalLoansTaken = 0;
            $activeLoans = 0;
            $overdueLoans = 0;
            $paidLoans = 0;
            $totalLoanPayments = 0;
            
            if ($permissions['loans']) {
                $totalLoansGiven   = $this->safeSum(Loan::where('type', 'given'), 'amount');
                $totalLoansTaken   = $this->safeSum(Loan::where('type', 'taken'), 'amount');
                $activeLoans       = Loan::where('status', '!=', 'paid')->count();
                $overdueLoans      = Loan::where('status', '!=', 'paid')
                                         ->whereNotNull('due_date')
                                         ->where('due_date', '<', now())
                                         ->count();
                $paidLoans         = Loan::where('status', 'paid')->count();
                $totalLoanPayments = $this->safeSum(LoanPayment::class, 'amount');
            }

            // ========== TIME-BASED SALES ==========
            $todaySales = 0;
            $monthSales = 0;
            $weekSales = 0;
            $yesterdaySales = 0;
            $salesChange = 0;
            
            if ($permissions['sales']) {
                $todaySales     = $this->safeSum(
                    $filterOwnSales ? Sale::whereDate('created_at', $today)->where('user_id', $userId) : Sale::whereDate('created_at', $today),
                    'total_amount'
                );
                $monthSales     = $this->safeSum(
                    $filterOwnSales ? Sale::where('created_at', '>=', $monthStart)->where('user_id', $userId) : Sale::where('created_at', '>=', $monthStart),
                    'total_amount'
                );
                $weekSales      = $this->safeSum(
                    $filterOwnSales ? Sale::where('created_at', '>=', $weekStart)->where('user_id', $userId) : Sale::where('created_at', '>=', $weekStart),
                    'total_amount'
                );
                $yesterdaySales = $this->safeSum(
                    $filterOwnSales ? Sale::whereDate('created_at', $today->copy()->subDay())->where('user_id', $userId) : Sale::whereDate('created_at', $today->copy()->subDay()),
                    'total_amount'
                );
                $salesChange    = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : 0;
            }

            // ========== STOCK ==========
            $totalStockValue = $permissions['stock'] ? $this->calculateStockValue() : 0;

            // ========== MY DAY (always user-specific) ==========
            $myTodaySalesTotal = 0;
            $myTodaySalesCount = 0;
            $myLatestSales = collect();
            
            if ($permissions['sales']) {
                $myTodaySalesTotal = $this->safeSum(
                    Sale::where('user_id', $user->id)->whereDate('created_at', $today),
                    'total_amount'
                );
                $myTodaySalesCount = Sale::where('user_id', $user->id)
                                        ->whereDate('created_at', $today)
                                        ->count();
                $myLatestSales = Sale::where('user_id', $user->id)
                                    ->with(['customer:id,name'])
                                    ->latest()
                                    ->take(5)
                                    ->get();
            }

            // ========== RECENT TRANSACTIONS ==========
            $recentTransactions = collect();
            if ($permissions['debits_credits']) {
                $recentTransactions = DebitCredit::with(['user:id,name', 'customer:id,name', 'supplier:id,name'])
                                                ->when($filterOwnTransactions, fn($q) => $q->where('user_id', $userId))
                                                ->latest()
                                                ->take(10)
                                                ->get();
            }

            // ========== CHART DATA ==========
            $chartLabels = collect();
            $chartSales = collect();
            $months = collect();
            $salesTrend = collect();
            $purchaseTrend = collect();
            
            if ($permissions['sales'] || $permissions['purchases']) {
                if ($permissions['sales']) {
                    [$chartLabels, $chartSales] = $this->getSalesChartData(30, $filterOwnSales ? $userId : null);
                }
                [$months, $salesTrend, $purchaseTrend] = $this->getTrendData(6, $filterOwnSales ? $userId : null);
            }

            // ========== INSIGHTS ==========
            $topProducts = collect();
            $topCustomers = collect();
            
            if ($permissions['sales']) {
                if ($permissions['products']) {
                    $topProducts = $this->getTopProducts(5, $filterOwnSales ? $userId : null);
                }
                if ($permissions['customers']) {
                    $topCustomers = $this->getTopCustomers(5, $filterOwnSales ? $userId : null);
                }
            }

            // ========== ACTIVE LOANS ==========
            $activeLoansList = $permissions['loans'] ? $this->getActiveLoans(8) : collect();

            // ========== EXPENSE BY CATEGORY ==========
            $expenseByCategory = ['labels' => [], 'amounts' => []];
            $recentExpenses = collect();
            
            if ($permissions['expenses']) {
                $expenseByCategory = $this->getExpensesByCategory($monthStart);
                $recentExpenses = Expense::with(['category:id,name', 'creator:id,name', 'supplier:id,name'])
                                        ->orderByDesc('date')
                                        ->take(8)
                                        ->get();
            }

            return view('dashboard', compact(
                'permissions',
                'totalSales', 'totalPurchases', 'totalProfit', 'pendingBalances',
                'totalCredits', 'totalDebits', 'netBalance',
                'totalExpenses', 'todayExpenses', 'weekExpenses', 'monthExpenses',
                'totalLoansGiven', 'totalLoansTaken', 'activeLoans', 'overdueLoans', 'paidLoans', 'totalLoanPayments',
                'todaySales', 'monthSales', 'weekSales', 'salesChange',
                'totalStockValue',
                'myTodaySalesTotal', 'myTodaySalesCount', 'myLatestSales',
                'recentTransactions',
                'months', 'salesTrend', 'purchaseTrend', 'chartLabels', 'chartSales',
                'topProducts', 'topCustomers',
                'expenseByCategory', 'recentExpenses', 'activeLoansList'
            ));

        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return view('dashboard')->with([
                'error' => 'Unable to load dashboard data. Please check logs.',
                'permissions' => []
            ]);
        }
    }

    // ========== HELPER METHODS ==========

    /**
     * Safely sum values, returning 0 on error
     */
    private function safeSum($query, $column = 'amount'): float
    {
        try {
            if (is_string($query)) {
                $query = $query::query();
            }
            return (float) $query->sum($column);
        } catch (\Exception $e) {
            Log::warning("Sum failed for {$column}: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calculate total stock value
     */
    /**
     * Calculate total stock value
     */
    private function calculateStockValue(): float
    {
        try {
            // Calculate stock value: sum(max(0, net_qty) * cost_price)
            // We use a subquery to calculate net quantity per product first,
            // clamp it to 0 (ignoring negative stock), then multiply by cost.
            // This matches the logic in ReportsController.
            
            $value = DB::table('products')
                ->join('stock_movements', 'products.id', '=', 'stock_movements.product_id')
                ->selectRaw('products.cost_price, SUM(CASE WHEN stock_movements.type = ? THEN stock_movements.quantity ELSE -stock_movements.quantity END) as net_qty', ['in'])
                ->groupBy('products.id', 'products.cost_price')
                ->get()
                ->sum(function ($product) {
                    return max(0, $product->net_qty) * $product->cost_price;
                });

            return (float) $value;
        } catch (\Exception $e) {
            Log::warning('Stock value calculation failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get sales chart data for last N days
     */
    private function getSalesChartData(int $days = 30, ?int $userId = null): array
    {
        try {
            $data = Sale::select(
                        DB::raw('DATE(created_at) as d'),
                        DB::raw('SUM(total_amount) as s')
                    )
                    ->when($userId, fn($q) => $q->where('user_id', $userId))
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->orderBy('d')
                    ->get();

            $labels = $data->pluck('d')->map(fn($d) => Carbon::parse($d)->format('M d'));
            $sales = $data->pluck('s');

            return [$labels, $sales];
        } catch (\Exception $e) {
            Log::warning('Sales chart data failed: ' . $e->getMessage());
            return [collect(), collect()];
        }
    }

    /**
     * Get trend data for last N months
     */
    private function getTrendData(int $months = 6, ?int $userId = null): array
    {
        try {
            $monthLabels = collect(range(1, $months))
                ->map(fn($i) => now()->subMonths($months - $i)->format('M'));

            $startDate = now()->subMonths($months)->startOfMonth();

            $salesData = Sale::selectRaw("DATE_TRUNC('month', created_at) as m, SUM(total_amount) as t")
                            ->when($userId, fn($q) => $q->where('user_id', $userId))
                            ->where('created_at', '>=', $startDate)
                            ->groupBy('m')
                            ->orderBy('m')
                            ->pluck('t');

            $purchaseData = Purchase::selectRaw("DATE_TRUNC('month', created_at) as m, SUM(total_amount) as t")
                                   ->where('created_at', '>=', $startDate)
                                   ->groupBy('m')
                                   ->orderBy('m')
                                   ->pluck('t');

            // Pad with zeros if needed
            while ($salesData->count() < $months) {
                $salesData->push(0);
            }
            while ($purchaseData->count() < $months) {
                $purchaseData->push(0);
            }

            return [$monthLabels, $salesData, $purchaseData];
        } catch (\Exception $e) {
            Log::warning('Trend data failed: ' . $e->getMessage());
            $empty = collect(array_fill(0, $months, 0));
            return [collect(), $empty, $empty];
        }
    }

    /**
     * Get top selling products
     */
    private function getTopProducts(int $limit = 5, ?int $userId = null)
    {
        try {
            return SaleItem::select(
                        'product_id',
                        DB::raw('SUM(quantity) as total_qty'),
                        DB::raw('SUM(subtotal) as total_revenue')
                    )
                    ->when($userId, fn($q) => $q->whereHas('sale', fn($sq) => $sq->where('user_id', $userId)))
                    ->groupBy('product_id')
                    ->orderByDesc('total_qty')
                    ->take($limit)
                    ->with('product:id,name')
                    ->get();
        } catch (\Exception $e) {
            Log::warning('Top products failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get top customers
     */
    private function getTopCustomers(int $limit = 5, ?int $userId = null)
    {
        try {
            return Sale::select(
                        'customer_id',
                        DB::raw('SUM(total_amount) as total_spent')
                    )
                    ->when($userId, fn($q) => $q->where('user_id', $userId))
                    ->whereNotNull('customer_id')
                    ->groupBy('customer_id')
                    ->orderByDesc('total_spent')
                    ->take($limit)
                    ->with('customer:id,name')
                    ->get();
        } catch (\Exception $e) {
            Log::warning('Top customers failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get active loans with payment sums
     */
    private function getActiveLoans(int $limit = 8)
    {
        try {
            return Loan::with(['customer:id,name', 'supplier:id,name'])
                      ->withSum('payments as paid_sum', 'amount')
                      ->where('status', '!=', 'paid')
                      ->latest()
                      ->take($limit)
                      ->get()
                      ->map(function ($loan) {
                          $paid = (float) ($loan->paid_sum ?? 0);
                          $loan->paid_sum = $paid;
                          $loan->remaining = max(0, (float) $loan->amount - $paid);
                          return $loan;
                      });
        } catch (\Exception $e) {
            Log::warning('Active loans failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get expenses grouped by category
     */
    private function getExpensesByCategory($fromDate): array
    {
        try {
            $result = ['labels' => [], 'amounts' => []];

            if (!Schema::hasTable('categories')) {
                return $result;
            }

            $data = Expense::join('categories', 'categories.id', '=', 'expenses.category_id')
                          ->where('expenses.date', '>=', $fromDate)
                          ->groupBy('categories.id', 'categories.name')
                          ->orderByDesc(DB::raw('SUM(expenses.amount)'))
                          ->select([
                              'categories.name',
                              DB::raw('SUM(expenses.amount) as total')
                          ])
                          ->get();

            $result['labels'] = $data->pluck('name')->toArray();
            $result['amounts'] = $data->pluck('total')->toArray();

            // Add uncategorized expenses
            $uncategorized = Expense::whereNull('category_id')
                                   ->where('date', '>=', $fromDate)
                                   ->sum('amount');

            if ($uncategorized > 0) {
                $result['labels'][] = 'Uncategorized';
                $result['amounts'][] = $uncategorized;
            }

            return $result;
        } catch (\Exception $e) {
            Log::warning('Expense category data failed: ' . $e->getMessage());
            return ['labels' => [], 'amounts' => []];
        }
    }

    // ========== API ENDPOINTS ==========

    public function salesChartData()
    {
        [$labels, $sales] = $this->getSalesChartData(30);

        return response()->json([
            'labels' => $labels,
            'sales' => $sales,
        ]);
    }

    public function cashflowChartData()
    {
        try {
            $start = now()->subDays(30)->startOfDay();
            $end = now()->endOfDay();

            // Generate all dates
            $labels = collect();
            for ($d = $start->copy(); $d <= $end; $d->addDay()) {
                $labels->push($d->copy());
            }

            // Get aggregated data
            $credits = DebitCredit::select(
                            DB::raw('DATE(created_at) as d'),
                            DB::raw('SUM(amount) as t')
                        )
                        ->where('type', 'credit')
                        ->whereBetween('created_at', [$start, $end])
                        ->groupBy(DB::raw('DATE(created_at)'))
                        ->pluck('t', 'd');

            $debits = DebitCredit::select(
                            DB::raw('DATE(created_at) as d'),
                            DB::raw('SUM(amount) as t')
                        )
                        ->where('type', 'debit')
                        ->whereBetween('created_at', [$start, $end])
                        ->groupBy(DB::raw('DATE(created_at)'))
                        ->pluck('t', 'd');

            return response()->json([
                'labels' => $labels->map(fn($d) => $d->format('M d')),
                'inflow' => $labels->map(fn($d) => (float) ($credits[$d->toDateString()] ?? 0)),
                'outflow' => $labels->map(fn($d) => (float) ($debits[$d->toDateString()] ?? 0)),
            ]);
        } catch (\Exception $e) {
            Log::error('Cashflow chart failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load cashflow data'], 500);
        }
    }
}
