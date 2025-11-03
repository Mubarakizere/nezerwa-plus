@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- 🔹 Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="package" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Purchases</span>
        </h1>

        <a href="{{ route('purchases.create') }}" class="btn btn-primary flex items-center gap-2 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New Purchase
        </a>
    </div>

    {{-- 🔹 Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- 🔹 Purchases Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Supplier</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Method</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($purchases as $purchase)
                        @php
                            $date = \Carbon\Carbon::parse($purchase->purchase_date);
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-all">
                            {{-- ID --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $purchase->id }}</td>

                            {{-- Supplier --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $purchase->supplier->name ?? 'Unknown Supplier' }}
                            </td>

                            {{-- Date --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $date->format('Y-m-d') }}
                            </td>

                            {{-- Total --}}
                            <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-100">
                                {{ number_format($purchase->total_amount, 2) }}
                            </td>

                            {{-- Paid --}}
                            <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-300">
                                {{ number_format($purchase->amount_paid, 2) }}
                            </td>

                            {{-- Balance --}}
                            <td class="px-4 py-3 text-right font-semibold {{ $purchase->balance_due > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ number_format($purchase->balance_due, 2) }}
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                @if ($purchase->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">
                                        Paid
                                    </span>
                                @elseif ($purchase->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300">
                                        Pending
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300">
                                        Cancelled
                                    </span>
                                @endif
                            </td>

                            {{-- Method --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 uppercase">
                                {{ strtoupper($purchase->method ?? 'CASH') }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right flex justify-end gap-2 flex-wrap">
                                <a href="{{ route('purchases.show', $purchase) }}"
                                   class="btn btn-secondary text-xs flex items-center gap-1">
                                   <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                </a>

                                <a href="{{ route('purchases.edit', $purchase) }}"
                                   class="btn btn-outline text-xs flex items-center gap-1">
                                   <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit
                                </a>

                                <a href="{{ route('purchases.invoice', $purchase) }}" target="_blank"
                                   class="btn btn-outline text-xs flex items-center gap-1">
                                   <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Invoice
                                </a>

                                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Delete this purchase? This will revert stock movements.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger text-xs flex items-center gap-1">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No purchases recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🔹 Pagination --}}
    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
@endsection
