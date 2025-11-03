@extends('layouts.app')
@section('title', "Loan #{$loan->id}")

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-800 dark:text-gray-100">
            <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Loan Details #{{ $loan->id }}
        </h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('loans.index') }}" class="btn btn-secondary text-sm flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            <a href="{{ route('loans.edit', $loan) }}" class="btn btn-outline text-sm flex items-center gap-1">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
            </a>
            <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="button"
                        @click="$store.confirm.openWith($el.closest('form'))"
                        class="btn btn-danger text-sm flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Loan Summary --}}
    @php
        $paid = $loan->payments()->sum('amount');
        $remaining = round(($loan->amount ?? 0) - $paid, 2);
        $progress = $loan->amount > 0 ? round(($paid / $loan->amount) * 100) : 0;
    @endphp

    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Loan Summary</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Type</p>
                <p class="font-semibold capitalize">{{ ucfirst($loan->type) }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Amount</p>
                <p class="font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($loan->amount, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Status</p>
                <span class="px-2 py-1 rounded-full text-xs font-semibold
                    {{ $loan->status === 'paid'
                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Loan Date</p>
                <p class="font-medium">{{ optional($loan->loan_date)->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Due Date</p>
                <p class="font-medium">
                    {{ optional($loan->due_date)->format('Y-m-d') ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Created By</p>
                <p class="font-medium">{{ $loan->user->name ?? 'System' }}</p>
            </div>
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

    {{-- Related Parties --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Parties Involved</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-300">Customer</h4>
                @if($loan->customer)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $loan->customer->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $loan->customer->email ?? '' }}</p>
                @else
                    <p class="text-sm text-gray-500 italic">— None —</p>
                @endif
            </div>
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-300">Supplier</h4>
                @if($loan->supplier)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $loan->supplier->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $loan->supplier->email ?? '' }}</p>
                @else
                    <p class="text-sm text-gray-500 italic">— None —</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Linked Transaction --}}
    @if($loan->sale || $loan->purchase)
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Linked Record</h3>
            @if($loan->sale)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    This loan is linked to <strong>Sale #{{ $loan->sale->id }}</strong>.
                </p>
                <a href="{{ route('sales.show', $loan->sale) }}" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline">
                    View Sale Details →
                </a>
            @elseif($loan->purchase)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    This loan is linked to <strong>Purchase #{{ $loan->purchase->id }}</strong>.
                </p>
                <a href="{{ route('purchases.show', $loan->purchase) }}" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline">
                    View Purchase Details →
                </a>
            @endif
        </section>
    @endif

    {{-- Payment History --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Payment History</h3>
            @if($loan->status !== 'paid')
                <a href="{{ route('loan-payments.create', $loan) }}" class="btn btn-success text-xs">
                    + Add Payment
                </a>
            @else
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-sm rounded-md">
                    Loan fully paid
                </span>
            @endif
        </div>

        @if($loan->payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-left">Method</th>
                            <th class="px-4 py-2 text-left">Recorded By</th>
                            <th class="px-4 py-2 text-left">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($loan->payments as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                                <td class="px-4 py-2">{{ optional($payment->payment_date)->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-right text-green-700 dark:text-green-400 font-semibold">
                                    {{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="px-4 py-2 capitalize text-gray-700 dark:text-gray-300">{{ $payment->method }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $payment->user->name ?? 'System' }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $payment->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-between text-sm text-gray-700 dark:text-gray-300 border-t border-gray-200 dark:border-gray-700 pt-3">
                <p><strong>Total Paid:</strong> {{ number_format($paid, 2) }}</p>
                <p><strong>Remaining:</strong>
                    <span class="{{ $remaining <= 0 ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-red-600 dark:text-red-400 font-semibold' }}">
                        {{ number_format($remaining, 2) }}
                    </span>
                </p>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 italic">No payments recorded yet.</p>
        @endif
    </section>

    {{-- Notes --}}
    @if($loan->notes)
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Notes</h3>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $loan->notes }}</p>
        </section>
    @endif
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
            <button type="button" class="btn btn-danger"
                    @click="$store.confirm.submitEl?.submit(); $store.confirm.open=false;">
                Delete
            </button>
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
