@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 mb-6">
        <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
            <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Products</span>
        </h1>

        <a href="{{ route('products.create') }}" class="btn btn-primary flex items-center gap-1 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Product
        </a>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Product Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Total In</th>
                        <th class="px-4 py-3 text-right">Total Out</th>
                        <th class="px-4 py-3 text-right">Current Stock</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($products as $product)
                        @php
                            $current = $product->currentStock();
                            $isLow = $current <= 5;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                            {{-- Name --}}
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium">
                                <a href="{{ route('products.show', $product) }}"
                                   class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                    {{ $product->name }}
                                </a>
                            </td>

                            {{-- Category --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $product->category->name ?? '—' }}
                            </td>

                            {{-- Price --}}
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ number_format($product->price, 0) }}
                            </td>

                            {{-- Total In --}}
                            <td class="px-4 py-3 text-right text-green-700 dark:text-green-400 font-medium">
                                {{ number_format($product->total_in ?? 0, 0) }}
                            </td>

                            {{-- Total Out --}}
                            <td class="px-4 py-3 text-right text-red-700 dark:text-red-400 font-medium">
                                {{ number_format($product->total_out ?? 0, 0) }}
                            </td>

                            {{-- Current Stock --}}
                            <td class="px-4 py-3 text-right font-semibold {{ $isLow ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ number_format($current, 0) }}
                                @if($isLow)
                                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                                        Low
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-secondary text-xs inline-flex items-center gap-1">
                                    <i data-lucide="eye" class="w-4 h-4"></i> View
                                </a>

                                <a href="{{ route('products.edit', $product) }}" class="btn btn-outline text-xs inline-flex items-center gap-1">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
                                </a>

                                <a href="{{ route('stock.history', ['product_id' => $product->id]) }}" class="btn btn-outline text-xs inline-flex items-center gap-1">
                                    <i data-lucide="history" class="w-4 h-4"></i> Movements
                                </a>

                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="btn btn-danger text-xs inline-flex items-center gap-1"
                                            @click="$store.confirm.openWith($el.closest('form'))">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="pt-4">
            {{ $products->links() }}
        </div>
    @endif
</div>

{{-- Global Delete Confirmation Modal --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.open=false">
    <div @click.outside="$store.confirm.open=false"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this product? This action will not remove stock history.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.open=false">Cancel</button>
            <button type="button" class="btn btn-danger"
                    @click="$store.confirm.submitEl?.submit(); $store.confirm.open=false;">
                Delete
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        submitEl: null,
        openWith(form) {
            this.submitEl = form;
            this.open = true;
        }
    });
});
</script>
@endpush
@endsection
