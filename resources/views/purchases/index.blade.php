@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="package" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Purchases</span>
        </h1>

        <a href="{{ route('purchases.create') }}" class="btn btn-primary flex items-center gap-2 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New Purchase
        </a>
    </div>

    {{-- Flash / Errors --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('purchases.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <input type="text" name="search" placeholder="Search supplier, notes, method..."
                   value="{{ request('search') }}"
                   class="lg:col-span-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">

            <input type="date" name="from" value="{{ request('from') }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">

            <input type="date" name="to" value="{{ request('to') }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">

            <select name="status"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                <option value="">All status</option>
                <option value="completed" @selected(request('status')==='completed')>Completed</option>
                <option value="pending"   @selected(request('status')==='pending')>Pending</option>
                <option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option>
            </select>

            <select name="channel"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                <option value="">All channels</option>
                <option value="cash" @selected(request('channel')==='cash')>Cash</option>
                <option value="bank" @selected(request('channel')==='bank')>Bank</option>
                <option value="momo" @selected(request('channel')==='momo')>MoMo</option>
            </select>

            <div class="flex gap-2 justify-end lg:col-span-6">
                <button type="submit" class="btn btn-outline text-sm px-4 py-2 flex items-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i> Filter
                </button>
                <a href="{{ route('purchases.index') }}" class="btn btn-outline text-sm px-4 py-2">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-x-auto">
        <table class="min-w-[960px] w-full text-sm text-left">
            <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3 text-right">Subtotal</th>
                    <th class="px-4 py-3 text-right">Tax</th>
                    <th class="px-4 py-3 text-right">Discount</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-right">Balance</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Channel</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($purchases as $purchase)
                    @php
                        $date = \Carbon\Carbon::parse($purchase->purchase_date);
                        $total    = (float)($purchase->total_amount ?? 0);
                        $paid     = (float)($purchase->amount_paid ?? 0);
                        $balance  = (float)($purchase->balance_due ?? ($total - $paid));
                        $channel  = strtoupper($purchase->payment_channel ?? $purchase->method ?? 'CASH');
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $purchase->id }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $purchase->supplier->name ?? 'Unknown Supplier' }}</td>

                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ number_format($purchase->subtotal ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ number_format($purchase->tax ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ number_format($purchase->discount ?? 0, 2) }}</td>

                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                            {{ number_format($total, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-300">
                            {{ number_format($paid, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ number_format($balance, 2) }}
                        </td>

                        <td class="px-4 py-3">
                            @php $status = $purchase->status ?? ($balance > 0 ? 'pending' : 'completed'); @endphp
                            @if ($status === 'completed')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">Paid</span>
                            @elseif ($status === 'pending')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300">Pending</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300">Cancelled</span>
                            @endif

                            @if (property_exists($purchase, 'loan') && $purchase->loan)
                                <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    {{ $purchase->loan->status === 'paid'
                                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                                        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' }}">
                                    Loan {{ ucfirst($purchase->loan->status) }}
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 uppercase">{{ $channel }}</td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end flex-wrap gap-1.5">
                                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-secondary text-xs px-2.5 py-1.5 flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                </a>
                                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit
                                </a>
                                <a href="{{ route('purchases.invoice', $purchase) }}" target="_blank" class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Invoice
                                </a>
                                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Delete this purchase? This will revert stock movements.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger text-xs px-2.5 py-1.5 flex items-center gap-1">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                            No purchases recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $purchases->appends(request()->query())->links() }}
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
@endsection
