@extends('layouts.app')
@section('title', "Customer: {$customer->name}")

@section('content')
@php
    use Carbon\Carbon;
    $hasDebt = $totalDebt > 0.009;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{
         payModalOpen: false,
         targetLoanId: '',
         payAmount: '{{ number_format($totalDebt, 2, '.', '') }}',
         payDate: '{{ now()->toDateString() }}',
         payMethod: 'cash',
         payNotes: '',
         totalDebt: {{ (float) $totalDebt }},
         initTarget(loanId, remaining) {
             this.targetLoanId = loanId || '';
             this.payAmount = remaining ? parseFloat(remaining).toFixed(2) : parseFloat(this.totalDebt).toFixed(2);
             this.payModalOpen = true;
         },
         remainingAfter() {
             const amt = parseFloat(this.payAmount) || 0;
             return Math.max(0, this.totalDebt - amt).toFixed(2);
         }
     }">

    {{-- Breadcrumbs & Back Navigation --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('customers.index') }}" class="btn btn-outline text-xs flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Customers</span>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span>{{ $customer->name }}</span>
                    @if($hasDebt)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                            <i data-lucide="alert-circle" class="w-3 h-3"></i> Has Unpaid Credit
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <i data-lucide="check-circle" class="w-3 h-3"></i> Clear (No Debt)
                        </span>
                    @endif
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Customer Account #{{ $customer->id }} &bull; Customer since {{ $customer->created_at ? $customer->created_at->format('M Y') : '—' }}
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            @if($hasDebt)
                <button type="button"
                        @click="initTarget('', totalDebt)"
                        class="btn btn-primary text-sm flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="hand-coins" class="w-4 h-4"></i>
                    <span>Receive Payment</span>
                </button>
            @endif

            <a href="{{ route('reports.customers.statement.pdf', ['customer_id' => $customer->id]) }}"
               target="_blank"
               class="btn btn-outline text-sm flex items-center gap-1.5"
               title="Download Customer Statement PDF">
                <i data-lucide="file-text" class="w-4 h-4 text-gray-500"></i>
                <span>Statement (PDF)</span>
            </a>

            @can('customers.edit')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline text-sm flex items-center gap-1.5">
                    <i data-lucide="edit-3" class="w-4 h-4 text-gray-500"></i>
                    <span>Edit</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Contact Details Card --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm text-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Phone</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $customer->phone ?: 'Not provided' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Email</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                        {{ $customer->email ?: 'Not provided' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Address</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $customer->address ?: 'Not provided' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Financial KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Card 1: Outstanding Debt --}}
        <div class="bg-white dark:bg-gray-800 border {{ $hasDebt ? 'border-rose-200 dark:border-rose-900/60 bg-rose-50/20' : 'border-gray-200 dark:border-gray-700' }} rounded-xl p-5 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Outstanding Debt</span>
                <span class="p-2 rounded-lg {{ $hasDebt ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' }}">
                    <i data-lucide="banknote" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-bold {{ $hasDebt ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ number_format($totalDebt, 2) }}
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Across <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $pendingLoans->count() }}</span> pending credit sale(s)
            </p>
        </div>

        {{-- Card 2: Total Invoiced Purchases --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Invoiced Sales</span>
                <span class="p-2 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ number_format($totalSalesAmount, 2) }}
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                From <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $totalSalesCount }}</span> total customer invoice(s)
            </p>
        </div>

        {{-- Card 3: Total Installments Paid --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Credit Repaid</span>
                <span class="p-2 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <i data-lucide="badge-check" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ number_format($totalPaidOnLoans, 2) }}
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Total partial payments received to date
            </p>
        </div>
    </div>

    {{-- Section 1: Active Outstanding Loans (Credit Sales) --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 bg-gray-50/50 dark:bg-gray-900/30">
            <div class="flex items-center gap-2.5">
                <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500"></i>
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    Active Credit Sales (Pending Loans)
                </h2>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                    {{ $pendingLoans->count() }} active
                </span>
            </div>

            @if($hasDebt)
                <button type="button"
                        @click="initTarget('', totalDebt)"
                        class="btn btn-primary btn-sm text-xs flex items-center gap-1.5">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Pay Towards Debt</span>
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-400 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-5 py-3 text-left">Sale / Reference</th>
                        <th class="px-5 py-3 text-left">Loan Date</th>
                        <th class="px-5 py-3 text-right">Credit Amount</th>
                        <th class="px-5 py-3 text-right">Paid to Date</th>
                        <th class="px-5 py-3 text-right">Remaining Balance</th>
                        <th class="px-5 py-3 text-center">Progress</th>
                        <th class="px-5 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($pendingLoans as $loan)
                        @php
                            $loanAmt  = (float) $loan->amount;
                            $loanPaid = (float) ($loan->payments_sum_amount ?? $loan->payments->sum('amount'));
                            $loanRem  = max(0.0, round($loanAmt - $loanPaid, 2));
                            $percent  = $loanAmt > 0 ? min(100, round(($loanPaid / $loanAmt) * 100)) : 100;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-gray-100">
                                @if($loan->sale_id)
                                    <a href="{{ route('sales.show', $loan->sale_id) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                                        <span>Invoice #{{ $loan->sale_id }}</span>
                                        <i data-lucide="external-link" class="w-3 h-3 text-indigo-400"></i>
                                    </a>
                                @else
                                    <span>Loan #{{ $loan->id }}</span>
                                @endif
                                @if($loan->notes)
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 font-normal mt-0.5 truncate max-w-xs">
                                        {{ $loan->notes }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-300 text-xs whitespace-nowrap">
                                {{ $loan->loan_date ? Carbon::parse($loan->loan_date)->format('d M Y') : '—' }}
                                @if($loan->due_date)
                                    <span class="block text-[10px] {{ $loan->is_overdue ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-gray-400' }}">
                                        Due: {{ Carbon::parse($loan->due_date)->format('d M Y') }}
                                        @if($loan->is_overdue) (Overdue) @endif
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ number_format($loanAmt, 2) }}
                            </td>

                            <td class="px-5 py-3.5 text-right font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                {{ number_format($loanPaid, 2) }}
                            </td>

                            <td class="px-5 py-3.5 text-right font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                {{ number_format($loanRem, 2) }}
                            </td>

                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <div class="w-24 mx-auto space-y-1">
                                    <div class="flex justify-between text-[10px] text-gray-500">
                                        <span>{{ $percent }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="initTarget('{{ $loan->id }}', '{{ $loanRem }}')"
                                            class="btn btn-primary btn-sm text-xs py-1 px-2.5 flex items-center gap-1">
                                        <i data-lucide="hand-coins" class="w-3 h-3"></i>
                                        <span>Pay</span>
                                    </button>

                                    @if($loan->sale_id)
                                        <a href="{{ route('sales.show', $loan->sale_id) }}"
                                           class="btn btn-outline btn-sm text-xs py-1 px-2"
                                           title="View Sale">
                                            <i data-lucide="eye" class="w-3 h-3"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    <i data-lucide="check-circle-2" class="w-7 h-7 text-emerald-500"></i>
                                    <p class="font-medium text-gray-800 dark:text-gray-200">No outstanding credit sales!</p>
                                    <p class="text-xs text-gray-400">All customer loans and credit purchases are settled in full.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 2: Installment Repayment History (Audit Trail) --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/30">
            <div class="flex items-center gap-2.5">
                <i data-lucide="history" class="w-5 h-5 text-indigo-500"></i>
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    Installment Repayment History
                </h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Audit log of partial payments
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-400 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-right">Amount Paid</th>
                        <th class="px-5 py-3 text-left">Method</th>
                        <th class="px-5 py-3 text-left">Applied To</th>
                        <th class="px-5 py-3 text-left">Cashier / User</th>
                        <th class="px-5 py-3 text-left">Notes / Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($paymentHistory as $pay)
                        @php
                            $channelChip = match(strtolower($pay->method)){
                                'bank' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                'momo' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                'mobile' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300 text-xs whitespace-nowrap font-medium">
                                {{ $pay->payment_date ? Carbon::parse($pay->payment_date)->format('d M Y') : '—' }}
                            </td>

                            <td class="px-5 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                + {{ number_format($pay->amount, 2) }}
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium uppercase {{ $channelChip }}">
                                    {{ $pay->method }}
                                </span>
                            </td>

                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300 text-xs whitespace-nowrap">
                                @if($pay->loan && $pay->loan->sale_id)
                                    <a href="{{ route('sales.show', $pay->loan->sale_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Sale #{{ $pay->loan->sale_id }}
                                    </a>
                                @elseif($pay->loan)
                                    <span>Loan #{{ $pay->loan->id }}</span>
                                @else
                                    <span>General Account</span>
                                @endif
                            </td>

                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-400 text-xs whitespace-nowrap">
                                {{ $pay->user->name ?? 'System' }}
                            </td>

                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 text-xs">
                                {{ $pay->notes ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400 text-xs">
                                No repayment history recorded for this customer yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paymentHistory->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                {{ $paymentHistory->links() }}
            </div>
        @endif
    </div>

    {{-- Section 3: Settled / Paid Loans (History) --}}
    @if($settledLoans->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/30">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        Previously Settled Loans (Paid in Full)
                    </h2>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-400 uppercase text-xs font-medium">
                        <tr>
                            <th class="px-5 py-3 text-left">Reference</th>
                            <th class="px-5 py-3 text-left">Loan Date</th>
                            <th class="px-5 py-3 text-right">Original Amount</th>
                            <th class="px-5 py-3 text-right">Total Paid</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-center">Sale</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-xs">
                        @foreach($settledLoans as $sLoan)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-200">
                                    Loan #{{ $sLoan->id }}
                                </td>
                                <td class="px-5 py-3 text-gray-500">
                                    {{ $sLoan->loan_date ? Carbon::parse($sLoan->loan_date)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right font-medium text-gray-800 dark:text-gray-200">
                                    {{ number_format($sLoan->amount, 2) }}
                                </td>
                                <td class="px-5 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($sLoan->amount, 2) }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        Settled
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($sLoan->sale_id)
                                        <a href="{{ route('sales.show', $sLoan->sale_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            #{{ $sLoan->sale_id }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Receive Customer Loan Payment Modal --}}
    <div x-cloak
         x-show="payModalOpen"
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div @click.outside="payModalOpen = false"
             class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/70 dark:bg-gray-900/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <i data-lucide="hand-coins" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Receive Customer Payment
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $customer->name }} &bull; Total Debt: <strong class="text-rose-600 dark:text-rose-400">{{ number_format($totalDebt, 2) }}</strong>
                        </p>
                    </div>
                </div>
                <button type="button" @click="payModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('customers.pay-loans', $customer) }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="loan_id" x-model="targetLoanId">

                {{-- Target info banner if paying specific loan --}}
                <div x-show="targetLoanId" class="p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-800 dark:text-indigo-300 flex items-center justify-between">
                    <span>Targeting payment towards <strong>Loan #<span x-text="targetLoanId"></span></strong> first</span>
                    <button type="button" @click="targetLoanId = ''; payAmount = parseFloat(totalDebt).toFixed(2)" class="underline hover:text-indigo-600">
                        Pay against full account
                    </button>
                </div>

                {{-- Amount Field --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Payment Amount <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               :max="totalDebt + 0.01"
                               name="amount"
                               x-model="payAmount"
                               class="form-input text-base font-bold pr-16"
                               required>
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-gray-400">
                            RWF
                        </span>
                    </div>

                    {{-- Dynamic Balance Calculation --}}
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>New balance after payment:</span>
                        <strong class="text-sm font-semibold"
                                :class="remainingAfter() > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'"
                                x-text="remainingAfter() + ' RWF'">
                        </strong>
                    </div>

                    <p x-show="parseFloat(payAmount) > (totalDebt + 0.009)" class="mt-1 text-xs text-rose-600 font-medium">
                        Payment exceeds customer's total outstanding debt ({{ number_format($totalDebt, 2) }}).
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Payment Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Payment Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               name="payment_date"
                               x-model="payDate"
                               class="form-input text-xs"
                               required>
                    </div>

                    {{-- Channel / Method --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Payment Channel <span class="text-red-500">*</span>
                        </label>
                        <select name="method" x-model="payMethod" class="form-input text-xs" required>
                            @foreach($paymentChannels as $ch)
                                <option value="{{ $ch->slug ?? strtolower($ch->name) }}">
                                    {{ $ch->name ?? strtoupper($ch->slug) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Notes / Reference --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Notes / Reference
                    </label>
                    <textarea name="notes"
                              x-model="payNotes"
                              rows="2"
                              placeholder="Cheque #, MoMo Txn ID, or cashier note..."
                              class="form-input text-xs"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="payModalOpen = false" class="btn btn-outline text-xs">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="!payAmount || parseFloat(payAmount) <= 0 || parseFloat(payAmount) > (totalDebt + 0.009)"
                            class="btn btn-primary text-xs flex items-center gap-1.5 shadow-sm">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Confirm Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
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
