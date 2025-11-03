@extends('layouts.app')
@section('title', "Purchase #{$purchase->id}")

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- 🔹 Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="shopping-cart" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Purchase #{{ $purchase->id }}</span>
        </h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchases.invoice', $purchase->id) }}" target="_blank"
               class="btn btn-primary flex items-center gap-1 text-sm">
                <i data-lucide="file-down" class="w-4 h-4"></i> Download PDF
            </a>
            <a href="{{ route('purchases.edit', $purchase->id) }}"
               class="btn btn-outline flex items-center gap-1 text-sm">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
            </a>
            <a href="{{ route('purchases.index') }}"
               class="btn btn-secondary flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
        </div>
    </div>

    {{-- 🔹 Purchase Info --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-6">
        <div class="space-y-2 text-sm">
            <p><strong class="text-gray-600 dark:text-gray-400">Supplier:</strong>
                {{ $purchase->supplier->name ?? 'Unknown Supplier' }}</p>
            <p><strong class="text-gray-600 dark:text-gray-400">Date:</strong>
                {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}</p>
            <p><strong class="text-gray-600 dark:text-gray-400">Status:</strong>
                @if ($purchase->status === 'completed')
                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 text-xs font-semibold">Paid</span>
                @elseif ($purchase->status === 'pending')
                    <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 text-xs font-semibold">Pending</span>
                @else
                    <span class="px-2 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 text-xs font-semibold">Cancelled</span>
                @endif
            </p>
            <p><strong class="text-gray-600 dark:text-gray-400">Recorded By:</strong>
                {{ $purchase->user->name ?? 'N/A' }}</p>
        </div>

        <div class="space-y-2 text-sm">
            <p><strong class="text-gray-600 dark:text-gray-400">Subtotal:</strong> {{ number_format($purchase->subtotal, 2) }}</p>
            <p><strong class="text-gray-600 dark:text-gray-400">Tax:</strong> {{ number_format($purchase->tax, 2) }}</p>
            <p><strong class="text-gray-600 dark:text-gray-400">Discount:</strong> {{ number_format($purchase->discount, 2) }}</p>
        </div>
    </div>

    {{-- 🔹 Purchased Items --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-hidden">
        <h3 class="px-6 py-3 text-lg font-semibold border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="clipboard-list" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i> Purchased Items
        </h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Product</th>
                        <th class="px-4 py-2 text-center">Qty</th>
                        <th class="px-4 py-2 text-right">Unit Cost</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($purchase->items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $item->product->name }}</td>
                            <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $item->quantity }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-100 font-medium">
                                {{ number_format($item->total_cost, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🔹 Summary & Payment --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="wallet" class="w-5 h-5 text-green-600 dark:text-green-400"></i> Purchase Summary
            </h3>
            <span class="text-2xl font-bold text-green-700 dark:text-green-400">
                RWF {{ number_format($purchase->total_amount, 2) }}
            </span>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="space-y-1">
                <p><strong class="text-gray-600 dark:text-gray-400">Paid:</strong>
                    <span class="text-gray-800 dark:text-gray-100">{{ number_format($purchase->amount_paid, 2) }}</span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Balance:</strong>
                    <span class="{{ $purchase->balance_due > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-green-600 dark:text-green-400 font-semibold' }}">
                        {{ number_format($purchase->balance_due, 2) }}
                    </span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Payment Method:</strong> {{ strtoupper($purchase->method ?? 'CASH') }}</p>
            </div>

            <div class="space-y-1">
                <p><strong class="text-gray-600 dark:text-gray-400">Transaction:</strong>
                    @if ($purchase->transaction)
                        {{ ucfirst($purchase->transaction->type) }} • {{ $purchase->transaction->user->name ?? 'N/A' }}
                    @else
                        <em class="text-gray-500 dark:text-gray-400">No transaction recorded</em>
                    @endif
                </p>
            </div>
        </div>

        {{-- Progress Bar --}}
        @php
            $progress = $purchase->total_amount > 0
                ? round(($purchase->amount_paid / $purchase->total_amount) * 100)
                : 0;
        @endphp
        <div class="mt-4">
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progress }}%"></div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Payment Progress: {{ $progress }}%</p>
        </div>
    </div>

    {{-- 🔹 Linked Loan --}}
    @php
        $loan = \App\Models\Loan::where('purchase_id', $purchase->id)->first();
    @endphp

    @if ($loan)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-2">
                <i data-lucide="banknote" class="w-5 h-5 text-yellow-500"></i> Linked Loan
            </h4>
            <div class="grid md:grid-cols-2 gap-2 text-sm">
                <p><strong>Loan Type:</strong> {{ ucfirst($loan->type) }}</p>
                <p><strong>Loan Amount:</strong> {{ number_format($loan->amount, 2) }}</p>
                <p><strong>Status:</strong>
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $loan->status === 'paid' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                        {{ ucfirst($loan->status) }}
                    </span>
                </p>
            </div>
            @if ($loan->status === 'pending')
                <div class="mt-3">
                    <a href="{{ route('loan-payments.create', $loan) }}" class="btn btn-success text-xs flex items-center gap-1">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Payment
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- 🔹 Notes --}}
    @if ($purchase->notes)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-2">
                <i data-lucide="sticky-note" class="w-5 h-5 text-indigo-500"></i> Notes
            </h4>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $purchase->notes }}</p>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
@endpush
@endsection
