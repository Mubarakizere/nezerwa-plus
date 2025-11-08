{{-- resources/views/purchases/show.blade.php --}}
@extends('layouts.app')
@section('title', "Purchase #{$purchase->id}")

@section('content')
@php
    use Carbon\Carbon;

    // Make sure relations exist (safe if already eager-loaded)
    try {
        $purchase->loadMissing([
            'items.product',
            'items.returnItems',
            'returns.items.product',
            'supplier',
            'user',
            'loan',
            'transaction',
        ]);
    } catch (\Throwable $e) {}

    // ---- Safe meta ----
    $date      = $purchase->purchase_date ?? $purchase->created_at;
    $status    = strtolower($purchase->status ?? 'completed');
    $channel   = strtolower($purchase->payment_channel ?? 'cash');
    $reference = trim((string) ($purchase->method ?? ''));

    // ---- Colors ----
    $statusColors = [
        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'partial'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
        'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300',
    ];
    $channelColors = [
        'cash' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'bank' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'momo' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
    ];
    $statusClass  = $statusColors[$status] ?? $statusColors['completed'];
    $channelClass = $channelColors[$channel] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300';

    // ---- Money helpers ----
    $fmt = fn($n) => number_format((float)($n ?? 0), 2);

    // ---- Subtotal fallback ----
    $computedSubtotal = $purchase->subtotal
        ?? optional($purchase->items)->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost);

    // ---- Totals ----
    $tax      = (float)($purchase->tax ?? 0);
    $discount = (float)($purchase->discount ?? 0);
    $total    = (float)($purchase->total_amount ?? ($computedSubtotal + $tax - $discount));
    $paid     = (float)($purchase->amount_paid ?? 0);
    $balance  = max(0, $total - $paid);
    $paidPct  = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;

    // ---- Returns overview ----
    $returns         = $purchase->returns->sortByDesc('return_date')->values();
    $sumReturnValue  = (float) $returns->sum('total_amount');
    $sumCashRefunds  = (float) $returns->sum('refund_amount');
    $netExposure     = max(0, $total - $paid - $sumCashRefunds);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Toasts --}}
    @if(session('success'))
        <div class="rounded-lg border border-emerald-300/60 bg-emerald-50 text-emerald-800 px-3 py-2 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="shopping-cart" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Purchase #{{ $purchase->id }}</span>
            </h1>

            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <span class="inline-flex items-center gap-1">
                    <i data-lucide="user" class="w-4 h-4 text-indigo-500"></i>
                    <span class="font-medium">{{ optional($purchase->supplier)->name ?? '—' }}</span>
                </span>

                <span class="hidden md:inline text-gray-400">•</span>

                <span class="inline-flex items-center gap-1">
                    <i data-lucide="calendar" class="w-4 h-4 text-indigo-500"></i>
                    {{ $date ? Carbon::parse($date)->format('M j, Y g:i A') : '—' }}
                </span>

                <span class="hidden md:inline text-gray-400">•</span>

                <span class="inline-flex items-center gap-1">
                    <i data-lucide="receipt" class="w-4 h-4 text-indigo-500"></i>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusClass }}">
                        {{ ucfirst($status) }}
                    </span>
                </span>

                <span class="hidden md:inline text-gray-400">•</span>

                <span class="inline-flex items-center gap-1">
                    <i data-lucide="wallet" class="w-4 h-4 text-indigo-500"></i>
                    <span class="px-2 py-0.5 rounded-md text-xs font-medium {{ $channelClass }}">
                        {{ strtoupper($channel) }}
                    </span>
                </span>

                @if($reference !== '')
                    <span class="hidden md:inline text-gray-400">•</span>
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="hash" class="w-4 h-4 text-indigo-500"></i>
                        Ref: <span class="font-medium">{{ $reference }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            @role('admin|manager|accountant')
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline flex items-center gap-1">
                    <i data-lucide="file-edit" class="w-4 h-4"></i> Edit
                </a>
            @endrole
            <a href="{{ route('purchases.invoice', $purchase) }}" target="_blank" class="btn btn-success flex items-center gap-1">
                <i data-lucide="printer" class="w-4 h-4"></i> Invoice (PDF)
            </a>

            {{-- Open modal ONLY when clicked --}}
            <button type="button" class="btn btn-warning" @click="openPurchaseReturn()">
                Return to Supplier
            </button>
        </div>
    </div>

    {{-- SUPPLIER / DETAILS / NOTES --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2 flex items-center gap-2">
                <i data-lucide="building-2" class="w-4 h-4 text-indigo-500"></i> Supplier
            </h3>
            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                <p class="font-medium">{{ optional($purchase->supplier)->name ?? '—' }}</p>
                @if(optional($purchase->supplier)->phone)
                    <p><span class="text-gray-500 dark:text-gray-400">Phone:</span> {{ $purchase->supplier->phone }}</p>
                @endif
                @if(optional($purchase->supplier)->email)
                    <p><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $purchase->supplier->email }}</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i> Details
            </h3>
            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                <p><span class="text-gray-500 dark:text-gray-400">Recorded By:</span> {{ optional($purchase->user)->name ?? '—' }}</p>
                <p><span class="text-gray-500 dark:text-gray-400">Status:</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClass }}">{{ ucfirst($status) }}</span>
                </p>
                <p><span class="text-gray-500 dark:text-gray-400">Channel:</span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-medium {{ $channelClass }}">{{ strtoupper($channel) }}</span>
                </p>
                @if($reference !== '')
                    <p><span class="text-gray-500 dark:text-gray-400">Reference:</span> {{ $reference }}</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2 flex items-center gap-2">
                <i data-lucide="sticky-note" class="w-4 h-4 text-indigo-500"></i> Notes
            </h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                {{ trim((string)($purchase->notes ?? '—')) }}
            </p>
        </div>
    </div>

    {{-- KPI + PAYMENT PROGRESS --}}
    <div class="grid grid-cols-1 lg:grid-cols-7 gap-3">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:col-span-5 gap-3">
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

        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4 lg:col-span-2">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Payment Progress</p>
            <div class="w-full h-3 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
                <div class="h-3 rounded-full bg-emerald-500" style="width: {{ $paidPct }}%"></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                <span>{{ $paidPct }}% paid</span>
                <span>Paid: RWF {{ $fmt($paid) }} • Balance: RWF {{ $fmt($balance) }}</span>
            </div>
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
                    @forelse($purchase->items as $item)
                        @php
                            $qty = (float)$item->quantity;
                            $uc  = (float)$item->unit_cost;
                            $lt  = $item->total_cost ?? $qty * $uc;
                        @endphp
                        <tr class="text-sm">
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ optional($item->product)->name ?? ('#'.$item->product_id) }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $fmt($qty) }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">RWF {{ $fmt($uc) }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">RWF {{ $fmt($lt) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">No items.</td>
                        </tr>
                    @endforelse
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
                        <td class="px-5 py-3 font-semibold {{ $balance>0?'text-rose-700 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                            RWF {{ $fmt($balance) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- TRANSACTION CARD --}}
    @if($purchase->transaction)
        @php $txn = $purchase->transaction; @endphp
        <div class="rounded-2xl ring-1 ring-emerald-200 dark:ring-emerald-900/40 bg-emerald-50 dark:bg-emerald-950/40 p-5 space-y-2">
            <div class="flex items-center gap-2">
                <i data-lucide="credit-card" class="w-5 h-5 text-emerald-700 dark:text-emerald-300"></i>
                <h3 class="text-sm font-semibold text-emerald-950 dark:text-emerald-200">Payment Transaction</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-sm">
                <div><span class="text-gray-600 dark:text-gray-400">Amount:</span> <span class="font-semibold">RWF {{ $fmt($txn->amount) }}</span></div>
                <div><span class="text-gray-600 dark:text-gray-400">Date:</span> {{ \Illuminate\Support\Str::of($txn->transaction_date)->substr(0,10) }}</div>
                <div><span class="text-gray-600 dark:text-gray-400">Channel:</span> {{ strtoupper($txn->method ?? $channel) }}</div>
                <div><span class="text-gray-600 dark:text-gray-400">Ref:</span> {{ $reference ?: '—' }}</div>
            </div>
            @if($txn->notes)
                <p class="text-sm text-emerald-900/90 dark:text-emerald-200/90 mt-1">{{ $txn->notes }}</p>
            @endif
        </div>
    @endif

    {{-- RETURNS LIST --}}
     @if($returns->count())
    <div class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="u-turn-left" class="w-5 h-5 text-indigo-600 dark:text-indigo-300"></i>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Returns to Supplier</h3>
            </div>
            <div class="flex gap-4 text-sm">
                <span class="text-gray-600 dark:text-gray-300">
                    Returned Value:
                    <span class="font-semibold">RWF {{ number_format($sumReturnValue,2) }}</span>
                </span>
                <span class="text-gray-600 dark:text-gray-300">
                    Cash Refunds:
                    <span class="font-semibold text-emerald-700 dark:text-emerald-300">RWF {{ number_format($sumCashRefunds,2) }}</span>
                </span>
                <span class="text-gray-600 dark:text-gray-300">
                    Net Exposure:
                    <span class="font-semibold {{ $netExposure>0?'text-rose-700 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                        RWF {{ number_format($netExposure,2) }}
                    </span>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Channel</th>
                        <th class="px-4 py-2">Reference</th>
                        <th class="px-4 py-2 text-right">Refund</th>
                        <th class="px-4 py-2 text-right">Return Value</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($returns as $ret)
                        <tr x-data="{ show:false }">
                            <td class="px-4 py-2">
                                {{ $ret->return_date ? \Carbon\Carbon::parse($ret->return_date)->format('M j, Y') : '—' }}
                            </td>
                            <td class="px-4 py-2">{{ strtoupper($ret->payment_channel ?? '-') }}</td>
                            <td class="px-4 py-2">{{ $ret->method ?: '—' }}</td>
                            <td class="px-4 py-2 text-right text-emerald-700 dark:text-emerald-300">
                                RWF {{ number_format($ret->refund_amount, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right font-medium">
                                RWF {{ number_format($ret->total_amount, 2) }}
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center justify-end gap-2">
                                    @if($ret->items->count())
                                        <button type="button"
                                                @click="show = !show"
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 hover:underline">
                                            <i data-lucide="chevron-down" class="w-4 h-4" :class="show ? 'rotate-180' : ''"></i>
                                            <span x-text="show ? 'Hide' : 'Details'"></span>
                                        </button>
                                    @endif

                                    <form method="POST"
                                          action="{{ route('purchases.returns.destroy', $ret) }}"
                                          onsubmit="return confirm('Delete this return and revert stock/ledger?');"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-rose-600 hover:text-rose-700 hover:underline">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        @if($ret->items->count())
                            <tr x-show="show" x-cloak class="bg-gray-50/60 dark:bg-gray-800/30">
                                <td colspan="6" class="px-4 py-2">
                                    <div class="text-xs text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold mr-2">Items:</span>
                                        @foreach($ret->items as $it)
                                            <span class="inline-block mr-4 mb-1">
                                                {{ optional($it->product)->name ?? '#'.$it->product_id }}
                                                × {{ number_format($it->quantity,2) }}
                                                @ RWF {{ number_format($it->unit_cost,2) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

</div>

{{-- Modal mount (no auto-open here) --}}
@include('purchases._return_modal', ['purchase' => $purchase])
@endsection

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide icons
  if (window.lucide) {
    lucide.createIcons();
  }

  console.log(' Purchase show page loaded');
});

window.openPurchaseReturn = function(){
  console.log('🔵 openPurchaseReturn() called');
  window.dispatchEvent(new CustomEvent('open-purchase-return'));
};
</script>
@endpush
