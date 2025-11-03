@extends('layouts.app')
@section('title', "Sale #{$sale->id}")

@section('content')
@php
    use Carbon\Carbon;

    $balance  = round(($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0), 2);
    $progress = $sale->total_amount > 0 ? round((($sale->amount_paid ?? 0) / $sale->total_amount) * 100) : 0;
    $date     = Carbon::parse($sale->sale_date);
    $qtyTotal = $sale->items->sum('quantity');
    $profitTotal = $sale->items->sum('profit');
    $channel  = strtolower($sale->payment_channel ?? ($sale->method ? 'cash' : 'cash'));
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i data-lucide="receipt" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Sale #{{ $sale->id }}</h1>
            {{-- Status chip --}}
            @php
                $statusClasses = match ($sale->status) {
                    'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                    'pending'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                    default     => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                };
                $channelClasses = [
                    'cash' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                    'bank' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                    'momo' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                ][$channel] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-200';
            @endphp
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClasses }}">
                {{ ucfirst($sale->status) }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $channelClasses }}">
                {{ strtoupper($channel) }}
            </span>
            @if($sale->loan)
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold
                    {{ $sale->loan->status === 'paid'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                    Loan {{ ucfirst($sale->loan->status) }}
                </span>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="btn btn-primary flex items-center gap-1 text-sm">
                <i data-lucide="file-down" class="w-4 h-4"></i> Invoice
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline flex items-center gap-1 text-sm">
                <i data-lucide="printer" class="w-4 h-4"></i> Print
            </button>
            <a href="{{ route('sales.edit', $sale) }}" class="btn btn-outline flex items-center gap-1 text-sm">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid md:grid-cols-4 gap-4">
        <x-stat-card title="Customer" icon="user" :value="$sale->customer->name ?? 'Walk-in'" />
        <x-stat-card title="Date" icon="calendar" :value="$date->format('Y-m-d')" />
        <x-stat-card title="Items (Qty)" icon="shopping-bag" :value="$qtyTotal" />
        <x-stat-card title="Recorded By" icon="shield-check" :value="$sale->user->name ?? 'N/A'" />
    </div>

    {{-- Totals & Payment --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-2 text-sm">
                <p><strong class="text-gray-600 dark:text-gray-400">Payment Channel:</strong>
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $channelClasses }}">{{ strtoupper($channel) }}</span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Reference:</strong>
                    <span class="ml-1 text-gray-800 dark:text-gray-200">{{ $sale->method ?: '-' }}</span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Sale Status:</strong>
                    <span class="ml-1 font-semibold">{{ ucfirst($sale->status) }}</span>
                </p>
            </div>

            <div class="space-y-2 text-sm">
                <p><strong class="text-gray-600 dark:text-gray-400">Total:</strong>
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">
                        RWF {{ number_format($sale->total_amount, 2) }}
                    </span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Paid:</strong>
                    <span class="font-semibold text-green-600 dark:text-green-400">
                        RWF {{ number_format($sale->amount_paid ?? 0, 2) }}
                    </span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Balance:</strong>
                    <span class="font-semibold {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        RWF {{ number_format($balance, 2) }}
                    </span>
                </p>
            </div>
        </div>

        {{-- Progress --}}
        <div class="mt-5">
            <div class="flex justify-between items-center mb-1 text-sm">
                <span class="text-gray-600 dark:text-gray-400 font-medium flex items-center gap-1">
                    <i data-lucide="credit-card" class="w-4 h-4"></i> Payment Progress
                </span>
                <span class="text-gray-700 dark:text-gray-300 font-semibold">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </div>

    {{-- Loan --}}
    @if($sale->loan)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-1">
                    <i data-lucide="banknote" class="w-5 h-5 text-yellow-500"></i> Linked Loan
                </h4>
                <span class="px-2 py-1 rounded-full text-xs font-medium
                    {{ $sale->loan->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                    {{ ucfirst($sale->loan->status) }}
                </span>
            </div>

            <div class="grid md:grid-cols-2 gap-2 text-sm">
                <p><strong>Loan Amount:</strong> RWF {{ number_format($sale->loan->amount, 2) }}</p>
                <p><strong>Type:</strong> {{ ucfirst($sale->loan->type) }}</p>
                <p><strong>Loan Date:</strong> {{ Carbon::parse($sale->loan->loan_date)->format('Y-m-d') }}</p>
                @if($sale->loan->due_date)
                    <p><strong>Due Date:</strong> {{ Carbon::parse($sale->loan->due_date)->format('Y-m-d') }}</p>
                @endif
            </div>

            @if($sale->loan->notes)
                <p class="mt-3 text-gray-600 dark:text-gray-400 text-sm italic">{{ $sale->loan->notes }}</p>
            @endif

            @if($sale->loan->status === 'pending')
                <div class="mt-4">
                    {{-- adjust route name if yours differs --}}
                    <a href="{{ route('loan-payments.create', $sale->loan) }}" class="btn btn-success text-xs">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Payment
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Items --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-hidden">
        <h4 class="px-6 py-3 text-lg font-semibold border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="shopping-bag" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            Sold Items
        </h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Product</th>
                        <th class="px-4 py-2 text-center">Qty</th>
                        <th class="px-4 py-2 text-right">Unit Price</th>
                        <th class="px-4 py-2 text-right">Subtotal</th>
                        <th class="px-4 py-2 text-right">Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($sale->items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $item->product->name ?? 'Unknown Product' }}</td>
                            <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $item->quantity }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($item->subtotal, 2) }}</td>
                            <td class="px-4 py-2 text-right text-green-700 dark:text-green-400">{{ number_format($item->profit ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-900/30 text-xs">
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-300">Totals</td>
                        <td class="px-4 py-2 text-center font-semibold text-gray-800 dark:text-gray-200">{{ $qtyTotal }}</td>
                        <td></td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-800 dark:text-gray-200">{{ number_format($sale->total_amount, 2) }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-green-700 dark:text-green-400">{{ number_format($profitTotal ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Transaction --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <h4 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="activity" class="w-5 h-5 text-sky-500"></i> Transaction Details
        </h4>
        @if($sale->transaction)
            <div class="grid md:grid-cols-2 gap-3 text-sm">
                <p><strong class="text-gray-600 dark:text-gray-400">Type:</strong> {{ ucfirst($sale->transaction->type) }}</p>
                <p><strong class="text-gray-600 dark:text-gray-400">Method:</strong> {{ strtoupper($channel) }}</p>
                <p><strong class="text-gray-600 dark:text-gray-400">Recorded By:</strong> {{ $sale->transaction->user->name ?? $sale->user->name ?? 'N/A' }}</p>
                <p><strong class="text-gray-600 dark:text-gray-400">Date:</strong>
                    @php
                        $txnDate = $sale->transaction->transaction_date ?? null;
                        $txnOut  = $txnDate ? Carbon::parse($txnDate)->format('Y-m-d H:i') : '-';
                    @endphp
                    {{ $txnOut }}
                </p>
            </div>
            @if($sale->transaction->notes)
                <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm italic">{{ $sale->transaction->notes }}</p>
            @endif
        @else
            <p class="text-gray-500 dark:text-gray-400 italic">No transaction recorded for this sale.</p>
        @endif
    </div>

    {{-- Notes --}}
    @if($sale->notes)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <h4 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="sticky-note" class="w-5 h-5 text-indigo-500"></i> Notes
            </h4>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $sale->notes }}</p>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
@endsection
