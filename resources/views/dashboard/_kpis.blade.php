<section>
    {{-- KPI Header --}}
    <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400">
        Key Performance Indicators
    </h3>

    {{-- Responsive KPI Grid --}}
    <div id="kpi-section"
         class="grid gap-6 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">

        <!-- Total Sales -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-indigo-500/20 to-purple-500/20 blur-2xl transition-all group-hover:scale-150"></div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sales</dt>
            <dd class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($totalSales) }} RWF</span>
            </dd>
            <div class="mt-4 flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400">
                <div class="flex items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 p-1.5">
                    <i data-lucide="dollar-sign" class="h-4 w-4"></i>
                </div>
                <span class="font-medium">Revenue</span>
            </div>
        </div>

        <!-- Total Profit -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-emerald-500/20 to-teal-500/20 blur-2xl transition-all group-hover:scale-150"></div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Profit</dt>
            <dd class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($totalProfit) }} RWF</span>
            </dd>
            <div class="mt-4 flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                <div class="flex items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 p-1.5">
                    <i data-lucide="trending-up" class="h-4 w-4"></i>
                </div>
                <span class="font-medium">Gross Profit</span>
            </div>
        </div>

        <!-- Pending Balances -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-rose-500/20 to-orange-500/20 blur-2xl transition-all group-hover:scale-150"></div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Balances</dt>
            <dd class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($pendingBalances) }} RWF</span>
            </dd>
            <div class="mt-4 flex items-center gap-2 text-sm text-rose-600 dark:text-rose-400">
                <div class="flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/30 p-1.5">
                    <i data-lucide="alert-circle" class="h-4 w-4"></i>
                </div>
                <span class="font-medium">Outstanding</span>
            </div>
        </div>


        <!-- Net Cash Balance -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-cyan-500/20 to-blue-500/20 blur-2xl transition-all group-hover:scale-150"></div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Cash Balance</dt>
            <dd class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($netBalance) }} RWF</span>
            </dd>
            <div class="mt-4 flex items-center gap-2 text-sm text-cyan-600 dark:text-cyan-400">
                <div class="flex items-center justify-center rounded-full bg-cyan-50 dark:bg-cyan-900/30 p-1.5">
                    <i data-lucide="wallet" class="h-4 w-4"></i>
                </div>
                <span class="font-medium">Liquidity</span>
            </div>
        </div>

        <!-- Stock Value -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-gradient-to-br from-amber-500/20 to-yellow-500/20 blur-2xl transition-all group-hover:scale-150"></div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock Value</dt>
            <dd class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($totalStockValue) }} RWF</span>
            </dd>
            <div class="mt-4 flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                <div class="flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/30 p-1.5">
                    <i data-lucide="package" class="h-4 w-4"></i>
                </div>
                <span class="font-medium">Inventory Asset</span>
            </div>
        </div>
    </div>

    {{-- Mini Summary Cards --}}
    <div class="mt-6 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))] text-sm">
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700
                    rounded-lg shadow-sm p-4 flex flex-col justify-between">
            <p class="text-gray-500 dark:text-gray-400 font-medium">Today’s Sales</p>
            <p class="font-semibold text-indigo-600 dark:text-indigo-400">
                RWF {{ number_format($todaySales, 0) }}
                <span class="ml-1 text-xs {{ $salesChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $salesChange >= 0 ? '+' : '' }}{{ number_format($salesChange, 1) }}%
                </span>
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700
                    rounded-lg shadow-sm p-4 flex flex-col justify-between">
            <p class="text-gray-500 dark:text-gray-400 font-medium">This Week</p>
            <p class="font-semibold text-indigo-600 dark:text-indigo-400">
                RWF {{ number_format($weekSales, 0) }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700
                    rounded-lg shadow-sm p-4 flex flex-col justify-between">
            <p class="text-gray-500 dark:text-gray-400 font-medium">This Month</p>
            <p class="font-semibold text-indigo-600 dark:text-indigo-400">
                RWF {{ number_format($monthSales, 0) }}
            </p>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kpiSection = document.getElementById('kpi-section');
            if (kpiSection) setTimeout(() => kpiSection.style.opacity = 1, 200);
        });
    </script>
    @endpush
</section>
