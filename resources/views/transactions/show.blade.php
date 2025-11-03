@extends('layouts.app')
@section('title', 'Transaction Details')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- 🔹 Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="banknote" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Transaction Details
        </h1>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back
        </a>
    </div>

    {{-- 🔸 Card --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-8 text-sm text-gray-700 dark:text-gray-300">

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Type</p>
                <p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $transaction->type === 'credit'
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                        {{ ucfirst($transaction->type) }}
                    </span>
                </p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Amount</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format($transaction->amount, 2) }} RWF
                </p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Party</p>
                <p>
                    @if($transaction->customer)
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="user" class="w-4 h-4 text-indigo-500"></i>
                            Customer: {{ $transaction->customer->name }}
                        </span>
                    @elseif($transaction->supplier)
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="truck" class="w-4 h-4 text-amber-500"></i>
                            Supplier: {{ $transaction->supplier->name }}
                        </span>
                    @else
                        <span class="text-gray-500">—</span>
                    @endif
                </p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Linked Record</p>
                <p>
                    @if($transaction->sale)
                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                            Sale #{{ $transaction->sale->id }}
                        </span>
                    @elseif($transaction->purchase)
                        <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                            <i data-lucide="package" class="w-4 h-4"></i>
                            Purchase #{{ $transaction->purchase->id }}
                        </span>
                    @else
                        <span class="text-gray-500">—</span>
                    @endif
                </p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Date</p>
                <p>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Method</p>
                <p>{{ $transaction->method ?? '—' }}</p>
            </div>

            <div class="sm:col-span-2">
                <p class="font-medium text-gray-500 dark:text-gray-400">Notes</p>
                <p class="whitespace-pre-line">{{ $transaction->notes ?? '—' }}</p>
            </div>
        </div>

        {{-- 🔹 Footer --}}
        <div class="flex justify-end gap-2 mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn btn-primary flex items-center gap-1">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
            </a>
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
