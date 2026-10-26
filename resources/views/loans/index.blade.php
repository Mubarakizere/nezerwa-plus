@extends('layouts.app')
@section('title', 'Loans Overview')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    // Compute page totals safely (works with paginator)
    $collection = $loans instanceof \Illuminate\Pagination\LengthAwarePaginator ? $loans->getCollection() : collect($loans);
    $pageAmountTotal   = $collection->sum(fn($l) => (float)($l->amount ?? 0));
    $pagePaidTotal     = $collection->sum(function ($l) {
        if (property_exists($l, 'payments_sum_amount')) return (float) $l->payments_sum_amount;
        try { return (float) $l->payments()->sum('amount'); } catch (\Throwable $e) { return 0; }
    });
    $pageRemainingTotal = max($pageAmountTotal - $pagePaidTotal, 0);

    $currentType    = request('type');
    $currentStatus  = request('status');
    $currentPeriod  = request('period');
    $isOverdueFilter = request()->boolean('overdue');
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Loans & Credit Management</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($loans->total()) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Track receivables (loans given to customers), payables (borrowed from suppliers), repayment schedules, and overdue balances.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @canany(['loans.export', 'loans.view'])
                <a href="{{ route('loans.export.csv', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    <span>Export CSV</span>
                </a>

                <a href="{{ route('loans.export.pdf', request()->query()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>PDF Report</span>
                </a>
            @endcanany

            @can('loans.create')
                <a href="{{ route('loans.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Loan</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- KPI Cards (Dashboard Grade) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Total Loan Portfolio --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Portfolio</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($stats['total_loans'] ?? 0) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total principal value</div>
            </div>
        </div>

        {{-- Repaid / Settled Loans --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Settled & Paid</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($stats['paid_loans'] ?? 0) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Principal settled</div>
            </div>
        </div>

        {{-- Outstanding / Pending Debt --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Debt</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                    {{ $fmt0($stats['pending_loans'] ?? 0) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Unsettled principal</div>
            </div>
        </div>

        {{-- Loans Given (Receivables) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Loans Given</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-sky-600 dark:text-sky-400 font-mono">
                    {{ $fmt0($stats['count_given'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Receivables from customers</div>
            </div>
        </div>

        {{-- Loans Taken (Payables) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Loans Taken</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400 font-mono">
                    {{ $fmt0($stats['count_taken'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Payables to suppliers</div>
            </div>
        </div>
    </div>

    {{-- Filters & Search Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4">
        
        {{-- Quick Filter Pills Row --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            {{-- Type & Status Tabs --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="layers" class="w-3.5 h-3.5"></i> Type:
                </span>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'type'), ['type' => null])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ !$currentType ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All Types
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'type'), ['type' => 'given'])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentType === 'given' ? 'bg-sky-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                    <span>Given (Receivables)</span>
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'type'), ['type' => 'taken'])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentType === 'taken' ? 'bg-purple-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <i data-lucide="arrow-down-left" class="w-3 h-3"></i>
                    <span>Taken (Payables)</span>
                </a>

                <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>

                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i> Status:
                </span>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'status', 'overdue'), ['status' => null, 'overdue' => null])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ !$currentStatus && !$isOverdueFilter ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'status', 'overdue'), ['status' => 'pending', 'overdue' => null])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentStatus === 'pending' && !$isOverdueFilter ? 'bg-amber-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span>Pending</span>
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'status', 'overdue'), ['status' => 'paid', 'overdue' => null])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentStatus === 'paid' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Paid</span>
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'overdue'), ['overdue' => 1])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $isOverdueFilter ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 border border-rose-200/80 dark:border-rose-800/60 hover:bg-rose-100 dark:hover:bg-rose-900/40' }}">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    <span>Overdue Only</span>
                </a>
            </div>

            {{-- Period Presets --}}
            <div class="flex flex-wrap items-center gap-1">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Period:
                </span>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'period', 'from', 'to'), ['period' => null])) }}"
                   class="px-2 py-0.5 rounded text-xs transition-colors {{ !$currentPeriod && !request('from') && !request('to') ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200' }}">
                    All Time
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'period', 'from', 'to'), ['period' => 'today'])) }}"
                   class="px-2 py-0.5 rounded text-xs transition-colors {{ $currentPeriod === 'today' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200' }}">
                    Today
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'period', 'from', 'to'), ['period' => 'week'])) }}"
                   class="px-2 py-0.5 rounded text-xs transition-colors {{ $currentPeriod === 'week' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200' }}">
                    This Week
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'period', 'from', 'to'), ['period' => 'month'])) }}"
                   class="px-2 py-0.5 rounded text-xs transition-colors {{ $currentPeriod === 'month' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200' }}">
                    This Month
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page', 'period', 'from', 'to'), ['period' => 'year'])) }}"
                   class="px-2 py-0.5 rounded text-xs transition-colors {{ $currentPeriod === 'year' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-200' }}">
                    This Year
                </a>
            </div>
        </div>

        {{-- Detailed Form --}}
        <form method="GET" action="{{ route('loans.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            @if(request('period'))
                <input type="hidden" name="period" value="{{ request('period') }}">
            @endif

            {{-- Free Search --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Search Notes or ID
                </label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by #ID or notes..."
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            {{-- Party Search --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Party Name
                </label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="party" value="{{ request('party') }}" placeholder="Customer / Supplier..."
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            {{-- From Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    From Date
                </label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            {{-- To Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    To Date
                </label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('loans.index') }}"
                   class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                   title="Reset All Filters">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            {{-- Hidden preserve fields if set via quick pills --}}
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('overdue'))
                <input type="hidden" name="overdue" value="1">
            @endif
        </form>
    </div>

    {{-- Main Loans Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        
        {{-- Table Toolbar Header --}}
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <i data-lucide="receipt" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Loan Records Journal
                </h2>
                <span class="text-xs text-slate-400">
                    (Page {{ $loans->currentPage() }} of {{ $loans->lastPage() }})
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                <span>Page Total: <strong class="font-mono text-slate-900 dark:text-slate-100">{{ $fmt0($pageAmountTotal) }} RWF</strong></span>
                <span class="text-slate-300 dark:text-slate-700">|</span>
                <span>Remaining: <strong class="font-mono text-rose-600 dark:text-rose-400">{{ $fmt0($pageRemainingTotal) }} RWF</strong></span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4"># ID / Date</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Counterparty</th>
                        <th class="py-3 px-4 text-right">Principal</th>
                        <th class="py-3 px-4 text-right">Paid & Progress</th>
                        <th class="py-3 px-4 text-right">Remaining Debt</th>
                        <th class="py-3 px-4">Due Date & Terms</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($loans as $loan)
                        @php
                            $paid = property_exists($loan, 'payments_sum_amount')
                                    ? (float) $loan->payments_sum_amount
                                    : (float) $loan->payments()->sum('amount');

                            $remaining = round(max(($loan->amount ?? 0) - $paid, 0), 2);
                            $progress  = ($loan->amount ?? 0) > 0 ? (int) round(($paid / $loan->amount) * 100) : 0;
                            if ($progress > 100) $progress = 100;

                            $due       = $loan->due_date ? Carbon::parse($loan->due_date) : null;
                            $isOverdue = $due && $remaining > 0 && $due->isPast();
                            $daysLeft  = $due ? Carbon::now()->startOfDay()->diffInDays($due, false) : null;
                        @endphp

                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors {{ $isOverdue ? 'bg-rose-500/5' : '' }}">
                            {{-- ID & Date --}}
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                    <a href="{{ route('loans.show', $loan) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        #{{ $loan->id }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ optional($loan->loan_date)->format('M d, Y') ?? '—' }}
                                </div>
                            </td>

                            {{-- Type --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($loan->type === 'given')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">
                                        <i data-lucide="arrow-up-right" class="w-3 h-3 text-sky-500"></i>
                                        <span>Given (Receivable)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
                                        <i data-lucide="arrow-down-left" class="w-3 h-3 text-purple-500"></i>
                                        <span>Taken (Payable)</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Counterparty --}}
                            <td class="py-3 px-4">
                                @if ($loan->type === 'given')
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                                {{ $loan->customer->name ?? 'Direct Client' }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 uppercase tracking-wider">Customer</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                                {{ $loan->supplier->name ?? 'Direct Supplier' }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 uppercase tracking-wider">Supplier / Lender</div>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            {{-- Principal Amount --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ $fmt0($loan->amount) }}
                                </span>
                                <span class="text-[10px] font-medium text-slate-400 ml-0.5">RWF</span>
                            </td>

                            {{-- Paid & Progress --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5 font-mono">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $fmt0($paid) }}</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-sans font-semibold {{ $progress >= 100 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                                        {{ $progress }}%
                                    </span>
                                </div>
                                <div class="mt-1 w-28 ml-auto bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="{{ $remaining <= 0 ? 'bg-emerald-500' : 'bg-indigo-600' }} h-1.5 rounded-full transition-all duration-300"
                                         style="width: {{ $progress }}%"></div>
                                </div>
                            </td>

                            {{-- Remaining Debt --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($remaining <= 0)
                                    <span class="inline-flex items-center gap-1 font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        <span>0 RWF</span>
                                    </span>
                                @else
                                    <span class="font-mono font-bold text-rose-600 dark:text-rose-400 text-sm">
                                        {{ $fmt0($remaining) }}
                                    </span>
                                    <span class="text-[10px] font-medium text-slate-400 ml-0.5">RWF</span>
                                @endif
                            </td>

                            {{-- Due Date & Alerts --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="text-slate-700 dark:text-slate-300 font-medium">
                                    {{ optional($loan->due_date)->format('M d, Y') ?? '—' }}
                                </div>
                                @if($due && $remaining > 0)
                                    @if($isOverdue)
                                        <span class="inline-flex items-center gap-1 mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/20">
                                            <i data-lucide="alert-triangle" class="w-3 h-3 text-rose-500"></i>
                                            <span>Overdue {{ abs($daysLeft) }}d</span>
                                        </span>
                                    @elseif(!is_null($daysLeft))
                                        <span class="inline-flex items-center gap-1 mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                            <i data-lucide="clock" class="w-3 h-3 text-amber-500"></i>
                                            <span>Due in {{ $daysLeft }}d</span>
                                        </span>
                                    @endif
                                @elseif($remaining <= 0)
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">Fully Settled</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($loan->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Paid</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Pending</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- View Details --}}
                                    @can('loans.view')
                                        <a href="{{ route('loans.show', $loan) }}"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                           title="View Details">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                    @endcan

                                    {{-- Add Payment --}}
                                    @if($loan->status !== 'paid')
                                        @can('loans.view')
                                            <a href="{{ route('loan-payments.create', $loan) }}"
                                               class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors"
                                               title="Record Payment">
                                                <i data-lucide="plus" class="w-3 h-3"></i>
                                                <span>Pay</span>
                                            </a>
                                        @endcan
                                    @endif

                                    {{-- Edit --}}
                                    @can('loans.edit')
                                        <a href="{{ route('loans.edit', $loan) }}"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                           title="Edit Loan">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                    @endcan

                                    {{-- Delete --}}
                                    @can('loans.delete')
                                        <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                                    title="Delete Loan"
                                                    @click="$store.confirm.openWith($el.closest('form'), '#{{ $loan->id }} ({{ $loan->type === 'given' ? ($loan->customer->name ?? 'Customer') : ($loan->supplier->name ?? 'Supplier') }})')">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center">
                                <div class="max-w-sm mx-auto flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                                        <i data-lucide="wallet" class="w-6 h-6"></i>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No loans found</h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Try adjusting your search filters or record a new loan entry.
                                    </p>
                                    @can('loans.create')
                                        <a href="{{ route('loans.create') }}"
                                           class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            <span>Create First Loan</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Page Summary Footer --}}
                @if($collection->count() > 0)
                    <tfoot class="bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200/80 dark:border-slate-800 font-semibold text-xs">
                        <tr>
                            <td colspan="3" class="py-3 px-4 text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px]">
                                Current Page Totals ({{ $collection->count() }} rows)
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-900 dark:text-slate-100 font-bold whitespace-nowrap">
                                {{ $fmt0($pageAmountTotal) }} <span class="text-[10px] text-slate-400 font-sans">RWF</span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-800 dark:text-slate-200 font-bold whitespace-nowrap">
                                {{ $fmt0($pagePaidTotal) }} <span class="text-[10px] text-slate-400 font-sans">RWF</span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold whitespace-nowrap {{ $pageRemainingTotal > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $fmt0($pageRemainingTotal) }} <span class="text-[10px] text-slate-400 font-sans">RWF</span>
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination --}}
        @if($loans instanceof \Illuminate\Pagination\LengthAwarePaginator && $loans->hasPages())
            <div class="px-5 py-3 border-t border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900">
                {{ $loans->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Global Delete Confirmation Modal --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
     @keydown.escape.window="$store.confirm.open = false">
    <div @click.outside="$store.confirm.open = false"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-md transform transition-all">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                    Confirm Loan Deletion
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Are you sure you want to permanently delete loan <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="$store.confirm.itemTitle || ''"></span>? All associated payment history will also be erased. This action cannot be reversed.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2.5">
            <button type="button"
                    class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                    @click="$store.confirm.open = false">
                Cancel
            </button>
            @can('loans.delete')
                <button type="button"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors"
                        @click="$store.confirm.submitEl?.submit(); $store.confirm.open = false;">
                    Yes, Delete Loan
                </button>
            @endcan
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        submitEl: null,
        itemTitle: '',
        openWith(form, title = '') {
            this.submitEl = form;
            this.itemTitle = title;
            this.open = true;
        }
    });
});
</script>
@endpush
@endsection
