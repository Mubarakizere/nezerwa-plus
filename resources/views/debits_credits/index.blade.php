@extends('layouts.app')
@section('title', 'Debits & Credits')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- 🔹 Flash Messages --}}
    @if(session('success'))
        <div class="p-3 rounded-md bg-green-100 text-green-700 text-sm border border-green-200">
            {{ session('success') }}
        </div>
    @elseif($errors->any())
        <div class="p-3 rounded-md bg-red-100 text-red-700 text-sm border border-red-200">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 🔸 Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Debits & Credits</span>
        </h1>
        <a href="{{ route('debits-credits.create') }}" class="btn btn-primary flex items-center gap-1 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New Entry
        </a>
    </div>

    {{-- 💰 Totals Summary --}}
    @php
        $netColor = $net > 0 ? 'text-green-600 dark:text-green-400'
                  : ($net < 0 ? 'text-red-600 dark:text-red-400'
                  : 'text-gray-800 dark:text-gray-300');
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total Debits" value="{{ number_format($debitsTotal, 2) }}" color="red" />
        <x-stat-card title="Total Credits" value="{{ number_format($creditsTotal, 2) }}" color="green" />
        <x-stat-card title="Net Balance"
            value="{{ number_format($net, 2) }}"
            color="{{ $net > 0 ? 'green' : ($net < 0 ? 'red' : 'gray') }}" />
    </div>

    {{-- 🔍 Filters --}}
    <form method="GET" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <div>
            <label class="form-label text-xs">Type</label>
            <select name="type" class="form-select">
                <option value="">All</option>
                <option value="debit" @selected(request('type')=='debit')>Debits</option>
                <option value="credit" @selected(request('type')=='credit')>Credits</option>
            </select>
        </div>

        <div>
            <label class="form-label text-xs">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
        </div>

        <div>
            <label class="form-label text-xs">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input">
        </div>

        <div class="md:col-span-1">
            <label class="form-label text-xs">Search</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..." class="form-input">
        </div>

        <div class="flex items-end">
            <button type="submit" class="btn btn-secondary w-full flex items-center gap-1">
                <i data-lucide="filter" class="w-4 h-4"></i> Filter
            </button>
        </div>
    </form>

    {{-- 📋 Table --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Description</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Supplier</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($records as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->date }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $item->type == 'debit'
                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                                        : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' }}">
                                    {{ ucfirst($item->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($item->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->description ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->customer->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->user->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('debits-credits.edit', $item->id) }}" class="btn btn-outline btn-sm" title="Edit">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    <button type="button"
                                            @click="$store.confirm.openWith($el.nextElementSibling)"
                                            class="btn btn-danger btn-sm" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    <form action="{{ route('debits-credits.destroy', $item->id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
            {{ $records->links() }}
        </div>
    </div>
</div>

{{-- 🗑️ Global Delete Modal --}}
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
            Are you sure you want to delete this entry? This action cannot be undone.
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
