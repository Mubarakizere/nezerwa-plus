<section>
    {{-- Insights Header --}}
    <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400">
        Advanced Insights
    </h3>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top Selling Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 space-y-4">
            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                <i data-lucide="trophy" class="w-5 h-5 text-yellow-500 mr-2"></i>
                Top 5 Selling Products
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2 text-right">Qty Sold</th>
                            <th class="px-3 py-2 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $p)
                            <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-3 py-2">{{ $p->product->name ?? 'Unknown' }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($p->total_qty, 0) }}</td>
                                <td class="px-3 py-2 text-right text-green-600 dark:text-green-400 font-semibold">
                                    RWF {{ number_format($p->total_revenue, 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-3 text-gray-500 dark:text-gray-400">
                                    No sales data yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Chart for Top Products --}}
            <div class="h-48">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 space-y-4">
            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                <i data-lucide="users" class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mr-2"></i>
                Top 5 Customers
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2 text-right">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCustomers as $c)
                            <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-3 py-2">{{ $c->customer->name ?? 'Unknown' }}</td>
                                <td class="px-3 py-2 text-right text-indigo-600 dark:text-indigo-400 font-semibold">
                                    RWF {{ number_format($c->total_spent, 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center py-3 text-gray-500 dark:text-gray-400">
                                    No customer data yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Chart for Top Customers --}}
            <div class="h-48">
                <canvas id="topCustomersChart"></canvas>
            </div>
        </div>
    </div>
</section>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // === Top Products Chart ===
            const productsCtx = document.getElementById('topProductsChart')?.getContext('2d');
            if (productsCtx) {
                new Chart(productsCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($topProducts->pluck('product.name')),
                        datasets: [{
                            label: 'Qty Sold',
                            data: @json($topProducts->pluck('total_qty')),
                            backgroundColor: 'rgba(79,70,229,0.7)',
                            borderRadius: 4,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { color: '#9CA3AF' }, grid: { display: false } },
                            y: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(156,163,175,0.1)' }, beginAtZero: true },
                        },
                    },
                });
            }

            // === Top Customers Chart ===
            const customersCtx = document.getElementById('topCustomersChart')?.getContext('2d');
            if (customersCtx) {
                new Chart(customersCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($topCustomers->pluck('customer.name')),
                        datasets: [{
                            label: 'Total Spent',
                            data: @json($topCustomers->pluck('total_spent')),
                            backgroundColor: [
                                '#4F46E5', '#6366F1', '#818CF8', '#A5B4FC', '#C7D2FE'
                            ],
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: '#6B7280' } },
                        },
                    },
                });
            }
        });
    </script>
@endpush
