<section>
    {{-- Charts Header --}}
    <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400">
        Sales Performance
    </h3>

    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-6 relative overflow-hidden">

        {{-- Loading Overlay --}}
        <div id="chart-loader"
             class="absolute inset-0 bg-gray-100 dark:bg-gray-800 animate-pulse z-10"></div>

        {{-- Combined Chart: Sales vs Purchases --}}
        <div class="h-56 sm:h-64">
            <canvas id="trendChart"></canvas>
        </div>

        {{-- 30-Day Sales Chart --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Sales (Last 30 Days)
            </h4>
            <div class="h-48 sm:h-56">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
</section>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            const loader = document.getElementById('chart-loader');
            setTimeout(() => loader?.remove(), 600);

            // Trend Chart: Sales vs Purchases
            const ctxTrend = document.getElementById('trendChart')?.getContext('2d');
            if (ctxTrend) {
                new Chart(ctxTrend, {
                    type: 'line',
                    data: {
                        labels: @json($months),
                        datasets: [
                            {
                                label: 'Sales',
                                data: @json($salesTrend),
                                borderColor: '#4F46E5',
                                backgroundColor: 'rgba(79,70,229,0.08)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 2,
                            },
                            {
                                label: 'Purchases',
                                data: @json($purchaseTrend),
                                borderColor: '#EC4899',
                                backgroundColor: 'rgba(236,72,153,0.08)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 2,
                            }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            x: { ticks: { color: '#9CA3AF' }, grid: { display: false } },
                            y: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(156,163,175,0.1)' }, beginAtZero: true },
                        },
                        plugins: { legend: { position: 'top', labels: { color: '#6B7280' } } }
                    }
                });
            }

            // Bar Chart: Last 30 Days Sales
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
                                    backgroundColor: 'rgba(79,70,229,0.6)',
                                    borderRadius: 5,
                                }]
                            },
                            options: {
                                maintainAspectRatio: false,
                                scales: {
                                    x: { ticks: { color: '#9CA3AF' }, grid: { display: false } },
                                    y: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(156,163,175,0.1)' }, beginAtZero: true },
                                },
                                plugins: { legend: { display: false } }
                            }
                        });
                    }
                });
        });
    </script>
@endpush
