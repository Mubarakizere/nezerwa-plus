@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Stock Movements
        </h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('stock.history.export.csv', request()->query()) }}"
               class="btn btn-success text-sm flex items-center gap-1">
                <i data-lucide="file-text" class="w-4 h-4"></i> Export CSV
            </a>
            <a href="{{ route('stock.history.export.pdf', request()->query()) }}"
               target="_blank"
               class="btn btn-primary text-sm flex items-center gap-1">
                <i data-lucide="file-down" class="w-4 h-4"></i> Export PDF
            </a>
        </div>
    </div>

    {{-- 🔸 Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total In" value="{{ number_format($totals['in'], 2) }}" color="green" />
        <x-stat-card title="Total Out" value="{{ number_format($totals['out'], 2) }}" color="red" />
        <x-stat-card title="Net Movement" value="{{ number_format($totals['net'], 2) }}" color="{{ $totals['net'] >= 0 ? 'blue' : 'red' }}" />
    </div>

    {{-- 🔸 Filters --}}
    <form method="GET" action="{{ route('stock.history') }}"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">

        <div class="md:col-span-2">
            <label class="form-label text-xs">Product</label>
            <select name="product_id" class="form-select">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label text-xs">Type</label>
            <select name="type" class="form-select">
                <option value="">All</option>
                <option value="in" @selected(request('type') == 'in')>In</option>
                <option value="out" @selected(request('type') == 'out')>Out</option>
            </select>
        </div>

        <div>
            <label class="form-label text-xs">From</label>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input">
        </div>

        <div>
            <label class="form-label text-xs">To</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input">
        </div>

        <div class="md:col-span-5 flex justify-end mt-2">
            <button type="submit" class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="filter" class="w-4 h-4"></i> Filter
            </button>
        </div>
    </form>

    {{-- 🔸 Table --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Product</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-right">Quantity</th>
                        <th class="px-4 py-3 text-right">Unit Cost</th>
                        <th class="px-4 py-3 text-right">Total Cost</th>
                        <th class="px-4 py-3 text-left">Recorded By</th>
                        <th class="px-4 py-3 text-left">Source</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($movements as $m)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $m->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $m->product->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $m->type === 'in'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ strtoupper($m->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-semibold">
                                {{ number_format($m->quantity, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                {{ number_format($m->unit_cost ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-semibold">
                                {{ number_format($m->total_cost ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $m->user->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($m->source_type === App\Models\Purchase::class)
                                    <a href="{{ route('purchases.show', $m->source_id) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Purchase #{{ $m->source_id }}
                                    </a>
                                @elseif($m->source_type === App\Models\Sale::class)
                                    <a href="{{ route('sales.show', $m->source_id) }}"
                                       class="text-green-600 dark:text-green-400 hover:underline">
                                        Sale #{{ $m->source_id }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                No movements found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
            {{ $movements->withQueryString()->links() }}
        </div>
    </div>
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
