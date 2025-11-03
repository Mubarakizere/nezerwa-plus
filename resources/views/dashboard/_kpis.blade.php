<section>
    {{-- KPI Header --}}
    <h3 class="text-lg font-semibold text-indigo-600 mb-4 dark:text-indigo-400">
        Key Performance Indicators
    </h3>

    {{-- Responsive KPI Grid --}}
    <div id="kpi-section"
         class="grid gap-4
                [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]
                opacity-0 transition-opacity duration-500">

        <x-stat label="Total Sales"
                :value="$totalSales"
                color="text-indigo-600"
                icon="dollar-sign" />

        <x-stat label="Total Profit"
                :value="$totalProfit"
                color="text-green-600"
                icon="trending-up" />

        <x-stat label="Pending Balances"
                :value="$pendingBalances"
                color="text-red-600"
                icon="alert-circle" />

        <x-stat label="Total Purchases"
                :value="$totalPurchases"
                color="text-pink-600"
                icon="shopping-bag" />

        <x-stat label="Stock Value"
                :value="$totalStockValue"
                color="text-amber-600"
                icon="package" />

        @if($sections['finance'])
            <x-stat label="Net Balance"
                    :value="$netBalance"
                    :color="$netBalance >= 0 ? 'text-green-600' : 'text-red-600'"
                    icon="scale" />
        @endif
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
