<x-app-layout>
    {{-- 🔹 Header --}}
    <x-slot name="header">
        <div class="bg-indigo-600 text-white px-4 py-3 rounded-md shadow flex flex-col md:flex-row md:justify-between md:items-center gap-2">
            <h2 class="font-semibold text-xl leading-tight flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-white"></i>
                Reports & Insights
            </h2>
            <span class="text-sm opacity-90">Updated {{ now()->format('d M Y H:i') }}</span>
        </div>
    </x-slot>

    @php
        // Safe fallbacks
        $start  = $start  ?? now()->subMonth()->toDateString();
        $end    = $end    ?? now()->toDateString();
        $q      = $q      ?? '';

        $totalSales       = $totalSales       ?? '0.00';
        $totalProfit      = $totalProfit      ?? '0.00';
        $pendingBalances  = $pendingBalances  ?? '0.00';
        $totalPurchases   = $totalPurchases   ?? '0.00';
        $credits          = $credits          ?? '0.00';
        $debits           = $debits           ?? '0.00';
        $netBalance       = $netBalance       ?? '0.00';
        $loansGiven       = $loansGiven       ?? '0.00';
        $loansTaken       = $loansTaken       ?? '0.00';

        $revenueGrowth    = $revenueGrowth    ?? 0;
        $profitMargin     = $profitMargin     ?? 0;
        $expenseRatio     = $expenseRatio     ?? 0;

        $months           = $months           ?? [];
        $monthlySales     = $monthlySales     ?? [];

        $revenueExpensesChart = $revenueExpensesChart ?? collect();
        $arBalance        = $arBalance        ?? 0;
        $apBalance        = $apBalance        ?? 0;
        $inventoryUnits   = $inventoryUnits   ?? 0;
        $inventoryValue   = $inventoryValue   ?? 0;

        $topProducts      = $topProducts      ?? collect();
        $topCustomers     = $topCustomers     ?? collect();

        // P&L
        $plRevenue        = $plRevenue        ?? 0;
        $plCogs           = $plCogs           ?? 0;
        $plGrossProfit    = $plGrossProfit    ?? 0;
        $plOtherExpenses  = $plOtherExpenses  ?? 0;
        $plNetProfit      = $plNetProfit      ?? 0;

        $plNetColor = $plNetProfit >= 0 ? 'green' : 'red';
    @endphp

    @can('reports.view')
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        {{-- 🔸 Filters --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 flex items-center gap-2">
                <i data-lucide="filter" class="w-5 h-5"></i> Filter Reports
            </h3>

            <form method="GET" action="{{ route('reports.index') }}"
                  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div class="md:col-span-1">
                    <label for="start_date" class="form-label">Start</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $start }}" class="form-input w-full">
                </div>

                <div class="md:col-span-1">
                    <label for="end_date" class="form-label">End</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $end }}" class="form-input w-full">
                </div>

                <div class="md:col-span-2">
                    <label for="q" class="form-label">Search (customer, product, notes)</label>
                    <input type="text" id="q" name="q" value="{{ $q }}" placeholder="Type to search…" class="form-input w-full">
                </div>

                <div class="md:col-span-1 flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1 flex items-center justify-center gap-1">
                        <i data-lucide="check-square" class="w-4 h-4"></i> Apply
                    </button>
                </div>

                {{-- Quick ranges --}}
                <div class="md:col-span-5 flex flex-wrap gap-2 mt-2">
                    @php
                        $today  = \Carbon\Carbon::today()->toDateString();
                        $last7  = \Carbon\Carbon::today()->subDays(6)->toDateString();
                        $last30 = \Carbon\Carbon::today()->subDays(29)->toDateString();
                        $monthS = \Carbon\Carbon::today()->startOfMonth()->toDateString();
                    @endphp
                    <a class="btn btn-outline btn-sm"
                       href="{{ route('reports.index', array_merge(request()->query(), ['start_date'=>$today,'end_date'=>$today])) }}">Today</a>
                    <a class="btn btn-outline btn-sm"
                       href="{{ route('reports.index', array_merge(request()->query(), ['start_date'=>$last7,'end_date'=>$today])) }}">Last 7d</a>
                    <a class="btn btn-outline btn-sm"
                       href="{{ route('reports.index', array_merge(request()->query(), ['start_date'=>$last30,'end_date'=>$today])) }}">Last 30d</a>
                    <a class="btn btn-outline btn-sm"
                       href="{{ route('reports.index', array_merge(request()->query(), ['start_date'=>$monthS,'end_date'=>$today])) }}">This Month</a>
                </div>
            </form>
        </section>

        {{-- 🔸 Summary Cards (Date-range) --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">
                Summary ({{ $start }} → {{ $end }})
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-stat-card title="Total Sales" :value="$totalSales" color="blue" />
                <x-stat-card title="Total Profit" :value="$totalProfit" color="green" />
                <x-stat-card title="Pending Balances" :value="$pendingBalances" color="red" />
                <x-stat-card title="Total Purchases" :value="$totalPurchases" color="indigo" />
                <x-stat-card title="Credits (In)" :value="$credits" color="green" />
                <x-stat-card title="Debits (Out)" :value="$debits" color="red" />
                <x-stat-card
                    title="Net Balance"
                    :value="$netBalance"
                    color="{{ ((float)str_replace(',', '', $netBalance)) >= 0 ? 'green' : 'red' }}" />
                <x-stat-card title="Loans Given" :value="$loansGiven" color="blue" />
                <x-stat-card title="Loans Taken" :value="$loansTaken" color="amber" />
            </div>
        </section>

        {{-- 🔸 Profit & Loss (Date-range) --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 flex items-center gap-2">
                <i data-lucide="line-chart" class="w-5 h-5"></i> Profit &amp; Loss
            </h3>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <x-stat-card title="Revenue" :value="'RWF '.number_format($plRevenue,2)" color="blue" />
                    <x-stat-card title="COGS" :value="'RWF '.number_format($plCogs,2)" color="amber" />
                    <x-stat-card title="Gross Profit" :value="'RWF '.number_format($plGrossProfit,2)" color="green" />
                    <x-stat-card title="Other Expenses" :value="'RWF '.number_format($plOtherExpenses,2)" color="red" />
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-xl font-semibold">
                        Net Profit:
                        <span class="{{ $plNetProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            RWF {{ number_format($plNetProfit, 2) }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('reports.export.pl.pdf', array_merge(request()->query(), ['start_date'=>$start,'end_date'=>$end])) }}"
                           class="btn btn-primary flex items-center gap-2">
                            <i data-lucide="file-down" class="w-4 h-4"></i> Export P&L (PDF)
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- 🔸 Balances & Inventory (Live snapshot) --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Balances & Inventory (Live)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card
                    title="Accounts Receivable (AR)"
                    :value="'RWF ' . number_format($arBalance, 2)"
                    color="{{ $arBalance > 0 ? 'amber' : 'green' }}" />

                <x-stat-card
                    title="Accounts Payable (AP)"
                    :value="'RWF ' . number_format($apBalance, 2)"
                    color="{{ $apBalance > 0 ? 'red' : 'green' }}" />

                <x-stat-card
                    title="Inventory Units"
                    :value="number_format($inventoryUnits, 0)"
                    color="blue" />

                <x-stat-card
                    title="Inventory Value @ cost"
                    :value="'RWF ' . number_format($inventoryValue, 2)"
                    color="purple" />
            </div>
        </section>

        {{-- 🔸 Export Buttons --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 flex items-center gap-2">
                <i data-lucide="download" class="w-5 h-5"></i> Export Reports
            </h3>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 flex flex-wrap gap-3">
                <a href="{{ route('reports.export.sales.csv', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
                   class="btn btn-success flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Sales Report (CSV)
                </a>
                <a href="{{ route('reports.export.finance.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
                   class="btn btn-primary flex items-center gap-2">
                    <i data-lucide="file-down" class="w-4 h-4"></i> Finance Report (PDF)
                </a>
                <a href="{{ route('reports.export.insights.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
                   class="btn btn-purple flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Insights Report (PDF)
                </a>
            </div>
        </section>

        {{-- 🔸 Business Insights --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Business Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-metric-card
                    label="Revenue Growth (vs Last Month)"
                    :value="number_format($revenueGrowth, 1) . '%'"
                    :color="$revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600'" />
                <x-metric-card
                    label="Profit Margin"
                    :value="number_format($profitMargin, 1) . '%'"
                    color="text-green-600" />
                <x-metric-card
                    label="Expense Ratio"
                    :value="number_format($expenseRatio, 1) . '%'"
                    color="text-indigo-600" />
            </div>
        </section>

        {{-- 🔸 Revenue vs Expenses Chart --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">
                Revenue vs Expenses (Last 6 Months)
            </h3>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
                <canvas id="revenueExpenseChart" height="120"></canvas>
            </div>
        </section>

        {{-- 🔸 Top Performers & Monthly Sales Trend --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Insights & Analytics</h3>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <x-top-table
                    title="Top 5 Products"
                    :headers="['Product', 'Total Sales']"
                    :items="$topProducts"
                    :columns="['product.name', 'total_sales']"
                    color="text-indigo-600" />

                <x-top-table
                    title="Top 5 Customers"
                    :headers="['Customer', 'Total Spent']"
                    :items="$topCustomers"
                    :columns="['customer.name', 'total_spent']"
                    color="text-green-600" />
            </div>

            <div class="mt-10 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
                <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    Monthly Sales Trend (Last 6 Months)
                </h4>
                <canvas id="monthlySalesChart" height="120"></canvas>
            </div>
        </section>
    </div>
    @else
        <div class="py-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-amber-50 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-700 rounded-xl p-6 text-sm text-amber-800 dark:text-amber-100">
                You do not have permission to view reports. Please contact your administrator if you believe this is a mistake.
            </div>
        </div>
    @endcan

    {{-- 🔸 Chart.js & Icons --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            // ---- Monthly Sales Chart ----
            const monthlyLabels = @json($months);
            const monthlyData   = @json($monthlySales);

            const monthlyCtx = document.getElementById('monthlySalesChart')?.getContext('2d');
            if (monthlyCtx && monthlyLabels.length) {
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Total Sales',
                            data: monthlyData,
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79,70,229,0.08)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: '#4F46E5'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // ---- Revenue vs Expenses Chart ----
            const reMonths   = @json($revenueExpensesChart->pluck('month'));
            const reSales    = @json($revenueExpensesChart->pluck('sales'));
            const reExpenses = @json($revenueExpensesChart->pluck('expenses'));

            const reCtx = document.getElementById('revenueExpenseChart')?.getContext('2d');
            if (reCtx && reMonths.length) {
                new Chart(reCtx, {
                    type: 'line',
                    data: {
                        labels: reMonths,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: reSales,
                                borderColor: '#22C55E',
                                backgroundColor: 'rgba(34,197,94,0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 3
                            },
                            {
                                label: 'Expenses',
                                data: reExpenses,
                                borderColor: '#EF4444',
                                backgroundColor: 'rgba(239,68,68,0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 3
                            },
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        });
    </script>
</x-app-layout>
