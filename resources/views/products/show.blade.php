@extends('layouts.app')
@section('title', $product->name . ' Details')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
            <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>{{ $product->name }}</span>
        </h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-outline text-sm flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary text-sm flex items-center gap-1">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
            </a>
        </div>
    </div>

    {{-- 🔸 Product Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-stat-card title="Category" value="{{ $product->category->name ?? '—' }}" color="gray" />
        <x-stat-card title="Price" value="RWF {{ number_format($product->price, 0) }}" color="indigo" />
        <x-stat-card title="Current Stock" value="{{ number_format($current, 0) }}" color="{{ $current <= 5 ? 'red' : 'green' }}" />
    </div>

    {{-- 🔸 Stock Totals --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total In" value="{{ number_format($totalIn, 0) }}" color="green" />
        <x-stat-card title="Total Out" value="{{ number_format($totalOut, 0) }}" color="red" />
        <x-stat-card title="Stock Value" value="RWF {{ number_format($product->stockValue(), 0) }}" color="blue" />
    </div>

    {{-- 🔹 Recent Stock Movements --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700
                rounded-xl shadow-sm dark:shadow-[0_0_15px_rgba(255,255,255,0.03)] overflow-hidden mt-4">

        {{-- Header --}}
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Recent Stock Movements</h2>
            <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
               class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline">
               View All →
            </a>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-right">Quantity</th>
                        <th class="px-4 py-3 text-right">Unit Cost</th>
                        <th class="px-4 py-3 text-right">Total Cost</th>
                        <th class="px-4 py-3 text-left">Recorded By</th>
                        <th class="px-4 py-3 text-left">Source</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($product->stockMovements as $move)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $move->created_at->format('Y-m-d H:i') }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $move->type === 'in'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ strtoupper($move->type) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">
                                {{ number_format($move->quantity, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                {{ number_format($move->unit_cost ?? 0, 2) }}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($move->total_cost ?? 0, 2) }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $move->user->name ?? 'System' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($move->source_type === App\Models\Purchase::class)
                                    <a href="{{ route('purchases.show', $move->source_id) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Purchase #{{ $move->source_id }}
                                    </a>
                                @elseif($move->source_type === App\Models\Sale::class)
                                    <a href="{{ route('sales.show', $move->source_id) }}"
                                       class="text-green-600 dark:text-green-400 hover:underline">
                                        Sale #{{ $move->source_id }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Manual</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No recent stock movements found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
