@extends('layouts.app')
@section('title', 'Inter-Company Item Loans')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    use Carbon\Carbon;

    $filters = $filters ?? [];
    $dir     = $filters['direction'] ?? '';
    $status  = $filters['status'] ?? '';
    $overdue = (bool)($filters['overdue'] ?? false);

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="handshake" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Inter-Company Item Lending</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($loans->total()) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Track cross-company inventory lending, borrowed items, return progress, due dates, and stock movements.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('partner-companies.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Manage Partners</span>
            </a>

            <a href="{{ route('item-loans.create') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Item Loan</span>
            </a>
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
        {{-- Total Item Loans --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Transfers</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="handshake" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($stats['total_loans'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">All item loan records</div>
            </div>
        </div>

        {{-- Active Lent (We Lent - Stock OUT) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Lent</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($stats['active_lent'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">We lent to partners (Stock OUT)</div>
            </div>
        </div>

        {{-- Active Borrowed (We Borrowed - Stock IN) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Borrowed</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400 font-mono">
                    {{ $fmt0($stats['active_borrowed'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Borrowed from partners (Stock IN)</div>
            </div>
        </div>

        {{-- Fully Returned --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fully Returned</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-sky-600 dark:text-sky-400 font-mono">
                    {{ $fmt0($stats['returned_count'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Completely settled transfers</div>
            </div>
        </div>

        {{-- Overdue --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Overdue Items</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                    {{ $fmt0($stats['overdue_count'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Unsettled past due date</div>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4">
        
        {{-- Quick Segmented Pills --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            {{-- Direction Pills --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="repeat" class="w-3.5 h-3.5"></i> Flow:
                </span>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'direction'), ['direction' => null])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ !$dir ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All Flows
                </a>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'direction'), ['direction' => 'given'])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $dir === 'given' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                    <span>We LENT (Given)</span>
                </a>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'direction'), ['direction' => 'taken'])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $dir === 'taken' ? 'bg-purple-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <i data-lucide="arrow-down-left" class="w-3 h-3"></i>
                    <span>We BORROWED (Taken)</span>
                </a>

                <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>

                {{-- Status Pills --}}
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i> Status:
                </span>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'status', 'overdue'), ['status' => null, 'overdue' => null])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ !$status && !$overdue ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All
                </a>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'status', 'overdue'), ['status' => 'pending', 'overdue' => null])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $status === 'pending' && !$overdue ? 'bg-amber-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span>Pending</span>
                </a>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'status', 'overdue'), ['status' => 'partial', 'overdue' => null])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $status === 'partial' && !$overdue ? 'bg-sky-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    <span>Partial</span>
                </a>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'status', 'overdue'), ['status' => 'returned', 'overdue' => null])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $status === 'returned' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Returned</span>
                </a>
                <a href="{{ route('item-loans.index', array_merge(request()->except('page', 'overdue'), ['overdue' => 1])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $overdue ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 border border-rose-200/80 dark:border-rose-800/60 hover:bg-rose-100 dark:hover:bg-rose-900/40' }}">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    <span>Overdue Only</span>
                </a>
            </div>
        </div>

        {{-- Filter Inputs Form --}}
        <form method="GET" action="{{ route('item-loans.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Search Item or Partner
                </label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Item name, partner company..."
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            {{-- Direction Select --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Direction
                </label>
                <select name="direction" class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <option value="">All Directions</option>
                    <option value="given" @selected($dir === 'given')>We LENT (Given)</option>
                    <option value="taken" @selected($dir === 'taken')>We BORROWED (Taken)</option>
                </select>
            </div>

            {{-- From Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    From Date
                </label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            {{-- To Date --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    To Date
                </label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('item-loans.index') }}"
                   class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                   title="Reset All Filters">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            {{-- Hidden preserve fields if set via pills --}}
            @if(!empty($filters['status']))
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif
            @if($overdue)
                <input type="hidden" name="overdue" value="1">
            @endif
        </form>
    </div>

    {{-- Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        
        {{-- Table Toolbar Header --}}
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <i data-lucide="boxes" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Inter-Company Lending Register
                </h2>
                <span class="text-xs text-slate-400">
                    (Page {{ $loans->currentPage() }} of {{ $loans->lastPage() }})
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                <span>Showing <strong class="font-mono text-slate-900 dark:text-slate-100">{{ $loans->count() }}</strong> items on this page</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Date / Ref</th>
                        <th class="py-3 px-4">Direction</th>
                        <th class="py-3 px-4">Partner Company</th>
                        <th class="py-3 px-4">Lent Item / Catalog</th>
                        <th class="py-3 px-4 text-right">Total Qty</th>
                        <th class="py-3 px-4 text-right">Returned</th>
                        <th class="py-3 px-4 text-right">Remaining</th>
                        <th class="py-3 px-4">Due Date & Urgency</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($loans as $loan)
                        @php
                            $qty       = (float) $loan->quantity;
                            $ret       = (float) $loan->quantity_returned;
                            $remaining = max($qty - $ret, 0);
                            $progress  = $qty > 0 ? (int) round(($ret / $qty) * 100) : 0;
                            if ($progress > 100) $progress = 100;

                            $due       = $loan->due_date ? Carbon::parse($loan->due_date) : null;
                            $isOverdue = $due && $remaining > 0 && $due->isPast();
                            $daysLeft  = $due ? Carbon::now()->startOfDay()->diffInDays($due, false) : null;
                        @endphp

                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors {{ $isOverdue ? 'bg-rose-500/5' : '' }}">
                            {{-- Date & Ref --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                    <a href="{{ route('item-loans.show', $loan) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        #{{ $loan->id }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ optional($loan->loan_date)->format('M d, Y') ?? '—' }}
                                </div>
                            </td>

                            {{-- Direction --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if ($loan->direction === 'given')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span>We LENT</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
                                        <i data-lucide="arrow-down-left" class="w-3.5 h-3.5 text-purple-500"></i>
                                        <span>We BORROWED</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Partner --}}
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                            {{ $loan->partner->name ?? 'External Partner' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">Partner Entity</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Item & Linked Catalog Product --}}
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $loan->item_name }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if($loan->product)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-1.5 py-0.5 rounded border border-indigo-200/60 dark:border-indigo-800/40">
                                            <i data-lucide="tag" class="w-2.5 h-2.5"></i>
                                            <span>{{ $loan->product->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                            Manual Item
                                        </span>
                                    @endif
                                    @if ($loan->unit)
                                        <span class="text-[10px] text-slate-400">({{ $loan->unit }})</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Total Quantity --}}
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ $fmt2($loan->quantity) }}
                                <span class="text-[10px] font-sans font-normal text-slate-400 ml-0.5">{{ $loan->unit }}</span>
                            </td>

                            {{-- Returned & Progress --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5 font-mono">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $fmt2($ret) }}</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-sans font-semibold {{ $progress >= 100 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                                        {{ $progress }}%
                                    </span>
                                </div>
                                <div class="mt-1 w-24 ml-auto bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="{{ $remaining <= 0 ? 'bg-emerald-500' : 'bg-indigo-600' }} h-1.5 rounded-full transition-all duration-300"
                                         style="width: {{ $progress }}%"></div>
                                </div>
                            </td>

                            {{-- Remaining to Return --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($remaining <= 0)
                                    <span class="inline-flex items-center gap-1 font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        <span>0.00 {{ $loan->unit }}</span>
                                    </span>
                                @else
                                    <span class="font-mono font-bold text-rose-600 dark:text-rose-400">
                                        {{ $fmt2($remaining) }}
                                    </span>
                                    <span class="text-[10px] font-sans text-slate-400 ml-0.5">{{ $loan->unit }}</span>
                                @endif
                            </td>

                            {{-- Due Date & Alert --}}
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
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">Completed</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @php
                                    $statusClasses = [
                                        'pending'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60 dot-bg-amber-500',
                                        'partial'  => 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800/60 dot-bg-sky-500',
                                        'returned' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60 dot-bg-emerald-500',
                                        'overdue'  => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60 dot-bg-rose-500',
                                    ][$loan->status] ?? 'bg-slate-50 text-slate-700 border-slate-200 dot-bg-slate-400';
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $statusClasses }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ str_contains($statusClasses, 'dot-bg-emerald') ? 'bg-emerald-500' : (str_contains($statusClasses, 'dot-bg-sky') ? 'bg-sky-500' : (str_contains($statusClasses, 'dot-bg-rose') ? 'bg-rose-500' : 'bg-amber-500')) }}"></span>
                                    <span>{{ ucfirst($loan->status) }}</span>
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- View Details & Returns --}}
                                    <a href="{{ route('item-loans.show', $loan) }}"
                                       class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                       title="View Details & Return History">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    {{-- Record Return Shortcut --}}
                                    @if($remaining > 0)
                                        <a href="{{ route('item-loans.show', $loan) }}#return-section"
                                           class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors"
                                           title="Record Item Return">
                                            <i data-lucide="corner-down-left" class="w-3 h-3"></i>
                                            <span>Return</span>
                                        </a>
                                    @endif

                                    {{-- Edit Loan --}}
                                    <a href="{{ route('item-loans.edit', $loan) }}"
                                       class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                       title="Edit Loan">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>

                                    {{-- Delete Loan --}}
                                    <form action="{{ route('item-loans.destroy', $loan) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                                title="Delete Loan"
                                                @click="$store.confirm.openWith($el.closest('form'), '#{{ $loan->id }} ({{ $loan->item_name }})')">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center">
                                <div class="max-w-sm mx-auto flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                                        <i data-lucide="handshake" class="w-6 h-6"></i>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No item loans recorded</h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        No cross-company item loans found matching your search filters.
                                    </p>
                                    <a href="{{ route('item-loans.create') }}"
                                       class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Record First Item Loan</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
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
                    Confirm Item Loan Deletion
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Are you sure you want to permanently delete item loan <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="$store.confirm.itemTitle || ''"></span>? Any linked return lines will also be erased.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2.5">
            <button type="button"
                    class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                    @click="$store.confirm.open = false">
                Cancel
            </button>
            <button type="button"
                    class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors"
                    @click="$store.confirm.submitEl?.submit(); $store.confirm.open = false;">
                Yes, Delete Loan
            </button>
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
