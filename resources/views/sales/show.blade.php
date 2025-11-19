@extends('layouts.app')
@section('title', "Sale #{$sale->id}")

@section('content')
@php
    use Carbon\Carbon;

    // Eager/safe loads
    try {
        $sale->loadMissing([
            'items.product',
            'returns.items.product',
            'customer',
            'user',
            'payments.user',
            'loan.payments.user',
        ])->loadSum('returns as returns_total','amount');
    } catch (\Throwable $e) {}

    // Dates / status
    $date    = $sale->sale_date ? Carbon::parse($sale->sale_date) : ($sale->created_at ?? now());
    $status  = $sale->status ?? 'completed';
    $channel = strtolower($sale->payment_channel ?? 'cash');

    // Totals (return-aware) + payments breakdown
    $returnsTotal = (float) ($sale->returns_total ?? $sale->returns()->sum('amount'));
    $grossTotal   = (float) ($sale->total_amount ?? 0);
    $netAfter     = max(0, $grossTotal - $returnsTotal);

    $paymentsCol  = $sale->payments ?? collect();
    $byMethod     = $paymentsCol->groupBy(fn($p) => strtolower($p->method ?? 'cash'))->map->sum('amount');
    $paid         = (float) ($paymentsCol->sum('amount') ?: ($sale->amount_paid ?? 0));
    $balance      = max(0, round($netAfter - $paid, 2));
    $progress     = $netAfter > 0 ? min(100, (int) round(($paid / $netAfter) * 100)) : 0;

    $qtyTotal    = (float) $sale->items->sum('quantity');
    $profitTotal = (float) $sale->items->sum('profit');

    // Badges
    $channelClasses = [
        'cash'   => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'bank'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'momo'   => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
        'mobile' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    ][$channel] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-200';

    $statusClasses = match ($status) {
        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'pending'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
        default     => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    };

    // Build item options for itemized return modal (respecting already-returned qty)
    $alreadyByItem = $sale->returns
        ->flatMap(fn($r) => $r->items ?? collect())
        ->groupBy('sale_item_id')
        ->map(fn($g) => (float) $g->sum('quantity'));

    $saleRows = $sale->items->map(function ($si) use ($alreadyByItem) {
        $returned = (float)($alreadyByItem[$si->id] ?? 0);
        $max      = max(0, (float)$si->quantity - $returned);
        return [
            'sale_item_id' => (int) $si->id,
            'product_id'   => (int) $si->product_id,
            'product_name' => $si->product->name ?? "#{$si->product_id}",
            'unit_price'   => (float) $si->unit_price,
            'max'          => (float) $max,
        ];
    })->values();

    // Loan helpers
    $methods  = ['cash','momo','bank'];
    $loan     = $sale->loan;
    $loanAmt  = (float) ($loan->amount ?? 0);
    $loanPaid = (float) ($loan ? $loan->payments->sum('amount') : 0);
    $loanRem  = $loan ? max(0, round($loanAmt - $loanPaid, 2)) : 0;
    $loanProg = $loanAmt > 0 ? min(100, (int) round(($loanPaid / $loanAmt) * 100)) : 0;
@endphp

@cannot('sales.view')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to view sales.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    </div>
@elsecan('sales.view')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-outline text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Sale #{{ $sale->id }}</h1>

            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClasses }}">
                {{ ucfirst($status) }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $channelClasses }}">
                {{ strtoupper($channel) }}
            </span>

            @if($loan)
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold
                    {{ $loan->status === 'paid'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                    Loan {{ ucfirst($loan->status) }}
                </span>
            @endif>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="btn btn-outline text-sm">
                <i data-lucide="file-text" class="w-4 h-4"></i> Invoice
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline text-sm">
                <i data-lucide="printer" class="w-4 h-4"></i> Print
            </button>
            @can('sales.edit')
                <a href="{{ route('sales.edit', $sale) }}" class="btn btn-outline text-sm">
                    <i data-lucide="edit" class="w-4 h-4"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    @if($status === 'cancelled')
        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-xs text-red-700 dark:text-red-200 flex items-start gap-2">
            <i data-lucide="alert-octagon" class="w-4 h-4 mt-0.5"></i>
            <div>
                <p class="font-semibold">Cancelled Sale</p>
                <p>This sale has been marked as cancelled. Totals are shown for audit only.</p>
            </div>
        </div>
    @endif

    {{-- Quick stats --}}
    <div class="grid md:grid-cols-4 gap-4">
        <x-stat-card title="Customer"  icon="user"         :value="$sale->customer->name ?? 'Walk-in'" />
        <x-stat-card title="Date"      icon="calendar"     :value="$date->format('Y-m-d')" />
        <x-stat-card title="Items (Qty)" icon="shopping-bag" :value="$qtyTotal" />
        <x-stat-card title="Recorded By" icon="shield-check" :value="$sale->user->name ?? 'N/A'" />
    </div>

    {{-- Totals + progress --}}
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
                    <span class="ml-1 font-semibold">{{ ucfirst($status) }}</span>
                </p>
            </div>

            <div class="space-y-2 text-sm">
                <p><strong class="text-gray-600 dark:text-gray-400">Gross Total:</strong>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($grossTotal, 2) }}</span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Returns:</strong>
                    <span class="font-semibold text-rose-600 dark:text-rose-400">- {{ number_format($returnsTotal, 2) }}</span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Net After Returns:</strong>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($netAfter, 2) }}</span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Paid:</strong>
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($paid, 2) }}</span>
                </p>
                <p><strong class="text-gray-600 dark:text-gray-400">Balance:</strong>
                    <span class="font-semibold {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ number_format($balance, 2) }}
                    </span>
                </p>
            </div>
        </div>

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

    {{-- Payments breakdown --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="wallet" class="w-5 h-5 text-indigo-500"></i>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Payments</h4>
            </div>
            <div class="flex flex-wrap gap-1">
                @forelse($byMethod as $m => $amt)
                    @php
                        $chip = match($m){
                            'bank' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            'momo' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                            'mobile' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            default => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] {{ $chip }}">
                        {{ strtoupper($m) }} {{ number_format($amt,2) }}
                    </span>
                @empty
                    <span class="text-xs text-gray-500">No split payments • using legacy amount.</span>
                @endforelse
            </div>
        </div>

        @if($paymentsCol->count())
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-left">Method</th>
                            <th class="px-4 py-2 text-left">Reference</th>
                            <th class="px-4 py-2 text-left">Phone</th>
                            <th class="px-4 py-2 text-left">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($paymentsCol->sortByDesc('paid_at') as $p)
                            @php
                                $pdate = $p->paid_at ? Carbon::parse($p->paid_at)->format('Y-m-d') : '';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $pdate }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-green-700 dark:text-green-400">{{ number_format($p->amount, 2) }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300 uppercase">{{ $p->method }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $p->reference ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $p->phone ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $p->user->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Returns --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="rotate-ccw" class="w-5 h-5 text-rose-500"></i>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Returns & Allowances</h4>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">Total: {{ number_format($returnsTotal, 2) }}</span>
            </div>
            @can('sales.edit')
                <button type="button" @click="$store.returns.open = true" class="btn btn-primary text-xs">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Return
                </button>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-left">Method</th>
                        <th class="px-4 py-2 text-left">Reference</th>
                        <th class="px-4 py-2 text-left">Reason</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($sale->returns as $ret)
                        @php
                            $retDate  = $ret->date ? Carbon::parse($ret->date)->format('Y-m-d') : '';
                            $retItems = $ret->items ?? collect();
                            $hasItems = $retItems->isNotEmpty();
                        @endphp
                        <tr x-data="{open:false}" class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $retDate }}</td>
                            <td class="px-4 py-2 text-right font-medium text-rose-600 dark:text-rose-400">- {{ number_format($ret->amount, 2) }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ strtoupper($ret->method ?? '—') }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $ret->reference ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                <span class="line-clamp-2">{{ $ret->reason ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center justify-end gap-2">
                                    @if($hasItems)
                                        <button type="button" @click="open=!open" class="btn btn-outline btn-sm text-xs">
                                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="open && 'rotate-180'"></i>
                                            Items
                                        </button>
                                    @endif

                                    @can('sales.edit')
                                        <form method="POST" action="{{ route('sales.returns.destroy', $ret) }}"
                                              onsubmit="return confirm('Delete this return?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger btn-sm text-xs">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>

                                @if($hasItems)
                                    <div x-show="open" x-transition class="mt-3 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-gray-100 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 uppercase">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">Product</th>
                                                    <th class="px-3 py-2 text-left">Disposition</th>
                                                    <th class="px-3 py-2 text-center">Qty</th>
                                                    <th class="px-3 py-2 text-right">Unit Price</th>
                                                    <th class="px-3 py-2 text-right">Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($retItems as $ri)
                                                    <tr>
                                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $ri->product->name ?? ('#'.$ri->product_id) }}</td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ strtoupper($ri->disposition ?? 'restock') }}</td>
                                                        <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ number_format($ri->quantity, 2) }}</td>
                                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($ri->unit_price, 2) }}</td>
                                                        <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($ri->line_total, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">No returns recorded.</td>
                        </tr>
                    @endforelse
                </tbody>

                @if($returnsTotal > 0)
                    <tfoot class="bg-gray-50 dark:bg-gray-900/30 text-xs">
                        <tr>
                            <td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-300">Totals</td>
                            <td class="px-4 py-2 text-right font-semibold text-rose-600 dark:text-rose-400">- {{ number_format($returnsTotal, 2) }}</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Loan (summary + quick pay + history) --}}
    @if($loan)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i data-lucide="banknote" class="w-5 h-5 text-yellow-500"></i>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Linked Loan</h4>
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        {{ $loan->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                        {{ ucfirst($loan->status) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    @if($loan->status === 'pending')
                        <button type="button" class="btn btn-primary btn-sm" @click="$store.loanpay.open = true">
                            <i data-lucide="plus" class="w-4 h-4"></i> Quick Pay
                        </button>
                    @endif
                    <a href="{{ route('loan-payments.create', $loan) }}" class="btn btn-outline btn-sm">More Options</a>
                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline btn-sm">View Loan</a>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Loan Amount</p>
                    <p class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($loanAmt, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Total Paid</p>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">{{ number_format($loanPaid, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Remaining</p>
                    <p class="text-lg font-semibold {{ $loanRem <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($loanRem, 2) }}
                    </p>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-1 text-xs text-gray-600 dark:text-gray-400">
                    <span>Repayment Progress</span><span>{{ $loanProg }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $loanProg }}%"></div>
                </div>
            </div>

            @if($loan->payments->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase">
                            <tr>
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-right">Amount</th>
                                <th class="px-3 py-2 text-left">Method</th>
                                <th class="px-3 py-2 text-left">By</th>
                                <th class="px-3 py-2 text-left">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($loan->payments->sortByDesc('payment_date') as $p)
                                @php $lpdate = $p->payment_date ? Carbon::parse($p->payment_date)->format('Y-m-d') : ''; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                    <td class="px-3 py-2">{{ $lpdate }}</td>
                                    <td class="px-3 py-2 text-right text-green-700 dark:text-green-400 font-semibold">{{ number_format($p->amount, 2) }}</td>
                                    <td class="px-3 py-2 capitalize">{{ $p->method }}</td>
                                    <td class="px-3 py-2">{{ $p->user->name ?? 'System' }}</td>
                                    <td class="px-3 py-2">{{ $p->notes ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                            <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ number_format($item->quantity, 2) }}</td>
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
                        <td class="px-4 py-2 text-center font-semibold text-gray-800 dark:text-gray-200">{{ number_format($qtyTotal, 2) }}</td>
                        <td></td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-800 dark:text-gray-200">{{ number_format($grossTotal, 2) }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-green-700 dark:text-green-400">{{ number_format($profitTotal ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Add Return Modal --}}
@can('sales.edit')
<div x-data
     x-cloak
     x-show="$store.returns.open"
     x-transition.opacity
     class="fixed inset-0 z-[9999]">

    <div class="absolute inset-0 bg-black/40"
         @click="$store.returns.open=false"
         @keydown.escape.window="$store.returns.open=false"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-800 shadow-xl"
             x-data="saleReturnModal({ items: @js($saleRows) })">

            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Add Sale Return</h3>
                <button type="button" @click="$store.returns.open=false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="px-5 pt-4">
                <div class="inline-flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <button type="button" class="px-3 py-1.5 text-xs"
                            :class="tab==='amount' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                            @click="tab='amount'">Quick amount</button>
                    <button type="button" class="px-3 py-1.5 text-xs"
                            :class="tab==='itemized' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                            @click="tab='itemized'">Itemized</button>
                </div>
            </div>

            {{-- Amount form --}}
            <form x-show="tab==='amount'" x-transition
                  action="{{ route('sales.returns.store', $sale) }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="mode" value="amount">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Method</label>
                        <select name="method" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">—</option>
                            <option value="cash">CASH</option>
                            <option value="bank">BANK</option>
                            <option value="momo">MOMO</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Reference</label>
                        <input type="text" name="reference" placeholder="Receipt / Txn ID"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Reason</label>
                    <textarea name="reason" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="Optional"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="$store.returns.open=false" class="btn btn-outline">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>

            {{-- Itemized form --}}
            <form x-show="tab==='itemized'" x-transition
                  action="{{ route('sales.returns.store', $sale) }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="mode" value="itemized">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium mb-1 text-gray-600 dark:text-gray-300">Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1 text-gray-600 dark:text-gray-300">Method</label>
                        <select name="method" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">—</option>
                            <option value="cash">CASH</option>
                            <option value="bank">BANK</option>
                            <option value="momo">MOMO</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1 text-gray-600 dark:text-gray-300">Reference</label>
                        <input type="text" name="reference" placeholder="Receipt / Txn ID"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-100 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 uppercase">
                            <tr>
                                <th class="px-3 py-2 text-left">Item</th>
                                <th class="px-3 py-2 text-right">Max</th>
                                <th class="px-3 py-2 text-right">Qty</th>
                                <th class="px-3 py-2 text-right">Unit</th>
                                <th class="px-3 py-2 text-left">Disposition</th>
                                <th class="px-3 py-2 text-right">Line</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700" x-data
                               x-init="if(lines.length===0) add()">
                            <template x-for="(row, i) in lines" :key="row.key">
                                <tr>
                                    <td class="px-3 py-2">
                                        <select class="w-56 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                :name="`items[${i}][sale_item_id]`"
                                                @change="onSelect(i, $event)">
                                            <option value="">Select item…</option>
                                            <template x-for="opt in options" :key="opt.sale_item_id">
                                                <option :value="opt.sale_item_id" x-text="opt.product_name + ' — sold @ ' + money(opt.unit_price)"></option>
                                            </template>
                                        </select>
                                        <input type="hidden" :name="`items[${i}][product_id]`" :value="row.product_id">
                                    </td>

                                    <td class="px-3 py-2 text-right" x-text="money(row.max)"></td>

                                    <td class="px-3 py-2 text-right">
                                        <input type="number" step="0.01" min="0.01"
                                               class="w-24 text-right rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                               :name="`items[${i}][quantity]`"
                                               x-model.number="row.quantity"
                                               @input="clamp(i)">
                                    </td>

                                    <td class="px-3 py-2 text-right">
                                        <input type="number" step="0.01" min="0"
                                               class="w-28 text-right rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                               :name="`items[${i}][unit_price]`"
                                               x-model.number="row.unit_price"
                                               @input="recalc()">
                                    </td>

                                    <td class="px-3 py-2">
                                        <select class="w-36 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                :name="`items[${i}][disposition]`"
                                                x-model="row.disposition">
                                            <option value="restock">Restock</option>
                                            <option value="writeoff">Write-off</option>
                                        </select>
                                    </td>

                                    <td class="px-3 py-2 text-right font-medium" x-text="money(row.quantity * row.unit_price)"></td>

                                    <td class="px-3 py-2">
                                        <button type="button" class="btn btn-danger btn-sm" @click="remove(i)">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="lines.length===0">
                                <td colspan="7" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No items. Add one.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <td colspan="7" class="px-3 py-2">
                                    <div class="flex items-center justify-between">
                                        <button type="button" class="btn btn-outline btn-sm" @click="add()">Add Line</button>
                                        <div class="text-sm">
                                            <span class="text-gray-600 dark:text-gray-300">Total:</span>
                                            <span class="ml-1 font-semibold" x-text="money(total)"></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <label class="block text-xs font-medium mb-1 text-gray-600 dark:text-gray-300">Reason</label>
                    <textarea name="reason" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="Optional"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="$store.returns.open=false" class="btn btn-outline">Cancel</button>
                    <button class="btn btn-primary" :disabled="lines.length===0 || total<=0">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Loan Quick-Pay Modal --}}
@if($loan && $loan->status === 'pending')
<div x-data
     x-cloak
     x-show="$store.loanpay.open"
     x-transition.opacity
     class="fixed inset-0 z-[10000]">

    <div class="absolute inset-0 bg-black/40"
         @click="$store.loanpay.open=false"
         @keydown.escape.window="$store.loanpay.open=false"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-800 shadow-xl"
             x-data="loanPayModal({
                remaining: {{ json_encode($loanRem) }},
                defaultAmount: {{ json_encode($loanRem) }},
                defaultMethod: {{ json_encode(in_array($channel, $methods, true) ? $channel : 'cash') }},
                today: {{ json_encode(now()->toDateString()) }}
             })">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Record Loan Payment</h3>
                <button type="button" @click="$store.loanpay.open=false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('loan-payments.store', $loan) }}" class="p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Amount <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" x-model="amount"
                               @input="clamp()" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                        <p class="mt-1 text-[11px]" :class="warn ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                            Remaining: <strong x-text="money(remaining)"></strong>
                            <span x-show="warn"> • Exceeds remaining — will be blocked.</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Payment Date <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" x-model="payment_date"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Method <span class="text-red-500">*</span></label>
                        <select name="method" x-model="method"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                            @foreach($methods as $m)
                                <option value="{{ $m }}">{{ strtoupper($m) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="Reference / comment…"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="btn btn-outline" @click="$store.loanpay.open=false">Cancel</button>
                    <button class="btn btn-success" :disabled="warn || !amount || amount<=0">Save Payment</button>
                </div>
            </form>

            <div class="px-5 pb-4 text-[11px] text-gray-500 dark:text-gray-400">
                Note: This posts to the loan payments endpoint and updates accounting (Transaction + DebitCredit) automatically.
            </div>
        </div>
    </div>
</div>
@endif

@endcan
@endsection

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) window.lucide.createIcons();
});

document.addEventListener('alpine:init', () => {
    if (!Alpine.store('returns')) Alpine.store('returns', { open:false });
    if (!Alpine.store('loanpay')) Alpine.store('loanpay', { open:false });

    // URL triggers: ?open=returns or ?open=loan
    const params = new URLSearchParams(location.search);
    if (params.get('open') === 'returns') Alpine.store('returns').open = true;
    if (params.get('open') === 'loan')    Alpine.store('loanpay').open   = true;
});

function saleReturnModal({ items }) {
    const uid = () => (crypto?.randomUUID?.() ?? String(Date.now() + Math.random()));
    return {
        tab: 'amount',
        options: items,
        lines: [],
        total: 0,
        add(){ this.lines.push({ key: uid(), sale_item_id: null, product_id: null, unit_price: 0, max: 0, quantity: 1, disposition: 'restock' }); },
        remove(i){ this.lines.splice(i,1); this.recalc(); },
        onSelect(i, ev){
            const id = Number(ev.target.value || 0);
            const opt = this.options.find(o => o.sale_item_id === id);
            if (!opt) return;
            const row = this.lines[i];
            row.sale_item_id = opt.sale_item_id;
            row.product_id   = opt.product_id;
            row.unit_price   = Number(opt.unit_price || 0);
            row.max          = Number(opt.max || 0);
            if (!row.quantity || row.quantity <= 0) row.quantity = Math.min(1, row.max);
            this.clamp(i);
        },
        clamp(i){
            const r = this.lines[i]; if (!r) return;
            if (r.quantity > r.max) r.quantity = r.max;
            if (r.quantity < 0.01) r.quantity = 0.01;
            this.recalc();
        },
        recalc(){ this.total = this.lines.reduce((s, r) => s + (Number(r.quantity||0) * Number(r.unit_price||0)), 0); },
        money(v){ return Number(v||0).toFixed(2); }
    }
}

function loanPayModal({ remaining, defaultAmount, defaultMethod, today }) {
    return {
        remaining: Number(remaining || 0),
        amount: Number(defaultAmount || 0).toFixed(2),
        method: defaultMethod || 'cash',
        payment_date: today || new Date().toISOString().slice(0,10),
        warn: false,
        clamp(){
            const amt = parseFloat(this.amount || 0);
            this.warn = isFinite(amt) ? (amt > (this.remaining + 0.009)) : true;
            if (isFinite(amt) && !this.warn) this.amount = amt.toFixed(2);
        },
        money(v){ return Number(v||0).toFixed(2); }
    }
}
</script>
@endpush
