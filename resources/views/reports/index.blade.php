<x-app-layout>
    {{-- 🔹 Header --}}
    <x-slot name="header">
        <div class="bg-indigo-600 text-white px-4 py-3 rounded-md shadow flex justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-white"></i>
                Reports & Insights
            </h2>
            <span class="text-sm opacity-90">Updated {{ now()->format('d M Y H:i') }}</span>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        {{-- 🔸 Filters --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 flex items-center gap-2">
                <i data-lucide="filter" class="w-5 h-5"></i> Filter Reports
            </h3>

            <form method="GET" action="{{ route('reports.index') }}"
                  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 flex flex-col md:flex-row md:items-end gap-4">

                <div class="flex-1">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $start }}" class="form-input w-full">
                </div>

                <div class="flex-1">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $end }}" class="form-input w-full">
                </div>

                <button type="submit" class="btn btn-primary flex items-center gap-1 self-start md:self-end">
                    <i data-lucide="check-square" class="w-4 h-4"></i> Apply Filters
                </button>
            </form>
        </section>

        {{-- 🔸 Summary Cards --}}
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
                <x-stat-card title="Net Balance"
                    :value="$netBalance"
                    color="{{ $netBalance >= 0 ? 'green' : 'red' }}" />
                <x-stat-card title="Loans Given" :value="$loansGiven" color="blue" />
                <x-stat-card title="Loans Taken" :value="$loansTaken" color="amber" />
            </div>
        </section>

        {{-- 🔸 Export Buttons --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 flex items-center gap-2">
                <i data-lucide="download" class="w-5 h-5"></i> Export Reports
            </h3>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 flex flex-wrap gap-3">
                <a href="{{ route('reports.export.sales.csv', ['start_date' => $start, 'end_date' => $end]) }}"
                   class="btn btn-success flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Sales Report (CSV)
                </a>
                <a href="{{ route('reports.export.finance.pdf', ['start_date' => $start, 'end_date' => $end]) }}"
                   class="btn btn-primary flex items-center gap-2">
                    <i data-lucide="file-down" class="w-4 h-4"></i> Finance Report (PDF)
                </a>
                <a href="{{ route('reports.export.insights.pdf', ['start_date' => $start, 'end_date' => $end]) }}"
                   class="btn btn-purple flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Insights Report (PDF)
                </a>
            </div>
        </section>

        {{-- 🔸 Business Insights --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Business Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-metric-card label="Revenue Growth (vs Last Month)"
                               :value="number_format($revenueGrowth, 1) . '%'"
                               :color="$revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600'" />
                <x-metric-card label="Profit Margin"
                               :value="number_format($profitMargin, 1) . '%'"
                               color="text-green-600" />
                <x-metric-card label="Expense Ratio"
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

        {{-- 🔸 Top Performers --}}
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4">Insights & Analytics</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <x-top-table title="Top 5 Products"
                             :headers="['Product', 'Total Sales']"
                             :items="$topProducts"
                             :columns="['product.name', 'total_sales']"
                             color="text-indigo-600" />

                <x-top-table title="Top 5 Customers"
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

    {{-- 🔸 Chart.js Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            // Monthly Sales Chart
            new Chart(document.getElementById('monthlySalesChart'), {
                type: 'line',
                data: {
                    labels: @json($months),
                    datasets: [{
                        label: 'Total Sales',
                        data: @json($monthlySales),
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

            // Revenue vs Expenses Chart
            new Chart(document.getElementById('revenueExpenseChart'), {
                type: 'line',
                data: {
                    labels: @json($revenueExpensesChart->pluck('month')),
                    datasets: [
                        {
                            label: 'Revenue',
                            data: @json($revenueExpensesChart->pluck('sales')),
                            borderColor: '#22C55E',
                            backgroundColor: 'rgba(34,197,94,0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2,
                            pointRadius: 3
                        },
                        {
                            label: 'Expenses',
                            data: @json($revenueExpensesChart->pluck('expenses')),
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
        });
    </script>
</x-app-layout>
