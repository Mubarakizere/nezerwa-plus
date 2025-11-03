@extends('layouts.app')
@section('title', 'Loans Overview')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Loans Overview</span>
        </h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('loans.export.pdf') }}" class="btn btn-outline flex items-center gap-1 text-sm">
                <i data-lucide="file-down" class="w-4 h-4"></i> PDF Summary
            </a>
            <a href="{{ route('loans.create') }}" class="btn btn-primary flex items-center gap-1 text-sm">
                <i data-lucide="plus" class="w-4 h-4"></i> New Loan
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <x-stat-card title="Total Loans" value="{{ number_format($stats['total_loans'], 2) }}" color="indigo" />
        <x-stat-card title="Paid Loans" value="{{ number_format($stats['paid_loans'], 2) }}" color="green" />
        <x-stat-card title="Pending Loans" value="{{ number_format($stats['pending_loans'], 2) }}" color="yellow" />
        <x-stat-card title="Loans Given" value="{{ $stats['count_given'] }}" color="blue" />
        <x-stat-card title="Loans Taken" value="{{ $stats['count_taken'] }}" color="purple" />
    </div>

    {{-- Loans Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"> {{-- ✅ Responsive container --}}
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Client / Supplier</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Remaining</th>
                        <th class="px-4 py-3 text-left">Loan Date</th>
                        <th class="px-4 py-3 text-left">Due Date</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($loans as $loan)
                        @php
                            $paid = $loan->payments()->sum('amount');
                            $remaining = round(($loan->amount ?? 0) - $paid, 2);
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all">
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium">#{{ $loan->id }}</td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $loan->type === 'given'
                                        ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
                                        : 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' }}">
                                    {{ ucfirst($loan->type) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if ($loan->type === 'given')
                                    {{ $loan->customer->name ?? '—' }}
                                @else
                                    {{ $loan->supplier->name ?? '—' }}
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-semibold">
                                {{ number_format($loan->amount, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                {{ number_format($paid, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold
                                {{ $remaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ number_format($remaining, 2) }}
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ optional($loan->loan_date)->format('Y-m-d') }}
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ optional($loan->due_date)->format('Y-m-d') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $loan->status === 'paid'
                                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                                        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    {{-- View --}}
                                    <a href="{{ route('loans.show', $loan) }}"
                                       class="btn btn-secondary text-xs inline-flex items-center gap-1">
                                        <i data-lucide="eye" class="w-4 h-4"></i> View
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('loans.edit', $loan) }}"
                                       class="btn btn-outline text-xs inline-flex items-center gap-1">
                                        <i data-lucide="edit" class="w-4 h-4"></i> Edit
                                    </a>

                                    {{-- Delete --}}
                                    <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="btn btn-danger text-xs inline-flex items-center gap-1"
                                                @click="$store.confirm.openWith($el.closest('form'))">
                                            <i data-lucide="trash" class="w-4 h-4"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No loans found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div> {{-- end responsive wrapper --}}
    </div>

    {{-- Pagination --}}
    @if($loans instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-4">
            {{ $loans->links() }}
        </div>
    @endif
</div>

{{-- Global Delete Confirmation Modal --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.open=false">
    <div @click.outside="$store.confirm.open=false"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this loan? This action cannot be undone.
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
