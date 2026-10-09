<section class="space-y-4">
    @if(($permissions['sales'] ?? false) && (($permissions['products'] ?? false) || ($permissions['customers'] ?? false)))
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
            Operational Insights & Leaders
        </h3>
        <span class="text-xs text-slate-400 font-medium">Top Performers</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- 1. Top Selling Products --}}
        @if($permissions['products'] ?? false)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-xs p-5 flex flex-col justify-between space-y-5">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center">
                            <i data-lucide="award" class="w-4 h-4"></i>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            Top 5 Selling Products
                        </h4>
                    </div>
                    <span class="text-xs text-slate-400">By quantity</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 uppercase tracking-wider font-semibold text-[10px]">
                                <th class="pb-2.5 pr-2">#</th>
                                <th class="pb-2.5">Product</th>
                                <th class="pb-2.5 text-right">Units</th>
                                <th class="pb-2.5 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($topProducts as $idx => $p)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-2.5 pr-2">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-bold {{ $idx === 0 ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                                            {{ $idx + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 font-medium text-slate-800 dark:text-slate-200 truncate max-w-[140px]">
                                        {{ $p->product->name ?? 'Unknown Product' }}
                                    </td>
                                    <td class="py-2.5 text-right text-slate-600 dark:text-slate-300 font-semibold">
                                        {{ number_format($p->total_qty, 0) }}
                                    </td>
                                    <td class="py-2.5 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($p->total_revenue, 0) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-slate-400">
                                        No sales records recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mini Chart for Products --}}
            <div class="h-36 relative pt-2 border-t border-slate-100 dark:border-slate-800">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
        @endif

        {{-- 2. Top Customers --}}
        @if($permissions['customers'] ?? false)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-xs p-5 flex flex-col justify-between space-y-5">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            Top 5 Customers
                        </h4>
                    </div>
                    <span class="text-xs text-slate-400">By total spent</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 uppercase tracking-wider font-semibold text-[10px]">
                                <th class="pb-2.5 pr-2">#</th>
                                <th class="pb-2.5">Customer</th>
                                <th class="pb-2.5 text-right">Total Spent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($topCustomers as $idx => $c)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-2.5 pr-2">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-bold {{ $idx === 0 ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                                            {{ $idx + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 font-medium text-slate-800 dark:text-slate-200 truncate max-w-[160px]">
                                        {{ $c->customer->name ?? 'Walk-in / Unknown' }}
                                    </td>
                                    <td class="py-2.5 text-right font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ number_format($c->total_spent, 0) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-6 text-slate-400">
                                        No customer purchase history yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mini Chart for Customers --}}
            <div class="h-36 relative pt-2 border-t border-slate-100 dark:border-slate-800">
                <canvas id="topCustomersChart"></canvas>
            </div>
        </div>
        @endif

    </div>
    @endif
</section>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const isDark = document.documentElement.classList.contains('dark');
            const tickColor = isDark ? '#94A3B8' : '#64748B';

            // Top Products Chart
            const productsCtx = document.getElementById('topProductsChart')?.getContext('2d');
            if (productsCtx) {
                new Chart(productsCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($topProducts->pluck('product.name')),
                        datasets: [{
                            label: 'Units Sold',
                            data: @json($topProducts->pluck('total_qty')),
                            backgroundColor: 'rgba(99, 102, 241, 0.75)',
                            hoverBackgroundColor: '#4F46E5',
                            borderRadius: 4,
                            barPercentage: 0.6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { padding: 8, cornerRadius: 6 }
                        },
                        scales: {
                            x: {
                                ticks: { color: tickColor, font: { size: 10 }, maxRotation: 20 },
                                grid: { display: false }
                            },
                            y: {
                                ticks: { color: tickColor, font: { size: 10 } },
                                grid: { display: false },
                                beginAtZero: true
                            },
                        },
                    },
                });
            }

            // Top Customers Chart
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
                                '#6366F1', '#818CF8', '#A5B4FC', '#06B6D4', '#10B981'
                            ],
                            borderWidth: 2,
                            borderColor: isDark ? '#0F172A' : '#FFFFFF',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 10 },
                                    color: tickColor
                                }
                            },
                            tooltip: {
                                padding: 8,
                                cornerRadius: 6,
                                callbacks: {
                                    label: ctx => ' ' + ctx.label + ': ' + Number(ctx.parsed).toLocaleString() + ' RWF'
                                }
                            }
                        },
                    },
                });
            }
        });
    </script>
@endpush
