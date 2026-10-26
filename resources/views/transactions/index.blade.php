@extends('layouts.app')
@section('title', 'Transactions')

@section('content')
@php
    use Carbon\Carbon;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $today  = Carbon::today()->toDateString();
    $last7  = Carbon::today()->subDays(6)->toDateString();
    $last30 = Carbon::today()->subDays(29)->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    $hasRunning = optional($transactions->first())->running_balance !== null;
    $netCash = (float)$totalCredits - (float)$totalDebits;
    $totalCount = $transactions->total();
@endphp

@cannot('transactions.view')
    <div class="max-w-3xl mx-auto py-10">
        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-6 h-6 text-amber-500 mt-0.5"></i>
                <div>
                    <h1 class="text-base font-semibold text-amber-900 dark:text-amber-200">
                        You don’t have permission to view transactions.
                    </h1>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact an administrator if you require access to financial audit records.
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
                    <i data-lucide="banknote" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Financial Transactions</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($totalCount) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Real-time financial journal, cash drawers, transfers, owner withdrawals, and payment channel reconciliations.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('transactions.create')
                <a href="{{ route('transactions.withdrawal') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-lg shadow-2xs hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Send to Boss</span>
                </a>

                <a href="{{ route('transactions.transfer') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="arrow-right-left" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Internal Transfer</span>
                </a>

                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Transaction</span>
                </a>
            @endcan

            <a href="{{ route('transactions.export.csv', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                <span>Export CSV</span>
            </a>

            <a href="{{ route('transactions.export.pdf', request()->query()) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Export PDF</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @elseif(session('error'))
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- KPI Summary Cards (Dashboard Grade) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- 1. Total Credits --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Credits (IN)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($totalCredits) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Inflow / Income</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">Page: {{ $fmt0($pageCredits) }} RWF</span>
            </div>
        </div>

        {{-- 2. Total Debits --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Debits (OUT)</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                    {{ $fmt0($totalDebits) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Outflow / Expenses</span>
                <span class="font-medium text-rose-600 dark:text-rose-400">Page: {{ $fmt0($pageDebits) }} RWF</span>
            </div>
        </div>

        {{-- 3. Net Cash Flow P&L --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Cash Flow</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight font-mono {{ $netCash >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $netCash >= 0 ? '+' : '' }}{{ $fmt0($netCash) }}
                    <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Filtered Cash P&L</span>
                <span class="font-medium {{ $netCash >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $netCash >= 0 ? 'Net Surplus' : 'Net Deficit' }}
                </span>
            </div>
        </div>

        {{-- 4. Activity Records --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Journal Activity</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($totalCount) }}
                    <span class="text-xs font-normal text-slate-400 font-sans">events</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Active Query Results</span>
                <span class="font-medium text-slate-600 dark:text-slate-300">{{ $transactions->count() }} on page</span>
            </div>
        </div>
    </div>

    {{-- Live Channel Balances Strip --}}
    @if(isset($channelBalances) && count($channelBalances) > 0)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="layers" class="w-4 h-4 text-indigo-500"></i>
                    <span>Real-Time Channel Balances (All-Time Net)</span>
                </h3>
                <span class="text-[11px] text-slate-400">Live reconciliations</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach($channelBalances as $name => $bal)
                    @php
                        $isNegative = $bal < 0;
                        $chSlug = strtolower(str_replace(' ', '', $name));
                    @endphp
                    <div class="p-3 rounded-lg border {{ $isNegative ? 'border-rose-200/80 dark:border-rose-900/40 bg-rose-50/30 dark:bg-rose-950/20' : 'border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/60' }}">
                        <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center justify-between">
                            <span>{{ $name }}</span>
                            <span class="w-1.5 h-1.5 rounded-full {{ $isNegative ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                        </div>
                        <div class="text-sm font-bold font-mono mt-1 {{ $isNegative ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">
                            {{ number_format($bal, 0) }} <span class="text-[10px] font-normal text-slate-400">RWF</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Streamlined Filter Panel --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4 space-y-4">
        {{-- Quick Type Filter Pills --}}
        <div class="flex flex-wrap items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Type:</span>

            @php
                $curType = request('type', '');
            @endphp

            <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ empty($curType) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>All Journal</span>
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
            <a href="{{ route('transactions.index', array_merge(request()->query(), ['date_from' => $today, 'date_to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                Today
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->query(), ['date_from' => $last7, 'date_to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                Last 7d
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->query(), ['date_from' => $last30, 'date_to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                Last 30d
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->query(), ['date_from' => $monthS, 'date_to' => $today, 'page' => 1])) }}"
               class="px-2.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                This Month
            </a>
        </div>

        <form method="GET" action="{{ route('transactions.index') }}" class="space-y-3">
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
                            placeholder="Reference, notes, user, payee…"
                            class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>
                </div>

                {{-- Method --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Channel / Method</label>
                    <select name="method"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Methods</option>
                        @foreach(['cash', 'bank', 'momo'] as $m)
                            <option value="{{ $m }}" @selected(request('method') === $m)>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Customer</label>
                    <select name="customer_id"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Customers</option>
                        @foreach(\App\Models\Customer::select('id','name')->orderBy('name')->get() as $customer)
                            <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Supplier --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Supplier</label>
                    <select name="supplier_id"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <option value="">All Suppliers</option>
                        @foreach(\App\Models\Supplier::select('id','name')->orderBy('name')->get() as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- From Date --}}
                <div class="md:col-span-1.5 sm:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">From</label>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full py-2 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                </div>

                {{-- To Date --}}
                <div class="md:col-span-1.5 sm:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">To</label>
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full py-2 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                </div>
            </div>

            {{-- Action Row --}}
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <div class="text-xs text-slate-400">
                    Active results: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $totalCount }}</span> total journal records
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Apply Filter</span>
                    </button>
                    <a href="{{ route('transactions.index') }}"
                       title="Reset all filters"
                       class="inline-flex items-center justify-center p-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Transactions Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Date & Time</th>
                        <th class="px-4 py-3 text-center">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        @if($hasRunning)
                            <th class="px-4 py-3 text-right">Running Balance</th>
                        @endif
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Source & Event</th>
                        <th class="px-4 py-3">Customer / Supplier</th>
                        <th class="px-4 py-3">Notes & Memo</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($transactions as $transaction)
                        @php
                            $id = data_get($transaction, 'id');
                            $isCredit = $transaction->type === 'credit';
                            $method = strtolower($transaction->method ?? 'cash');

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

                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors {{ $transaction->is_withdrawal ? 'bg-rose-50/30 dark:bg-rose-950/20' : ($transaction->is_transfer ? 'bg-sky-50/30 dark:bg-sky-950/20' : '') }}">
                            {{-- 1. Date & Time --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-slate-800 dark:text-slate-200">
                                    {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('M j, Y') }}
                                </div>
                                <div class="text-[11px] text-slate-400 font-mono">
                                    {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('H:i') }}
                                </div>
                            </td>

                            {{-- 2. Type Badge --}}
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
                                    {{ $isCredit ? '+' : '-' }}{{ $fmt2($transaction->amount) }}
                                    <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                </div>
                            </td>

                            {{-- Running Balance (PostgreSQL only) --}}
                            @if($hasRunning)
                                <td class="px-4 py-3 text-right font-mono font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    {{ $fmt2($transaction->running_balance) }}
                                </td>
                            @endif

                            {{-- 4. Method / Channel --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $methodPalette['badge'] }}">
                                    <i data-lucide="{{ $methodPalette['icon'] }}" class="w-3 h-3"></i>
                                    <span>{{ strtoupper($method) }}</span>
                                </div>
                            </td>

                            {{-- 5. Source / Classification --}}
                            <td class="px-4 py-3">
                                @if($transaction->is_withdrawal)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                        <i data-lucide="send" class="w-3 h-3"></i>
                                        Boss Withdrawal
                                    </span>
                                @elseif($transaction->is_transfer)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-sky-500/10 text-sky-700 dark:text-sky-400 border border-sky-500/20">
                                        <i data-lucide="arrow-right-left" class="w-3 h-3"></i>
                                        Transfer
                                    </span>
                                @elseif($transaction->sale_id && !$transaction->purchase_id)
                                    <a href="{{ route('sales.show', $transaction->sale_id) }}"
                                       class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors">
                                        <i data-lucide="shopping-cart" class="w-3 h-3"></i>
                                        Sale #{{ $transaction->sale_id }}
                                    </a>
                                @elseif($transaction->purchase_id && !$transaction->sale_id)
                                    <a href="{{ route('purchases.show', $transaction->purchase_id) }}"
                                       class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-colors">
                                        <i data-lucide="truck" class="w-3 h-3"></i>
                                        Purchase #{{ $transaction->purchase_id }}
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400 text-xs">
                                        <i data-lucide="pencil" class="w-3 h-3"></i>
                                        Manual
                                    </span>
                                @endif
                            </td>

                            {{-- 6. Parties (Customer / Supplier / User) --}}
                            <td class="px-4 py-3">
                                @if($transaction->customer)
                                    <div class="flex items-center gap-1 text-slate-800 dark:text-slate-200 font-medium">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $transaction->customer->name }}</span>
                                    </div>
                                @elseif($transaction->supplier)
                                    <div class="flex items-center gap-1 text-slate-800 dark:text-slate-200 font-medium">
                                        <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $transaction->supplier->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 font-mono italic">Store Counter</span>
                                @endif

                                @if($transaction->user)
                                    <div class="text-[10px] text-slate-400 mt-0.5">By: {{ $transaction->user->name }}</div>
                                @endif
                            </td>

                            {{-- 7. Notes --}}
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400 max-w-xs">
                                <span class="line-clamp-2" title="{{ $transaction->notes }}">
                                    {{ $transaction->notes ?: '—' }}
                                </span>
                            </td>

                            {{-- 8. Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    @if($id)
                                        @can('transactions.view')
                                            <a href="{{ route('transactions.show', $id) }}"
                                               title="View Transaction"
                                               class="p-1.5 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg transition-colors">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('transactions.edit')
                                            <a href="{{ route('transactions.edit', $id) }}"
                                               title="Edit Transaction"
                                               class="p-1.5 text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('transactions.delete')
                                            <button type="button"
                                                    title="Delete Transaction"
                                                    @click="$store.confirm.open('tx-del-{{ $id }}')"
                                                    class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>

                                            <form id="tx-del-{{ $id }}"
                                                  action="{{ route('transactions.destroy', $id) }}"
                                                  method="POST"
                                                  class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $hasRunning ? 10 : 9 }}" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                        <i data-lucide="banknote" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">No transactions found</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        No financial records matched your filter criteria. Try adjusting dates or resetting the search filter.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Table Summary Footer --}}
                @if($transactions->isNotEmpty())
                    <tfoot class="bg-slate-50/80 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <tr>
                            <td colspan="2" class="px-4 py-3 uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                                Page Totals:
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                                {{ $fmt2($pageCredits + $pageDebits) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                            </td>
                            @if($hasRunning)
                                <td class="px-4 py-3 text-right font-mono">—</td>
                            @endif
                            <td colspan="{{ $hasRunning ? 6 : 5 }}" class="px-4 py-3">
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
        @if(method_exists($transactions, 'links') && $transactions->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Showing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $transactions->firstItem() ?? 0 }}</span>
                    to <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $transactions->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fmt0($transactions->total()) }}</span> transactions
                </div>
                <div>
                    {{ $transactions->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endcannot

{{-- Global Delete Confirmation Modal --}}
<div x-data x-show="$store.confirm.open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4"
     @keydown.escape.window="$store.confirm.close()">
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl p-6 w-full max-w-md transform transition-all">
        <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">Delete Transaction</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5 leading-relaxed">
            Are you sure you want to delete this financial transaction? This action will reverse journal ledger balances.
        </p>
        <div class="flex justify-end gap-2.5">
            <button type="button"
                    class="px-3.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                    @click="$store.confirm.close()">
                Cancel
            </button>
            <button type="button"
                    class="px-3.5 py-1.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors"
                    @click="$store.confirm.confirm()">
                Delete Transaction
            </button>
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
    Alpine.store('confirm', {
        open: false,
        formId: null,
        open(id){ this.formId = id; this.open = true; },
        close(){ this.open = false; this.formId = null; },
        confirm(){
            const f = this.formId ? document.getElementById(this.formId) : null;
            if (f) f.submit();
            this.close();
        },
    });
});
</script>
@endpush
@endsection
