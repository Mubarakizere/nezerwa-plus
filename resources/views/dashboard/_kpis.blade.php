<section class="space-y-4">
    {{-- Section Title --}}
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
            Key Performance Metrics
        </h3>
        <span class="text-xs text-slate-400 font-medium">Real-time sync</span>
    </div>

    {{-- Main KPI Grid --}}
    <div id="kpi-section" class="grid gap-4 sm:gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

        {{-- 1. Total Sales --}}
        @if($permissions['sales'] ?? false)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Sales</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ number_format($totalSales) }}
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Revenue volume</span>
                <span class="font-medium text-indigo-600 dark:text-indigo-400">Paid + Unpaid</span>
            </div>
        </div>
        @endif

        {{-- 2. Total Profit --}}
        @if($permissions['sales'] ?? false)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Profit</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="badge-percent" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                    {{ number_format($totalProfit) }}
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Earned Margin</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">Net Markup</span>
            </div>
        </div>
        @endif

        {{-- 3. Pending Balances --}}
        @if($permissions['sales'] ?? false)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Balances</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">
                    {{ number_format($pendingBalances) }}
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Outstanding debt</span>
                <span class="font-medium text-rose-600 dark:text-rose-400">To collect</span>
            </div>
        </div>
        @endif

        {{-- 4. Net Cash Balance --}}
        @if($permissions['debits_credits'] ?? false)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Cash</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ number_format($netBalance) }}
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Cash liquidity</span>
                <span class="font-medium text-sky-600 dark:text-sky-400">Credits - Debits</span>
            </div>
        </div>
        @endif

        {{-- 5. Stock Value --}}
        @if($permissions['stock'] ?? false)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stock Value</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="boxes" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ number_format($totalStockValue) }}
                    <span class="text-xs font-medium text-slate-400">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Current inventory</span>
                <span class="font-medium text-amber-600 dark:text-amber-400">At Cost</span>
            </div>
        </div>
        @endif

    </div>

    {{-- Mini Summary Bar (Today, Week, Month) --}}
    @if($permissions['sales'] ?? false)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
        {{-- Today --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Today's Sales</p>
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                    {{ number_format($todaySales) }} <span class="text-xs text-slate-400 font-normal">RWF</span>
                </p>
            </div>
            <div class="flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold {{ $salesChange >= 0 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400' }}">
                <span>{{ $salesChange >= 0 ? '↑' : '↓' }} {{ abs(round($salesChange, 1)) }}%</span>
                <span class="text-[10px] opacity-75 hidden xl:inline">vs yest.</span>
            </div>
        </div>

        {{-- This Week --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">This Week</p>
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                    {{ number_format($weekSales) }} <span class="text-xs text-slate-400 font-normal">RWF</span>
                </p>
            </div>
            <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
            </div>
        </div>

        {{-- This Month --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">This Month</p>
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                    {{ number_format($monthSales) }} <span class="text-xs text-slate-400 font-normal">RWF</span>
                </p>
            </div>
            <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                <i data-lucide="bar-chart-2" class="w-3.5 h-3.5"></i>
            </div>
        </div>
    </div>
    @endif
</section>
