@extends('layouts.app')
@section('title', 'Customer Statement (AR)')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $todayStr   = now()->toDateString();
    $monthStart = now()->startOfMonth()->toDateString();
    $yearStart  = now()->startOfYear()->toDateString();

    $netBalanceVal = (float)($kpis['balance'] ?? 0);
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('reports.index') }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Back to Reports Overview">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="user-check" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Customer Account Statement (AR)</span>
                </h1>
                @if($customer)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/40">
                        {{ $customer->name }}
                    </span>
                @endif
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Accounts receivable audit ledger, sales invoices, client payments, merchandise returns, and running balance.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($customerId)
                <a href="{{ route('reports.customers.statement.pdf', request()->all()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="file-down" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Export Statement PDF</span>
                </a>
                <a href="{{ route('customers.show', $customerId) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Customer Profile</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Filter & Customer Selector Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4">
        
        {{-- Quick Range Presets (when customer selected) --}}
        @if($customerId)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Date Preset:
                </span>
                <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ empty($from) && empty($to) ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All Time
                </a>
                <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId, 'from' => $monthStart, 'to' => $todayStr]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $from === $monthStart && $to === $todayStr ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Month
                </a>
                <a href="{{ route('reports.customers.statement', ['customer_id' => $customerId, 'from' => $yearStart, 'to' => $todayStr]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $from === $yearStart && $to === $todayStr ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Year
                </a>
            </div>

            @if($from || $to)
                <div class="text-xs text-slate-400 font-mono">
                    Period: <strong class="text-slate-700 dark:text-slate-300">{{ $from ?? 'Beginning' }}</strong> → <strong class="text-slate-700 dark:text-slate-300">{{ $to ?? 'Today' }}</strong>
                </div>
            @endif
        </div>
        @endif

        {{-- Filter Form --}}
        <form method="GET" action="{{ route('reports.customers.statement') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-5">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Select Client Account
                </label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <select name="customer_id"
                            class="w-full pl-9 pr-8 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="">— Choose a customer to inspect statement —</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected((int)request('customer_id') === (int)$c->id)>
                                {{ $c->name }} @if($c->phone) ({{ $c->phone }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    From Date
                </label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    To Date
                </label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div class="lg:col-span-1 flex items-center gap-1.5">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors"
                        title="Generate Statement">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Apply</span>
                </button>
            </div>
        </form>
    </div>

    @if(request('customer_id') && $customer)
        {{-- Customer Profile Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                        {{ $customer->name }}
                    </h2>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        @if($customer->phone)
                            <span class="flex items-center gap-1">
                                <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                                {{ $customer->phone }}
                            </span>
                        @endif
                        @if($customer->email)
                            <span class="flex items-center gap-1">
                                <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                                {{ $customer->email }}
                            </span>
                        @endif
                        @if($customer->address)
                            <span class="flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                {{ $customer->address }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('reports.customers.statement.pdf', request()->all()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                    <span>Download Official PDF</span>
                </a>
            </div>
        </div>

        {{-- Customer Statement KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Sales Total --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Invoiced Sales</span>
                    <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-indigo-600 dark:text-indigo-400">
                        {{ $fmt0($kpis['sales_total'] ?? 0) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Regular invoiced sales (excl. gifts)</div>
                </div>
            </div>

            {{-- 2. Paid by Customer --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Paid by Customer</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                        {{ $fmt0($kpis['paid_total'] ?? 0) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Verified payments &amp; cash receipts</div>
                </div>
            </div>

            {{-- 3. Customer Refunds --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Customer Refunds</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
                        <i data-lucide="undo-2" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-amber-600 dark:text-amber-400">
                        {{ $fmt0($kpis['refunds_total'] ?? 0) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Disbursed return refunds</div>
                </div>
            </div>

            {{-- 4. Net Outstanding Balance --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Outstanding Balance</span>
                    <div class="w-7 h-7 rounded-lg {{ $netBalanceVal > 0 ? 'bg-rose-500/10 text-rose-600' : 'bg-emerald-500/10 text-emerald-600' }} flex items-center justify-center">
                        <i data-lucide="{{ $netBalanceVal > 0 ? 'alert-circle' : 'check' }}" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono {{ $netBalanceVal > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $fmt0($netBalanceVal) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        {{ $netBalanceVal > 0 ? 'Amount owed by customer' : 'Customer account fully settled' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Statement Timeline Journal --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Statement Transaction Journal
                    </h2>
                </div>
                <span class="text-xs text-slate-400">Chronological ledger with running balance</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-3 px-5">Date</th>
                            <th class="py-3 px-5">Event Type</th>
                            <th class="py-3 px-5">Reference / Notes</th>
                            <th class="py-3 px-5">Payment Method</th>
                            <th class="py-3 px-5 text-right">Debit / Credit</th>
                            <th class="py-3 px-5 text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($events as $e)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-5 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                    {{ $e['date']->format('M d, Y') }}
                                </td>
                                <td class="py-3 px-5 whitespace-nowrap">
                                    @if($e['type'] === 'Sale')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                                            <i data-lucide="shopping-cart" class="w-3 h-3 text-indigo-500"></i>
                                            <span>Sales Invoice</span>
                                        </span>
                                    @elseif($e['type'] === 'Payment')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                            <i data-lucide="check" class="w-3 h-3 text-emerald-500"></i>
                                            <span>Received Payment</span>
                                        </span>
                                    @elseif(str_contains($e['type'], 'Gift'))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
                                            <i data-lucide="gift" class="w-3 h-3 text-purple-500"></i>
                                            <span>Boss Gift</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                            <i data-lucide="undo-2" class="w-3 h-3 text-amber-500"></i>
                                            <span>Customer Refund</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-5">
                                    @if($e['link'])
                                        <a href="{{ $e['link'] }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $e['ref'] }}
                                        </a>
                                    @else
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $e['ref'] }}</span>
                                    @endif
                                    @if(!empty($e['note']))
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-sm">{{ $e['note'] }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ $e['method'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-5 text-right font-mono font-bold whitespace-nowrap {{ $e['amount'] >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $e['amount'] >= 0 ? '+ ' . $fmt0($e['amount']) : '- ' . $fmt0(abs($e['amount'])) }}
                                    <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                                </td>
                                <td class="py-3 px-5 text-right font-mono font-bold whitespace-nowrap {{ $e['balance'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $fmt0($e['balance']) }}
                                    <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    No ledger activity recorded for this customer in the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Returns from Customer (Informational Audit Table) --}}
        @if($returns->count() > 0)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="u-turn-left" class="w-4 h-4 text-amber-500"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Goods Returns from Customer (Transparency Audit)
                    </h2>
                </div>
                <span class="text-xs text-slate-400">Refunds issued already reflected via payments</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-3 px-5">Return Date</th>
                            <th class="py-3 px-5">Sale Reference</th>
                            <th class="py-3 px-5">Channel</th>
                            <th class="py-3 px-5 text-right">Returned Value</th>
                            <th class="py-3 px-5 text-right">Cash Refund Disbursed</th>
                            <th class="py-3 px-5 text-right">Item Units</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($returns as $r)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="py-3 px-5 text-slate-600 dark:text-slate-300">
                                {{ $r->return_date ? \Carbon\Carbon::parse($r->return_date)->format('M d, Y') : '—' }}
                            </td>
                            <td class="py-3 px-5">
                                <a href="{{ route('sales.show', $r->sale_id) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Sale #{{ $r->sale_id }}
                                </a>
                            </td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ strtoupper($r->payment_channel ?? '-') }}
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $fmt0($r->total_amount ?? 0) }} RWF
                            </td>
                            <td class="py-3 px-5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $fmt0($r->refund_amount ?? 0) }} RWF
                            </td>
                            <td class="py-3 px-5 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ $r->items_count ?? $r->items->count() }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    @else
        {{-- Empty State: Please Select a Customer --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-12 text-center shadow-2xs">
            <div class="w-12 h-12 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="user-round" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No Customer Selected</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                Please pick a client from the dropdown menu above to inspect their billing history, invoices, payments, and running balance.
            </p>
        </div>
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
