{{-- resources/views/sales/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Sales')

@section('content')
@php use Carbon\Carbon; @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
            <i data-lucide="shopping-cart" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Sales</span>
        </h1>

        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('sales.export', request()->query()) }}"
               class="btn btn-outline text-sm flex items-center gap-1">
                <i data-lucide="download" class="w-4 h-4"></i> Export CSV
            </a>
            <a href="{{ route('sales.create') }}"
               class="btn btn-primary flex items-center gap-2 text-sm sm:text-base">
                <i data-lucide="plus" class="w-4 h-4"></i> New Sale
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
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
                    <option value="cash" {{ request('channel')==='cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank" {{ request('channel')==='bank' ? 'selected' : '' }}>Bank</option>
                    <option value="momo" {{ request('channel')==='momo' ? 'selected' : '' }}>MoMo</option>
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

            {{-- Sort (optional, matches controller if enabled) --}}
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

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-x-auto">
        <table class="w-full text-sm text-left min-w-[1100px]">
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
                            default => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        };
                        $icon  = match($channel) {
                            'bank' => 'credit-card',
                            'momo' => 'smartphone',
                            default => 'banknote',
                        };

                        $returnsTotal = (float) ($sale->returns_total ?? $sale->returns()->sum('amount'));
                        $grossTotal   = (float) ($sale->total_amount ?? 0);
                        $netAfter     = max(0, $grossTotal - $returnsTotal);
                        $paid         = (float) ($sale->amount_paid ?? 0);
                        $balance      = max(0, round($netAfter - $paid, 2));
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

                            @if ($sale->loan)
                                <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    {{ $sale->loan->status === 'paid'
                                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                                        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' }}">
                                    Loan {{ ucfirst($sale->loan->status) }}
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[11px] font-medium {{ $badge }}">
                                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                                {{ strtoupper($channel) }}
                            </span>
                            @if($sale->method)
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    Ref: {{ $sale->method }}
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end flex-wrap gap-1.5">
                                <a href="{{ route('sales.show', $sale) }}"
                                   class="btn btn-secondary text-xs px-2.5 py-1.5 flex items-center gap-1">
                                   <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                </a>

                                <a href="{{ route('sales.edit', $sale) }}"
                                   class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                   <i data-lucide="edit" class="w-3.5 h-3.5"></i> Edit
                                </a>

                                <a href="{{ route('sales.invoice', $sale) }}" target="_blank"
                                   class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                   <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Invoice
                                </a>

                                <a href="{{ route('sales.show', $sale) }}?open=returns"
                                   class="btn btn-outline text-xs px-2.5 py-1.5 flex items-center gap-1">
                                   <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Returns
                                </a>

                                <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Delete this sale? This will revert stock movements.');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger text-xs px-2.5 py-1.5 flex items-center gap-1">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                    </button>
                                </form>
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
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
@endsection
