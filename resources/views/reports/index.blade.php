@extends('layouts.app')
@section('title', 'Reports & Business Intelligence')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    use Carbon\Carbon;

    // Helper functions for safe number parsing
    $cleanNum = fn($v) => (float) str_replace(',', '', (string)($v ?? 0));
    $fmt0 = fn($v) => number_format($cleanNum($v), 0);
    $fmt2 = fn($v) => number_format($cleanNum($v), 2);

    $start = $start ?? now()->subMonth()->toDateString();
    $end   = $end   ?? now()->toDateString();
    $q     = $q     ?? '';

    $startFormatted = Carbon::parse($start)->format('M d, Y');
    $endFormatted   = Carbon::parse($end)->format('M d, Y');

    $netCashVal = $cleanNum($netBalance ?? 0);
    $plNetVal   = (float) ($plNetProfit ?? 0);

    $today   = Carbon::today()->toDateString();
    $last7   = Carbon::today()->subDays(6)->toDateString();
    $last30  = Carbon::today()->subDays(29)->toDateString();
    $thisMoS = Carbon::today()->startOfMonth()->toDateString();
    $thisYrS = Carbon::today()->startOfYear()->toDateString();

    $maxProductSales = $topProducts->max('total_sales') ?: 1;
    $maxCustomerSpend = $topCustomers->max('total_spent') ?: 1;
@endphp

@cannot('reports.view')
    <div class="max-w-3xl mx-auto py-10">
        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-6 h-6 text-amber-500 mt-0.5"></i>
                <div>
                    <h1 class="text-base font-semibold text-amber-900 dark:text-amber-200">
                        You don’t have permission to view business reports.
                    </h1>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact an administrator if you require access to financial statements and intelligence reports.
                    </p>
                </div>
            </div>
        </div>
    </div>
@else
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Reports & Business Intelligence</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/40">
                    Live Analytics
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Executive statements, profit & loss, working capital, inventory valuation, and sales performance.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('reports.daily-collection', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-lg shadow-2xs hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors">
                <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>Daily Collections</span>
            </a>

            <a href="{{ route('reports.export.finance.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Finance PDF</span>
            </a>

            <a href="{{ route('reports.export.sales.csv', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                <span>Sales CSV</span>
            </a>
        </div>
    </div>

    {{-- Filter Panel & Range Presets --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4">
        
        {{-- Preset Pills Row --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="calendar-range" class="w-3.5 h-3.5"></i> Quick Range:
                </span>
                <a href="{{ route('reports.index', array_merge(request()->except('start_date', 'end_date'), ['start_date' => $today, 'end_date' => $today])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $start === $today && $end === $today ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Today
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except('start_date', 'end_date'), ['start_date' => $last7, 'end_date' => $today])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $start === $last7 && $end === $today ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last 7 Days
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except('start_date', 'end_date'), ['start_date' => $last30, 'end_date' => $today])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $start === $last30 && $end === $today ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Last 30 Days
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except('start_date', 'end_date'), ['start_date' => $thisMoS, 'end_date' => $today])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $start === $thisMoS && $end === $today ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Month
                </a>
                <a href="{{ route('reports.index', array_merge(request()->except('start_date', 'end_date'), ['start_date' => $thisYrS, 'end_date' => $today])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $start === $thisYrS && $end === $today ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Year
                </a>
            </div>

            <div class="text-xs font-mono font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Active Period: <strong class="text-slate-800 dark:text-slate-200">{{ $startFormatted }}</strong> → <strong class="text-slate-800 dark:text-slate-200">{{ $endFormatted }}</strong></span>
            </div>
        </div>

        {{-- Filter Inputs Form --}}
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Start Date
                </label>
                <input type="date" name="start_date" value="{{ $start }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    End Date
                </label>
                <input type="date" name="end_date" value="{{ $end }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Filter by Customer, Product or Notes
                </label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Search term..."
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Apply Filter</span>
                </button>
                <a href="{{ route('reports.index') }}"
                   class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                   title="Reset Date Range">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Executive Financial KPI Cards (Date Range) --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i data-lucide="activity" class="w-4 h-4 text-indigo-500"></i>
                <span>Operating Performance ({{ $startFormatted }} → {{ $endFormatted }})</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Total Sales --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Sales</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                        {{ $fmt0($totalSales) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Paid + Unpaid Sales</div>
                </div>
            </div>

            {{-- 2. Total Gross Profit --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Profit</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ $fmt0($totalProfit) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Margin: {{ number_format($profitMargin, 1) }}% of sales</div>
                </div>
            </div>

            {{-- 3. Pending Uncollected Sales --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Uncollected Credit</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                        {{ $fmt0($pendingBalances) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Period unpaid customer balances</div>
                </div>
            </div>

            {{-- 4. Total Purchases --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Purchases & Restock</span>
                    <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="package-check" class="w-4 h-4"></i>
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

            {{-- 5. Cash Inflows (Credits) --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cash Inflows (Credits)</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ $fmt0($credits) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total collected cash & receipts</div>
                </div>
            </div>

            {{-- 6. Cash Outflows (Debits) --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cash Outflows (Debits)</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                        {{ $fmt0($debits) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Payments & operating disbursements</div>
                </div>
            </div>

            {{-- 7. Net Cash Movement --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Cash Flow</span>
                    <div class="w-8 h-8 rounded-lg {{ $netCashVal >= 0 ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600' : 'bg-rose-500/10 border border-rose-500/20 text-rose-600' }} flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $netCashVal >= 0 ? 'check' : 'trending-down' }}" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight font-mono {{ $netCashVal >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $fmt0($netBalance) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Inflows minus Outflows</div>
                </div>
            </div>

            {{-- 8. Capital Loans Flow --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Principal Loans</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs font-medium text-slate-600 dark:text-slate-400">
                        <span>Given: <strong class="font-mono text-sky-600">{{ $fmt0($loansGiven) }}</strong></span>
                        <span>Taken: <strong class="font-mono text-purple-600">{{ $fmt0($loansTaken) }}</strong></span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-2">Financing principal created in period</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Executive Profit & Loss (P&L Statement) --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-800/30">
            <div>
                <div class="flex items-center gap-2">
                    <i data-lucide="line-chart" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Profit & Loss Statement (P&L)
                    </h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Revenue from sales items, Cost of Goods Sold (COGS), operating expenses, and net profit.
                </p>
            </div>

            <a href="{{ route('reports.export.pl.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                <span>Export P&L PDF</span>
            </a>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- 1. Sales Item Revenue --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">1. Total Revenue</span>
                    <div class="text-xl font-bold font-mono text-slate-900 dark:text-slate-100 mt-1">
                        {{ $fmt0($plRevenue) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <span class="text-[11px] text-slate-400">Sum of sale item subtotals</span>
                </div>

                {{-- 2. Cost of Goods Sold --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">2. Cost of Goods Sold (COGS)</span>
                    <div class="text-xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">
                        {{ $fmt0($plCogs) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <span class="text-[11px] text-slate-400">Product procurement cost base</span>
                </div>

                {{-- 3. Gross Margin / Profit --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">3. Gross Profit</span>
                    <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ $fmt0($plGrossProfit) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <span class="text-[11px] text-slate-400">Revenue minus COGS</span>
                </div>

                {{-- 4. Operating Overhead --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">4. Operating Expenses</span>
                    <div class="text-xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">
                        {{ $fmt0($plOtherExpenses) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <span class="text-[11px] text-slate-400">General & admin debit outlays</span>
                </div>
            </div>

            {{-- Net Profit Highlight Banner --}}
            <div class="rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 {{ $plNetVal >= 0 ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-rose-500/10 border border-rose-500/20' }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $plNetVal >= 0 ? 'bg-emerald-500/20 text-emerald-600' : 'bg-rose-500/20 text-rose-600' }} flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $plNetVal >= 0 ? 'trophy' : 'alert-circle' }}" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider {{ $plNetVal >= 0 ? 'text-emerald-800 dark:text-emerald-300' : 'text-rose-800 dark:text-rose-300' }}">
                            {{ $plNetVal >= 0 ? 'Net Operating Profit' : 'Net Operating Deficit' }}
                        </div>
                        <div class="text-2xl font-extrabold font-mono {{ $plNetVal >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                            {{ $fmt0($plNetVal) }} <span class="text-sm font-sans font-medium">RWF</span>
                        </div>
                    </div>
                </div>

                <div class="text-xs text-slate-600 dark:text-slate-400 text-left sm:text-right">
                    <div>Gross Profit Margin: <strong class="font-mono text-slate-800 dark:text-slate-200">{{ number_format($profitMargin, 1) }}%</strong></div>
                    <div>Expense to Sales Ratio: <strong class="font-mono text-slate-800 dark:text-slate-200">{{ number_format($expenseRatio, 1) }}%</strong></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Working Capital & Inventory Snapshot --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i data-lucide="layers" class="w-4 h-4 text-indigo-500"></i>
                <span>Working Capital & Inventory Valuation (Live Snapshot)</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Accounts Receivable --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Accounts Receivable (AR)</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
                        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-amber-600 dark:text-amber-400">
                        {{ $fmt0($arBalance) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total customer credit outstanding</div>
                </div>
            </div>

            {{-- Accounts Payable --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Accounts Payable (AP)</span>
                    <div class="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-600 flex items-center justify-center">
                        <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-rose-600 dark:text-rose-400">
                        {{ $fmt0($apBalance) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total supplier debts outstanding</div>
                </div>
            </div>

            {{-- Inventory Units --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Stock Units</span>
                    <div class="w-7 h-7 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center">
                        <i data-lucide="boxes" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-slate-900 dark:text-slate-100">
                        {{ number_format($inventoryUnits, 0) }} <span class="text-xs font-sans text-slate-400">Units</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total physical inventory on hand</div>
                </div>
            </div>

            {{-- Inventory Value @ Cost --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inventory Value @ Cost</span>
                    <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="badge-dollar-sign" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-indigo-600 dark:text-indigo-400">
                        {{ $fmt0($inventoryValue) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Valuation at supplier cost price</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Performance Charts (6 Months Trend) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Revenue vs Expenses Chart --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Revenue vs. Purchases (Last 6 Months)
                    </h3>
                    <p class="text-xs text-slate-400">Sales volume compared against inventory restocking outlays</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Revenue</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Purchases</span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="revenueExpenseChart"></canvas>
            </div>
        </div>

        {{-- Monthly Sales Trajectory --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Monthly Sales Trajectory
                    </h3>
                    <p class="text-xs text-slate-400">Sales trend across preceding 6 months</p>
                </div>
                <div class="text-xs font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                    Growth: {{ number_format($revenueGrowth, 1) }}%
                </div>
            </div>
            <div class="h-64">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Performers: Products & Customers --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Products --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Top 5 Best-Selling Products
                    </h3>
                </div>
                <span class="text-xs text-slate-400">By Subtotal Revenue</span>
            </div>

            <div class="p-4 divide-y divide-slate-100 dark:divide-slate-800/80">
                @forelse($topProducts as $idx => $item)
                    @php
                        $sub = (float) $item->total_sales;
                        $pct = round(($sub / $maxProductSales) * 100);
                    @endphp
                    <div class="py-2.5 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-indigo-500/10 text-indigo-600 font-bold text-[10px] flex items-center justify-center">
                                    {{ $idx + 1 }}
                                </span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $item->product->name ?? 'Product #' . $item->product_id }}
                                </span>
                            </div>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $fmt0($sub) }} RWF
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">
                        No product sales recorded in this period.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Top 5 High-Value Customers
                    </h3>
                </div>
                <span class="text-xs text-slate-400">By Gross Volume</span>
            </div>

            <div class="p-4 divide-y divide-slate-100 dark:divide-slate-800/80">
                @forelse($topCustomers as $idx => $client)
                    @php
                        $spend = (float) $client->total_spent;
                        $pct = round(($spend / $maxCustomerSpend) * 100);
                    @endphp
                    <div class="py-2.5 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-600 font-bold text-[10px] flex items-center justify-center">
                                    {{ $idx + 1 }}
                                </span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $client->customer->name ?? 'Customer #' . $client->customer_id }}
                                </span>
                            </div>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $fmt0($spend) }} RWF
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-emerald-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">
                        No customer spending recorded in this period.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Executive Report Directory & Export Hub --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-5 shadow-2xs">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="folder-down" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Specialized Report Catalog & Export Hub</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Direct links to ledger statements, audits, and compliance documentation.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ route('reports.daily-collection', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               class="p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="calendar-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Daily Collections</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">End-of-day register balancing & export</p>
                </div>
            </a>

            <a href="{{ route('reports.customers.statement') }}"
               class="p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Customer Statements</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">Client account ledgers & billing audit</p>
                </div>
            </a>

            <a href="{{ route('reports.suppliers.statement') }}"
               class="p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="truck" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Supplier Statements</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">Vendor procurement balances & reconciliations</p>
                </div>
            </a>

            <a href="{{ route('reports.export.insights.pdf', array_merge(request()->query(), ['start_date' => $start, 'end_date' => $end])) }}"
               target="_blank"
               class="p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Executive Insights PDF</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">Formal executive management report</p>
                </div>
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // ---- Monthly Sales Chart ----
    const monthlyLabels = @json($months);
    const monthlyData   = @json($monthlySales);

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = isDark ? '#94A3B8' : '#64748B';

    const monthlyCtx = document.getElementById('monthlySalesChart')?.getContext('2d');
    if (monthlyCtx && monthlyLabels.length) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Sales (RWF)',
                    data: monthlyData,
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.08)',
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#4F46E5',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` Sales: ${Number(ctx.raw).toLocaleString()} RWF`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            font: { size: 11 },
                            callback: (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v)
                        }
                    }
                }
            }
        });
    }

    // ---- Revenue vs Expenses Chart ----
    const reMonths   = @json($revenueExpensesChart->pluck('month'));
    const reSales    = @json($revenueExpensesChart->pluck('sales'));
    const reExpenses = @json($revenueExpensesChart->pluck('expenses'));

    const reCtx = document.getElementById('revenueExpenseChart')?.getContext('2d');
    if (reCtx && reMonths.length) {
        new Chart(reCtx, {
            type: 'line',
            data: {
                labels: reMonths,
                datasets: [
                    {
                        label: 'Revenue',
                        data: reSales,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#10B981'
                    },
                    {
                        label: 'Purchases',
                        data: reExpenses,
                        borderColor: '#F43F5E',
                        backgroundColor: 'rgba(244, 63, 94, 0.06)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#F43F5E'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.dataset.label}: ${Number(ctx.raw).toLocaleString()} RWF`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            font: { size: 11 },
                            callback: (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v)
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endcannot
@endsection
