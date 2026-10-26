{{-- resources/views/sales/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Sales')

@section('content')
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $paymentsPdfAction = \Illuminate\Support\Facades\Route::has('sales.payments.pdf')
        ? route('sales.payments.pdf')
        : (\Illuminate\Support\Facades\Route::has('reports.sales.payments.pdf')
            ? route('reports.sales.payments.pdf')
            : null);

    $user = auth()->user();

    $rows = $sales instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $sales->getCollection()
        : collect($sales);

    $fmt = fn($n) => number_format((float)$n, 2);
@endphp

<div
    x-data="{
        applyRange(preset){
            const url = new URL(window.location.href);
            const today = new Date();
            const fmt = d => d.toISOString().slice(0,10);

            let from=null, to=null;
            if(preset==='today'){
                from = fmt(today); to = fmt(today);
            }else if(preset==='week'){
                const day = today.getDay();
                const diffToMon = (day === 0 ? -6 : 1 - day);
                const monday = new Date(today); monday.setDate(today.getDate()+diffToMon);
                const sunday = new Date(monday); sunday.setDate(monday.getDate()+6);
                from = fmt(monday); to = fmt(sunday);
            }else if(preset==='month'){
                const first = new Date(today.getFullYear(), today.getMonth(), 1);
                const last  = new Date(today.getFullYear(), today.getMonth()+1, 0);
                from = fmt(first); to = fmt(last);
            }else if(preset==='all'){
                url.searchParams.delete('from');
                url.searchParams.delete('to');
                window.location.href = url.toString(); return;
            }

            if(from&&to){
                url.searchParams.set('from', from);
                url.searchParams.set('to', to);
            }
            window.location.href = url.toString();
        }
    }"
    class="max-w-7xl mx-auto space-y-6"
>

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                    Sales Management
                </h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ method_exists($sales, 'total') ? $sales->total() : count($sales) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Monitor orders, track payment channels, and manage customer sales.
            </p>
        </div>

        @can('sales.view')
            <div class="flex items-center flex-wrap gap-2">
                {{-- CSV Export --}}
                <a href="{{ route('sales.export', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="download" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Export CSV</span>
                </a>

                {{-- PDF Summary --}}
                @if (\Illuminate\Support\Facades\Route::has('reports.sales.summary.pdf'))
                    <a href="{{ route('reports.sales.summary.pdf', request()->query()) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Summary PDF</span>
                    </a>
                @elseif (\Illuminate\Support\Facades\Route::has('reports.sales.pdf'))
                    <a href="{{ route('reports.sales.pdf', request()->query()) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Summary PDF</span>
                    </a>
                @endif

                {{-- Payments PDF --}}
                @if ($paymentsPdfAction)
                    <button type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                            @click="$store.paypdf.open = true">
                        <i data-lucide="receipt" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Payments PDF</span>
                    </button>
                @endif

                {{-- New Sale Button --}}
                @can('sales.create')
                    <a href="{{ route('sales.create') }}"
                       class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>New Sale</span>
                    </a>
                @endcan
            </div>
        @endcan
    </div>

    @cannot('sales.view')
        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                        You don’t have permission to view sales.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- Channel Summary Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($paymentChannels as $channel)
                @php
                    $slug = $channel->slug;
                    $label = $channel->name;

                    $palette = match($slug) {
                        'cash' => [
                            'badge' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20',
                            'icon' => 'banknote',
                        ],
                        'bank' => [
                            'badge' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20',
                            'icon' => 'building-2',
                        ],
                        'momo', 'mobile_money', 'mobile' => [
                            'badge' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20',
                            'icon' => 'smartphone',
                        ],
                        'boss_gift' => [
                            'badge' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20',
                            'icon' => 'gift',
                        ],
                        default => [
                            'badge' => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20',
                            'icon' => 'credit-card',
                        ]
                    };

                    $filtered = $rows->filter(fn($s) => strtolower($s->payment_channel ?? 'cash') === $slug);
                    $count    = $filtered->count();
                    $total    = $filtered->sum(fn($s) => (float)($s->total_amount ?? 0));
                    $paid     = $filtered->sum(fn($s) => (float)($s->amount_paid ?? 0));
                    $balance  = ($slug === 'boss_gift') ? 0.0 : max(0, $total - $paid);
                @endphp
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            {{ $label }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $palette['badge'] }}">
                            <i data-lucide="{{ $palette['icon'] }}" class="w-3 h-3"></i>
                            {{ $count }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Volume</div>
                        <div class="text-lg font-bold text-slate-900 dark:text-slate-100">
                            {{ $fmt($total) }} <span class="text-xs font-normal text-slate-400">RWF</span>
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                        <div class="text-slate-500 dark:text-slate-400">
                            Paid: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $fmt($paid) }}</span>
                        </div>
                        @if ($slug === 'boss_gift')
                            <span class="text-[10px] font-semibold text-purple-600 dark:text-purple-400">Gift Sale</span>
                        @else
                            <div class="text-slate-500 dark:text-slate-400">
                                Bal: <span class="font-semibold {{ $balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">{{ $fmt($balance) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Streamlined Filter Panel --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4">
            <form method="GET" action="{{ route('sales.index') }}" class="space-y-3.5">
                {{-- Row 1: Search and Core Filters --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                    {{-- Search Input --}}
                    <div class="lg:col-span-4">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search</label>
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input
                                type="text"
                                name="search"
                                placeholder="Search customer, product, status, or #ID"
                                value="{{ request('search') }}"
                                class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        </div>
                    </div>

                    {{-- Channel Select --}}
                    <div class="lg:col-span-3">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Channel</label>
                        <select name="channel"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">All Channels</option>
                            @foreach($paymentChannels as $ch)
                                <option value="{{ $ch->slug }}" {{ request('channel')===$ch->slug ? 'selected' : '' }}>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Select --}}
                    <div class="lg:col-span-3">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                        <select name="status"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">All Statuses</option>
                            <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending"   {{ request('status')==='pending'   ? 'selected' : '' }}>Pending</option>
                            <option value="cancelled" {{ request('status')==='cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    {{-- Sale Type Select --}}
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Sale Type</label>
                        <select name="type"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">All Types</option>
                            <option value="regular" {{ request('type')==='regular' ? 'selected' : '' }}>Regular Sales</option>
                            <option value="gift" {{ request('type')==='gift' ? 'selected' : '' }}>🎁 Boss Gifts</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2: Dates, Quick Ranges, Sorting & Submit --}}
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Date Inputs --}}
                        <div class="flex items-center gap-1.5">
                            <input type="date" name="from" value="{{ request('from') }}" title="From Date"
                                   class="py-1.5 px-2.5 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100">
                            <span class="text-xs text-slate-400">to</span>
                            <input type="date" name="to" value="{{ request('to') }}" title="To Date"
                                   class="py-1.5 px-2.5 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100">
                        </div>

                        {{-- Quick Presets --}}
                        <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/60">
                            <button type="button" class="px-2 py-1 text-[11px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-md transition" @click="applyRange('today')">Today</button>
                            <button type="button" class="px-2 py-1 text-[11px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-md transition" @click="applyRange('week')">Week</button>
                            <button type="button" class="px-2 py-1 text-[11px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-md transition" @click="applyRange('month')">Month</button>
                            <button type="button" class="px-2 py-1 text-[11px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-md transition" @click="applyRange('all')">All</button>
                        </div>

                        {{-- With Returns Checkbox --}}
                        <label class="inline-flex items-center gap-1.5 ml-2 cursor-pointer text-xs text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="has_returns" value="1" {{ request('has_returns') ? 'checked' : '' }}
                                   class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span>With Returns</span>
                        </label>
                    </div>

                    {{-- Sort & Apply Buttons --}}
                    <div class="flex items-center gap-2 self-end lg:self-auto">
                        <select name="sort" class="py-1.5 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300">
                            @php $sort = request('sort','sale_date'); @endphp
                            <option value="sale_date"    {{ $sort==='sale_date'    ? 'selected' : '' }}>Date</option>
                            <option value="total_amount" {{ $sort==='total_amount' ? 'selected' : '' }}>Total</option>
                            <option value="amount_paid"  {{ $sort==='amount_paid'  ? 'selected' : '' }}>Paid</option>
                            <option value="id"           {{ $sort==='id'           ? 'selected' : '' }}>Sale #</option>
                        </select>

                        <select name="dir" class="py-1.5 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300">
                            @php $dir = request('dir','desc'); @endphp
                            <option value="desc" {{ $dir==='desc' ? 'selected' : '' }}>Desc</option>
                            <option value="asc"  {{ $dir==='asc'  ? 'selected' : '' }}>Asc</option>
                        </select>

                        <select name="per_page" onchange="this.form.submit()" class="py-1.5 px-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300">
                            <option value="15" {{ (int)request('per_page',15)===15 ? 'selected' : '' }}>15 / page</option>
                            <option value="25" {{ (int)request('per_page')===25 ? 'selected' : '' }}>25 / page</option>
                            <option value="50" {{ (int)request('per_page')===50 ? 'selected' : '' }}>50 / page</option>
                            <option value="all" {{ request('per_page')==='all' ? 'selected' : '' }}>All</option>
                        </select>

                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Filter</span>
                        </button>

                        <a href="{{ route('sales.index') }}" class="px-2.5 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Sales Data Table --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left min-w-[980px]">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Date & Time</th>
                            <th scope="col" class="px-4 py-3">Customer</th>
                            <th scope="col" class="px-4 py-3">Products Sold</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Channel</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">Total</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">Paid</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">Balance Due</th>
                            <th scope="col" class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse ($sales as $sale)
                            @php
                                $date    = $sale->sale_date ? Carbon::parse($sale->sale_date) : ($sale->created_at ?? now());
                                $channel = strtolower($sale->payment_channel ?? 'cash');

                                $palette = match($channel) {
                                    'bank' => [
                                        'badge' => 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/40',
                                        'icon' => 'building-2',
                                    ],
                                    'momo', 'mobile_money', 'mobile' => [
                                        'badge' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40',
                                        'icon' => 'smartphone',
                                    ],
                                    'boss_gift' => [
                                        'badge' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/40',
                                        'icon' => 'gift',
                                    ],
                                    default => [
                                        'badge' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40',
                                        'icon' => 'banknote',
                                    ],
                                };

                                $returnsTotal = (float) ($sale->returns_total ?? $sale->returns()->sum('amount'));
                                $grossTotal   = (float) ($sale->total_amount ?? 0);
                                $netAfter     = max(0, $grossTotal - $returnsTotal);
                                $paid         = (float) ($sale->amount_paid ?? 0);
                                $balance      = $sale->isGift() ? 0.0 : max(0, round($netAfter - $paid, 2));

                                $splitHint = '';
                                try {
                                    if (method_exists($sale, 'payments')) {
                                        $cnt = $sale->payments()->count();
                                        if ($cnt > 1) $splitHint = "Split";
                                    }
                                } catch (\Throwable $e) {}
                            @endphp

                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                {{-- Date & Time with subtle receipt number --}}
                                <td class="px-4 py-3.5 whitespace-nowrap align-top">
                                    <div class="font-medium text-slate-800 dark:text-slate-200 text-xs">
                                        {{ $date->format('M j, Y') }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                        <span>{{ $date->format('g:i A') }}</span>
                                        <span class="text-slate-300 dark:text-slate-600">•</span>
                                        <span class="font-mono text-slate-400 dark:text-slate-500">#{{ $sale->id }}</span>
                                    </div>
                                </td>

                                {{-- Customer --}}
                                <td class="px-4 py-3.5 align-top">
                                    <div class="min-w-0 max-w-[170px]">
                                        @if ($sale->customer)
                                            <div class="font-semibold text-slate-900 dark:text-slate-100 text-xs truncate" title="{{ $sale->customer->name }}">
                                                {{ $sale->customer->name }}
                                            </div>
                                            @if ($sale->customer->phone)
                                                <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                                                    {{ $sale->customer->phone }}
                                                </div>
                                            @endif
                                        @elseif ($sale->supplier)
                                            <div class="font-semibold text-amber-700 dark:text-amber-400 text-xs truncate" title="{{ $sale->supplier->name }}">
                                                {{ $sale->supplier->name }}
                                            </div>
                                            <div class="text-[10px] text-amber-600 dark:text-amber-500 font-medium mt-0.5">
                                                Supplier Offset
                                            </div>
                                        @else
                                            <div class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                                                Walk-in Customer
                                            </div>
                                        @endif

                                        @if ($sale->isGift())
                                            <div class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200/50 dark:border-purple-800/40" title="{{ $sale->gift_reason ?? 'Boss Gift' }}">
                                                <i data-lucide="gift" class="w-3 h-3 text-purple-500"></i>
                                                <span>Boss Gift</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Products Sold --}}
                                <td class="px-4 py-3.5 align-top">
                                    @php
                                        $items = $sale->items ?? collect();
                                        $itemsCount = $items->count();
                                        $totalUnits = $items->sum('quantity');
                                    @endphp

                                    @if($itemsCount > 0)
                                        <div class="space-y-1 min-w-[200px] max-w-[280px]">
                                            @foreach($items->take(2) as $item)
                                                @php
                                                    $qty = (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : (float)$item->quantity;
                                                    $pName = $item->product->name ?? 'Unknown item';
                                                @endphp
                                                <div class="flex items-center gap-1.5 text-xs">
                                                    <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded text-[10px] font-semibold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 shrink-0">
                                                        {{ $qty }}×
                                                    </span>
                                                    <span class="truncate font-medium text-slate-800 dark:text-slate-200" title="{{ $pName }} ({{ $qty }} pcs)">
                                                        {{ $pName }}
                                                    </span>
                                                </div>
                                            @endforeach

                                            @if($itemsCount > 2)
                                                @php
                                                    $allTooltip = $items->map(function($it) {
                                                        $q = (float)$it->quantity == (int)$it->quantity ? (int)$it->quantity : (float)$it->quantity;
                                                        return $q . '× ' . ($it->product->name ?? 'Item');
                                                    })->implode(', ');
                                                @endphp
                                                <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium cursor-help flex items-center gap-1" title="{{ $allTooltip }}">
                                                    <span class="text-indigo-600 dark:text-indigo-400 hover:underline">+{{ $itemsCount - 2 }} more</span>
                                                    <span class="text-slate-300 dark:text-slate-600">•</span>
                                                    <span>{{ (float)$totalUnits == (int)$totalUnits ? (int)$totalUnits : (float)$totalUnits }} units</span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">No products recorded</span>
                                    @endif
                                </td>

                                {{-- Channel --}}
                                <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium {{ $palette['badge'] }}">
                                            <i data-lucide="{{ $palette['icon'] }}" class="w-3.5 h-3.5"></i>
                                            <span>{{ strtoupper($channel) }}</span>
                                        </span>
                                        @if($splitHint)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/40">
                                                {{ $splitHint }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Total Amount --}}
                                <td class="px-4 py-3.5 text-right whitespace-nowrap align-top">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                        {{ number_format($grossTotal, 2) }}
                                        <span class="text-[10px] font-normal text-slate-400">RWF</span>
                                    </div>
                                    @if ($returnsTotal > 0)
                                        <div class="text-[10px] text-rose-500 font-medium mt-0.5">
                                            -{{ number_format($returnsTotal, 2) }} ret.
                                        </div>
                                    @endif
                                </td>

                                {{-- Paid Amount --}}
                                <td class="px-4 py-3.5 text-right whitespace-nowrap align-top">
                                    <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        {{ number_format($paid, 2) }}
                                        <span class="text-[10px] font-normal text-slate-400">RWF</span>
                                    </div>
                                </td>

                                {{-- Balance Due --}}
                                <td class="px-4 py-3.5 text-right whitespace-nowrap align-top">
                                    @if ($sale->isGift())
                                        <span class="text-[11px] font-medium text-purple-600 dark:text-purple-400">
                                            Gift (0.00)
                                        </span>
                                    @elseif ($balance <= 0)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                            Cleared
                                        </span>
                                    @else
                                        <div class="text-xs font-bold text-rose-600 dark:text-rose-400">
                                            {{ number_format($balance, 2) }}
                                            <span class="text-[10px] font-normal text-rose-400">RWF</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap align-top">
                                    @if ($sale->status === 'completed')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/50 dark:border-emerald-800/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Completed
                                        </span>
                                    @elseif ($sale->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/50 dark:border-amber-800/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/50 dark:border-rose-800/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Cancelled
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3.5 text-right whitespace-nowrap align-top">
                                    <div class="inline-flex items-center gap-0.5 justify-end">
                                        @can('sales.view')
                                            <a href="{{ route('sales.show', $sale) }}"
                                               title="View Sale Details"
                                               class="p-1.5 rounded-md text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>

                                            <a href="{{ route('sales.invoice', $sale) }}" target="_blank"
                                               title="View & Print Invoice"
                                               class="p-1.5 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                            </a>

                                            <a href="{{ route('sales.show', $sale) }}?open=returns"
                                               title="Process Returns"
                                               class="p-1.5 rounded-md text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('sales.edit')
                                            <a href="{{ route('sales.edit', $sale) }}"
                                               title="Edit Sale"
                                               class="p-1.5 rounded-md text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('sales.delete')
                                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="button"
                                                        title="Delete Sale"
                                                        class="p-1.5 rounded-md text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors"
                                                        @click="$store.confirm.openWith($el.closest('form'))">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <i data-lucide="shopping-bag" class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600"></i>
                                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No sales records found</p>
                                        <p class="text-xs text-slate-500">Try adjusting your filters or record a new sale.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Table Pagination Footer --}}
            @if(method_exists($sales, 'hasPages') && $sales->hasPages())
                <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>

    @endcannot
</div>

{{-- Global Delete Confirmation Modal --}}
@can('sales.delete')
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4"
     @keydown.escape.window="$store.confirm.close()"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-md">
        <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mb-1.5">Confirm Sale Deletion</h2>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mb-6">
            Are you sure you want to delete this sale? This action will reverse stock movements and monetary logs and cannot be undone.
        </p>
        <div class="flex justify-end gap-2.5">
            <button type="button" class="px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors" @click="$store.confirm.close()">
                Cancel
            </button>
            <button type="button" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors" @click="$store.confirm.confirm()">
                Yes, Delete Sale
            </button>
        </div>
    </div>
</div>
@endcan

{{-- Payments PDF Modal --}}
@if ($paymentsPdfAction)
    @can('sales.view')
    <div x-data
         x-show="$store.paypdf.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4"
         @keydown.escape.window="$store.paypdf.open=false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.outside="$store.paypdf.open=false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-md">

            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <i data-lucide="file-bar-chart" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                        Export Payments PDF
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Breakdown of collected funds across channels
                    </p>
                </div>
            </div>

            <form id="paymentsPdfForm" method="GET" action="{{ $paymentsPdfAction }}" class="space-y-4 mt-4">
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Select Reporting Period</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" name="period" value="daily" class="text-indigo-600 border-slate-300" checked>
                            <span class="font-medium text-slate-700 dark:text-slate-200">Daily (Today)</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" name="period" value="weekly" class="text-indigo-600 border-slate-300">
                            <span class="font-medium text-slate-700 dark:text-slate-200">This Week</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" name="period" value="monthly" class="text-indigo-600 border-slate-300">
                            <span class="font-medium text-slate-700 dark:text-slate-200">This Month</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                            <input type="radio" name="period" value="custom" class="text-indigo-600 border-slate-300"
                                   x-model="$store.paypdf.period"
                                   @change="$store.paypdf.period='custom'">
                            <span class="font-medium text-slate-700 dark:text-slate-200">Custom Range</span>
                        </label>
                    </div>
                </div>

                {{-- Custom Range --}}
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">From</label>
                        <input type="date" name="from"
                               x-bind:disabled="$store.paypdf.period!=='custom'"
                               x-model="$store.paypdf.from"
                               class="w-full py-1.5 px-2.5 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 disabled:opacity-50">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">To</label>
                        <input type="date" name="to"
                               x-bind:disabled="$store.paypdf.period!=='custom'"
                               x-model="$store.paypdf.to"
                               class="w-full py-1.5 px-2.5 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 disabled:opacity-50">
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" class="px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors" @click="$store.paypdf.open=false">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Download Statement PDF</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan
@endif

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('confirm', {
            open: false,
            submitEl: null,
            openWith(form) {
                this.submitEl = form;
                this.open = true;
            },
            close() {
                this.open = false;
                this.submitEl = null;
            },
            confirm() {
                if (this.submitEl) this.submitEl.submit();
                this.close();
            },
        });

        Alpine.store('paypdf', {
            open: false,
            period: 'daily',
            from: '{{ request('from') ?? now()->toDateString() }}',
            to:   '{{ request('to')   ?? now()->toDateString() }}'
        });
    });
</script>
@endpush
@endsection
