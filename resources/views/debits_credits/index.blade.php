@extends('layouts.app')
@section('title', 'Debits & Credits')

@section('content')
@php
    use Carbon\Carbon;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    // Quick date ranges
    $today  = Carbon::today()->toDateString();
    $last7  = Carbon::today()->subDays(6)->toDateString();
    $last30 = Carbon::today()->subDays(29)->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    // Page-only totals
    $pageCredits = $records->getCollection()->where('type', 'credit')->sum('amount');
    $pageDebits  = $records->getCollection()->where('type', 'debit')->sum('amount');
    $totalCount  = $records->total();
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="scale" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Debits & Credits Ledger</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($totalCount) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Dual-entry accounting ledger tracking customer adjustments, supplier balances, and credit reconciliations.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('transactions.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="banknote" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Transactions Journal</span>
            </a>

            @can('debits-credits.create')
                <a href="{{ route('debits-credits.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Ledger Entry</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @elseif($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-medium">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- KPI Summary Cards (Dashboard Grade) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- 1. Total Credits --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Credits</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($creditsTotal) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Credit inflow</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">Page: {{ $fmt0($pageCredits) }} RWF</span>
            </div>
        </div>

        {{-- 2. Total Debits --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Debits</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                    {{ $fmt0($debitsTotal) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Debit outflow</span>
                <span class="font-medium text-rose-600 dark:text-rose-400">Page: {{ $fmt0($pageDebits) }} RWF</span>
            </div>
        </div>

        {{-- 3. Net Balance --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Balance</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight font-mono {{ $net >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $net >= 0 ? '+' : '' }}{{ $fmt0($net) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Net Position</span>
                <span class="font-medium {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $net >= 0 ? 'Net Surplus' : 'Net Deficit' }}
                </span>
            </div>
        </div>

        {{-- 4. Ledger Activity --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ledger Entries</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($totalCount) }}
                    <span class="text-xs font-normal text-slate-400 font-sans">records</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Active Query Results</span>
                <span class="font-medium text-slate-600 dark:text-slate-300">{{ $records->count() }} on page</span>
            </div>
        </div>
    </div>

    {{-- Streamlined Filter Panel --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4 space-y-4">
        {{-- Quick Type & Date Filter Pills --}}
        <div class="flex flex-wrap items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Type:</span>

            @php
                $curType = request('type', '');
            @endphp

            <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ empty($curType) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>All Entries</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['type' => 'credit', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curType === 'credit' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Credits (IN)</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['type' => 'debit', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curType === 'debit' ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>Debits (OUT)</span>
            </a>

            <span class="text-slate-300 dark:text-slate-700 mx-1">|</span>

            {{-- Date Presets --}}
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Date:</span>
            <a href="{{ route('debits-credits.index', array_merge(request()->query(), ['from' => $today, 'to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                Today
            </a>
            <a href="{{ route('debits-credits.index', array_merge(request()->query(), ['from' => $last7, 'to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                Last 7d
            </a>
            <a href="{{ route('debits-credits.index', array_merge(request()->query(), ['from' => $last30, 'to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                Last 30d
            </a>
            <a href="{{ route('debits-credits.index', array_merge(request()->query(), ['from' => $monthS, 'to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                This Month
            </a>
        </div>

        <form method="GET" action="{{ route('debits-credits.index') }}" class="space-y-3">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">
                {{-- Search --}}
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search</label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Description, party, notes…"
                            class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>
                </div>

                {{-- Type select --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Entry Type</label>
                    <select name="type"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Types</option>
                        <option value="debit"  @selected(request('type') === 'debit')>Debits (OUT)</option>
                        <option value="credit" @selected(request('type') === 'credit')>Credits (IN)</option>
                    </select>
                </div>

                {{-- Customer --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Customer</label>
                    <select name="customer_id"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Customers</option>
                        @foreach(\App\Models\Customer::select('id','name')->orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Supplier --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Supplier</label>
                    <select name="supplier_id"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Suppliers</option>
                        @foreach(\App\Models\Supplier::select('id','name')->orderBy('name')->get() as $s)
                            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- From Date --}}
                <div class="md:col-span-1.5 sm:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">From</label>
                    <input type="date"
                           name="from"
                           value="{{ request('from') }}"
                           class="w-full py-2 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                </div>

                {{-- To Date --}}
                <div class="md:col-span-1.5 sm:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">To</label>
                    <input type="date"
                           name="to"
                           value="{{ request('to') }}"
                           class="w-full py-2 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                </div>
            </div>

            {{-- Action Row --}}
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <div class="text-xs text-slate-400">
                    Active ledger records: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $totalCount }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Apply Filter</span>
                    </button>
                    <a href="{{ route('debits-credits.index') }}"
                       title="Reset all filters"
                       class="inline-flex items-center justify-center p-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Ledger Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Date & ID</th>
                        <th class="px-4 py-3 text-center">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Customer / Supplier</th>
                        <th class="px-4 py-3">Recorded By</th>
                        <th class="px-4 py-3">Linked Transaction</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($records as $item)
                        @php
                            $isCredit = $item->type === 'credit';
                        @endphp

                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- 1. Date & ID --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                    #{{ $item->id }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $item->date ? Carbon::parse($item->date)->format('M j, Y') : '—' }}
                                </div>
                            </td>

                            {{-- 2. Type --}}
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($isCredit)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Credit (IN)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Debit (OUT)
                                    </span>
                                @endif
                            </td>

                            {{-- 3. Amount --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="font-bold font-mono text-sm {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-slate-100' }}">
                                    {{ $isCredit ? '+' : '-' }}{{ $fmt2($item->amount) }}
                                    <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                </div>
                            </td>

                            {{-- 4. Description --}}
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300 max-w-xs">
                                <span class="line-clamp-2" title="{{ $item->description }}">
                                    {{ $item->description ?: '—' }}
                                </span>
                            </td>

                            {{-- 5. Party (Customer or Supplier) --}}
                            <td class="px-4 py-3">
                                @if($item->customer)
                                    <div class="flex items-center gap-1.5 text-slate-800 dark:text-slate-200 font-medium">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $item->customer->name }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400">Customer</div>
                                @elseif($item->supplier)
                                    <div class="flex items-center gap-1.5 text-slate-800 dark:text-slate-200 font-medium">
                                        <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $item->supplier->name }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400">Supplier</div>
                                @else
                                    <span class="text-slate-400 font-mono italic">General Store</span>
                                @endif
                            </td>

                            {{-- 6. User --}}
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $item->user->name ?? 'System' }}</span>
                                </div>
                            </td>

                            {{-- 7. Linked Transaction --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($item->transaction_id)
                                    @can('transactions.view')
                                        <a href="{{ route('transactions.show', $item->transaction_id) }}"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 hover:bg-indigo-100 transition-colors">
                                            <i data-lucide="banknote" class="w-3 h-3"></i>
                                            <span>#{{ $item->transaction_id }}</span>
                                        </a>
                                    @else
                                        <span class="text-xs font-mono text-slate-500">#{{ $item->transaction_id }}</span>
                                    @endcan
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- 8. Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @canany(['debits-credits.edit', 'debits-credits.delete'])
                                    <div class="inline-flex items-center gap-1">
                                        @can('debits-credits.edit')
                                            <a href="{{ route('debits-credits.edit', $item->id) }}"
                                               title="Edit Ledger Entry"
                                               class="p-1.5 text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('debits-credits.delete')
                                            <button type="button"
                                                    title="Delete Entry"
                                                    class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors"
                                                    @click="$store.confirm.openWith($el.nextElementSibling)">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>

                                            <form action="{{ route('debits-credits.destroy', $item->id) }}"
                                                  method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endcan
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endcanany
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                        <i data-lucide="scale" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">No ledger entries found</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        No debit or credit records match your selected filter criteria.
                                    </p>
                                    @can('debits-credits.create')
                                        <a href="{{ route('debits-credits.create') }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors mt-3">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            <span>New Ledger Entry</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Table Summary Footer --}}
                @if($records->isNotEmpty())
                    <tfoot class="bg-slate-50/80 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <tr>
                            <td colspan="2" class="px-4 py-3 uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                                Page Totals:
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                                {{ $fmt2($pageCredits + $pageDebits) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                            </td>
                            <td colspan="5" class="px-4 py-3">
                                <div class="flex items-center gap-4 text-xs font-mono">
                                    <span class="text-emerald-600 dark:text-emerald-400">
                                        Credits: +{{ $fmt0($pageCredits) }} RWF
                                    </span>
                                    <span class="text-slate-300 dark:text-slate-600">|</span>
                                    <span class="text-rose-600 dark:text-rose-400">
                                        Debits: -{{ $fmt0($pageDebits) }} RWF
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if(method_exists($records, 'links') && $records->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Showing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $records->firstItem() ?? 0 }}</span>
                    to <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $records->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fmt0($records->total()) }}</span> entries
                </div>
                <div>
                    {{ $records->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

{{-- Global Delete Modal --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4"
     @keydown.escape.window="$store.confirm.close()">
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl p-6 w-full max-w-md transform transition-all">
        <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">Delete Ledger Entry</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5 leading-relaxed">
            Are you sure you want to delete this debit/credit entry? This will adjust accounting balances.
        </p>
        <div class="flex justify-end gap-2.5">
            <button type="button"
                    class="px-3.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                    @click="$store.confirm.close()">
                Cancel
            </button>
            @can('debits-credits.delete')
                <button type="button"
                        class="px-3.5 py-1.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors"
                        @click="$store.confirm.confirm()">
                    Delete Entry
                </button>
            @endcan
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
});

document.addEventListener('alpine:init', () => {
    if (!Alpine.store('confirm')) {
        Alpine.store('confirm', {
            open: false,
            submitEl: null,
            openWith(form) {
                this.submitEl = form;
                this.open = true;
            },
            close() {
                this.open = false;
                this.submitEl = null;
            },
            confirm() {
                if (this.submitEl) {
                    this.submitEl.submit();
                }
                this.close();
            },
        });
    }
});
</script>
@endpush
@endsection
