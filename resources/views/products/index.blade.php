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
    $coll = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products);

    $isPaginated = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $pageCount   = $isPaginated ? $products->count() : $coll->count();
    $totalCount  = $isPaginated ? $products->total() : $pageCount;

    // Page (current result set) derived stats
    $pageUnits   = $coll->sum(fn($p) => max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0)));
    $pageValue   = $coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->cost_price ?? 0);
    });
    $pageRevenue = $coll->sum(function($p){
        $units = max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0));
        return $units * (float)($p->price ?? 0);
    });
    $pageReturns = $coll->sum(fn($p) => (float)($p->qty_returned ?? 0));
    $potentialProfit = max(0, $pageRevenue - $pageValue);
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Products Catalog</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($totalCount) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Manage catalog items, monitor real-time stock levels, pricing, and inventory valuation.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('stock.view')
                <a href="{{ route('stock.history', request()->only('product_id')) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="history" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Stock Movements</span>
                </a>
            @endcan

            @can('products.view')
                {{-- Excel Import Dropdown --}}
                <div x-data="{ open: false }" class="relative" @click.away="open = false">
                    <button @click="open = !open"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                        <span>Excel Import</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-1.5 w-52 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1 z-30">
                        <a href="{{ route('products.import.template') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="download" class="w-4 h-4 text-slate-400"></i>
                            <span>Download Template</span>
                        </a>
                        <button @click="open = false; $dispatch('open-import-modal')" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left">
                            <i data-lucide="upload" class="w-4 h-4 text-slate-400"></i>
                            <span>Upload & Import</span>
                        </button>
                    </div>
                </div>

                {{-- Export Report Dropdown --}}
                <div x-data="{ open: false }" class="relative" @click.away="open = false">
                    <button @click="open = !open"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Export PDF</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-1.5 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1 z-30">
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'all']) }}" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <span>All Products</span>
                        </a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'low']) }}" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <span>Low Stock (≤ {{ $threshold }})</span>
                        </a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'out']) }}" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <span>Out of Stock</span>
                        </a>
                    </div>
                </div>
            @endcan

            @can('products.create')
                <a href="{{ route('products.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Product</span>
                </a>
            @endcan
        </div>
    </div>

    @cannot('products.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                        You don’t have permission to view products.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- KPI Summary Cards (Clean Dashboard Style) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Catalog Products --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Catalog Items</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="package" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                        {{ $fmt0($pageCount) }}
                        @if($isPaginated && $totalCount !== $pageCount)
                            <span class="text-xs font-normal text-slate-400">of {{ $fmt0($totalCount) }}</span>
                        @else
                            <span class="text-xs font-normal text-slate-400">items</span>
                        @endif
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Active catalog</span>
                    <span class="font-medium text-indigo-600 dark:text-indigo-400">Page results</span>
                </div>
            </div>

            {{-- 2. Physical Units in Stock --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Units In Stock</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="boxes" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-sky-600 dark:text-sky-400">
                        {{ $fmt0($pageUnits) }}
                        <span class="text-xs font-normal text-slate-400">units</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Available stock</span>
                    @if($pageReturns > 0)
                        <span class="font-medium text-amber-600 dark:text-amber-400">{{ $fmt0($pageReturns) }} returns</span>
                    @else
                        <span class="font-medium text-slate-400">Physical count</span>
                    @endif
                </div>
            </div>

            {{-- 3. Stock Value @ Cost --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inventory Value</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ number_format($pageValue) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Capital tied up</span>
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">Cost basis</span>
                </div>
            </div>

            {{-- 4. Potential Revenue --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Potential Revenue</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                        {{ number_format($pageRevenue) }}
                        <span class="text-xs font-medium text-slate-400 font-sans">RWF</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Retail valuation</span>
                    <span class="font-medium text-amber-600 dark:text-amber-400">+{{ number_format($potentialProfit) }} profit</span>
                </div>
            </div>
        </div>

        {{-- Streamlined Filter Panel --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4 space-y-4">
            {{-- Quick Filter Pills --}}
            <div class="flex flex-wrap items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800/80">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Status:</span>

                @php
                    $currentStatus = request('stock_status', '');
                @endphp

                <a href="{{ request()->fullUrlWithQuery(['stock_status' => null, 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ empty($currentStatus) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>All Products</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['stock_status' => 'in', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $currentStatus === 'in' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>In Stock</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['stock_status' => 'low', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $currentStatus === 'low' ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Low Stock (≤ {{ $threshold }})</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['stock_status' => 'out', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $currentStatus === 'out' ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>Out of Stock</span>
                </a>
            </div>

            <form
                method="GET"
                action="{{ route('products.index') }}"
                x-data="{ qcat: '' }"
                class="space-y-3"
            >
                @if(request('stock_status'))
                    <input type="hidden" name="stock_status" value="{{ request('stock_status') }}">
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">
                    {{-- Search --}}
                    <div class="md:col-span-4">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Catalog</label>
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search by name, SKU, or ID…"
                                class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="md:col-span-4">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Category</label>
                        <select
                            name="category_id"
                            class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                        >
                            <option value="">All Categories</option>
                            @foreach($usableCategories as $c)
                                @php
                                    $label = trim($c->name.' '.($c->code ? "({$c->code})" : ''));
                                @endphp
                                <option
                                    value="{{ $c->id }}"
                                    @selected(request('category_id') == $c->id)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Per Page --}}
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Per Page</label>
                        <select name="per_page"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                                onchange="this.form.submit()">
                            @foreach([10, 20, 50, 100] as $pp)
                                <option value="{{ $pp }}" @selected((int)request('per_page', 20) === $pp)>{{ $pp }} rows</option>
                            @endforeach
                            <option value="-1" @selected((int)request('per_page') === -1)>All rows</option>
                        </select>
                    </div>

                    {{-- Buttons --}}
                    <div class="md:col-span-2 flex items-end gap-2">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('products.index') }}"
                           title="Reset all filters"
                           class="inline-flex items-center justify-center p-2 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Products Table Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div id="products-table-container">
                @include('products._table')
            </div>

            {{-- Pagination Footer --}}
            @if($isPaginated)
                <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Showing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $products->firstItem() ?? 0 }}</span>
                        to <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $products->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fmt0($products->total()) }}</span> products
                    </div>
                    <div>
                        {{ $products->withQueryString()->links() }}
                    </div>
                </div>
            @endif
        </div>

    @endcannot
</div>

{{-- Global Delete Confirmation Modal --}}
@can('products.delete')
<div
    x-data
    x-show="$store.confirm.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4"
    @keydown.escape.window="$store.confirm.close()"
>
    <div
        @click.outside="$store.confirm.close()"
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl p-6 w-full max-w-md transform transition-all"
    >
        <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">Delete Product</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5 leading-relaxed">
            Are you sure you want to delete this product? This action cannot be undone if sales or inventory history are linked.
        </p>
        <div class="flex justify-end gap-2.5">
            <button type="button"
                    class="px-3.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                    @click="$store.confirm.close()">
                Cancel
            </button>
            <button type="button"
                    class="px-3.5 py-1.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors"
                    @click="$store.confirm.confirm()">
                Delete Product
            </button>
        </div>
    </div>
</div>
@endcan

{{-- Excel Import Modal --}}
@can('products.view')
<div
    x-data="importModal()"
    @open-import-modal.window="open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4"
    @keydown.escape.window="close()"
>
    <div
        @click.outside="close()"
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto"
    >
        {{-- Step 1: Upload --}}
        <div x-show="step === 1" class="p-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="upload-cloud" class="w-5 h-5 text-indigo-600"></i>
                        <span>Import Products from Excel</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Step 1 of 2: Upload Excel spreadsheet</p>
                </div>
                <button @click="close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Upload Area --}}
            <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 text-center bg-slate-50/50 dark:bg-slate-850">
                <i data-lucide="file-spreadsheet" class="w-12 h-12 mx-auto text-indigo-600 dark:text-indigo-400 mb-3"></i>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 mb-1">Upload Product Spreadsheet</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Supported formats: .xlsx, .xls, .csv (Max 10MB)</p>

                <input
                    type="file"
                    x-ref="fileInput"
                    @change="handleFileSelect($event)"
                    accept=".xlsx,.xls,.csv"
                    class="hidden"
                >

                <button
                    @click="$refs.fileInput.click()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors"
                >
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    Choose Spreadsheet File
                </button>

                <div x-show="selectedFile" class="mt-3 text-xs text-slate-700 dark:text-slate-300">
                    <span class="font-semibold">Selected file:</span> <span class="font-mono" x-text="selectedFile?.name"></span>
                </div>
            </div>

            {{-- Loading State --}}
            <div x-show="uploading" class="mt-4 p-3.5 bg-blue-500/10 rounded-xl border border-blue-500/20">
                <div class="flex items-center gap-3">
                    <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-medium text-blue-800 dark:text-blue-300">Processing file rows, please wait...</span>
                </div>
            </div>

            {{-- Error Messages --}}
            <div x-show="errors.length > 0" class="mt-4 p-4 bg-rose-500/10 rounded-xl border border-rose-500/20">
                <h4 class="font-semibold text-xs text-rose-800 dark:text-rose-200 mb-2 flex items-center gap-1.5">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    Errors Found in Spreadsheet
                </h4>
                <ul class="text-xs text-rose-700 dark:text-rose-300 space-y-1 list-disc list-inside">
                    <template x-for="error in errors.slice(0, 8)" :key="error.row">
                        <li>Row <span class="font-mono font-bold" x-text="error.row"></span>: <span x-text="error.errors?.join(', ')"></span></li>
                    </template>
                </ul>
                <p x-show="errors.length > 8" class="text-[11px] text-rose-600 dark:text-rose-400 mt-2">
                    And <span x-text="errors.length - 8"></span> more errors...
                </p>
            </div>

            <div class="flex justify-end gap-2.5 mt-6">
                <button @click="close()" class="px-3.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Cancel</button>
            </div>
        </div>

        {{-- Step 2: Preview & Confirm --}}
        <div x-show="step === 2" class="p-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                        <span>Review & Confirm Import</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Step 2 of 2: Choose import mode and finalize</p>
                </div>
                <button @click="close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-3 gap-3 mb-5">
                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3.5 text-center">
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 font-mono" x-text="stats.new || 0"></div>
                    <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300 mt-0.5">New Products</div>
                </div>
                <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3.5 text-center">
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 font-mono" x-text="stats.existing || 0"></div>
                    <div class="text-xs font-medium text-amber-700 dark:text-amber-300 mt-0.5">Existing Products</div>
                </div>
                <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3.5 text-center">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 font-mono" x-text="stats.total || 0"></div>
                    <div class="text-xs font-medium text-indigo-700 dark:text-indigo-300 mt-0.5">Total Spreadsheet Rows</div>
                </div>
            </div>

            {{-- Import Mode Selection --}}
            <div class="mb-5 p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-xl">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2.5">
                    Select Import Mode
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="relative flex items-start p-3.5 border-2 rounded-xl cursor-pointer transition"
                           :class="mode === 'add' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                        <input type="radio" name="mode" value="add" x-model="mode" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2.5">
                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100">Add to Stock</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Adds Excel qty to existing stock (e.g. 10 + 5 = 15).
                            </div>
                        </div>
                    </label>
                    <label class="relative flex items-start p-3.5 border-2 rounded-xl cursor-pointer transition"
                           :class="mode === 'replace' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                        <input type="radio" name="mode" value="replace" x-model="mode" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2.5">
                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100">Replace Stock</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Overwrites stock with exact Excel qty (e.g. 10 → 5).
                            </div>
                        </div>
                    </label>
                    <label class="relative flex items-start p-3.5 border-2 rounded-xl cursor-pointer transition"
                           :class="mode === 'full_replace' ? 'border-rose-600 bg-rose-50/50 dark:bg-rose-950/40' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                        <input type="radio" name="mode" value="full_replace" x-model="mode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                        <div class="ml-2.5">
                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100">Full Catalog Sync</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Deletes unlisted items, sets stock to file qty.
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Preview Table --}}
            <div class="border border-slate-200/80 dark:border-slate-800 rounded-xl overflow-hidden mb-5">
                <div class="bg-slate-50 dark:bg-slate-800/50 px-4 py-2.5 border-b border-slate-200/80 dark:border-slate-800">
                    <h3 class="text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Preview (first 10 rows)</h3>
                </div>
                <div class="overflow-x-auto max-h-72">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-100/70 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-semibold sticky top-0">
                            <tr>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Product Name</th>
                                <th class="px-3 py-2">Category</th>
                                <th class="px-3 py-2 text-right">Price</th>
                                <th class="px-3 py-2 text-right">Current Stock</th>
                                <th class="px-3 py-2 text-right">Excel Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            <template x-for="item in parsed" :key="item.row">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td class="px-3 py-2">
                                        <span x-show="item.status === 'new'" class="px-2 py-0.5 text-[10px] rounded-full font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">New</span>
                                        <span x-show="item.status === 'existing'" class="px-2 py-0.5 text-[10px] rounded-full font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">Update</span>
                                    </td>
                                    <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100" x-text="item.data.name"></td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-slate-400" x-text="item.data.category || '—'"></td>
                                    <td class="px-3 py-2 text-right font-mono text-slate-700 dark:text-slate-300" x-text="'RWF ' + parseFloat(item.data.price || 0).toLocaleString()"></td>
                                    <td class="px-3 py-2 text-right font-mono" x-text="item.current_stock || 0"></td>
                                    <td class="px-3 py-2 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="item.data.stock"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-between items-center gap-3">
                <button @click="step = 1; errors = []" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back
                </button>
                <div class="flex items-center gap-2.5">
                    <button @click="close()" class="px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button
                        @click="executeImport()"
                        :disabled="!mode || importing"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors"
                        :class="{'opacity-50 cursor-not-allowed': !mode || importing}"
                    >
                        <span x-show="!importing" class="inline-flex items-center gap-1.5">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            Confirm Import
                        </span>
                        <span x-show="importing" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importing Products...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    function importModal() {
        return {
            open: false,
            step: 1,
            selectedFile: null,
            uploading: false,
            importing: false,
            mode: 'add',
            parsed: [],
            errors: [],
            stats: {},

            close() {
                this.open = false;
                this.step = 1;
                this.selectedFile = null;
                this.parsed = [];
                this.errors = [];
                this.stats = {};
                this.mode = 'add';
            },

            async handleFileSelect(event) {
                this.selectedFile = event.target.files[0];
                if (!this.selectedFile) return;

                this.uploading = true;
                this.errors = [];

                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route("products.import.upload") }}', {
                        method: 'POST',
                        body: formData,
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.parsed = data.parsed;
                        this.stats = data.stats;
                        this.errors = data.errors || [];
                        this.step = 2;
                        setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 100);
                    } else {
                        this.errors = [{ row: 0, errors: [data.message || 'Error processing file'] }];
                    }
                } catch (error) {
                    this.errors = [{ row: 0, errors: ['Failed to upload file. Please try again.'] }];
                } finally {
                    this.uploading = false;
                }
            },

            async executeImport() {
                if (!this.mode) return;

                this.importing = true;

                const formData = new FormData();
                formData.append('mode', this.mode);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route("products.import.execute") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Import failed. Please try again.');
                        this.importing = false;
                    }
                } catch (error) {
                    console.error(error);
                    alert('Import failed. Please check the console or try again.');
                    this.importing = false;
                }
            }
        };
    }

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