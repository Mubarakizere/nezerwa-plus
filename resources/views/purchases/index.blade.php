@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
@php
    use App\Models\Purchase;
    use Carbon\Carbon;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $rows = $purchases instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $purchases->getCollection()
        : collect($purchases);

    $calcTotal = function($p){
        $subtotal = (float)($p->subtotal ?? 0);
        $tax      = (float)($p->tax ?? 0);
        $discount = (float)($p->discount ?? 0);
        return $subtotal + $tax - $discount;
    };

    $pageTotal   = $rows->sum(fn($p) => $calcTotal($p));
    $pagePaid    = $rows->sum(fn($p) => (float)($p->amount_paid ?? 0));
    $pageBalance = max(0, $pageTotal - $pagePaid);

    $pageReturned = $rows->sum(function($p){
        if (isset($p->returns_value_sum)) return (float)$p->returns_value_sum;
        return (float) optional($p->returns)->sum('total_amount');
    });
    $pageRefund = $rows->sum(function($p){
        if (isset($p->returns_refund_sum)) return (float)$p->returns_refund_sum;
        return (float) optional($p->returns)->sum('refund_amount');
    });

    $totalCount = method_exists($purchases, 'total') ? $purchases->total() : $purchases->count();
@endphp

<div
    x-data="{
        showDel: false, delAction: '', delName: '',
        openDel(action, name){ this.delAction = action; this.delName = name; this.showDel = true; },
        closeDel(){ this.showDel = false; this.delAction = ''; this.delName = ''; }
    }"
    class="max-w-7xl mx-auto space-y-6"
>

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="truck" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Purchases Management</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($totalCount) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Monitor supplier procurement orders, track settlement channels, balances, and returns.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('stock.history') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="history" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Stock Movements</span>
            </a>

            @can('purchases.create')
                <a href="{{ route('purchases.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Purchase</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @cannot('purchases.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                        You don’t have permission to view purchases.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- KPI Summary Cards (Dashboard Grade) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Total Volume --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Purchase Volume</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                        {{ $fmt0($pageTotal) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Page orders</span>
                    <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $rows->count() }} purchases</span>
                </div>
            </div>

            {{-- 2. Total Paid --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Paid to Suppliers</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ $fmt0($pagePaid) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Settled obligations</span>
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">Cleared funds</span>
                </div>
            </div>

            {{-- 3. Outstanding Payables --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Outstanding Balance</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight font-mono {{ $pageBalance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">
                        {{ $fmt0($pageBalance) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Accounts payable</span>
                    <span class="font-medium {{ $pageBalance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $pageBalance > 0 ? 'Due to suppliers' : 'Fully settled' }}
                    </span>
                </div>
            </div>

            {{-- 4. Returns & Refunds --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Supplier Returns</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="u-turn-left" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                        {{ $fmt0($pageReturned) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Refunded Amount</span>
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $fmt0($pageRefund) }} RWF</span>
                </div>
            </div>
        </div>

        {{-- Streamlined Filter Panel --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4 space-y-4">
            {{-- Quick Filter Status Pills --}}
            <div class="flex flex-wrap items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800/80">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Status:</span>

                @php
                    $curStatus = request('status', '');
                @endphp

                <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ empty($curStatus) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>All Purchases</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['status' => 'completed', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curStatus === 'completed' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Completed (Paid)</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['status' => 'partial', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curStatus === 'partial' ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Partial Balance</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curStatus === 'pending' ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>Pending (Unpaid)</span>
                </a>
            </div>

            <form method="GET" action="{{ route('purchases.index') }}" class="space-y-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">
                    {{-- Search --}}
                    <div class="md:col-span-4">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Purchases</label>
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Supplier, invoice #, notes, or ID…"
                                class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        </div>
                    </div>

                    {{-- Supplier --}}
                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Supplier</label>
                        <select name="supplier_id"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">All Suppliers</option>
                            @foreach(($suppliers ?? []) as $s)
                                <option value="{{ $s->id }}" @selected((string)request('supplier_id') === (string)$s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Payment Channel --}}
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Channel</label>
                        <select name="payment_channel"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">All Channels</option>
                            @foreach(($paymentChannels ?? []) as $ch)
                                <option value="{{ $ch->slug }}" @selected(request('payment_channel') === $ch->slug)>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="md:col-span-1.5 sm:col-span-1">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">From</label>
                        <input type="date"
                               name="from"
                               value="{{ request('from') }}"
                               class="w-full py-2 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>

                    {{-- Date To --}}
                    <div class="md:col-span-1.5 sm:col-span-1">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">To</label>
                        <input type="date"
                               name="to"
                               value="{{ request('to') }}"
                               class="w-full py-2 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>
                </div>

                {{-- Action Row --}}
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80">
                    <div class="text-xs text-slate-400">
                        Filtering <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $totalCount }}</span> total purchases
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Apply Filter</span>
                        </button>
                        <a href="{{ route('purchases.index') }}"
                           title="Reset all filters"
                           class="inline-flex items-center justify-center p-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Purchases Table Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Purchase # / Date</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Order Total</th>
                            <th class="px-4 py-3 text-right">Paid & Balance</th>
                            <th class="px-4 py-3 text-right">Returns</th>
                            <th class="px-4 py-3">Payment Channel</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
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
                                $ref      = $p->method ?: ($p->invoice_number ?: null);

                                $returnsCount   = (int)($p->returns_count ?? optional($p->returns)->count() ?? 0);
                                $returnedValue  = (float)($p->returns_value_sum ?? optional($p->returns)->sum('total_amount') ?? 0);
                                $refundSum      = (float)($p->returns_refund_sum ?? optional($p->returns)->sum('refund_amount') ?? 0);
                                $hasReturns     = $returnsCount > 0;

                                $statusBadge = match($status) {
                                    'completed' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20',
                                    'partial'   => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20',
                                    'pending'   => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20',
                                    'cancelled' => 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-500/20',
                                    default     => 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-500/20',
                                };

                                $channelPalette = match($channel) {
                                    'cash' => [
                                        'badge' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20',
                                        'icon'  => 'banknote'
                                    ],
                                    'bank' => [
                                        'badge' => 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border border-sky-500/20',
                                        'icon'  => 'building-2'
                                    ],
                                    'momo', 'mobile_money', 'mobile' => [
                                        'badge' => 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20',
                                        'icon'  => 'smartphone'
                                    ],
                                    default => [
                                        'badge' => 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border border-slate-500/20',
                                        'icon'  => 'credit-card'
                                    ]
                                };
                            @endphp

                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                {{-- 1. Order ID & Date --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                            #{{ $p->id }}
                                        </div>
                                        @if($hasReturns)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20"
                                                  title="{{ $returnsCount }} item return(s)">
                                                <i data-lucide="rotate-ccw" class="w-2.5 h-2.5"></i>
                                                {{ $returnsCount }}x ret
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        <span>{{ $pdate ? Carbon::parse($pdate)->format('M j, Y') : '—' }}</span>
                                        @if($p->invoice_number)
                                            <span class="font-mono text-slate-500">({{ $p->invoice_number }})</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- 2. Supplier --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="building" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 dark:text-slate-100 line-clamp-1">
                                                {{ optional($p->supplier)->name ?? 'Walk-in / Unknown' }}
                                            </div>
                                            @if($ref)
                                                <div class="text-[10px] text-slate-400 line-clamp-1">{{ $ref }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 3. Status --}}
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge }}">
                                        @if($status === 'completed')
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        @elseif($status === 'partial')
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        @else
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        @endif
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>

                                {{-- 4. Order Total --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 font-mono">
                                        {{ $fmt2($total) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                    </div>
                                    @if($discount > 0 || $tax > 0)
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Sub: {{ $fmt0($subtotal) }}
                                            @if($discount > 0) -{{ $fmt0($discount) }}@endif
                                            @if($tax > 0) +{{ $fmt0($tax) }}@endif
                                        </div>
                                    @endif
                                </td>

                                {{-- 5. Paid & Balance --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                        Paid: {{ $fmt2($paid) }}
                                    </div>
                                    @if($balance > 0)
                                        <div class="font-mono text-[11px] font-bold text-rose-600 dark:text-rose-400 mt-0.5">
                                            Bal: {{ $fmt2($balance) }}
                                        </div>
                                    @else
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                            Fully Settled
                                        </div>
                                    @endif
                                </td>

                                {{-- 6. Returns & Refunds --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if($hasReturns)
                                        <div class="font-mono text-amber-600 dark:text-amber-400 font-semibold">
                                            {{ $fmt0($returnedValue) }} RWF
                                        </div>
                                        @if($refundSum > 0)
                                            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono">
                                                Ref: {{ $fmt0($refundSum) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-slate-400 font-mono">—</span>
                                    @endif
                                </td>

                                {{-- 7. Payment Channel --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $channelPalette['badge'] }}">
                                        <i data-lucide="{{ $channelPalette['icon'] }}" class="w-3 h-3"></i>
                                        <span>{{ strtoupper($channel) }}</span>
                                    </div>
                                </td>

                                {{-- 8. Actions --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        @can('purchases.view')
                                            <a href="{{ route('purchases.show', $p) }}"
                                               title="View Purchase Details"
                                               class="p-1.5 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg transition-colors">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('purchases.edit')
                                            <a href="{{ route('purchases.edit', $p) }}"
                                               title="Edit Purchase"
                                               class="p-1.5 text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('purchases.view')
                                            @if (Route::has('purchases.invoice'))
                                                <a href="{{ route('purchases.invoice', $p) }}"
                                                   target="_blank"
                                                   title="View / Print Invoice"
                                                   class="p-1.5 text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 rounded-lg transition-colors">
                                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                        @endcan

                                        @can('purchases.delete')
                                            <button type="button"
                                                    title="Delete Purchase"
                                                    @click="openDel('{{ route('purchases.destroy', $p) }}', 'Purchase #{{ $p->id }}')"
                                                    class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                            <i data-lucide="truck" class="w-6 h-6"></i>
                                        </div>
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">No purchases found</h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            No procurement records match your active search or filters. Try adjusting your parameters or resetting filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Table Summary Footer --}}
                    @if($rows->isNotEmpty())
                        <tfoot class="bg-slate-50/80 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                                    Page Totals ({{ $rows->count() }} orders):
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                    {{ $fmt2($pageTotal) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">
                                    <div class="text-emerald-600 dark:text-emerald-400 font-bold">Paid: {{ $fmt2($pagePaid) }}</div>
                                    @if($pageBalance > 0)
                                        <div class="text-rose-600 dark:text-rose-400 text-[11px] font-bold">Bal: {{ $fmt2($pageBalance) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-amber-600 dark:text-amber-400 font-bold">
                                    {{ $pageReturned > 0 ? $fmt0($pageReturned) . ' RWF' : '—' }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Pagination Footer --}}
            @if(method_exists($purchases, 'links') && $purchases->hasPages())
                <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Showing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $purchases->firstItem() ?? 0 }}</span>
                        to <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $purchases->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fmt0($purchases->total()) }}</span> purchases
                    </div>
                    <div>
                        {{ $purchases->withQueryString()->links() }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Delete Confirmation Modal --}}
        @can('purchases.delete')
            <div x-cloak
                 x-show="showDel"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4"
                 @keydown.escape.window="closeDel()">
                <div @click.outside="closeDel()"
                     class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl p-6 w-full max-w-md transform transition-all">
                    <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">Delete Purchase</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-5 leading-relaxed">
                        Are you sure you want to delete <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="delName"></span>?
                        This will automatically revert any associated inventory stock movements and supplier balance adjustments.
                    </p>
                    <div class="flex justify-end gap-2.5">
                        <button type="button"
                                @click="closeDel()"
                                class="px-3.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            Cancel
                        </button>
                        <form :action="delAction" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3.5 py-1.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors">
                                Delete Purchase
                            </button>
                        </form>
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
