<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        @php
            $roleColors = [
                'admin'     => 'bg-indigo-600',
                'manager'   => 'bg-green-600',
                'accountant'=> 'bg-sky-600',
                'cashier'   => 'bg-yellow-500',
            ];
            $titles = [
                'admin'     => 'Business Dashboard',
                'manager'   => 'Management Dashboard',
                'accountant'=> 'Finance Dashboard',
                'cashier'   => 'Daily Sales Dashboard',
            ];
            $color = $roleColors[$role] ?? 'bg-gray-600';
            $title = $titles[$role] ?? 'Dashboard';
        @endphp

        <div class="{{ $color }} text-white px-4 py-3 rounded-md shadow flex justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight flex items-center gap-2">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>{{ $title }}</span>
            </h2>
            <span class="text-sm opacity-90">Updated {{ now()->format('d M Y H:i') }}</span>
        </div>

        {{-- Quick Actions --}}
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('sales.create') }}" class="btn btn-primary flex items-center gap-1.5 shadow-sm hover:shadow transition-shadow">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                <span>New Sale</span>
            </a>

            @if(in_array($role, ['admin','manager','accountant']))
                <a href="{{ route('purchases.create') }}" class="btn btn-secondary flex items-center gap-1.5 shadow-sm hover:shadow transition-shadow">
                    <i data-lucide="package-plus" class="w-4 h-4"></i>
                    <span>New Purchase</span>
                </a>
                <a href="{{ route('loans.create') }}" class="btn btn-success flex items-center gap-1.5 shadow-sm hover:shadow transition-shadow">
                    <i data-lucide="hand-coins" class="w-4 h-4"></i>
                    <span>Add Loan</span>
                </a>
                <a href="{{ route('expenses.create') }}" class="btn btn-warning flex items-center gap-1.5 shadow-sm hover:shadow transition-shadow">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                    <span>Add Expense</span>
                </a>
                <a href="{{ route('transactions.create') }}" class="btn btn-outline flex items-center gap-1.5 shadow-sm hover:shadow transition-shadow">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                    <span>Add Transaction</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

        {{-- Error State --}}
        @if(isset($error))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5"></i>
                    <div>
                        <h4 class="font-semibold text-red-800 dark:text-red-300">Dashboard Loading Error</h4>
                        <p class="text-red-700 dark:text-red-400 text-sm mt-1">{{ $error }}</p>
                        <p class="text-red-600 dark:text-red-500 text-xs mt-2">
                            Try running: <code class="bg-red-100 dark:bg-red-900/40 px-1.5 py-0.5 rounded">php artisan dashboard:debug --seed</code>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Empty State Check --}}
        @php
            $hasData = ($totalSales ?? 0) > 0 || ($totalPurchases ?? 0) > 0 || ($totalExpenses ?? 0) > 0;
        @endphp

        @if(!$hasData && !isset($error))
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-8 text-center border border-indigo-100 dark:border-gray-700">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-full mb-4">
                    <i data-lucide="inbox" class="w-8 h-8 text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Welcome to Your Dashboard!</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    Get started by creating your first sale, purchase, or expense. Your business insights will appear here.
                </p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ route('sales.create') }}" class="btn btn-primary">
                        <i data-lucide="shopping-cart" class="w-4 h-4 mr-1.5"></i>
                        Create First Sale
                    </a>
                    @if(in_array($role, ['admin','manager','accountant']))
                        <a href="{{ route('expenses.create') }}" class="btn btn-secondary">
                            <i data-lucide="wallet" class="w-4 h-4 mr-1.5"></i>
                            Add Expense
                        </a>
                    @endif
                </div>
                @if(app()->environment('local'))
                    <div class="mt-6 pt-6 border-t border-indigo-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Development Mode: Need sample data?</p>
                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded">php artisan dashboard:debug --seed</code>
                    </div>
                @endif
            </div>
        @endif

        {{-- KPIs --}}
        @if(($sections['kpis'] ?? false) && $hasData)
        <section>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                    <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
                    Key Performance Indicators
                </h3>
                <button onclick="refreshKPIs()" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Refresh
                </button>
            </div>

            <div id="kpi-section" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 opacity-0 transition-opacity duration-500">
                <x-stat label="Total Sales" :value="$totalSales" color="text-indigo-600" icon="dollar-sign" />
                <x-stat label="Total Profit" :value="$totalProfit" color="text-green-600" icon="trending-up" />
                <x-stat label="Pending Balances" :value="$pendingBalances" color="text-red-600" icon="alert-circle" />
                <x-stat label="Total Purchases" :value="$totalPurchases" color="text-pink-600" icon="shopping-bag" />
                <x-stat label="Total Expenses" :value="$totalExpenses" color="text-amber-600" icon="wallet" />
                @if($sections['finance'] ?? false)
                    <x-stat label="Net Balance" :value="$netBalance" :color="$netBalance >= 0 ? 'text-green-600' : 'text-red-600'" icon="scale" />
                @endif
            </div>

            {{-- Mini Summary --}}
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Today's Sales</p>
                        <i data-lucide="calendar-days" class="w-4 h-4 text-gray-400"></i>
                    </div>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400 text-lg">
                        RWF {{ number_format($todaySales, 0) }}
                    </p>
                    <span class="inline-flex items-center mt-1 text-xs {{ $salesChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        <i data-lucide="{{ $salesChange >= 0 ? 'trending-up' : 'trending-down' }}" class="w-3 h-3 mr-1"></i>
                        {{ $salesChange >= 0 ? '+' : '' }}{{ number_format($salesChange, 1) }}% vs yesterday
                    </span>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-500 dark:text-gray-400 font-medium">This Week</p>
                        <i data-lucide="calendar-range" class="w-4 h-4 text-gray-400"></i>
                    </div>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400 text-lg">
                        RWF {{ number_format($weekSales, 0) }}
                    </p>
                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">Last 7 days</span>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-500 dark:text-gray-400 font-medium">This Month</p>
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                    </div>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400 text-lg">
                        RWF {{ number_format($monthSales, 0) }}
                    </p>
                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">{{ now()->format('F Y') }}</span>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Monthly Expenses</p>
                        <i data-lucide="receipt" class="w-4 h-4 text-gray-400"></i>
                    </div>
                    <p class="font-semibold text-amber-600 text-lg">
                        RWF {{ number_format($monthExpenses, 0) }}
                    </p>
                    @php
                        $profitMargin = $monthSales > 0 ? (($monthSales - $monthExpenses) / $monthSales) * 100 : 0;
                    @endphp
                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                        {{ number_format($profitMargin, 1) }}% profit margin
                    </span>
                </div>
            </div>
        </section>
        @endif

        {{-- Charts: Trend + Daily + Cashflow --}}
        @if(($sections['charts'] ?? false) && $hasData)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400 flex items-center gap-2">
                <i data-lucide="line-chart" class="w-5 h-5"></i>
                Performance & Cashflow
            </h3>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-10 relative overflow-hidden">
                <div id="chart-loader" class="absolute inset-0 bg-gray-100 dark:bg-gray-800 flex items-center justify-center z-10">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-2"></div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Loading charts...</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-indigo-600"></i>
                        6-Month Trend (Sales vs Purchases)
                    </h4>
                    <canvas id="trendChart" height="100"></canvas>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-600"></i>
                            Daily Sales (Last 30 Days)
                        </h4>
                        <canvas id="salesChart" height="140"></canvas>
                    </div>
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i data-lucide="wallet" class="w-4 h-4 text-emerald-600"></i>
                            Cashflow (Last 30 Days)
                        </h4>
                        <canvas id="cashflowChart" height="140"></canvas>
                    </div>
                </div>
            </div>
        </section>
        @endif

        {{-- Expenses Overview --}}
        @if(($sections['expenses'] ?? false) && ($totalExpenses > 0 || count($recentExpenses) > 0))
        <section class="space-y-4">
            <h3 class="text-lg font-semibold text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                <i data-lucide="receipt" class="w-5 h-5"></i>
                Expenses Overview
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <x-stat label="Total Expenses" :value="$totalExpenses" color="text-rose-600" icon="coins" />
                <x-stat label="Today" :value="$todayExpenses" color="text-rose-600" icon="calendar" />
                <x-stat label="This Week" :value="$weekExpenses" color="text-rose-600" icon="calendar-range" />
                <x-stat label="This Month" :value="$monthExpenses" color="text-rose-600" icon="calendar-days" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 lg:col-span-2">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-rose-600"></i>
                        By Category (This Month)
                    </h4>
                    @if(!empty($expenseByCategory['labels']) && count($expenseByCategory['labels']) > 0)
                        <canvas id="expensesCategoryChart" height="120"></canvas>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <i data-lucide="inbox" class="w-12 h-12 mb-2"></i>
                            <p class="text-sm">No categorized expenses this month</p>
                        </div>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300">Recent Expenses</h4>
                        <a href="{{ route('expenses.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium">Category</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($recentExpenses as $e)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-3 py-2 text-xs">{{ optional($e->date)->format('M d') ?? '—' }}</td>
                                        <td class="px-3 py-2 text-xs">{{ $e->category->name ?? 'Uncategorized' }}</td>
                                        <td class="px-3 py-2 text-right font-medium text-rose-600">RWF {{ number_format($e->amount, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-3 py-6 text-center text-gray-400 text-xs">No expenses yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
        @endif

        {{-- Loans Overview --}}
        @if(($sections['loans'] ?? false) && ($totalLoansGiven > 0 || $totalLoansTaken > 0 || $activeLoans > 0))
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400 flex items-center gap-2">
                <i data-lucide="hand-coins" class="w-5 h-5"></i>
                Loans Overview
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="rounded-xl ring-1 ring-emerald-200 dark:ring-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300 font-medium">Loans Given</p>
                        <i data-lucide="arrow-up-right" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">RWF {{ number_format($totalLoansGiven, 0) }}</p>
                </div>
                <div class="rounded-xl ring-1 ring-rose-200 dark:ring-rose-800 bg-rose-50 dark:bg-rose-900/20 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-300 font-medium">Loans Taken</p>
                        <i data-lucide="arrow-down-right" class="w-4 h-4 text-rose-600"></i>
                    </div>
                    <p class="text-2xl font-bold text-rose-700 dark:text-rose-300">RWF {{ number_format($totalLoansTaken, 0) }}</p>
                </div>
                <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400 font-medium">Loan Status</p>
                        <i data-lucide="list-checks" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $activeLoans }} Active</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-red-600 dark:text-red-400 font-semibold">{{ $overdueLoans }} Overdue</span>
                        <span class="text-gray-300">|</span>
                        <span class="text-green-600 dark:text-green-400 font-semibold">{{ $paidLoans }} Paid</span>
                    </div>
                </div>
            </div>

            @if($activeLoansList && count($activeLoansList) > 0)
            <div class="mt-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                        Active Loans
                    </h4>
                    <a href="{{ route('loans.index') }}" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline flex items-center gap-1">
                        View all
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium">Party</th>
                                <th class="px-4 py-3 text-left text-xs font-medium">Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium">Paid</th>
                                <th class="px-4 py-3 text-right text-xs font-medium">Remaining</th>
                                <th class="px-4 py-3 text-left text-xs font-medium">Due Date</th>
                                <th class="px-4 py-3 text-right text-xs font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($activeLoansList as $L)
                                @php
                                    $party = $L->type === 'given' ? optional($L->customer)->name : optional($L->supplier)->name;
                                    $isOverdue = $L->due_date && $L->due_date->isPast();
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3 font-medium">#{{ $L->id }}</td>
                                    <td class="px-4 py-3">{{ $party ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $L->type === 'given' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ ucfirst($L->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">RWF {{ number_format($L->amount, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-600 font-medium">RWF {{ number_format($L->paid_sum ?? 0, 0) }}</td>
                                    <td class="px-4 py-3 text-right {{ $L->remaining > 0 ? 'text-rose-600' : 'text-emerald-600' }} font-medium">
                                        RWF {{ number_format($L->remaining ?? 0, 0) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($L->due_date)
                                            <span class="inline-flex items-center {{ $isOverdue ? 'text-red-600' : 'text-gray-600' }}">
                                                @if($isOverdue)
                                                    <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i>
                                                @endif
                                                {{ $L->due_date->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <a href="{{ route('loans.show', $L) }}" class="text-indigo-600 hover:underline text-xs">View</a>
                                        @if($L->status !== 'paid')
                                            <span class="text-gray-300">|</span>
                                            <a href="{{ route('loan-payments.create', $L) }}" class="text-emerald-600 hover:underline text-xs">Pay</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>
        @endif

        {{-- Insights: Top Products & Customers --}}
        @if(($sections['insights'] ?? false) && ($topProducts->count() > 0 || $topCustomers->count() > 0))
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400 flex items-center gap-2">
                <i data-lucide="lightbulb" class="w-5 h-5"></i>
                Advanced Insights
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Top Products --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 space-y-4">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <i data-lucide="trophy" class="w-5 h-5 text-yellow-500"></i>
                        Top 5 Selling Products
                    </h4>
                    @if($topProducts->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300">
                                    <tr>
                                        <th class="px-4 py-2 text-xs font-medium">Product</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium">Qty Sold</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($topProducts as $p)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-4 py-3">{{ $p->product->name ?? 'Unknown' }}</td>
                                            <td class="px-4 py-3 text-right font-medium">{{ number_format($p->total_qty, 0) }}</td>
                                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400 font-semibold">
                                                RWF {{ number_format($p->total_revenue, 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <canvas id="topProductsChart" height="140"></canvas>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <i data-lucide="package" class="w-12 h-12 mb-2"></i>
                            <p class="text-sm">No sales data yet</p>
                        </div>
                    @endif
                </div>

                {{-- Top Customers --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 space-y-4">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        Top 5 Customers
                    </h4>
                    @if($topCustomers->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300">
                                    <tr>
                                        <th class="px-4 py-2 text-xs font-medium">Customer</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($topCustomers as $c)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-4 py-3">{{ $c->customer->name ?? 'Unknown' }}</td>
                                            <td class="px-4 py-3 text-right text-indigo-600 dark:text-indigo-400 font-semibold">
                                                RWF {{ number_format($c->total_spent, 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <canvas id="topCustomersChart" height="140"></canvas>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <i data-lucide="users" class="w-12 h-12 mb-2"></i>
                            <p class="text-sm">No customer data yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- Recent Transactions --}}
        @if(($sections['recentTransactions'] ?? false) && isset($recentTransactions) && count($recentTransactions) > 0)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-3 dark:text-indigo-400 flex items-center gap-2">
                <i data-lucide="activity" class="w-5 h-5"></i>
                Recent Ledger Transactions
            </h3>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium">Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($recentTransactions as $t)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3">{{ optional($t->transaction_date ?? $t->created_at)->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $t->type === 'credit' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ ucfirst($t->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right {{ $t->type === 'credit' ? 'text-emerald-700' : 'text-rose-600' }} font-semibold">
                                        RWF {{ number_format($t->amount, 0) }}
                                    </td>
                                    <td class="px-4 py-3">{{ optional($t->user)->name ?? 'System' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $t->reference ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        @endif

        {{-- Cashier Daily View --}}
        @if(($sections['cashierDaily'] ?? false) && isset($myTodaySalesTotal))
        <section>
            <h3 class="text-lg font-semibold text-yellow-600 mb-4 dark:text-yellow-400 flex items-center gap-2">
                <i data-lucide="user-circle" class="w-5 h-5"></i>
                My Daily Performance
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-5 border border-yellow-200 dark:border-yellow-800">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Today's Sales</p>
                        <i data-lucide="trending-up" class="w-5 h-5 text-yellow-600"></i>
                    </div>
                    <p class="text-3xl font-bold text-yellow-900 dark:text-yellow-100">RWF {{ number_format($myTodaySalesTotal, 0) }}</p>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Transactions</p>
                        <i data-lucide="receipt" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ $myTodaySalesCount }}</p>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-5 border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-green-700 dark:text-green-300">Avg per Sale</p>
                        <i data-lucide="dollar-sign" class="w-5 h-5 text-green-600"></i>
                    </div>
                    <p class="text-3xl font-bold text-green-900 dark:text-green-100">
                        RWF {{ $myTodaySalesCount > 0 ? number_format($myTodaySalesTotal / $myTodaySalesCount, 0) : 0 }}
                    </p>
                </div>
            </div>

            @if(isset($myLatestSales) && count($myLatestSales) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700">
                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    My Recent Sales
                </h4>
                <div class="space-y-2">
                    @foreach($myLatestSales as $sale)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                    <i data-lucide="shopping-cart" class="w-5 h-5 text-indigo-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $sale->customer->name ?? 'Walk-in Customer' }}</p>
                                    <p class="text-xs text-gray-500">{{ $sale->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-indigo-600 dark:text-indigo-400">RWF {{ number_format($sale->total_amount, 0) }}</p>
                                <span class="text-xs px-2 py-0.5 rounded {{ $sale->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </section>
        @endif

    </div>

    {{-- Charts JavaScript --}}
    @if(($sections['charts'] ?? false) || ($sections['insights'] ?? false) || ($sections['expenses'] ?? false))
        <script src="https://unpkg.com/lucide@latest"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Initialize Lucide icons
                if (window.lucide) lucide.createIcons();

                // Fade in KPIs
                const kpiSection = document.getElementById('kpi-section');
                setTimeout(() => { if(kpiSection) kpiSection.style.opacity = 1; }, 200);

                // Remove chart loader
                const loader = document.getElementById('chart-loader');
                setTimeout(() => loader?.remove(), 600);

                // Chart default options
                Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#4B5563';
                Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB';

                // ========== TREND CHART ==========
                const ctxTrend = document.getElementById('trendChart')?.getContext('2d');
                if (ctxTrend) {
                    new Chart(ctxTrend, {
                        type: 'line',
                        data: {
                            labels: @json($months ?? []),
                            datasets: [
                                {
                                    label: 'Sales',
                                    data: @json($salesTrend ?? []),
                                    borderColor: '#4F46E5',
                                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'Purchases',
                                    data: @json($purchaseTrend ?? []),
                                    borderColor: '#EC4899',
                                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': RWF ' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'RWF ' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        },
                    });
                }

                // ========== DAILY SALES CHART ==========
                fetch('{{ route('dashboard.sales.chart') }}')
                    .then(res => res.json())
                    .then(data => {
                        const ctxSales = document.getElementById('salesChart')?.getContext('2d');
                        if (ctxSales) {
                            new Chart(ctxSales, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: 'Daily Sales',
                                        data: data.sales,
                                        backgroundColor: 'rgba(79, 70, 229, 0.6)',
                                        borderColor: '#4F46E5',
                                        borderWidth: 1,
                                        borderRadius: 6,
                                        hoverBackgroundColor: 'rgba(79, 70, 229, 0.8)',
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return 'RWF ' + context.parsed.y.toLocaleString();
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value) {
                                                    return 'RWF ' + value.toLocaleString();
                                                }
                                            }
                                        }
                                    }
                                },
                            });
                        }
                    })
                    .catch(err => console.error('Failed to load sales chart:', err));

                // ========== CASHFLOW CHART ==========
                fetch('{{ route('dashboard.cashflow.chart') }}')
                    .then(res => res.json())
                    .then(data => {
                        const ctx = document.getElementById('cashflowChart')?.getContext('2d');
                        if (ctx) {
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [
                                        {
                                            label: 'Inflow',
                                            data: data.inflow,
                                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                                            borderColor: '#10B981',
                                            borderWidth: 1,
                                            borderRadius: 6,
                                        },
                                        {
                                            label: 'Outflow',
                                            data: data.outflow,
                                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                                            borderColor: '#EF4444',
                                            borderWidth: 1,
                                            borderRadius: 6,
                                        },
                                    ],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'top',
                                        },
                                        tooltip: {
                                            mode: 'index',
                                            intersect: false,
                                            callbacks: {
                                                label: function(context) {
                                                    return context.dataset.label + ': RWF ' + context.parsed.y.toLocaleString();
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: { stacked: true },
                                        y: {
                                            stacked: true,
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value) {
                                                    return 'RWF ' + value.toLocaleString();
                                                }
                                            }
                                        }
                                    }
                                },
                            });
                        }
                    })
                    .catch(err => console.error('Failed to load cashflow chart:', err));

                // ========== TOP PRODUCTS CHART ==========
                const productsCtx = document.getElementById('topProductsChart')?.getContext('2d');
                if (productsCtx) {
                    const productData = @json($topProducts ?? collect());
                    if (productData.length > 0) {
                        new Chart(productsCtx, {
                            type: 'bar',
                            data: {
                                labels: productData.map(p => p.product?.name || 'Unknown'),
                                datasets: [{
                                    label: 'Qty Sold',
                                    data: productData.map(p => p.total_qty),
                                    backgroundColor: '#4F46E5',
                                    borderRadius: 6,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                indexAxis: 'y',
                                plugins: {
                                    legend: { display: false },
                                },
                                scales: {
                                    x: { beginAtZero: true }
                                }
                            },
                        });
                    }
                }

                // ========== TOP CUSTOMERS CHART ==========
                const customersCtx = document.getElementById('topCustomersChart')?.getContext('2d');
                if (customersCtx) {
                    const customerData = @json($topCustomers ?? collect());
                    if (customerData.length > 0) {
                        new Chart(customersCtx, {
                            type: 'doughnut',
                            data: {
                                labels: customerData.map(c => c.customer?.name || 'Unknown'),
                                datasets: [{
                                    label: 'Total Spent',
                                    data: customerData.map(c => c.total_spent),
                                    backgroundColor: [
                                        '#4F46E5',
                                        '#6366F1',
                                        '#818CF8',
                                        '#A5B4FC',
                                        '#C7D2FE',
                                    ],
                                    borderWidth: 2,
                                    borderColor: document.documentElement.classList.contains('dark') ? '#1F2937' : '#FFFFFF',
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': RWF ' + context.parsed.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            },
                        });
                    }
                }

                // ========== EXPENSES BY CATEGORY CHART ==========
                const expCtx = document.getElementById('expensesCategoryChart')?.getContext('2d');
                if (expCtx) {
                    const expenseData = @json($expenseByCategory ?? ['labels' => [], 'amounts' => []]);
                    if (expenseData.labels.length > 0) {
                        new Chart(expCtx, {
                            type: 'doughnut',
                            data: {
                                labels: expenseData.labels,
                                datasets: [{
                                    data: expenseData.amounts,
                                    backgroundColor: [
                                        '#EF4444',
                                        '#F97316',
                                        '#F59E0B',
                                        '#EAB308',
                                        '#84CC16',
                                        '#22C55E',
                                        '#10B981',
                                        '#14B8A6',
                                    ],
                                    borderWidth: 2,
                                    borderColor: document.documentElement.classList.contains('dark') ? '#1F2937' : '#FFFFFF',
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': RWF ' + context.parsed.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            },
                        });
                    }
                }
            });

            // Refresh function
            function refreshKPIs() {
                location.reload();
            }
        </script>
    @endif
</x-app-layout>
