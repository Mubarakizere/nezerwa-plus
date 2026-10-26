@extends('layouts.app')
@section('title', 'Daily Collection Report')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $isRange = ($mode ?? 'single') === 'range';
    $displayDate = $isRange ? ($startDateInput . ' → ' . $endDateInput) : $startDateInput;
@endphp

<div class="max-w-7xl mx-auto space-y-6" x-data="{ showFormulas: true, mode: '{{ $mode ?? 'single' }}' }">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('reports.index') }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Back to Reports Overview">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="calendar-check" class="w-6 h-6 text-emerald-600 dark:text-emerald-400"></i>
                    <span>Daily Collection & Cash Reconciliation</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40 font-mono">
                    {{ $displayDate }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Official end-of-day register balancing, cash drawer audit, Mobile Money reconciliation, and bank deposit slips.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Print Statement</span>
            </button>

            <a href="{{ route('reports.daily-collection.export', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    {{-- Filter Panel & Range Switcher --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4 print:hidden">
        
        {{-- Quick Range Pills --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Presets:
                </span>
                @php
                    $todayDate     = now()->toDateString();
                    $yesterdayDate = now()->subDay()->toDateString();
                    $weekStartDate = now()->startOfWeek()->toDateString();
                    $monthStartDate = now()->startOfMonth()->toDateString();
                @endphp
                <a href="{{ route('reports.daily-collection', ['mode'=>'single', 'start_date'=>$todayDate]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ ($mode ?? 'single') === 'single' && $startDateInput === $todayDate ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Today
                </a>
                <a href="{{ route('reports.daily-collection', ['mode'=>'single', 'start_date'=>$yesterdayDate]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ ($mode ?? 'single') === 'single' && $startDateInput === $yesterdayDate ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    Yesterday
                </a>
                <a href="{{ route('reports.daily-collection', ['mode'=>'range', 'start_date'=>$weekStartDate, 'end_date'=>$todayDate]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ ($mode ?? 'single') === 'range' && $startDateInput === $weekStartDate ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Week
                </a>
                <a href="{{ route('reports.daily-collection', ['mode'=>'range', 'start_date'=>$monthStartDate, 'end_date'=>$todayDate]) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ ($mode ?? 'single') === 'range' && $startDateInput === $monthStartDate ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    This Month
                </a>
            </div>

            <button type="button"
                    @click="showFormulas = !showFormulas"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                <i data-lucide="calculator" class="w-3.5 h-3.5 text-amber-500"></i>
                <span x-text="showFormulas ? 'Hide Accounting Formulas' : 'Show Accounting Formulas'"></span>
            </button>
        </div>

        {{-- Filter Inputs Form --}}
        <form method="GET" action="{{ route('reports.daily-collection') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Mode
                </label>
                <select name="mode" x-model="mode"
                        class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <option value="single">Single Day Audit</option>
                    <option value="range">Date Range Audit</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    <span x-text="mode === 'range' ? 'Start Date' : 'Report Date'">Report Date</span>
                </label>
                <input type="date" name="start_date" value="{{ $startDateInput }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div x-show="mode === 'range'" x-cloak>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    End Date
                </label>
                <input type="date" name="end_date" value="{{ $endDateInput }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Generate Report</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Formulas Legend Accordion --}}
    <div x-show="showFormulas" x-transition x-cloak class="rounded-xl border border-amber-200/80 dark:border-amber-800/60 bg-amber-50/60 dark:bg-amber-950/20 p-4 text-xs text-amber-900 dark:text-amber-200 print:hidden">
        <div class="flex items-center gap-2 font-bold text-amber-800 dark:text-amber-300 mb-2">
            <i data-lucide="help-circle" class="w-4 h-4 text-amber-500"></i>
            <span>Accounting Formulas & Calculation Legend</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-[11px] text-amber-800/90 dark:text-amber-300/90">
            <div>• <strong>Total Ledger Sales:</strong> Cash Sales + MoMo Sales + Credit Sales (Unpaid) + Boss Gifts + Supplier Offset</div>
            <div>• <strong>Opening Cash/MoMo:</strong> Cumulative Prior Inflows minus Outflows as of report start date</div>
            <div>• <strong>Available Balance:</strong> Opening Balance + Sales + Previous Debt Received + Miscellaneous Receipts</div>
            <div>• <strong>Closing Cash/MoMo:</strong> Total Available minus Outflows (Operating Expenses, Charges, Deposits)</div>
        </div>
    </div>

    {{-- Executive Balances High-Impact KPI Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Cash Available --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Cash Available</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold font-mono text-slate-900 dark:text-slate-100">
                    {{ $fmt0($totalCashAvailable) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">Opening + Sales + Collections</div>
            </div>
        </div>

        {{-- Cash Closing Drawer --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cash Closing Balance</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                    {{ $fmt0($cashClosingBalance) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">Physical cash drawer balance</div>
            </div>
        </div>

        {{-- MoMo Available --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total MoMo Available</span>
                <div class="w-7 h-7 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center">
                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold font-mono text-slate-900 dark:text-slate-100">
                    {{ $fmt0($totalMomoAvailable) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">Opening + MoMo Sales + Collections</div>
            </div>
        </div>

        {{-- MoMo Closing Balance --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">MoMo Closing Balance</span>
                <div class="w-7 h-7 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center">
                    <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-xl font-bold font-mono text-sky-600 dark:text-sky-400">
                    {{ $fmt0($momoClosingBalance) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">Net Mobile Money wallet balance</div>
            </div>
        </div>
    </div>

    {{-- SECTION 1: DAILY SALES REPORT SUMMARY --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <i data-lucide="shopping-bag" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                    1. Daily Sales Summary (By Channel & Type)
                </h2>
            </div>
            <span class="text-xs text-slate-400">Sales recorded in period</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-5">Sales Category</th>
                        <th class="py-3 px-5 text-right">Amount (RWF)</th>
                        <th class="py-3 px-5 text-slate-400 font-normal">Audit Reference & Explanation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-5 font-semibold text-slate-800 dark:text-slate-200">Ledger Cash Sales</td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">{{ $fmt0($ledgerCashSales) }}</td>
                        <td class="py-3 px-5 text-slate-500 dark:text-slate-400">Sales settled in cash during this period</td>
                    </tr>
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-5 font-semibold text-slate-800 dark:text-slate-200">Ledger MoMo Sales</td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-sky-600 dark:text-sky-400">{{ $fmt0($ledgerMomoSales) }}</td>
                        <td class="py-3 px-5 text-slate-500 dark:text-slate-400">Sales settled via Mobile Money payments</td>
                    </tr>
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-5 font-semibold text-slate-800 dark:text-slate-200">Ledger Credit Sales</td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-amber-600 dark:text-amber-400">{{ $fmt0($ledgerCreditSales) }}</td>
                        <td class="py-3 px-5 text-slate-500 dark:text-slate-400">Unpaid balances on sales made in this period</td>
                    </tr>
                    @if(($ledgerGiftSales ?? 0) > 0)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-5 font-semibold text-purple-600 dark:text-purple-400 flex items-center gap-1.5">
                            <i data-lucide="gift" class="w-3.5 h-3.5 text-purple-500"></i>
                            <span>Boss Gift Sales</span>
                        </td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-purple-600 dark:text-purple-400">{{ $fmt0($ledgerGiftSales) }}</td>
                        <td class="py-3 px-5 text-slate-500 dark:text-slate-400">Complimentary products granted with inventory offset</td>
                    </tr>
                    @endif
                    @if(($ledgerSupplierOffsetSales ?? 0) > 0)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-5 font-semibold text-indigo-600 dark:text-indigo-400">Supplier Offset Sales</td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $fmt0($ledgerSupplierOffsetSales) }}</td>
                        <td class="py-3 px-5 text-slate-500 dark:text-slate-400">Sales balanced against vendor procurement credit</td>
                    </tr>
                    @endif
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200/80 dark:border-slate-800 font-semibold text-xs">
                    <tr>
                        <td class="py-3.5 px-5 uppercase text-slate-700 dark:text-slate-200 font-bold">Total Ledger Sales</td>
                        <td class="py-3.5 px-5 text-right font-mono font-bold text-base text-indigo-600 dark:text-indigo-400">
                            {{ $fmt0($totalLedgerSales) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                        </td>
                        <td class="py-3.5 px-5 text-slate-500 dark:text-slate-400 text-[11px]">Sum of all operational sales channels</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- SECTION 2: CASH ON HAND REPORT & RECONCILIATION --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-emerald-50/40 dark:bg-emerald-950/20">
            <div class="flex items-center gap-2">
                <i data-lucide="banknote" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">
                    2. Cash Drawer Reconciliation (Cash on Hand)
                </h2>
            </div>
            <span class="text-xs font-mono font-bold text-emerald-700 dark:text-emerald-300">
                Closing: {{ $fmt0($cashClosingBalance) }} RWF
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-5">Cash Line Item</th>
                        <th class="py-3 px-5 text-right">Amount (RWF)</th>
                        <th class="py-3 px-5 text-slate-400 font-normal">Audit Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    {{-- Cash Inflows --}}
                    <tr class="bg-emerald-50/30 dark:bg-emerald-950/10">
                        <td class="py-2.5 px-5 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="corner-down-right" class="w-3.5 h-3.5 text-emerald-500"></i>
                            <span>Opening Cash Balance</span>
                        </td>
                        <td class="py-2.5 px-5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">{{ $fmt0($cashOpeningBalance) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Previous day verified closing drawer balance</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-5 pl-8 text-slate-700 dark:text-slate-300">Cash Sales by System</td>
                        <td class="py-2.5 px-5 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $fmt0($cashSalesSystem) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Direct cash sales in period</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-5 pl-8 text-slate-700 dark:text-slate-300">Cash Received from Previous Credit / Loans</td>
                        <td class="py-2.5 px-5 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $fmt0($totalCashDebtReceived) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Past customer debts collected in cash</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-5 pl-8 text-slate-700 dark:text-slate-300">Other Cash Receipts</td>
                        <td class="py-2.5 px-5 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $fmt0($otherCashReceived) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Miscellaneous cash inflows</td>
                    </tr>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/40 font-bold border-y border-slate-200/80 dark:border-slate-800">
                        <td class="py-3 px-5 uppercase text-slate-800 dark:text-slate-200">Total Cash Available</td>
                        <td class="py-3 px-5 text-right font-mono text-emerald-600 dark:text-emerald-400 text-sm">{{ $fmt0($totalCashAvailable) }}</td>
                        <td class="py-3 px-5 text-slate-500 text-[11px]">Opening + Sales + Past Debt + Other</td>
                    </tr>

                    {{-- Cash Outflows --}}
                    <tr class="bg-slate-100/60 dark:bg-slate-800/60 font-semibold text-slate-600 dark:text-slate-300">
                        <td colspan="3" class="py-2 px-5 text-[11px] uppercase tracking-wider">
                            Cash Disbursements &amp; Expenses
                        </td>
                    </tr>
                    @foreach($cashExpenseItems as $label => $amt)
                    <tr>
                        <td class="py-2 px-5 pl-8 text-slate-700 dark:text-slate-300">{{ $label }}</td>
                        <td class="py-2 px-5 text-right font-mono text-rose-600 dark:text-rose-400">{{ $fmt0($amt) }}</td>
                        <td class="py-2 px-5 text-slate-400 text-[11px]">Disbursed from cash drawer</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td class="py-2.5 px-5 pl-8 font-semibold text-slate-800 dark:text-slate-200">Bank Cash Deposits</td>
                        <td class="py-2.5 px-5 text-right font-mono font-bold text-rose-600 dark:text-rose-400">{{ $fmt0($cashDeposit) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Cash deposited into bank accounts</td>
                    </tr>
                </tbody>
                <tfoot class="bg-emerald-500/10 border-t-2 border-emerald-500/30 font-bold text-xs">
                    <tr>
                        <td class="py-3.5 px-5 uppercase text-emerald-900 dark:text-emerald-200 text-sm">
                            Closing Cash Balance (On Hand)
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono font-extrabold text-base text-emerald-600 dark:text-emerald-400">
                            {{ $fmt0($cashClosingBalance) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                        </td>
                        <td class="py-3.5 px-5 text-slate-500 dark:text-slate-400 text-[11px]">
                            Total Cash Available minus Total Disbursements
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- SECTION 3: MOBILE MONEY RECONCILIATION --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-sky-50/40 dark:bg-sky-950/20">
            <div class="flex items-center gap-2">
                <i data-lucide="smartphone" class="w-4 h-4 text-sky-600 dark:text-sky-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-sky-900 dark:text-sky-200">
                    3. Mobile Money Collections &amp; Wallet Reconciliation
                </h2>
            </div>
            <span class="text-xs font-mono font-bold text-sky-700 dark:text-sky-300">
                Closing: {{ $fmt0($momoClosingBalance) }} RWF
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-5">MoMo Line Item</th>
                        <th class="py-3 px-5 text-right">Amount (RWF)</th>
                        <th class="py-3 px-5 text-slate-400 font-normal">Audit Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    {{-- MoMo Inflows --}}
                    <tr class="bg-sky-50/30 dark:bg-sky-950/10">
                        <td class="py-2.5 px-5 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="corner-down-right" class="w-3.5 h-3.5 text-sky-500"></i>
                            <span>Opening MoMo Balance</span>
                        </td>
                        <td class="py-2.5 px-5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">{{ $fmt0($momoOpeningBalance) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Previous day closing wallet balance</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-5 pl-8 text-slate-700 dark:text-slate-300">MoMo Sales by System</td>
                        <td class="py-2.5 px-5 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $fmt0($momoSalesSystem) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Direct Mobile Money sales collected</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-5 pl-8 text-slate-700 dark:text-slate-300">MoMo Received from Previous Credit / Loans</td>
                        <td class="py-2.5 px-5 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $fmt0($totalMomoDebtReceived) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Past customer debts collected via MoMo</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-5 pl-8 text-slate-700 dark:text-slate-300">Other MoMo Receipts</td>
                        <td class="py-2.5 px-5 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $fmt0($otherMomoReceived) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Miscellaneous wallet inflows</td>
                    </tr>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/40 font-bold border-y border-slate-200/80 dark:border-slate-800">
                        <td class="py-3 px-5 uppercase text-slate-800 dark:text-slate-200">Total MoMo Available</td>
                        <td class="py-3 px-5 text-right font-mono text-sky-600 dark:text-sky-400 text-sm">{{ $fmt0($totalMomoAvailable) }}</td>
                        <td class="py-3 px-5 text-slate-500 text-[11px]">Opening + Sales + Past Debt + Other</td>
                    </tr>

                    {{-- MoMo Outflows --}}
                    <tr class="bg-slate-100/60 dark:bg-slate-800/60 font-semibold text-slate-600 dark:text-slate-300">
                        <td colspan="3" class="py-2 px-5 text-[11px] uppercase tracking-wider">
                            MoMo Disbursements &amp; Expenses
                        </td>
                    </tr>
                    @foreach($momoExpenseItems as $label => $amt)
                    <tr>
                        <td class="py-2 px-5 pl-8 text-slate-700 dark:text-slate-300">{{ $label }}</td>
                        <td class="py-2 px-5 text-right font-mono text-rose-600 dark:text-rose-400">{{ $fmt0($amt) }}</td>
                        <td class="py-2 px-5 text-slate-400 text-[11px]">Disbursed via Mobile Money</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td class="py-2.5 px-5 pl-8 font-semibold text-slate-800 dark:text-slate-200">Total MoMo Transfers Out</td>
                        <td class="py-2.5 px-5 text-right font-mono font-bold text-rose-600 dark:text-rose-400">{{ $fmt0($totalMomoTransfers) }}</td>
                        <td class="py-2.5 px-5 text-slate-500 dark:text-slate-400">Wallet cashouts and transfers</td>
                    </tr>
                </tbody>
                <tfoot class="bg-sky-500/10 border-t-2 border-sky-500/30 font-bold text-xs">
                    <tr>
                        <td class="py-3.5 px-5 uppercase text-sky-900 dark:text-sky-200 text-sm">
                            Closing MoMo Balance (Wallet)
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono font-extrabold text-base text-sky-600 dark:text-sky-400">
                            {{ $fmt0($momoClosingBalance) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                        </td>
                        <td class="py-3.5 px-5 text-slate-500 dark:text-slate-400 text-[11px]">
                            Total MoMo Available minus Total Disbursements
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- SECTION 4: BANK DEPOSITS LIST & SUMMARY --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <i data-lucide="building-2" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                    4. Verified Bank Deposits
                </h2>
            </div>
            <span class="text-xs font-mono font-bold text-purple-600 dark:text-purple-400">
                Total Deposited: {{ $fmt0($totalBankDeposits) }} RWF
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-5">Deposit Description / Note</th>
                        <th class="py-3 px-5 text-right">Amount (RWF)</th>
                        <th class="py-3 px-5">Source Mode</th>
                        <th class="py-3 px-5">Target Bank</th>
                        <th class="py-3 px-5 text-right">Slip / Transaction Ref</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($bankDeposits as $deposit)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-5 font-semibold text-slate-800 dark:text-slate-200">
                            {{ $deposit->notes ?? 'Bank Deposit Entry' }}
                        </td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                            {{ $fmt0($deposit->amount) }}
                        </td>
                        <td class="py-3 px-5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                {{ $deposit->method ?? 'CSH' }}
                            </span>
                        </td>
                        <td class="py-3 px-5 text-slate-600 dark:text-slate-400">
                            BK / I&amp;M / Equity Bank
                        </td>
                        <td class="py-3 px-5 text-right font-mono text-slate-500">
                            #{{ $deposit->id }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-400">
                            No bank deposits recorded during this reporting period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($bankDeposits->count() > 0)
                <tfoot class="bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200/80 dark:border-slate-800 font-semibold text-xs">
                    <tr>
                        <td class="py-3 px-5 uppercase text-slate-700 dark:text-slate-200 font-bold">Total Bank Deposits</td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-sm text-purple-600 dark:text-purple-400">
                            {{ $fmt0($totalBankDeposits) }} <span class="text-xs font-sans text-slate-400">RWF</span>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
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
