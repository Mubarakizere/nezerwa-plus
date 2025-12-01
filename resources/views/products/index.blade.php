@extends('layouts.app')
@section('title', 'Products')

@section('content')
@php
    use App\Models\Category;
    use Illuminate\Support\Str;

    // Number formatting helpers
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    // Low-stock threshold (fallback to 5 if not provided)
    $threshold = (int)($threshold ?? 5);

    // Ensure categories exist even if controller forgot to pass them
    $allCategories    = $categories ?? Category::orderBy('name')->get();
    $usableCategories = collect($allCategories)
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']))
        ->values();

    // Make stats work whether $products is a Paginator or a Collection
    $__coll = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products);

    $isPaginated = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $pageCount   = $isPaginated ? $products->count() : $__coll->count();
    $totalCount  = $isPaginated ? $products->total() : $pageCount;

    // Page (current result set) derived stats
    $pageUnits   = $__coll->sum(fn($p) => max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0)));
    $pageValue   = $__coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->cost_price ?? 0);
    });
    $pageRevenue = $__coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->price ?? 0);
    });
    $pageReturns = $__coll->sum(fn($p) => (float)($p->qty_returned ?? 0));
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="flex items-center gap-2 text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
            <i data-lucide="package" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <span>Products</span>
        </h1>

        <div class="flex flex-wrap gap-2 justify-start md:justify-end">
            @can('products.create')
                <a href="{{ route('products.create') }}"
                   class="btn btn-primary flex items-center gap-1 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Product</span>
                </a>
            @endcan

            @can('stock.view')
                <a href="{{ route('stock.history', request()->only('product_id')) }}"
                   class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    <span>Stock Movements</span>
                </a>
            @endcan
        </div>
    </div>

    @cannot('products.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to view products.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else


        <div>
            {{-- Filters --}}
            <form method="GET" action="{{ route('products.index') }}"
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-3"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3">
                    {{-- Search --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Name, SKU…"
                                class="form-input w-full pl-8 text-sm">
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Category</label>
                        <select name="category_id" class="form-select w-full text-sm" onchange="this.form.submit()">
                            <option value="">All categories</option>
                            @foreach($usableCategories as $c)
                                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} {{ $c->code ? "({$c->code})" : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Stock status --}}
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Stock Status</label>
                        <select name="stock_status" class="form-select w-full text-sm" onchange="this.form.submit()">
                            <option value="">Any</option>
                            <option value="in" {{ request('stock_status') == 'in' ? 'selected' : '' }}>In stock</option>
                            <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Low (≤ {{ $threshold }})</option>
                            <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Out of stock</option>
                        </select>
                    </div>

                    {{-- Per page --}}
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Per page</label>
                        <select name="per_page" class="form-select w-full text-sm" onchange="this.form.submit()">
                            @foreach([10,20,50,100] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page') == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 justify-end pt-2">
                    <a href="{{ route('products.index') }}"
                       class="btn btn-outline text-sm flex items-center gap-1">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span>Reset</span>
                    </a>
                    <button type="submit" class="btn btn-primary text-sm flex items-center gap-1">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Search</span>
                    </button>
                </div>
            </form>

            {{-- Page stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-6">
                <x-stat-card title="Products (page)" value="{{ $pageCount }}" color="indigo" />
                <x-stat-card title="Units in stock (page)" value="{{ $fmt0($pageUnits) }}" color="blue" />
                <x-stat-card title="Stock value @ cost (page)" value="RWF {{ $fmt2($pageValue) }}" color="emerald" />
                <x-stat-card title="Potential revenue (page)" value="RWF {{ $fmt2($pageRevenue) }}" color="amber" />
            </div>

            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Showing {{ $pageCount }} of {{ $totalCount }} matching products.
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-x-auto mt-4 relative">
                @include('products._table')
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $products->withQueryString()->links() }}
            </div>
        </div>



{{-- Global Delete Confirmation Modal --}}
@can('products.delete')
<div
    x-data
    x-show="$store.confirm.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    @keydown.escape.window="$store.confirm.close()"
>
    <div
        @click.outside="$store.confirm.close()"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md"
    >
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Delete product</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this product?
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

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });

    document.addEventListener('alpine:init', () => {
        // Global delete-confirm store (same pattern as sales)
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
    });
</script>
@endpush
@endsection
