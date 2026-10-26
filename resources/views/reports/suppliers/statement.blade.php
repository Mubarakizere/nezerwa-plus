@extends('layouts.app')
@section('title', 'Supplier Statement (AP)')

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
                    <i data-lucide="file-text" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Supplier Account Statement (AP)</span>
                </h1>
                @if($supplier)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/40">
                        {{ $supplier->name }}
                    </span>
                @endif
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Accounts payable audit ledger, purchase invoices, supplier disbursements, goods return credits, and running balance.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($supplierId)
                <a href="{{ route('reports.suppliers.statement.pdf', request()->all()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="file-down" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Export Statement PDF</span>
                </a>
                <a href="{{ route('suppliers.show', $supplierId) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Supplier Profile</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Filter & Supplier Selector Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4">
        
        {{-- Quick Range Presets (when supplier selected) --}}
        @if($supplierId)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Date Preset:
                </span>
                <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ empty($from) && empty($to) ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All Time
                </a>
                <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId, 'from' => $monthStart, 'to' => $todayStr]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $from === $monthStart && $to === $todayStr ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Month
                </a>
                <a href="{{ route('reports.suppliers.statement', ['supplier_id' => $supplierId, 'from' => $yearStart, 'to' => $todayStr]) }}"
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
        <form method="GET" action="{{ route('reports.suppliers.statement') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-5">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Select Supplier Company
                </label>
                <div class="relative">
                    <i data-lucide="truck" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <select name="supplier_id"
                            class="w-full pl-9 pr-8 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="">— Choose a supplier to inspect statement —</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((int)$supplierId === (int)$s->id)>
                                {{ $s->name }} @if($s->phone) ({{ $s->phone }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    From Date
                </label>
                <input type="date" name="from" value="{{ $from }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    To Date
                </label>
                <input type="date" name="to" value="{{ $to }}"
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

    @if($supplierId && $supplier)
        {{-- Supplier Profile Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                        {{ $supplier->name }}
                    </h2>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        @if($supplier->phone)
                            <span class="flex items-center gap-1">
                                <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                                {{ $supplier->phone }}
                            </span>
                        @endif
                        @if($supplier->email)
                            <span class="flex items-center gap-1">
                                <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                                {{ $supplier->email }}
                            </span>
                        @endif
                        @if($supplier->address)
                            <span class="flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                {{ $supplier->address }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('reports.suppliers.statement.pdf', request()->all()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                    <span>Download Official PDF</span>
                </a>
            </div>
        </div>

        {{-- Supplier Statement KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Purchases Total --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Purchases</span>
                    <div class="w-7 h-7 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center">
                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-purple-600 dark:text-purple-400">
                        {{ $fmt0($kpis['purchases_total'] ?? 0) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Gross billed procurement</div>
                </div>
            </div>

            {{-- 2. Total Paid --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Paid to Supplier</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                        {{ $fmt0($kpis['paid_total'] ?? 0) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Verified payments disbursed</div>
                </div>
            </div>

            {{-- 3. Supplier Refunds --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Supplier Refunds</span>
                    <div class="w-7 h-7 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center">
                        <i data-lucide="undo-2" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono text-sky-600 dark:text-sky-400">
                        {{ $fmt0($kpis['refunds_total'] ?? 0) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Refund credits received from supplier</div>
                </div>
            </div>

            {{-- 4. Net Balance Owed --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Outstanding Balance</span>
                    <div class="w-7 h-7 rounded-lg {{ $netBalanceVal > 0 ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600' }} flex items-center justify-center">
                        <i data-lucide="{{ $netBalanceVal > 0 ? 'alert-circle' : 'check' }}" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-xl font-bold font-mono {{ $netBalanceVal > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $fmt0($netBalanceVal) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        {{ $netBalanceVal > 0 ? 'Net amount payable to supplier' : 'Account is fully settled' }}
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
                                    @if($e['type'] === 'Purchase')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
                                            <i data-lucide="shopping-bag" class="w-3 h-3 text-purple-500"></i>
                                            <span>Purchase Invoice</span>
                                        </span>
                                    @elseif($e['type'] === 'Payment')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                            <i data-lucide="check" class="w-3 h-3 text-emerald-500"></i>
                                            <span>Disbursed Payment</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">
                                            <i data-lucide="undo-2" class="w-3 h-3 text-sky-500"></i>
                                            <span>Supplier Refund</span>
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
                                <td class="py-3 px-5 text-right font-mono font-bold whitespace-nowrap {{ $e['balance'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $fmt0($e['balance']) }}
                                    <span class="text-[10px] font-sans font-normal text-slate-400">RWF</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    No ledger activity recorded for this supplier in the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Returns to Supplier (Informational Audit Table) --}}
        @if($returns->count() > 0)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="u-turn-left" class="w-4 h-4 text-rose-500"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Goods Returns to Supplier (Transparency Audit)
                    </h2>
                </div>
                <span class="text-xs text-slate-400">Already factored into net purchases</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-3 px-5">Return Date</th>
                            <th class="py-3 px-5">Purchase Reference</th>
                            <th class="py-3 px-5">Channel</th>
                            <th class="py-3 px-5 text-right">Returned Value</th>
                            <th class="py-3 px-5 text-right">Cash Refund Received</th>
                            <th class="py-3 px-5 text-right">Item Units</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($returns as $r)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="py-3 px-5 text-slate-600 dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($r->return_date)->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-5">
                                <a href="{{ route('purchases.show', $r->purchase_id) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Purchase #{{ $r->purchase_id }}
                                </a>
                            </td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ strtoupper($r->payment_channel ?? '-') }}
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $fmt0($r->total_amount) }} RWF
                            </td>
                            <td class="py-3 px-5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $fmt0($r->refund_amount) }} RWF
                            </td>
                            <td class="py-3 px-5 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ $r->items_count }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    @else
        {{-- Empty State: Please Select a Supplier --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-12 text-center shadow-2xs">
            <div class="w-12 h-12 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="truck" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No Supplier Selected</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                Please pick a vendor from the dropdown menu above to inspect their billing statement, purchase records, and balance.
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
