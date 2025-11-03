@extends('layouts.app')
@section('title', 'Transactions')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="banknote" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Transactions</span>
        </h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('transactions.create') }}" class="btn btn-primary text-sm flex items-center gap-1">
                <i data-lucide="plus" class="w-4 h-4"></i> New Transaction
            </a>
            <a href="{{ route('transactions.export.csv') }}" class="btn btn-success text-sm flex items-center gap-1">
                <i data-lucide="file-text" class="w-4 h-4"></i> Export CSV
            </a>
            <a href="{{ route('transactions.export.pdf') }}" class="btn btn-danger text-sm flex items-center gap-1">
                <i data-lucide="file-down" class="w-4 h-4"></i> Export PDF
            </a>
        </div>
    </div>

    {{-- 🔹 Flash Messages --}}
    @if(session('success'))
        <div class="p-3 rounded-md bg-green-100 text-green-700 text-sm border border-green-200">
            {{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="p-3 rounded-md bg-red-100 text-red-700 text-sm border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- 🔸 Filter Section --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('transactions.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="form-label" for="type">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All</option>
                        <option value="credit" @selected(request('type')=='credit')>Credit</option>
                        <option value="debit" @selected(request('type')=='debit')>Debit</option>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="date_from">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-input">
                </div>

                <div>
                    <label class="form-label" for="date_to">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-input">
                </div>

                <div>
                    <label class="form-label" for="customer_id">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Models\Customer::all() as $customer)
                            <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Models\Supplier::all() as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary w-full">Filter</button>
                </div>
            </div>
        </form>
    </div>

    {{-- 🔸 Totals Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total Credits" value="{{ number_format($totalCredits, 2) }}" color="green" />
        <x-stat-card title="Total Debits" value="{{ number_format($totalDebits, 2) }}" color="red" />
        <x-stat-card title="Net Balance"
                     value="{{ number_format($totalCredits - $totalDebits, 2) }}"
                     color="{{ ($totalCredits - $totalDebits) >= 0 ? 'green' : 'red' }}" />
    </div>

    {{-- 🔸 Transactions Table --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700
                rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Method</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Supplier</th>
                        <th class="px-4 py-3 text-left">Notes</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $transaction->transaction_date }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $transaction->type === 'credit'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-semibold">
                                {{ number_format($transaction->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $transaction->method ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $transaction->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $transaction->customer->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $transaction->supplier->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $transaction->notes ?? '—' }}</td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('transactions.show', $transaction) }}"
                                       class="btn btn-secondary btn-sm" title="View">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('transactions.edit', $transaction) }}"
                                       class="btn btn-outline btn-sm" title="Edit">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    <button type="button"
                                            @click="$store.confirm.openWith($el.nextElementSibling)"
                                            class="btn btn-danger btn-sm"
                                            title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
            {{ $transactions->links() }}
        </div>
    </div>
</div>

{{-- ✅ Global Delete Confirmation Modal --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.close()"
     x-transition>
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
        open: false,
        submitEl: null,
        openWith(form) {
            this.submitEl = form;
            this.open = true;
        },
        close() {
            this.open = false;
            this.submitEl = null;
        },
        confirm() {
            if (this.submitEl) this.submitEl.submit();
            this.close();
        },
    });
});
</script>
@endpush
@endsection
