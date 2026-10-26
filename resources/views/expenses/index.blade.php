@extends('layouts.app')
@section('title', 'Expenses')

@section('content')
@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $methods = ['cash', 'bank', 'momo'];

    // Current page collection
    $rows = $expenses->getCollection();
    $pageTotal = $rows->sum(fn($e) => (float)($e->amount ?? 0));

    // Per-method breakdown on page
    $byMethod = collect($methods)->mapWithKeys(function ($m) use ($rows) {
        $filtered = $rows->filter(fn($e) => strtolower($e->method ?? '') === $m);
        return [$m => [
            'count'  => $filtered->count(),
            'amount' => (float) $filtered->sum('amount'),
        ]];
    });

    $totalExpenses = $expenses->total();
@endphp

<div
    x-data="{
        showDel: false, delAction: '', delName: '',
        openDel(action, name){ this.delAction = action; this.delName = name; this.showDel = true; },
        closeDel(){ this.showDel = false; this.delAction = ''; this.delName = ''; }
    }"
    class="max-w-7xl mx-auto space-y-6"
>

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Expenses Management</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($totalExpenses) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Record and audit operational expenses, supplier overheads, and cash outflows.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('expenses.create')
                <a href="{{ route('expenses.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Expense</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- KPI Summary Cards (Dashboard Grade) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- 1. Total Page Expenditure --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Page Expenditure</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($pageTotal) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Total disbursements</span>
                <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $rows->count() }} records on page</span>
            </div>
        </div>

        {{-- 2. Cash Disbursements --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cash Payments</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="banknote" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($byMethod['cash']['amount'] ?? 0) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Petty cash out</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $byMethod['cash']['count'] ?? 0 }} transactions</span>
            </div>
        </div>

        {{-- 3. Bank Transfers --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bank Transfers</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="building-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-sky-600 dark:text-sky-400 font-mono">
                    {{ $fmt0($byMethod['bank']['amount'] ?? 0) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Account wires</span>
                <span class="font-medium text-sky-600 dark:text-sky-400">{{ $byMethod['bank']['count'] ?? 0 }} transactions</span>
            </div>
        </div>

        {{-- 4. Mobile Money (MoMo) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Mobile Money</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="smartphone" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400 font-mono">
                    {{ $fmt0($byMethod['momo']['amount'] ?? 0) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>MoMo out</span>
                <span class="font-medium text-purple-600 dark:text-purple-400">{{ $byMethod['momo']['count'] ?? 0 }} transactions</span>
            </div>
        </div>
    </div>

    {{-- Streamlined Filter Panel --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4 space-y-4">
        {{-- Quick Method Filter Pills --}}
        <div class="flex flex-wrap items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Method:</span>

            @php
                $curMethod = request('method', '');
            @endphp

            <a href="{{ request()->fullUrlWithQuery(['method' => null, 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ empty($curMethod) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>All Methods</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['method' => 'cash', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curMethod === 'cash' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="banknote" class="w-3 h-3 text-emerald-500"></i>
                <span>Cash</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['method' => 'bank', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curMethod === 'bank' ? 'bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="building-2" class="w-3 h-3 text-sky-500"></i>
                <span>Bank</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['method' => 'momo', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curMethod === 'momo' ? 'bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <i data-lucide="smartphone" class="w-3 h-3 text-purple-500"></i>
                <span>MoMo</span>
            </a>
        </div>

        <form method="GET" action="{{ route('expenses.index') }}" class="space-y-3">
            @if(request('method'))
                <input type="hidden" name="method" value="{{ request('method') }}">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Expenses</label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Reference, note, supplier, category…"
                            class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>
                </div>

                {{-- Category --}}
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Category</label>
                    <select name="category_id"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)request('category_id') === (string)$c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Supplier --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Supplier / Payee</label>
                    <select name="supplier_id"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Payees</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string)request('supplier_id') === (string)$s->id)>
                                {{ $s->name }}
                            </option>
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
                    Displaying <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $totalExpenses }}</span> recorded expenses
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Apply Filter</span>
                    </button>
                    <a href="{{ route('expenses.index') }}"
                       title="Reset all filters"
                       class="inline-flex items-center justify-center p-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Expenses Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Expense # / Date</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Supplier / Payee</th>
                        <th class="px-4 py-3">Payment Method</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Note / Memo</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($expenses as $e)
                        @php
                            $method = strtolower($e->method ?? 'cash');
                            $methodPalette = match($method) {
                                'cash' => [
                                    'badge' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20',
                                    'icon'  => 'banknote'
                                ],
                                'bank' => [
                                    'badge' => 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border border-sky-500/20',
                                    'icon'  => 'building-2'
                                ],
                                'momo', 'mobile_money', 'mobile' => [
                                    'badge' => 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20',
                                    'icon'  => 'smartphone'
                                ],
                                default => [
                                    'badge' => 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-500/20',
                                    'icon'  => 'credit-card'
                                ]
                            };
                        @endphp

                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- 1. ID & Date --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                    #{{ $e->id }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ optional($e->date)->format('M j, Y') ?? '—' }}
                                </div>
                            </td>

                            {{-- 2. Category --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    <span>{{ $e->category->name ?? 'General' }}</span>
                                </div>
                            </td>

                            {{-- 3. Supplier / Payee --}}
                            <td class="px-4 py-3">
                                @if($e->supplier)
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $e->supplier->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 font-mono italic">Internal / Store</span>
                                @endif
                            </td>

                            {{-- 4. Payment Method --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $methodPalette['badge'] }}">
                                    <i data-lucide="{{ $methodPalette['icon'] }}" class="w-3 h-3"></i>
                                    <span>{{ strtoupper($method) }}</span>
                                </div>
                            </td>

                            {{-- 5. Reference --}}
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400 font-mono whitespace-nowrap">
                                {{ $e->reference ?: '—' }}
                            </td>

                            {{-- 6. Amount --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-slate-100 font-mono text-sm">
                                    {{ $fmt2($e->amount) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                </div>
                            </td>

                            {{-- 7. Note / Memo --}}
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400 max-w-xs">
                                <span class="line-clamp-2" title="{{ $e->note }}">
                                    {{ $e->note ?: '—' }}
                                </span>
                            </td>

                            {{-- 8. Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    @can('expenses.view')
                                        <a href="{{ route('expenses.show', $e) }}"
                                           title="View Expense Details"
                                           class="p-1.5 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg transition-colors">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                    @endcan

                                    @can('expenses.edit')
                                        <a href="{{ route('expenses.edit', $e) }}"
                                           title="Edit Expense"
                                           class="p-1.5 text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                    @endcan

                                    @can('expenses.delete')
                                        <button type="button"
                                                title="Delete Expense"
                                                @click="openDel('{{ route('expenses.destroy', $e) }}', 'Expense #{{ $e->id }} ({{ $fmt0($e->amount) }} RWF)')"
                                                class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                        <i data-lucide="wallet" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">No expenses found</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        No expenditure records match your active query or filters. Try clearing dates or category filters.
                                    </p>
                                    @can('expenses.create')
                                        <a href="{{ route('expenses.create') }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors mt-3">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            <span>Record Expense</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Table Summary Footer --}}
                @if($rows->isNotEmpty())
                    <tfoot class="bg-slate-50/80 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                                Page Total ({{ $rows->count() }} items):
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                                {{ $fmt2($pageTotal) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if(method_exists($expenses, 'links') && $expenses->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Showing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $expenses->firstItem() ?? 0 }}</span>
                    to <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $expenses->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fmt0($expenses->total()) }}</span> expenses
                </div>
                <div>
                    {{ $expenses->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    @can('expenses.delete')
        <div x-cloak
             x-show="showDel"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4"
             @keydown.escape.window="closeDel()">
            <div @click.outside="closeDel()"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl p-6 w-full max-w-md transform transition-all">
                <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">Delete Expense</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-5 leading-relaxed">
                    Are you sure you want to delete <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="delName"></span>?
                    This will permanently remove this record from cash flow accounting.
                </p>
                <div class="flex justify-end gap-2.5">
                    <button type="button"
                            @click="closeDel()"
                            class="px-3.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <form :action="delAction" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-3.5 py-1.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors">
                            Delete Expense
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endcan

</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
