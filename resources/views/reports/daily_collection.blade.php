<x-app-layout>
    <x-slot name="header">
        <div class="bg-indigo-700 text-white px-4 py-3 rounded-md shadow flex flex-col md:flex-row md:justify-between md:items-center gap-2">
            <h2 class="font-semibold text-xl leading-tight flex items-center gap-2">
                <i data-lucide="calendar-check" class="w-5 h-5 text-emerald-300"></i>
                M Beverage & Services - Daily Report Format
            </h2>
            <div class="flex items-center gap-2 text-sm">
                <span class="bg-indigo-800 px-3 py-1 rounded-full text-indigo-100 font-medium">
                    {{ $mode === 'range' ? $startDateInput . ' to ' . $endDateInput : $startDateInput }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="{ showFormulas: true, mode: '{{ $mode }}' }">

        {{-- 🔸 Action & Filter Bar --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 print:hidden">
            <form method="GET" action="{{ route('reports.daily-collection') }}" class="space-y-4">
                
                {{-- Mode Switcher & Date Pickers --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Filter Mode</label>
                        <select name="mode" x-model="mode" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <option value="single">Single Day</option>
                            <option value="range">Date Range</option>
                        </select>
                    </div>

                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            <span x-text="mode === 'range' ? 'Start Date' : 'Report Date'">Report Date</span>
                        </label>
                        <input type="date" name="start_date" value="{{ $startDateInput }}" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    </div>

                    <div class="md:col-span-1" x-show="mode === 'range'" x-cloak>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">End Date</label>
                        <input type="date" name="end_date" value="{{ $endDateInput }}" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-1 shadow-sm">
                            <i data-lucide="filter" class="w-4 h-4"></i> Apply Filter
                        </button>

                        <button type="button" @click="showFormulas = !showFormulas" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium flex items-center gap-1 shadow-sm">
                            <i data-lucide="calculator" class="w-4 h-4"></i>
                            <span x-text="showFormulas ? 'Hide Formulas' : 'Show Formulas'">Toggle Formulas</span>
                        </button>
                    </div>
                </div>

                {{-- Quick Presets & Export Actions --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Quick Date:</span>
                        @php
                            $todayDate = now()->toDateString();
                            $yesterdayDate = now()->subDay()->toDateString();
                            $weekStartDate = now()->startOfWeek()->toDateString();
                            $monthStartDate = now()->startOfMonth()->toDateString();
                        @endphp
                        <a href="{{ route('reports.daily-collection', ['mode'=>'single', 'start_date'=>$todayDate]) }}" 
                           class="px-2.5 py-1 text-xs rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-gray-700 dark:text-gray-300 font-medium">
                            Today
                        </a>
                        <a href="{{ route('reports.daily-collection', ['mode'=>'single', 'start_date'=>$yesterdayDate]) }}" 
                           class="px-2.5 py-1 text-xs rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-gray-700 dark:text-gray-300 font-medium">
                            Yesterday
                        </a>
                        <a href="{{ route('reports.daily-collection', ['mode'=>'range', 'start_date'=>$weekStartDate, 'end_date'=>$todayDate]) }}" 
                           class="px-2.5 py-1 text-xs rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-gray-700 dark:text-gray-300 font-medium">
                            This Week
                        </a>
                        <a href="{{ route('reports.daily-collection', ['mode'=>'range', 'start_date'=>$monthStartDate, 'end_date'=>$todayDate]) }}" 
                           class="px-2.5 py-1 text-xs rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-gray-700 dark:text-gray-300 font-medium">
                            This Month
                        </a>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('reports.daily-collection.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold flex items-center gap-1.5 shadow">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Export Excel
                        </a>
                        <button onclick="window.print()" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg text-sm font-medium flex items-center gap-1.5 shadow">
                            <i data-lucide="printer" class="w-4 h-4"></i> Print
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- 🔸 Formulas Legend Card --}}
        <div x-show="showFormulas" x-transition class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl p-5 text-amber-900 dark:text-amber-100 text-sm shadow-sm print:hidden">
            <h3 class="font-bold text-base flex items-center gap-2 mb-3 text-amber-800 dark:text-amber-300">
                <i data-lucide="help-circle" class="w-5 h-5"></i> Formulas &amp; Calculation Legend
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                <div><strong>1. Total Ledger Sales:</strong> Cash Sales + MoMo Sales + Credit Sales (Unpaid balances)</div>
                <div><strong>2. Opening Balance:</strong> Automatically calculated as the previous day's closing balance ($\text{Prior Inflows} - \text{Prior Outflows}$)</div>
                <div><strong>3. Total Cash Available:</strong> Opening Cash + System Cash Sales + Past Debt Receipts + Other Cash Received</div>
                <div><strong>4. Cash Closing Balance:</strong> Total Cash Available - Total Cash Outflows (Expenses + Bank Deposits)</div>
                <div><strong>5. Total MoMo Available:</strong> Opening MoMo + System MoMo Sales + Past Debt Receipts + Other MoMo Received</div>
                <div><strong>6. MoMo Closing Balance:</strong> Total MoMo Available - Total MoMo Outflows (Expenses + Charges + Transfers)</div>
            </div>
        </div>

        {{-- 🔸 Report Print Header --}}
        <div class="text-center space-y-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-black tracking-wide text-gray-900 dark:text-gray-100 uppercase">M BEVERAGE AND SERVICES</h1>
            <h2 class="text-lg font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">DAILY REPORT FORMAT</h2>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase">DAILY SALES REPORT SUMMARY</h3>
            <p class="text-xs text-gray-400">Date Period: {{ $startDateInput }} @if($mode === 'range') to {{ $endDateInput }} @endif</p>
        </div>

        {{-- 🔸 SECTION 1: DAILY SALES REPORT SUMMARY --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
                <h4 class="font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider text-sm flex items-center gap-2">
                    <i data-lucide="shopping-bag" class="w-4 h-4 text-indigo-500"></i> Sales recorded
                </h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-xs uppercase font-bold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3 text-right">Amount (RWF)</th>
                            <th class="px-6 py-3 text-gray-400 font-normal">Formula / Explanation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                        <tr>
                            <td class="px-6 py-3 font-medium">Ledger Cash Sales</td>
                            <td class="px-6 py-3 text-right font-mono font-semibold">{{ number_format($ledgerCashSales, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">Sales paid in Cash during period</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 font-medium text-red-600 dark:text-red-400">Ledger MoMo Sales</td>
                            <td class="px-6 py-3 text-right font-mono font-semibold text-red-600 dark:text-red-400">{{ number_format($ledgerMomoSales, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">Sales paid via Mobile Money during period</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 font-medium text-red-600 dark:text-red-400">Ledger Credit Sales</td>
                            <td class="px-6 py-3 text-right font-mono font-semibold text-red-600 dark:text-red-400">{{ number_format($ledgerCreditSales, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">Sales made on credit (unpaid balance)</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-800 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="px-6 py-3 uppercase">Total Ledger Sales</td>
                            <td class="px-6 py-3 text-right font-mono text-base text-indigo-600 dark:text-indigo-400">{{ number_format($totalLedgerSales, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">Cash + MoMo + Credit Sales</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 🔸 SECTION 2: DAILY COLLECTION BREAKDOWN --}}
        <div class="text-center py-2">
            <h2 class="text-xl font-black uppercase text-gray-900 dark:text-gray-100 tracking-wider">DAILY COLLECTION BREAKDOWN</h2>
        </div>

        {{-- A. Cash on Hand Report --}}
        <div class="bg-white dark:bg-gray-900 border-2 border-gray-800 dark:border-gray-600 rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-3 bg-gray-900 text-white font-bold text-base uppercase tracking-wider">
                A. Cash on hand report
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 font-bold text-xs uppercase text-gray-700 dark:text-gray-300 border-b border-gray-300 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-3">Item</th>
                            <th class="px-6 py-3 text-right">Amount (RWF)</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        {{-- Inflows --}}
                        <tr class="bg-emerald-50/50 dark:bg-emerald-950/20 font-semibold">
                            <td class="px-6 py-2.5">opening balance</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ number_format($cashOpeningBalance, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Closing balance of previous day</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2.5">cash sales by system</td>
                            <td class="px-6 py-2.5 text-right font-mono font-semibold">{{ number_format($cashSalesSystem, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Current period cash sales</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2.5">Cash Received from Previous Credit Customers</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ number_format($totalCashDebtReceived, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Past debt repayments collected in cash</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2.5">Other Cash Received</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ number_format($otherCashReceived, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Miscellaneous cash income</td>
                        </tr>
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold border-t border-b border-gray-300 dark:border-gray-600">
                            <td class="px-6 py-3 uppercase">Total Cash available</td>
                            <td class="px-6 py-3 text-right font-mono text-base text-emerald-700 dark:text-emerald-400">{{ number_format($totalCashAvailable, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">Opening + Sales + Repayments + Other</td>
                        </tr>

                        {{-- Outflows / Expenses --}}
                        <tr class="bg-gray-900 text-white font-bold text-xs uppercase">
                            <td colspan="3" class="px-6 py-2">PAYMENT PAYMENT by CASH</td>
                        </tr>
                        @foreach($cashExpenseItems as $label => $amt)
                        <tr>
                            <td class="px-6 py-2.5 pl-8">{{ $label }}</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ number_format($amt, 0) }}</td>
                            <td class="px-6 py-2.5"></td>
                        </tr>
                        @endforeach
                        <tr>
                            <td class="px-6 py-2.5 pl-8 font-semibold">CASH DEPOSIT</td>
                            <td class="px-6 py-2.5 text-right font-mono font-semibold">{{ number_format($cashDeposit, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Bank cash deposits</td>
                        </tr>
                        <tr class="bg-gray-900 text-white font-black text-base border-t-2 border-gray-900">
                            <td class="px-6 py-3 uppercase">CLOSING BALANCE</td>
                            <td class="px-6 py-3 text-right font-mono text-lg text-emerald-400">{{ number_format($cashClosingBalance, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-300">Total Cash Available - Total Cash Outflows</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- B. Mobile Money Collections --}}
        <div class="bg-white dark:bg-gray-900 border-2 border-gray-800 dark:border-gray-600 rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-3 bg-gray-900 text-white font-bold text-base uppercase tracking-wider">
                B. Mobile Money Collections
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 font-bold text-xs uppercase text-gray-700 dark:text-gray-300 border-b border-gray-300 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-3">Item</th>
                            <th class="px-6 py-3 text-right">Amount (RWF)</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        {{-- Inflows --}}
                        <tr class="bg-blue-50/50 dark:bg-blue-950/20 font-bold">
                            <td class="px-6 py-2.5 uppercase">OPEING BALANCE</td>
                            <td class="px-6 py-2.5 text-right font-mono text-base font-bold">{{ number_format($momoOpeningBalance, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Closing MoMo balance of previous day</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2.5">MoMo Sales BY system</td>
                            <td class="px-6 py-2.5 text-right font-mono font-semibold">{{ number_format($momoSalesSystem, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Current period MoMo sales</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2.5">MoMo Received from Previous Credit Customers</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ number_format($totalMomoDebtReceived, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Past debt repayments collected via MoMo</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2.5">Other MoMo Received</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ number_format($otherMomoReceived, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">Miscellaneous MoMo income</td>
                        </tr>
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold border-t border-b border-gray-300 dark:border-gray-600">
                            <td class="px-6 py-3 uppercase">Total MoMo AVALABLES</td>
                            <td class="px-6 py-3 text-right font-mono text-base text-blue-700 dark:text-blue-400">{{ number_format($totalMomoAvailable, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">Opening + Sales + Repayments + Other</td>
                        </tr>

                        {{-- Outflows / Expenses --}}
                        <tr class="bg-gray-900 text-white font-bold text-xs uppercase">
                            <td colspan="3" class="px-6 py-2">PAYMENT by MOMO</td>
                        </tr>
                        @foreach($momoExpenseItems as $label => $amt)
                        <tr>
                            <td class="px-6 py-2.5 pl-8">{{ $label }}</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ number_format($amt, 0) }}</td>
                            <td class="px-6 py-2.5"></td>
                        </tr>
                        @endforeach
                        <tr>
                            <td class="px-6 py-2.5 pl-8 font-semibold">Total transfer</td>
                            <td class="px-6 py-2.5 text-right font-mono font-semibold">{{ number_format($totalMomoTransfers, 0) }}</td>
                            <td class="px-6 py-2.5 text-xs text-gray-500">MoMo transfers out</td>
                        </tr>
                        <tr class="bg-gray-900 text-white font-black text-base border-t-2 border-gray-900">
                            <td class="px-6 py-3 uppercase">CLOSING BALANCE ON MOMO</td>
                            <td class="px-6 py-3 text-right font-mono text-lg text-blue-300">{{ number_format($momoClosingBalance, 0) }}</td>
                            <td class="px-6 py-3 text-xs text-gray-300">Total MoMo Available - Total MoMo Outflows</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- C. BANK DEPOSITS --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-800 text-white font-bold text-sm uppercase tracking-wider">
                BANK DEPOSITS
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-xs font-bold uppercase text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-3">Deposit Description</th>
                            <th class="px-6 py-3 text-right">Amount (RWF)</th>
                            <th class="px-6 py-3">Mode (Cash/MoMo)</th>
                            <th class="px-6 py-3">Bank</th>
                            <th class="px-6 py-3">Deposit Slip No.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($bankDeposits as $deposit)
                        <tr>
                            <td class="px-6 py-3 font-medium">{{ $deposit->notes ?? 'Bank Deposit' }}</td>
                            <td class="px-6 py-3 text-right font-mono font-semibold">{{ number_format($deposit->amount, 0) }}</td>
                            <td class="px-6 py-3 uppercase font-semibold text-xs">{{ $deposit->method ?? 'CSH' }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">BK / I&M / Equity</td>
                            <td class="px-6 py-3 font-mono text-xs">{{ $deposit->id }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-3 text-gray-400 font-medium">No recorded bank deposits</td>
                            <td class="px-6 py-3 text-right font-mono">0</td>
                            <td class="px-6 py-3 text-xs text-gray-400">CSH</td>
                            <td class="px-6 py-3"></td>
                            <td class="px-6 py-3"></td>
                        </tr>
                        @endforelse
                        <tr class="bg-gray-100 dark:bg-gray-800 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="px-6 py-3 uppercase">Total Deposits</td>
                            <td class="px-6 py-3 text-right font-mono text-base text-indigo-600 dark:text-indigo-400">{{ number_format($totalBankDeposits, 0) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- D. UNTRADED SUMMARY --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden p-6 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="font-bold text-gray-700 dark:text-gray-300 uppercase text-xs">MOMO NOT TRADED</span>
                    <span class="font-mono font-bold text-base">{{ number_format($momoNotTraded, 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="font-bold text-gray-700 dark:text-gray-300 uppercase text-xs">CASH NOT TRADED</span>
                    <span class="font-mono font-bold text-base">{{ number_format($cashNotTraded, 0) }}</span>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</x-app-layout>
