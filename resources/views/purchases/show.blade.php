@extends('layouts.app')
@section('title', "Purchase #{$purchase->id}")

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="shopping-cart" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Purchase #{{ $purchase->id }}</span>
            </h1>
            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                @php
                    $date = $purchase->date ?? $purchase->purchased_at ?? $purchase->created_at;
                    $status = $purchase->status ?? 'completed';
                    $method = $purchase->method ?? 'cash';
                    $statusColors = [
                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                        'partial'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                        'draft'     => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300',
                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                    ];
                @endphp

                <span class="inline-flex items-center gap-1">
                    <i data-lucide="user" class="w-4 h-4 text-indigo-500"></i>
                    <span class="font-medium">{{ $purchase->supplier->name ?? '—' }}</span>
                </span>

                <span class="hidden md:inline text-gray-400">•</span>

                <span class="inline-flex items-center gap-1">
                    <i data-lucide="calendar" class="w-4 h-4 text-indigo-500"></i>
                    {{ \Carbon\Carbon::parse($date)->format('M j, Y g:i A') }}
                </span>

                <span class="hidden md:inline text-gray-400">•</span>

                <span class="inline-flex items-center gap-1">
                    <i data-lucide="receipt" class="w-4 h-4 text-indigo-500"></i>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? $statusColors['completed'] }}">
                        {{ ucfirst($status) }}
                    </span>
                </span>

                <span class="hidden md:inline text-gray-400">•</span>

                <span class="inline-flex items-center gap-1">
                    <i data-lucide="wallet" class="w-4 h-4 text-indigo-500"></i>
                    <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-medium">
                        Method: {{ strtoupper($method) }}
                    </span>
                </span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="file-edit" class="w-4 h-4"></i> Edit
            </a>
            <a href="{{ route('purchases.invoice', $purchase) }}" class="btn btn-success flex items-center gap-1">
                <i data-lucide="printer" class="w-4 h-4"></i> Invoice (PDF)
            </a>
        </div>
    </div>

    {{-- KPI CARDS --}}
    @php
        $computedSubtotal = $purchase->subtotal ?? $purchase->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost);
        $tax      = $purchase->tax ?? $purchase->tax_amount ?? 0;
        $discount = $purchase->discount ?? $purchase->discount_amount ?? 0;
        $total    = $purchase->total ?? $purchase->total_amount ?? ($computedSubtotal + $tax - $discount);
        $paid     = $purchase->paid ?? $purchase->amount_paid ?? 0;
        $balance  = max(0, $total - $paid);
        $fmt = fn($n) => number_format((float)$n, 2);
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Subtotal</p>
            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">RWF {{ $fmt($computedSubtotal) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Tax</p>
            <p class="mt-1 text-lg font-semibold text-blue-700 dark:text-blue-300">+ RWF {{ $fmt($tax) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Discount</p>
            <p class="mt-1 text-lg font-semibold text-amber-700 dark:text-amber-300">– RWF {{ $fmt($discount) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</p>
            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">RWF {{ $fmt($total) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid</p>
            <p class="mt-1 text-lg font-semibold text-emerald-700 dark:text-emerald-300">RWF {{ $fmt($paid) }}</p>
        </div>
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Balance</p>
            <p class="mt-1 text-lg font-semibold {{ $balance > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                RWF {{ $fmt($balance) }}
            </p>
        </div>
    </div>

    {{-- ITEMS TABLE --}}
    <div class="rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
            <i data-lucide="list" class="w-4 h-4 text-indigo-500"></i>
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Qty</th>
                        <th class="px-5 py-3">Unit Cost</th>
                        <th class="px-5 py-3">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($purchase->items as $item)
                        @php
                            $qty = (float)$item->quantity;
                            $uc  = (float)$item->unit_cost;
                            $lt  = $item->total_cost ?? $qty * $uc;
                        @endphp
                        <tr class="text-sm">
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $item->product->name ?? ('#'.$item->product_id) }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $qty }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">RWF {{ $fmt($uc) }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">RWF {{ $fmt($lt) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50/70 dark:bg-gray-800/50 text-sm">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">Subtotal</td>
                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">RWF {{ $fmt($computedSubtotal) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">Tax</td>
                        <td class="px-5 py-3 font-medium text-blue-700 dark:text-blue-300">+ RWF {{ $fmt($tax) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">Discount</td>
                        <td class="px-5 py-3 font-medium text-amber-700 dark:text-amber-300">– RWF {{ $fmt($discount) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-gray-800 dark:text-gray-100">Total</td>
                        <td class="px-5 py-3 font-semibold text-gray-900 dark:text-gray-100">RWF {{ $fmt($total) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">Paid</td>
                        <td class="px-5 py-3 font-medium text-emerald-700 dark:text-emerald-300">RWF {{ $fmt($paid) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">Balance</td>
                        <td class="px-5 py-3 font-semibold {{ $balance>0?'text-rose-700 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">RWF {{ $fmt($balance) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- LOAN CARD (if any, type=taken) --}}
    @if(optional($purchase->loan)->type === 'taken')
        @php
            $loan = $purchase->loan;
            $lTotal   = (float)($loan->amount ?? $loan->total ?? 0);
            $lPaid    = (float)($loan->paid ?? $loan->amount_paid ?? 0);
            $lBalance = max(0, $lTotal - $lPaid);
            $lStatus  = $loan->status ?? ($lBalance>0 ? 'active' : 'cleared');
        @endphp
        <div class="rounded-2xl ring-1 ring-indigo-200 dark:ring-indigo-900/40 bg-indigo-50 dark:bg-indigo-950/40 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="landmark" class="w-5 h-5 text-indigo-600 dark:text-indigo-300"></i>
                    <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">Linked Loan (Taken)</h3>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                    {{ $lStatus==='active' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'
                                           : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                    {{ ucfirst($lStatus) }}
                </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <div class="rounded-lg bg-white/60 dark:bg-gray-900/40 ring-1 ring-white/50 dark:ring-white/10 p-3">
                    <p class="text-gray-500 dark:text-gray-400">Loan Amount</p>
                    <p class="text-indigo-900 dark:text-indigo-200 font-semibold">RWF {{ $fmt($lTotal) }}</p>
                </div>
                <div class="rounded-lg bg-white/60 dark:bg-gray-900/40 ring-1 ring-white/50 dark:ring-white/10 p-3">
                    <p class="text-gray-500 dark:text-gray-400">Paid</p>
                    <p class="text-emerald-700 dark:text-emerald-300 font-semibold">RWF {{ $fmt($lPaid) }}</p>
                </div>
                <div class="rounded-lg bg-white/60 dark:bg-gray-900/40 ring-1 ring-white/50 dark:ring-white/10 p-3">
                    <p class="text-gray-500 dark:text-gray-400">Balance</p>
                    <p class="font-semibold {{ $lBalance>0?'text-rose-700 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                        RWF {{ $fmt($lBalance) }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(Route::has('loans.show'))
                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline flex items-center gap-1">
                        <i data-lucide="eye" class="w-4 h-4"></i> View Loan
                    </a>
                @endif
                @if(Route::has('loan-payments.create'))
                    <a href="{{ route('loan-payments.create', ['loan' => $loan->id]) }}" class="btn btn-primary flex items-center gap-1">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Payment
                    </a>
                @endif
            </div>
        </div>
    @endif

</div>
@endsection
