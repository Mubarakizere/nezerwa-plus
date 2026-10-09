<section class="space-y-4">
    @if(($permissions['sales'] ?? false) || ($permissions['purchases'] ?? false))
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
            Sales & Purchases Performance
        </h3>
        <span class="text-xs text-slate-400 font-medium">Monthly Trends</span>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-xs p-5 space-y-6">

        {{-- Top Trend: Sales vs Purchases --}}
        <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                        Monthly Comparison
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Sales vs. Purchases volume over the last 6 months
                    </p>
                </div>
                <div class="flex items-center gap-4 text-xs font-medium">
                    <span class="inline-flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span> Sales
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-rose-500 dark:text-rose-400">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Purchases
                    </span>
                </div>
            </div>

            <div class="h-60 sm:h-64 relative">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        {{-- Bottom: 30-Day Daily Sales --}}
        <div class="pt-5 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                        Daily Sales Activity
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Day-by-day revenue breakdown for the past 30 days
                    </p>
                </div>
            </div>

            <div class="h-48 sm:h-52 relative">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    @endif
</section>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const isDark = document.documentElement.classList.contains('dark');
            const tickColor = isDark ? '#94A3B8' : '#64748B';
            const gridColor = isDark ? 'rgba(148, 163, 184, 0.08)' : 'rgba(226, 232, 240, 0.6)';

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
                                borderColor: '#6366F1',
                                backgroundColor: 'rgba(99, 102, 241, 0.08)',
                                borderWidth: 2.5,
                                tension: 0.35,
                                fill: true,
                                pointBackgroundColor: '#6366F1',
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            },
                            {
                                label: 'Purchases',
                                data: @json($purchaseTrend),
                                borderColor: '#F43F5E',
                                backgroundColor: 'rgba(244, 63, 94, 0.06)',
                                borderWidth: 2.5,
                                tension: 0.35,
                                fill: true,
                                pointBackgroundColor: '#F43F5E',
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                boxPadding: 4,
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: tickColor, font: { size: 11 } },
                                grid: { display: false }
                            },
                            y: {
                                ticks: {
                                    color: tickColor,
                                    font: { size: 11 },
                                    callback: v => Number(v).toLocaleString()
                                },
                                grid: { color: gridColor },
                                beginAtZero: true
                            },
                        }
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
                                    backgroundColor: 'rgba(99, 102, 241, 0.75)',
                                    hoverBackgroundColor: '#4F46E5',
                                    borderRadius: 4,
                                    barPercentage: 0.7,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        padding: 10,
                                        cornerRadius: 8,
                                        callbacks: {
                                            label: ctx => 'Sales: ' + Number(ctx.parsed.y).toLocaleString() + ' RWF'
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        ticks: { color: tickColor, font: { size: 10 }, maxRotation: 45 },
                                        grid: { display: false }
                                    },
                                    y: {
                                        ticks: {
                                            color: tickColor,
                                            font: { size: 11 },
                                            callback: v => Number(v).toLocaleString()
                                        },
                                        grid: { color: gridColor },
                                        beginAtZero: true
                                    },
                                }
                            }
                        });
                    }
                });
        });
    </script>
@endpush
