{{-- resources/views/sales/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Sales')

@section('content')
@php
    use Carbon\Carbon;

    $paymentsPdfAction = \Illuminate\Support\Facades\Route::has('sales.payments.pdf')
        ? route('sales.payments.pdf')
        : (\Illuminate\Support\Facades\Route::has('reports.sales.payments.pdf')
            ? route('reports.sales.payments.pdf')
            : null);

    $user = auth()->user();
@endphp

<div
    x-data="{
        // Quick range helpers (filters)
        applyRange(preset){
            const url = new URL(window.location.href);
            const today = new Date();
            const fmt = d => d.toISOString().slice(0,10);

            let from=null, to=null;
            if(preset==='today'){
                from = fmt(today); to = fmt(today);
            }else if(preset==='week'){
                const day = today.getDay(); // 0-6
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
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
            <i data-lucide="shopping-cart" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Sales</span>
        </h1>

        @can('sales.view')
            <div class="flex items-center flex-wrap gap-2">
                {{-- CSV --}}
                <a href="{{ route('sales.export', request()->query()) }}"
                   class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="download" class="w-4 h-4"></i> Export CSV
                </a>

                {{-- PDF: Summary (optional/if exists) --}}
                @if (\Illuminate\Support\Facades\Route::has('reports.sales.summary.pdf'))
                    <a href="{{ route('reports.sales.summary.pdf', request()->query()) }}"
                       class="btn btn-outline text-sm flex items-center gap-1">
                        <i data-lucide="file-down" class="w-4 h-4"></i> PDF Summary
                    </a>
                @elseif (\Illuminate\Support\Facades\Route::has('reports.sales.pdf'))
                    <a href="{{ route('reports.sales.pdf', request()->query()) }}"
                       class="btn btn-outline text-sm flex items-center gap-1">
                        <i data-lucide="file-down" class="w-4 h-4"></i> PDF Summary
                    </a>
                @endif

                {{-- PDF: Payments by Method (exportPaymentsPdf) --}}
                @if ($paymentsPdfAction)
                    <button type="button"
                            class="btn btn-outline text-sm flex items-center gap-1"
                            @click="$store.paypdf.open = true">
                        <i data-lucide="file-bar-chart-2" class="w-4 h-4"></i> Payments PDF
                    </button>
                @endif

                {{-- New Sale --}}
                @can('sales.create')
                    <a href="{{ route('sales.create') }}"
                       class="btn btn-primary flex items-center gap-2 text-sm sm:text-base">
                        <i data-lucide="plus" class="w-4 h-4"></i> New Sale
                    </a>
                @endcan
            </div>
        @endcan
    </div>

    @cannot('sales.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6 mt-4">
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
    @else

        {{-- SUMMARY CARDS (Page Level) --}}
        @php
            // Calculate page-level stats
            $rows = $sales instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                ? $sales->getCollection()
                : collect($sales);

            $fmt = fn($n) => number_format((float)$n, 2);
        @endphp

        

        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col md:flex-row flex-wrap items-end gap-3">

                {{-- Search --}}
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Search</label>
                    <input
                        type="text"
                        name="search"
                        placeholder="Customer, channel, status, or #"
                        value="{{ request('search') }}"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                </div>

                {{-- Channel --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Channel</label>
                    <select name="channel"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                        <option value="">All</option>
                        @foreach($paymentChannels as $ch)
                            <option value="{{ $ch->slug }}" {{ request('channel')===$ch->slug ? 'selected' : '' }}>
                                {{ $ch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select name="status"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                        <option value="">All</option>
                        <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending"   {{ request('status')==='pending'   ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ request('status')==='cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                {{-- Date range --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                </div>

                {{-- Quick ranges --}}
                <div class="flex items-center gap-1 h-[38px] mt-5 md:mt-0">
                    <button type="button" class="btn btn-outline text-xs px-2 py-1" @click="applyRange('today')">Today</button>
                    <button type="button" class="btn btn-outline text-xs px-2 py-1" @click="applyRange('week')">This Week</button>
                    <button type="button" class="btn btn-outline text-xs px-2 py-1" @click="applyRange('month')">This Month</button>
                    <button type="button" class="btn btn-outline text-xs px-2 py-1" @click="applyRange('all')">All</button>
                </div>

                {{-- Has returns --}}
                <div class="flex items-center h-[38px] mt-5 md:mt-0">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="has_returns" value="1" {{ request('has_returns') ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        <span>With Returns</span>
                    </label>
                </div>

                {{-- Per page --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Per page</label>
                    <select name="per_page" onchange="this.form.submit()"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                        @foreach([10,15,25,50,100] as $n)
                            <option value="{{ $n }}" {{ (int)request('per_page',15)===$n ? 'selected' : '' }}>
                                {{ $n }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Sort --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Sort</label>
                    <select name="sort" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                        @php $sort = request('sort','sale_date'); @endphp
                        <option value="sale_date"    {{ $sort==='sale_date'    ? 'selected' : '' }}>Date</option>
                        <option value="total_amount" {{ $sort==='total_amount' ? 'selected' : '' }}>Total</option>
                        <option value="amount_paid"  {{ $sort==='amount_paid'  ? 'selected' : '' }}>Paid</option>
                        <option value="id"           {{ $sort==='id'           ? 'selected' : '' }}>ID</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Direction</label>
                    <select name="dir" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2">
                        @php $dir = request('dir','desc'); @endphp
                        <option value="asc"  {{ $dir==='asc'  ? 'selected' : '' }}>Asc</option>
                        <option value="desc" {{ $dir==='desc' ? 'selected' : '' }}>Desc</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-outline text-sm px-4 py-2 flex items-center gap-1">
                        <i data-lucide="filter" class="w-4 h-4"></i> Filter
                    </button>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline text-sm px-4 py-2">
                        Reset
                    </a>
                </div>
            </form>
        </div>
         
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
            @foreach ($paymentChannels as $channel)
                @php
                    $slug = $channel->slug;
                    $label = $channel->name;
                    $color = match($slug) {
                        'cash' => 'green',
                        'bank' => 'blue',
                        'momo', 'mobile_money' => 'purple',
                        default => 'gray'
                    };

                    // Filter rows for this channel
                    $filtered = $rows->filter(fn($s) => strtolower($s->payment_channel ?? 'cash') === $slug);
                    $count    = $filtered->count();
                    
                    // Sums
                    $total    = $filtered->sum(fn($s) => (float)($s->total_amount ?? 0));
                    $paid     = $filtered->sum(fn($s) => (float)($s->amount_paid ?? 0));
                    // Net after returns logic if needed, but keeping simple for now matching purchases
                    $balance  = max(0, $total - $paid);
                @endphp
                <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $label }} ({{ $count }})
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/40 dark:text-{{ $color }}-300">
                            {{ strtoupper($slug) }}
                        </span>
                    </div>
                    <div class="mt-2 space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Total</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $fmt($total) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Paid</span>
                            <span class="font-medium text-emerald-700 dark:text-emerald-300">{{ $fmt($paid) }}</span>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-800 pt-2 flex justify-between items-center text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Balance</span>
                            <span class="font-medium {{ $balance>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                {{ $fmt($balance) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[1150px]">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Returns</th>
                        <th class="px-4 py-3 text-right">Net After Returns</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Channel</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($sales as $sale)
                        @php
                            $date    = $sale->sale_date ? Carbon::parse($sale->sale_date) : ($sale->created_at ?? now());
                            $channel = strtolower($sale->payment_channel ?? 'cash');

                            $badge = match($channel) {
                                'bank' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                'momo' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                'mobile' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                default => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            };
                            $icon  = match($channel) {
                                'bank' => 'credit-card',
                                'momo' => 'smartphone',
                                'mobile' => 'phone',
                                default => 'banknote',
                            };

                            $returnsTotal = (float) ($sale->returns_total ?? $sale->returns()->sum('amount'));
                            $grossTotal   = (float) ($sale->total_amount ?? 0);
                            $netAfter     = max(0, $grossTotal - $returnsTotal);
                            $paid         = (float) ($sale->amount_paid ?? 0);
                            $balance      = max(0, round($netAfter - $paid, 2));

                            $splitHint = '';
                            try {
                                if (method_exists($sale, 'payments')) {
                                    $cnt = $sale->payments()->count();
                                    if ($cnt > 1) $splitHint = "Split";
                                }
                            } catch (\Throwable $e) {}
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $sale->id }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $sale->customer->name ?? 'Walk-in' }}</td>

                            <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-200">
                                {{ number_format($grossTotal, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right font-medium {{ $returnsTotal>0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $returnsTotal>0 ? '- '.number_format($returnsTotal, 2) : number_format(0, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($netAfter, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-300">
                                {{ number_format($paid, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold {{ $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ number_format($balance, 2) }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($sale->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">Completed</span>
                                @elseif ($sale->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300">Pending</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300">Cancelled</span>
                                @endif

                                @if ($returnsTotal > 0)
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                        Returns
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[11px] font-medium {{ $badge }}">
                                        <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                                        {{ strtoupper($channel) }}
                                    </span>
                                    @if($splitHint)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            {{ $splitHint }}
                                        </span>
                                    @endif
                                </div>
                                @if($sale->method)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Ref: {{ $sale->method }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end flex-wrap gap-1.5">

                                    @can('sales.view')
                                        <a href="{{ route('sales.show', $sale) }}"
                                           class="btn btn-secondary text-xs px-2.5 py-1.5 flex items-center gap-1">
                                           <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                        </a>

                                        <a href="{{ route('sales.invoice', $sale) }}" target="_blank"
                                           class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                           <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Invoice
                                        </a>

                                        <a href="{{ route('sales.show', $sale) }}?open=returns"
                                           class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                           <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Returns
                                        </a>
                                    @endcan

                                    @can('sales.edit')
                                        <a href="{{ route('sales.edit', $sale) }}"
                                           class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                           <i data-lucide="edit" class="w-3.5 h-3.5"></i> Edit
                                        </a>
                                    @endcan

                                    @can('sales.delete')
                                        {{-- Delete -> global confirm store --}}
                                        <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-danger text-xs px-2.5 py-1.5 inline-flex items-center gap-1"
                                                    @click="$store.confirm.openWith($el.closest('form'))">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No sales recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $sales->links() }}
        </div>

    @endcannot
</div>

{{-- Global Delete Confirmation Modal (Alpine Store) --}}
@can('sales.delete')
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.close()"
     x-transition>
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this sale? This action cannot be undone and will revert stock movements.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.close()">Cancel</button>
            <button type="button" class="btn btn-danger" @click="$store.confirm.confirm()">
                Delete
            </button>
        </div>
    </div>
</div>
@endcan

{{-- Payments PDF Modal (Alpine Store) --}}
@if ($paymentsPdfAction)
    @can('sales.view')
    <div x-data
         x-show="$store.paypdf.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @keydown.escape.window="$store.paypdf.open=false"
         x-transition>
        <div @click.outside="$store.paypdf.open=false"
             class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">

            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">
                Export Payments PDF
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                Choose a period or custom range for the payments-by-method report.
            </p>

            <form id="paymentsPdfForm" method="GET" action="{{ $paymentsPdfAction }}" class="space-y-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400">Period</label>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="period" value="daily" class="text-indigo-600 border-gray-300" checked>
                            <span>Daily (today)</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="period" value="weekly" class="text-indigo-600 border-gray-300">
                            <span>Weekly (this week)</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="period" value="monthly" class="text-indigo-600 border-gray-300">
                            <span>Monthly (this month)</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="period" value="custom" class="text-indigo-600 border-gray-300"
                                   x-model="$store.paypdf.period"
                                   @change="$store.paypdf.period='custom'">
                            <span>Custom range</span>
                        </label>
                    </div>
                </div>

                {{-- Custom range fields (only used when period=custom) --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">From</label>
                        <input type="date" name="from"
                               x-bind:disabled="$store.paypdf.period!=='custom'"
                               x-model="$store.paypdf.from"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">To</label>
                        <input type="date" name="to"
                               x-bind:disabled="$store.paypdf.period!=='custom'"
                               x-model="$store.paypdf.to"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900/50 dark:text-gray-100 text-sm px-3 py-2">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-outline" @click="$store.paypdf.open=false">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download PDF</span>
                    </button>
                </div>
            </form>

            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
                Tip: If you already set <em>From/To</em> in the filters above, pick <strong>Custom</strong> and the dates will prefill.
            </p>
        </div>
    </div>
    @endcan
@endif

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });

    document.addEventListener('alpine:init', () => {
        // Global delete-confirm store (reused)
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

        // Payments PDF modal store
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
