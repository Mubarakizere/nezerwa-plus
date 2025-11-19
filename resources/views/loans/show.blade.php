@extends('layouts.app')
@section('title', "Loan #{$loan->id}")

@section('content')
@php
    // Fast paid sum if controller used withSum; fallback to query sum
    $paidRaw   = $loan->payments_sum_amount ?? null;
    $paid      = (float) ($paidRaw ?? $loan->payments()->sum('amount'));
    $amount    = (float) ($loan->amount ?? 0);
    $remaining = max(round($amount - $paid, 2), 0);
    $progress  = $amount > 0 ? min(100, round(($paid / $amount) * 100)) : 0;

    $dueDate   = optional($loan->due_date);
    $today     = now()->startOfDay();
    $dueDiff   = $loan->due_date ? $today->diffInDays($loan->due_date, false) : null; // negative => overdue
    $isOverdue = $loan->status === 'pending' && $loan->due_date && $today->gt($loan->due_date);
    $isDueSoon = $loan->status === 'pending' && $loan->due_date && !$isOverdue && $dueDiff !== null && $dueDiff <= 7;

    $typePillClasses = $loan->type === 'given'
        ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
        : 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300';

    $statusPillClasses = $loan->status === 'paid'
        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300';
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-800 dark:text-gray-100">
            <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Loan #{{ $loan->id }}

            {{-- Type + Status pills --}}
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium {{ $typePillClasses }}">
                {{ ucfirst($loan->type) }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusPillClasses }}">
                {{ ucfirst($loan->status) }}
            </span>

            {{-- Overdue / Due soon flag --}}
            @if($isOverdue)
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                    Overdue {{ abs($dueDiff) }} {{ abs($dueDiff) === 1 ? 'day' : 'days' }}
                </span>
            @elseif($isDueSoon)
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300">
                    Due in {{ $dueDiff }} {{ $dueDiff === 1 ? 'day' : 'days' }}
                </span>
            @endif
        </h1>

        <div class="flex flex-wrap gap-2">
            @can('loans.view')
                <a href="{{ route('loans.index') }}" class="btn btn-secondary text-sm flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
            @endcan

            @can('loans.edit')
                <a href="{{ route('loans.edit', $loan) }}" class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
                </a>
            @endcan

            {{-- Optional quick actions (guarded by Route::has + permission) --}}
            @if (\Illuminate\Support\Facades\Route::has('loans.markPaid') && $loan->status !== 'paid')
                @can('loans.mark-paid')
                    <form action="{{ route('loans.markPaid', $loan) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-success text-sm flex items-center gap-1">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Mark Paid
                        </button>
                    </form>
                @endcan
            @endif

            @if (\Illuminate\Support\Facades\Route::has('loans.recalculate'))
                @can('loans.recalculate')
                    <form action="{{ route('loans.recalculate', $loan) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-outline text-sm flex items-center gap-1">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Recalculate
                        </button>
                    </form>
                @endcan
            @endif

            @can('loans.delete')
                <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                            @click="$store.confirm.openWith($el.closest('form'))"
                            class="btn btn-danger text-sm flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Alert strip for overdue --}}
    @if ($isOverdue)
        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            <div class="flex items-start gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5"></i>
                <p>
                    This loan is <strong>overdue by {{ abs($dueDiff) }} {{ abs($dueDiff) === 1 ? 'day' : 'days' }}</strong>.
                    Consider adding a payment or updating the due date.
                </p>
            </div>
        </div>
    @endif

    {{-- Summary Cards --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Amount</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($amount, 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Paid</div>
            <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">{{ number_format($paid, 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Remaining</div>
            <div class="mt-1 text-2xl font-semibold {{ $remaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                {{ number_format($remaining, 2) }}
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Due Date</div>
            <div class="mt-1 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $dueDate?->format('Y-m-d') ?? '—' }}
            </div>
            @if($isOverdue)
                <div class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">Overdue {{ abs($dueDiff) }}d</div>
            @elseif($isDueSoon)
                <div class="mt-1 text-xs font-medium text-orange-600 dark:text-orange-400">Due in {{ $dueDiff }}d</div>
            @endif
        </div>
    </section>

    {{-- Loan Summary with progress bar --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Loan Summary</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Type</p>
                <p class="font-semibold capitalize">{{ ucfirst($loan->type) }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Status</p>
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusPillClasses }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Loan Date</p>
                <p class="font-medium">{{ optional($loan->loan_date)->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Created By</p>
                <p class="font-medium">{{ $loan->user->name ?? 'System' }}</p>
            </div>
            @if($loan->notes)
                <div class="sm:col-span-2 md:col-span-3">
                    <p class="text-gray-500 dark:text-gray-400">Notes</p>
                    <p class="font-medium text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $loan->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Progress Bar --}}
        <div class="pt-4">
            <div class="flex justify-between items-center mb-1 text-sm text-gray-600 dark:text-gray-400">
                <span>Repayment Progress</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </section>

    {{-- Parties --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Parties Involved</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-300">Customer</h4>
                @if($loan->customer)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $loan->customer->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $loan->customer->email ?? '' }}</p>

                    {{-- Optional deep-link to that party’s loans if route exists --}}
                    @if (\Illuminate\Support\Facades\Route::has('loans.party'))
                        <a href="{{ route('loans.party', ['party' => 'customer', 'id' => $loan->customer->id]) }}"
                           class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1">
                            <i data-lucide="list" class="w-3.5 h-3.5"></i> View all this customer’s loans
                        </a>
                    @endif
                @else
                    <p class="text-sm text-gray-500 italic">— None —</p>
                @endif
            </div>
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-300">Supplier</h4>
                @if($loan->supplier)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $loan->supplier->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $loan->supplier->email ?? '' }}</p>
                    @if (\Illuminate\Support\Facades\Route::has('loans.party'))
                        <a href="{{ route('loans.party', ['party' => 'supplier', 'id' => $loan->supplier->id]) }}"
                           class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1">
                            <i data-lucide="list" class="w-3.5 h-3.5"></i> View all this supplier’s loans
                        </a>
                    @endif
                @else
                    <p class="text-sm text-gray-500 italic">— None —</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Linked Record --}}
    @if($loan->sale || $loan->purchase)
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Linked Record</h3>
            @if($loan->sale)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Linked to <strong>Sale #{{ $loan->sale->id }}</strong>.
                </p>
                @can('sales.view')
                    <a href="{{ route('sales.show', $loan->sale) }}" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline">
                        View Sale Details →
                    </a>
                @endcan
            @elseif($loan->purchase)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Linked to <strong>Purchase #{{ $loan->purchase->id }}</strong>.
                </p>
                @can('purchases.view')
                    <a href="{{ route('purchases.show', $loan->purchase) }}" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline">
                        View Purchase Details →
                    </a>
                @endcan
            @endif
        </section>
    @endif

    {{-- Payment History with running balance --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Payment History</h3>
            @if($loan->status !== 'paid')
                @can('loans.view') {{-- payments are under loans.view middleware --}}
                    <a href="{{ route('loan-payments.create', $loan) }}" class="btn btn-success text-xs">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Payment
                    </a>
                @endcan
            @else
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-sm rounded-md">
                    Loan fully paid
                </span>
            @endif
        </div>

        @if($loan->payments->count() > 0)
            @php
                // Sort by date asc, then id asc for stable order
                $rows = $loan->payments->sortBy(fn($p) => sprintf('%s-%09d', optional($p->payment_date)->format('Ymd') ?? '00000000', $p->id));
                $running = 0.0;
            @endphp
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-left">Method</th>
                            <th class="px-4 py-2 text-left">Recorded By</th>
                            <th class="px-4 py-2 text-right">Running Paid</th>
                            <th class="px-4 py-2 text-right">Remaining</th>
                            <th class="px-4 py-2 text-left">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($rows as $payment)
                            @php
                                $running += (float) $payment->amount;
                                $rowRemaining = max(round($amount - $running, 2), 0);
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                                <td class="px-4 py-2">{{ optional($payment->payment_date)->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-right text-green-700 dark:text-green-400 font-semibold">
                                    {{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="px-4 py-2 capitalize text-gray-700 dark:text-gray-300">{{ $payment->method }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $payment->user->name ?? 'System' }}</td>
                                <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($running, 2) }}</td>
                                <td class="px-4 py-2 text-right {{ $rowRemaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} font-semibold">
                                    {{ number_format($rowRemaining, 2) }}
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $payment->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900/30 text-sm">
                        <tr>
                            <td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-300">Totals</td>
                            <td class="px-4 py-2 text-right font-semibold text-green-700 dark:text-green-400">{{ number_format($paid, 2) }}</td>
                            <td colspan="3"></td>
                            <td class="px-4 py-2 text-right font-semibold {{ $remaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ number_format($remaining, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 italic">No payments recorded yet.</p>
        @endif
    </section>
</div>

{{-- Global Confirm Modal --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.open=false">
    <div @click.outside="$store.confirm.open=false"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this loan? This will also remove related payments.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.open=false">Cancel</button>
            @can('loans.delete')
                <button type="button" class="btn btn-danger"
                        @click="$store.confirm.submitEl?.submit(); $store.confirm.open=false;">
                    Delete
                </button>
            @endcan
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        submitEl: null,
        openWith(form) {
            this.submitEl = form;
            this.open = true;
        }
    });
});
</script>
@endpush
@endsection
