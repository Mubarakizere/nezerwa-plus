@extends('layouts.app')
@section('title', "Transaction #{$transaction->id}")

@section('content')
@php
    use Carbon\Carbon;
    $fmt = fn($n) => number_format((float)$n, 2);
    $date = $transaction->transaction_date
        ? Carbon::parse($transaction->transaction_date)->format('d M Y, H:i')
        : '—';
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="banknote" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Transaction #{{ $transaction->id }}</span>
        </h1>

        <div class="flex gap-2">
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>

            <a href="{{ route('transactions.edit', $transaction->getKey()) }}"
               class="btn btn-primary flex items-center gap-1">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
            </a>

            <button type="button"
                    class="btn btn-danger flex items-center gap-1"
                    @click="$store.confirm.openWith($refs.deleteForm)">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
            </button>

            <form x-ref="deleteForm" class="hidden"
                  action="{{ route('transactions.destroy', $transaction->getKey()) }}"
                  method="POST">
                @csrf @method('DELETE')
            </form>
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8 text-sm text-gray-700 dark:text-gray-300">

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Type</p>
                <p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $transaction->type === 'credit'
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                        {{ ucfirst($transaction->type ?? '—') }}
                    </span>
                </p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Amount</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ $fmt($transaction->amount ?? 0) }} RWF
                </p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Method / Channel</p>
                <p class="capitalize">{{ $transaction->method ?: '—' }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Date</p>
                <p>{{ $date }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">User</p>
                <p>{{ optional($transaction->user)->name ?? '—' }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Customer</p>
                <p>{{ optional($transaction->customer)->name ?? '—' }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Supplier</p>
                <p>{{ optional($transaction->supplier)->name ?? '—' }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Linked Record</p>
                <p class="flex flex-col gap-1">
                    @if($transaction->sale)
                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                            Sale #{{ $transaction->sale->id }}
                        </span>
                    @endif

                    @if($transaction->purchase)
                        <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                            <i data-lucide="package" class="w-4 h-4"></i>
                            Purchase #{{ $transaction->purchase->id }}
                        </span>
                    @endif

                    @if(!$transaction->sale && !$transaction->purchase)
                        <span class="text-gray-500">—</span>
                    @endif
                </p>
            </div>

            <div class="sm:col-span-2">
                <p class="font-medium text-gray-500 dark:text-gray-400">Notes</p>
                <p class="whitespace-pre-line">{{ $transaction->notes ?? '—' }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Created</p>
                <p>{{ optional($transaction->created_at)->format('d M Y, H:i') ?? '—' }}</p>
            </div>

            <div>
                <p class="font-medium text-gray-500 dark:text-gray-400">Updated</p>
                <p>{{ optional($transaction->updated_at)->format('d M Y, H:i') ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Global Delete Confirm Modal (Alpine store) --}}
<div x-data x-show="$store.confirm.open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.close()" x-transition>
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this transaction? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.close()">Cancel</button>
            <button type="button" class="btn btn-danger" @click="$store.confirm.confirm()">Delete</button>
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
        open: false, submitEl: null,
        openWith(form) { this.submitEl = form; this.open = true },
        close()       { this.open = false; this.submitEl = null },
        confirm()     { if (this.submitEl) this.submitEl.submit(); this.close() },
    });
});
</script>
@endpush
@endsection
