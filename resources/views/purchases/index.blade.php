@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $fmt = fn($n) => number_format((float)$n, 2);
    $channels = ['cash','bank','momo'];
@endphp

<div
    x-data="{
        showDel:false, delAction:'', delName:'',
        openDel(action, name){ this.delAction = action; this.delName = name; this.showDel = true },
        closeDel(){ this.showDel=false; this.delAction=''; this.delName='' }
    }"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Purchases</span>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Track supplier purchases, payments, balances and returns.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('purchases.create')
                <a href="{{ route('purchases.create') }}"
                   class="btn btn-primary flex items-center gap-1 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Purchase</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- FLASH / ERRORS --}}
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

    @cannot('purchases.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to view purchases.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- FILTERS --}}
        <form method="GET" action="{{ route('purchases.index') }}"
              class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
                {{-- Supplier --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Supplier</label>
                    <select name="supplier_id"
                            class="form-select w-full text-sm">
                        <option value="">All suppliers</option>
                        @foreach(($suppliers ?? []) as $s)
                            <option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" class="form-select w-full text-sm">
                        <option value="">All</option>
                        <option value="completed" @selected(request('status')==='completed')>Completed</option>
                        <option value="partial"   @selected(request('status')==='partial')>Partial</option>
                        <option value="pending"   @selected(request('status')==='pending')>Pending</option>
                        <option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option>
                    </select>
                </div>

                {{-- Channel --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Channel</label>
                    <select name="payment_channel" class="form-select w-full text-sm">
                        <option value="">Any</option>
                        @foreach($channels as $ch)
                            <option value="{{ $ch }}" @selected(request('payment_channel')===$ch)>
                                {{ strtoupper($ch) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- From --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-input w-full text-sm">
                </div>

                {{-- To --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-input w-full text-sm">
                </div>

                {{-- Search --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Supplier, reference, notes…"
                           class="form-input w-full text-sm">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-1 border-t border-dashed border-gray-200 dark:border-gray-800">
                <button class="btn btn-secondary flex items-center gap-1 text-sm">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('purchases.index') }}"
                   class="btn btn-outline flex items-center gap-1 text-sm">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>

        @php
            // Current page collection (paginator-safe)
            $rows = $purchases instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                ? $purchases->getCollection()
                : collect($purchases);

            // Monetary fields
            $calcTotal = function($p){
                $subtotal = (float)($p->subtotal ?? 0);
                $tax      = (float)($p->tax ?? 0);
                $discount = (float)($p->discount ?? 0);
                return $subtotal + $tax - $discount;
            };

            $pageTotal   = $rows->sum(fn($p) => $calcTotal($p));
            $pagePaid    = $rows->sum(fn($p) => (float)($p->amount_paid ?? 0));
            $pageBalance = max(0, $pageTotal - $pagePaid);

            // Per-channel aggregation
            $byChannel = collect($channels)->mapWithKeys(function($ch) use ($rows, $calcTotal){
                $filtered = $rows->filter(fn($p) => strtolower($p->payment_channel ?? 'cash') === $ch);
                $total    = $filtered->sum(fn($p) => $calcTotal($p));
                $paid     = $filtered->sum(fn($p) => (float)($p->amount_paid ?? 0));
                $balance  = max(0, $total - $paid);
                return [$ch => [
                    'count'   => $filtered->count(),
                    'total'   => $total,
                    'paid'    => $paid,
                    'balance' => $balance,
                ]];
            });

            // Status counts on page
            $statusCounts = $rows
                ->groupBy(fn($p) => strtolower($p->status ?? 'pending'))
                ->map->count();

            // Page-level returns/refunds (works with or without eager loads / withSum)
            $pageReturned = $rows->sum(function($p){
                if (isset($p->returns_value_sum)) return (float)$p->returns_value_sum;
                return (float) optional($p->returns)->sum('total_amount');
            });
            $pageRefund = $rows->sum(function($p){
                if (isset($p->returns_refund_sum)) return (float)$p->returns_refund_sum;
                return (float) optional($p->returns)->sum('refund_amount');
            });
        @endphp

        {{-- PAGE SUMMARY / TOTALS --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Total (page)</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    RWF {{ $fmt($pageTotal) }}
                </div>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Paid (page)</div>
                <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">
                    RWF {{ $fmt($pagePaid) }}
                </div>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Balance (page)</div>
                <div class="text-lg font-semibold {{ $pageBalance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                    RWF {{ $fmt($pageBalance) }}
                </div>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">Status (page)</div>
                <div class="mt-1 flex flex-wrap gap-1.5 text-[11px]">
                    @foreach (['completed'=>'green','partial'=>'yellow','pending'=>'amber','cancelled'=>'gray'] as $st=>$clr)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 bg-{{ $clr }}-100 text-{{ $clr }}-800 dark:bg-{{ $clr }}-900/40 dark:text-{{ $clr }}-300">
                            {{ ucfirst($st) }}: {{ $statusCounts[$st] ?? 0 }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- PER-CHANNEL SUMMARY --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @php
                $chStyle = [
                    'cash' => ['label'=>'Cash','clr'=>'green'],
                    'bank' => ['label'=>'Bank','clr'=>'blue'],
                    'momo' => ['label'=>'MoMo','clr'=>'purple'],
                ];
            @endphp
            @foreach ($byChannel as $key => $agg)
                @php $s = $chStyle[$key]; @endphp
                <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $s['label'] }} ({{ $agg['count'] }})
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-{{ $s['clr'] }}-100 text-{{ $s['clr'] }}-800 dark:bg-{{ $s['clr'] }}-900/40 dark:text-{{ $s['clr'] }}-300">
                            {{ strtoupper($key) }}
                        </span>
                    </div>
                    <div class="mt-1 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                RWF {{ $fmt($agg['total']) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Paid</div>
                            <div class="font-medium text-emerald-700 dark:text-emerald-300">
                                RWF {{ $fmt($agg['paid']) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Balance</div>
                            <div class="font-medium {{ $agg['balance']>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                RWF {{ $fmt($agg['balance']) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TABLE --}}
        <div class="rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-indigo-500"></i>
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    Results
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1250px] w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-5 py-3 text-left">#</th>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Supplier</th>
                            <th class="px-5 py-3 text-right">Subtotal</th>
                            <th class="px-5 py-3 text-right">Tax</th>
                            <th class="px-5 py-3 text-right">Discount</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Balance</th>
                            <th class="px-5 py-3 text-right">Returned</th>
                            <th class="px-5 py-3 text-right">Refund</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Channel</th>
                            <th class="px-5 py-3 text-left">Reference</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($purchases as $p)
                            @php
                                $pdate    = $p->purchase_date ?? $p->created_at;
                                $subtotal = (float)($p->subtotal ?? 0);
                                $tax      = (float)($p->tax ?? 0);
                                $discount = (float)($p->discount ?? 0);
                                $total    = $subtotal + $tax - $discount;
                                $paid     = (float)($p->amount_paid ?? 0);
                                $balance  = max(0, $total - $paid);
                                $status   = strtolower($p->status ?? ($balance > 0 ? 'pending' : 'completed'));
                                $channel  = strtolower($p->payment_channel ?? 'cash');
                                $ref      = $p->method ?: '—';

                                // Returns info
                                $returnsCount   = (int)($p->returns_count ?? optional($p->returns)->count() ?? 0);
                                $returnedValue  = (float)($p->returns_value_sum ?? optional($p->returns)->sum('total_amount') ?? 0);
                                $refundSum      = (float)($p->returns_refund_sum ?? optional($p->returns)->sum('refund_amount') ?? 0);
                                $hasReturns     = $returnsCount > 0;

                                $statusClass = match($status) {
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    'partial'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                    'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300',
                                    default     => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300',
                                };
                                $chClass = [
                                    'cash' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    'bank' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                    'momo' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                ][$channel] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300';
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    #{{ $p->id }}
                                    @if($hasReturns)
                                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold
                                                    bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                              title="{{ $returnsCount }} return(s)">
                                            {{ $returnsCount }}x
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $pdate ? \Carbon\Carbon::parse($pdate)->format('Y-m-d') : '—' }}
                                </td>

                                <td class="px-5 py-3 text-gray-900 dark:text-gray-100">
                                    {{ optional($p->supplier)->name ?? '—' }}
                                </td>

                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    {{ $fmt($subtotal) }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    {{ $fmt($tax) }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    - {{ $fmt($discount) }}
                                </td>

                                <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $fmt($total) }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-200">
                                    {{ $fmt($paid) }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold {{ $balance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                    {{ $fmt($balance) }}
                                </td>

                                {{-- Returned value --}}
                                <td class="px-5 py-3 text-right {{ $hasReturns ? 'text-amber-700 dark:text-amber-300 font-medium' : 'text-gray-400 dark:text-gray-600' }}">
                                    {{ $hasReturns ? $fmt($returnedValue) : '—' }}
                                </td>

                                {{-- Refund --}}
                                <td class="px-5 py-3 text-right {{ $refundSum>0 ? 'text-emerald-700 dark:text-emerald-300 font-medium' : 'text-gray-400 dark:text-gray-600' }}">
                                    {{ $refundSum>0 ? $fmt($refundSum) : '—' }}
                                </td>

                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClass }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>

                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $chClass }}">
                                        {{ strtoupper($channel) }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $ref }}
                                </td>

                                <td class="px-5 py-3">
                                    <div class="flex justify-end flex-wrap gap-1.5">
                                        @can('purchases.view')
                                            <a href="{{ route('purchases.show', $p) }}"
                                               class="btn btn-secondary text-xs flex items-center gap-1">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                                <span>View</span>
                                            </a>
                                        @endcan

                                        @can('purchases.edit')
                                            <a href="{{ route('purchases.edit', $p) }}"
                                               class="btn btn-outline text-xs flex items-center gap-1">
                                                <i data-lucide="file-edit" class="w-4 h-4"></i>
                                                <span>Edit</span>
                                            </a>
                                        @endcan

                                        @can('purchases.view')
                                            @if (Route::has('purchases.invoice'))
                                                <a href="{{ route('purchases.invoice', $p) }}"
                                                   target="_blank"
                                                   class="btn btn-outline text-xs flex items-center gap-1">
                                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                                    <span>Invoice</span>
                                                </a>
                                            @endif
                                        @endcan

                                        @can('purchases.delete')
                                            <form x-on:submit.prevent="openDel($el.action, 'Purchase #{{ $p->id }}')"
                                                  method="POST"
                                                  action="{{ route('purchases.destroy', $p) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger text-xs flex items-center gap-1">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    <span>Delete</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No purchases found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- PAGE TOTALS FOOTER --}}
                    <tfoot class="bg-gray-50 dark:bg-gray-800/50 text-sm">
                        <tr>
                            <td colspan="6" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">
                                Page Totals
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ $fmt($pageTotal) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ $fmt($pagePaid) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $pageBalance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                {{ $fmt($pageBalance) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-amber-700 dark:text-amber-300">
                                {{ $fmt($pageReturned) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">
                                {{ $fmt($pageRefund) }}
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                {{ $purchases instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                    ? $purchases->withQueryString()->links()
                    : '' }}
            </div>
        </div>

        {{-- DELETE MODAL (only if user can delete) --}}
        @can('purchases.delete')
            <div x-cloak x-show="showDel" class="fixed inset-0 z-40">
                <div x-show="showDel" x-transition.opacity class="absolute inset-0 bg-black/40"></div>
                <div x-show="showDel" x-transition class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-800 shadow-xl">
                        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                Delete Purchase
                            </h3>
                        </div>
                        <div class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                            Are you sure you want to delete
                            <span class="font-semibold" x-text="delName"></span>?
                            This will revert any related stock movements.
                        </div>
                        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                            <button type="button" @click="closeDel()" class="btn btn-outline">
                                Cancel
                            </button>
                            <form :action="delAction" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

    @endcannot
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
