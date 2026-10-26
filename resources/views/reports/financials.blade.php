@extends('layouts.app')
@section('title', 'Financial Overview')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    use Carbon\Carbon;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $startCarbon = Carbon::parse($startDate);
    $endCarbon   = Carbon::parse($endDate);

    $todayStr   = now()->toDateString();
    $last7Str   = now()->subDays(6)->toDateString();
    $monthStart = now()->startOfMonth()->toDateString();
    $yearStart  = now()->startOfYear()->toDateString();

    $netLoans = (float)$loansGiven - (float)$loansTaken;
    $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('reports.index') }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Back to Reports Overview">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="landmark" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Financial Ledger &amp; Audit Overview</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/40 font-mono">
                    {{ $startCarbon->format('M d, Y') }} → {{ $endCarbon->format('M d, Y') }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Audit records of sales receipts, procurement expenses, financing activity, and payment channel distributions.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Print Ledger</span>
            </button>

            @if(Route::has('reports.reconciliation'))
                <a href="{{ route('reports.reconciliation') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 rounded-lg shadow-2xs hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span>Reconciliation Audit</span>
                </a>
            @endif

            <a href="{{ route('reports.export.finance.pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                <i data-lucide="file-down" class="w-4 h-4"></i>
                <span>Finance PDF</span>
            </a>
        </div>
    </div>

    {{-- Filter Panel & Presets --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4 print:hidden">
        {{-- Quick Range Pills --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Date Preset:
                </span>
                <a href="{{ route('audit.financials', ['start_date' => $todayStr, 'end_date' => $todayStr]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $startDate === $todayStr && $endDate === $todayStr ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Today
                </a>
                <a href="{{ route('audit.financials', ['start_date' => $last7Str, 'end_date' => $todayStr]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $startDate === $last7Str && $endDate === $todayStr ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last 7 Days
                </a>
                <a href="{{ route('audit.financials', ['start_date' => $monthStart, 'end_date' => $todayStr]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $startDate === $monthStart && $endDate === $todayStr ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Month
                </a>
                <a href="{{ route('audit.financials', ['start_date' => $yearStart, 'end_date' => $todayStr]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $startDate === $yearStart && $endDate === $todayStr ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Year
                </a>
            </div>

            <div class="text-xs font-mono font-medium text-slate-500 dark:text-slate-400">
                Active Audit Window: <strong class="text-slate-800 dark:text-slate-200">{{ $startCarbon->format('M d, Y') }}</strong> → <strong class="text-slate-800 dark:text-slate-200">{{ $endCarbon->format('M d, Y') }}</strong>
            </div>
        </div>

        {{-- Filter Inputs Form --}}
        <form method="GET" action="{{ route('audit.financials') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Start Date
                </label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    End Date
                </label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Generate Financials</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Executive Financial Overview Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Sales --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Invoiced Sales</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($totalSales) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Collected: <strong class="font-mono text-emerald-600">{{ $fmt0($totalReceived) }}</strong> RWF
                </div>
            </div>
        </div>

        {{-- Gross Operating Profit --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Margin Profit</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($totalProfit) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Margin: {{ number_format($profitMargin, 1) }}% of sales
                </div>
            </div>
        </div>

        {{-- Total Purchases --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Procurement Expenses</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="package" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400 font-mono">
                    {{ $fmt0($totalPurchases) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Supplier inventory procurement</div>
            </div>
        </div>

        {{-- Loans & Financing Activity --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Financing Activity</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                    <span>Given: <strong class="font-mono text-sky-600">{{ $fmt0($loansGiven) }}</strong></span>
                    <span>Taken: <strong class="font-mono text-purple-600">{{ $fmt0($loansTaken) }}</strong></span>
                </div>
                <div class="text-xs font-mono font-bold mt-1.5 {{ $netLoans >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    Net Impact: {{ $netLoans >= 0 ? '+' : '' }}{{ $fmt0($netLoans) }} RWF
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue by Payment Channel --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i data-lucide="credit-card" class="w-4 h-4 text-indigo-500"></i>
                <span>Collected Revenue by Payment Channel</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @forelse($revenueByChannel as $channel => $amount)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ ucfirst($channel ?: 'Standard') }}
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center">
                            @if(str_contains(strtolower($channel ?? ''), 'cash'))
                                <i data-lucide="banknote" class="w-3.5 h-3.5 text-emerald-500"></i>
                            @elseif(str_contains(strtolower($channel ?? ''), 'momo') || str_contains(strtolower($channel ?? ''), 'mobile'))
                                <i data-lucide="smartphone" class="w-3.5 h-3.5 text-sky-500"></i>
                            @elseif(str_contains(strtolower($channel ?? ''), 'bank') || str_contains(strtolower($channel ?? ''), 'pos'))
                                <i data-lucide="building-2" class="w-3.5 h-3.5 text-purple-500"></i>
                            @else
                                <i data-lucide="credit-card" class="w-3.5 h-3.5 text-indigo-500"></i>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-xl font-bold font-mono text-slate-900 dark:text-slate-100">
                            {{ $fmt0($amount) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            @if($totalReceived > 0)
                                {{ number_format(($amount / $totalReceived) * 100, 1) }}% of total receipts
                            @else
                                0%
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-6 text-center text-xs text-slate-400">
                    No payment channel collections recorded in this period.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Detailed Double-Audit Ledgers (Sales vs Purchases) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- Exact Sales Ledger --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden flex flex-col">
            <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="shopping-cart" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Sales Audit Ledger ({{ $allSales->count() }})
                    </h3>
                </div>
                <span class="text-xs text-slate-400">Invoices in period</span>
            </div>

            <div class="overflow-y-auto max-h-[500px]">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50/90 dark:bg-slate-800/90 sticky top-0 border-b border-slate-200/80 dark:border-slate-800 z-10 backdrop-blur-xs">
                        <tr class="text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-4">Date / Ref</th>
                            <th class="py-2.5 px-4">Customer</th>
                            <th class="py-2.5 px-4 text-right">Amount</th>
                            <th class="py-2.5 px-4">Channel</th>
                            <th class="py-2.5 px-4 text-right">Cashier</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($allSales as $sale)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <a href="{{ route('sales.show', $sale->id) }}" class="font-mono font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    #{{ $sale->id }}
                                </a>
                                <div class="text-[10px] text-slate-400">
                                    {{ optional($sale->sale_date)->format('M d') }}
                                </div>
                            </td>
                            <td class="py-2.5 px-4 font-medium text-slate-800 dark:text-slate-200 truncate max-w-[120px]">
                                {{ $sale->customer->name ?? 'Walk-in Customer' }}
                            </td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ $fmt0($sale->total_amount) }}
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ $sale->payment_channel ?: 'CSH' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-right text-slate-500 dark:text-slate-400 text-[11px] truncate max-w-[90px]">
                                {{ $sale->user->name ?? 'System' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">No sales recorded in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Exact Purchases Ledger --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden flex flex-col">
            <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Purchases Audit Ledger ({{ $allPurchases->count() }})
                    </h3>
                </div>
                <span class="text-xs text-slate-400">Procurements in period</span>
            </div>

            <div class="overflow-y-auto max-h-[500px]">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50/90 dark:bg-slate-800/90 sticky top-0 border-b border-slate-200/80 dark:border-slate-800 z-10 backdrop-blur-xs">
                        <tr class="text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-4">Date / Ref</th>
                            <th class="py-2.5 px-4">Supplier</th>
                            <th class="py-2.5 px-4 text-right">Amount</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4 text-right">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($allPurchases as $purchase)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <a href="{{ route('purchases.show', $purchase->id) }}" class="font-mono font-semibold text-purple-600 dark:text-purple-400 hover:underline">
                                    #{{ $purchase->id }}
                                </a>
                                <div class="text-[10px] text-slate-400">
                                    {{ optional($purchase->purchase_date)->format('M d') }}
                                </div>
                            </td>
                            <td class="py-2.5 px-4 font-medium text-slate-800 dark:text-slate-200 truncate max-w-[120px]">
                                {{ $purchase->supplier->name ?? 'External Vendor' }}
                            </td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ $fmt0($purchase->total_amount) }}
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $purchase->status === 'paid' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300' }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-right text-slate-500 dark:text-slate-400 text-[11px] truncate max-w-[90px]">
                                {{ $purchase->user->name ?? 'System' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">No purchases recorded in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
