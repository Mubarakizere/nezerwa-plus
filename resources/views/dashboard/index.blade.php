<x-app-layout>
    {{-- Dashboard Header --}}
    <x-slot name="header">
        @php
            $roleColors = [
                'admin'   => 'bg-indigo-600',
                'manager' => 'bg-green-600',
                'cashier' => 'bg-yellow-500',
            ];
            $titles = [
                'admin'   => 'Business Dashboard',
                'manager' => 'Management Dashboard',
                'cashier' => 'Daily Sales Dashboard',
            ];
            $color = $roleColors[$role] ?? 'bg-gray-600';
            $title = $titles[$role] ?? 'Dashboard';
        @endphp

        <div class="{{ $color }} text-white px-4 py-3 rounded-md shadow flex justify-between items-center dark:shadow-none">
            <h2 class="font-semibold text-xl leading-tight flex items-center space-x-2">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>{{ $title }}</span>
            </h2>
            <span class="text-sm opacity-90">Updated {{ now()->format('d M Y H:i') }}</span>
        </div>

        {{-- Quick Actions --}}
        <div class="mt-4 flex flex-wrap gap-3">
            @if(in_array($role, ['admin','manager']))
                <a href="{{ route('sales.create') }}" class="btn btn-primary flex items-center space-x-1">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i><span>New Sale</span>
                </a>
                <a href="{{ route('purchases.create') }}" class="btn btn-secondary flex items-center space-x-1">
                    <i data-lucide="package-plus" class="w-4 h-4"></i><span>New Purchase</span>
                </a>
                <a href="{{ route('loans.create') }}" class="btn btn-success flex items-center space-x-1">
                    <i data-lucide="hand-coins" class="w-4 h-4"></i><span>Add Loan</span>
                </a>
                <a href="{{ route('transactions.create') }}" class="btn btn-outline flex items-center space-x-1">
                    <i data-lucide="activity" class="w-4 h-4"></i><span>Add Transaction</span>
                </a>
            @elseif($role === 'cashier')
                <a href="{{ route('sales.create') }}" class="btn btn-primary flex items-center space-x-1">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i><span>New Sale</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

        {{-- KPI Section --}}
        @if($sections['kpis'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400">Key Performance Indicators</h3>

            <div id="kpi-section" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 opacity-0 transition-opacity duration-500">
                <x-stat label="Total Sales" :value="$totalSales" color="text-indigo-600" icon="dollar-sign" />
                <x-stat label="Total Profit" :value="$totalProfit" color="text-green-600" icon="trending-up" />
                <x-stat label="Pending Balances" :value="$pendingBalances" color="text-red-600" icon="alert-circle" />
                <x-stat label="Total Purchases" :value="$totalPurchases" color="text-pink-600" icon="shopping-bag" />
                <x-stat label="Stock Value" :value="$totalStockValue" color="text-amber-600" icon="package" />
                @if($sections['finance'])
                    <x-stat label="Net Balance" :value="$netBalance" :color="$netBalance >= 0 ? 'text-green-600' : 'text-red-600'" icon="scale" />
                @endif
            </div>

            {{-- Mini Summary --}}
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm p-4">
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Today’s Sales</p>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400">
                        RWF {{ number_format($todaySales, 0) }}
                        <span class="ml-1 text-xs {{ $salesChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $salesChange >= 0 ? '+' : '' }}{{ number_format($salesChange, 1) }}%
                        </span>
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm p-4">
                    <p class="text-gray-500 dark:text-gray-400 font-medium">This Week</p>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400">RWF {{ number_format($weekSales, 0) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm p-4">
                    <p class="text-gray-500 dark:text-gray-400 font-medium">This Month</p>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400">RWF {{ number_format($monthSales, 0) }}</p>
                </div>
            </div>
        </section>
        @endif

        {{-- Charts Section --}}
        @if($sections['charts'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400">Sales Performance</h3>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-10 relative overflow-hidden">
                <div id="chart-loader" class="absolute inset-0 bg-gray-100 dark:bg-gray-800 animate-pulse z-10"></div>
                <canvas id="trendChart" height="120"></canvas>
                <div class="mt-10">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3">Sales (Last 30 Days)</h4>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </section>
        @endif

        {{-- Insights Section --}}
        @if($sections['insights'] ?? false)
        <section>
            <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400">Advanced Insights</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Top Products --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700 space-y-5">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                        <i data-lucide="trophy" class="w-5 h-5 text-yellow-500 mr-2"></i> Top 5 Selling Products
                    </h4>
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2 text-right">Qty Sold</th>
                                <th class="px-4 py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $p)
                                <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-4 py-2">{{ $p->product->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($p->total_qty, 0) }}</td>
                                    <td class="px-4 py-2 text-right text-green-600 dark:text-green-400 font-semibold">
                                        RWF {{ number_format($p->total_revenue, 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3 text-gray-500 dark:text-gray-400">No sales data yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        <canvas id="topProductsChart" height="140"></canvas>
                    </div>
                </div>

                {{-- Top Customers --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700 space-y-5">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                        <i data-lucide="users" class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mr-2"></i> Top 5 Customers
                    </h4>
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2">Customer</th>
                                <th class="px-4 py-2 text-right">Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCustomers as $c)
                                <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-4 py-2">{{ $c->customer->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-2 text-right text-indigo-600 dark:text-indigo-400 font-semibold">
                                        RWF {{ number_format($c->total_spent, 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center py-3 text-gray-500 dark:text-gray-400">No customer data yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        <canvas id="topCustomersChart" height="140"></canvas>
                    </div>
                </div>
            </div>
        </section>
        @endif
    </div>

    @if($sections['charts'] || $sections['insights'])
        <script src="https://unpkg.com/lucide@latest"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                lucide.createIcons();
                const kpiSection = document.getElementById('kpi-section');
                setTimeout(() => { kpiSection.style.opacity = 1; }, 200);
                const loader = document.getElementById('chart-loader');
                setTimeout(() => loader?.remove(), 800);

                const ctxTrend = document.getElementById('trendChart')?.getContext('2d');
                if (ctxTrend) {
                    new Chart(ctxTrend, {
                        type: 'line',
                        data: {
                            labels: @json($months),
                            datasets: [
                                { label: 'Sales', data: @json($salesTrend), borderColor: '#4F46E5', backgroundColor: 'rgba(79,70,229,0.1)', tension: 0.35, fill: true },
                                { label: 'Purchases', data: @json($purchaseTrend), borderColor: '#EC4899', backgroundColor: 'rgba(236,72,153,0.1)', tension: 0.35, fill: true },
                            ],
                        },
                        options: { responsive: true, scales: { y: { beginAtZero: true } } },
                    });
                }

                fetch('{{ route('dashboard.sales.chart') }}')
                    .then(res => res.json())
                    .then(data => {
                        const ctxSales = document.getElementById('salesChart')?.getContext('2d');
                        if (ctxSales) {
                            new Chart(ctxSales, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{ label: 'Daily Sales', data: data.sales, backgroundColor: 'rgba(79,70,229,0.6)', borderRadius: 5 }],
                                },
                                options: { responsive: true, scales: { y: { beginAtZero: true } } },
                            });
                        }
                    });

                const productsCtx = document.getElementById('topProductsChart')?.getContext('2d');
                if (productsCtx) {
                    new Chart(productsCtx, {
                        type: 'bar',
                        data: {
                            labels: @json($topProducts->pluck('product.name')),
                            datasets: [{ label: 'Qty Sold', data: @json($topProducts->pluck('total_qty')), backgroundColor: '#4F46E5', borderRadius: 6 }],
                        },
                        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
                    });
                }

                const customersCtx = document.getElementById('topCustomersChart')?.getContext('2d');
                if (customersCtx) {
                    new Chart(customersCtx, {
                        type: 'doughnut',
                        data: {
                            labels: @json($topCustomers->pluck('customer.name')),
                            datasets: [{ label: 'Total Spent', data: @json($topCustomers->pluck('total_spent')), backgroundColor: ['#4F46E5','#6366F1','#818CF8','#A5B4FC','#C7D2FE'], borderWidth: 1 }],
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
                    });
                }
            });
        </script>
    @endif
</x-app-layout>
