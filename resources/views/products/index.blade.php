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

            @can('products.view')
                {{-- Excel Import Dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="btn btn-success text-sm flex items-center gap-1">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                        <span>Excel Import</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 ml-1"></i>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg py-1 z-50">
                        <a href="{{ route('products.import.template') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex items-center gap-2">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Download Template</span>
                            </div>
                        </a>
                        <button @click="open = false; $dispatch('open-import-modal')" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex items-center gap-2">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                                <span>Upload & Import</span>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Export Report Dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="btn btn-outline text-sm flex items-center gap-1">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Export Report</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 ml-1"></i>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg py-1 z-50">
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'all']) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">All Products</a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'low']) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Low Stock (≤ {{ $threshold }})</a>
                        <a href="{{ route('products.export.stock.pdf', ['filter' => 'out']) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Out of Stock</a>
                    </div>
                </div>
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

        {{-- Filters --}}
        <form
            method="GET"
            action="{{ route('products.index') }}"
            x-data="{ qcat: '' }"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-3"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3">
                {{-- Search --}}
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Search</label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Name, SKU…"
                            class="form-input w-full pl-8 text-sm">
                    </div>
                </div>

                {{-- Category --}}
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Category</label>

                    <div class="relative mb-1">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            x-model="qcat"
                            type="text"
                            placeholder="Filter categories…"
                            class="form-input w-full pl-8 text-xs"
                            aria-label="Filter categories">
                    </div>

                    <select
                        name="category_id"
                        class="form-select w-full text-sm"
                        x-init="
                            $watch('qcat', v => {
                                const opts = $el.querySelectorAll('option[data-name]');
                                const k = (v || '').toLowerCase();
                                opts.forEach(o => {
                                    o.hidden = !o.dataset.name.includes(k) && o.value !== '';
                                });
                            })
                        "
                    >
                        <option value="">All categories</option>
                        @foreach($usableCategories as $c)
                            @php
                                $label = trim($c->name.' '.($c->code ? "({$c->code})" : ''));
                            @endphp
                            <option
                                value="{{ $c->id }}"
                                data-name="{{ Str::lower($label) }}"
                                @selected(request('category_id') == $c->id)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Stock status --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Stock Status</label>
                    <select name="stock_status" class="form-select w-full text-sm">
                        <option value="">Any</option>
                        <option value="in"  @selected(request('stock_status')==='in')>In stock</option>
                        <option value="low" @selected(request('stock_status')==='low')>Low (≤ {{ $threshold }})</option>
                        <option value="out" @selected(request('stock_status')==='out')>Out of stock</option>
                    </select>
                </div>

                {{-- Per page --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Per page</label>
                    <select name="per_page" class="form-select w-full text-sm" onchange="this.form.submit()">
                        @foreach([10,20,50,100] as $pp)
                            <option value="{{ $pp }}" @selected((int)request('per_page', 20)===$pp)>{{ $pp }}</option>
                        @endforeach
                            <option value="-1" @selected((int)request('per_page')===-1)>All</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-2">
                <button class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('products.index') }}"
                   class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>

        {{-- Page stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <x-stat-card title="Products (page)" value="{{ $fmt0($pageCount) }}" color="indigo" />
            <x-stat-card title="Units in stock (page)" value="{{ $fmt0($pageUnits) }}" color="blue" />
            <x-stat-card title="Stock value @ cost (page)" value="RWF {{ $fmt2($pageValue) }}" color="emerald" />
            <x-stat-card title="Potential revenue (page)" value="RWF {{ $fmt2($pageRevenue) }}" color="amber" />
        </div>

        @if($isPaginated && $totalCount !== $pageCount)
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Showing {{ $pageCount }} of {{ $fmt0($totalCount) }} matching products.
            </div>
        @endif

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Margin</th>
                        <th class="px-4 py-3 text-right">In</th>
                        <th class="px-4 py-3 text-right">Out</th>
                        <th class="px-4 py-3 text-right">Returned</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($products as $p)
                        @php
                            $in   = (float)($p->qty_in ?? 0);
                            $out  = (float)($p->qty_out ?? 0);
                            $ret  = (float)($p->qty_returned ?? 0);
                            $stk  = max(0, $in - $out);
                            $low  = $stk <= $threshold && $stk > 0;
                            $zero = $stk <= 0;

                            $cost   = (float)($p->cost_price ?? 0);
                            $price  = (float)($p->price ?? 0);
                            $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;

                            $last  = $p->last_moved_at ? \Carbon\Carbon::parse($p->last_moved_at)->diffForHumans() : '—';

                            $cat   = $p->category ?? null;
                            $catOk = $cat && (($cat->is_active ?? true) && in_array($cat->kind ?? 'product',['product','both']));
                            $dot   = $cat->color ?? '#6b7280';
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                            {{-- Name --}}
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                @can('products.view')
                                    <a href="{{ route('products.show', $p) }}"
                                       class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $p->name }}
                                    </a>
                                @else
                                    {{ $p->name }}
                                @endcan

                                @if($ret > 0)
                                    <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <i data-lucide="u-turn-left" class="w-3 h-3"></i>
                                        Returns
                                    </span>
                                @endif
                            </td>

                            {{-- Category --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($cat)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $dot }}"></span>
                                        <span>{{ $cat->name }}</span>

                                        @if(!empty($cat->code))
                                            <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                                {{ $cat->code }}
                                            </span>
                                        @endif

                                        @unless($catOk)
                                            <span class="ml-2 text-[11px] px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                                Not usable
                                            </span>
                                        @endunless
                                    </span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-300">—</span>
                                @endif
                            </td>

                            {{-- Price --}}
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ $fmt2($price) }}
                            </td>

                            {{-- Margin --}}
                            <td class="px-4 py-3 text-right">
                                <span class="font-medium {{ $margin >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                                    {{ $fmt2($margin) }}%
                                </span>
                            </td>

                            {{-- In --}}
                            <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-300 font-semibold">
                                {{ $fmt0($in) }}
                            </td>

                            {{-- Out --}}
                            <td class="px-4 py-3 text-right text-rose-700 dark:text-rose-300 font-semibold">
                                {{ $fmt0($out) }}
                            </td>

                            {{-- Returned --}}
                            <td class="px-4 py-3 text-right text-amber-700 dark:text-amber-300">
                                {{ $fmt0($ret) }}
                            </td>

                            {{-- Stock --}}
                            <td class="px-4 py-3 text-right font-semibold
                                {{ $zero ? 'text-rose-600 dark:text-rose-300'
                                         : ($low ? 'text-amber-700 dark:text-amber-300'
                                                 : 'text-gray-900 dark:text-gray-100') }}">
                                {{ $fmt0($stk) }}
                                @if($zero)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                                        Out
                                    </span>
                                @elseif($low)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                        Low
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                                @can('products.view')
                                    <a href="{{ route('products.show', $p) }}"
                                       class="btn btn-secondary text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        View
                                    </a>
                                @endcan

                                @can('products.edit')
                                    <a href="{{ route('products.edit', $p) }}"
                                       class="btn btn-outline text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        Edit
                                    </a>
                                @endcan

                                @can('stock.view')
                                    <a href="{{ route('stock.history', ['product_id' => $p->id]) }}"
                                       class="btn btn-outline text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                        Moves
                                    </a>
                                @endcan

                                @can('products.delete')
                                    <form action="{{ route('products.destroy', $p) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            class="btn btn-danger text-xs inline-flex items-center gap-1 px-2.5 py-1.5"
                                            @click="$store.confirm.openWith($el.closest('form'))">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                {{-- Footer for Totals --}}
                <tfoot class="bg-gray-50 dark:bg-gray-900/40 font-semibold text-gray-900 dark:text-gray-100">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-xs uppercase text-gray-500 dark:text-gray-400">Total (Page):</td>
                        <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-300">
                            {{ $fmt0($products->sum(fn($p) => (float)($p->qty_in ?? 0))) }}
                        </td>
                        <td class="px-4 py-3 text-right text-rose-700 dark:text-rose-300">
                            {{ $fmt0($products->sum(fn($p) => (float)($p->qty_out ?? 0))) }}
                        </td>
                        <td class="px-4 py-3 text-right text-amber-700 dark:text-amber-300">
                            {{ $fmt0($products->sum(fn($p) => (float)($p->qty_returned ?? 0))) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ $fmt0($pageUnits) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Pagination --}}
        @if($isPaginated)
            <div class="mt-4">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif

    @endcannot
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

{{-- Excel Import Modal --}}
@can('products.view')
<div
    x-data="importModal()"
    @open-import-modal.window="open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    @keydown.escape.window="close()"
>
    <div
        @click.outside="close()"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto"
    >
        {{-- Step 1: Upload --}}
        <div x-show="step === 1" class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <i data-lucide="upload-cloud" class="w-6 h-6 text-indigo-600"></i>
                        Import Products from Excel
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Step 1 of 2: Upload and preview</p>
                </div>
                <button @click="close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            {{-- Upload Area --}}
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center bg-gray-50 dark:bg-gray-900/20">
                <i data-lucide="file-spreadsheet" class="w-16 h-16 mx-auto text-indigo-600 dark:text-indigo-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Upload Excel File</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Supported formats: .xlsx, .xls, .csv (Max 10MB)</p>
                
                <input
                    type="file"
                    x-ref="fileInput"
                    @change="handleFileSelect($event)"
                    accept=".xlsx,.xls,.csv"
                    class="hidden"
                >
                
                <button
                    @click="$refs.fileInput.click()"
                    class="btn btn-primary inline-flex items-center gap-2 mb-3"
                >
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    Choose File
                </button>
                
                <div x-show="selectedFile" class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Selected:</span> <span x-text="selectedFile?.name"></span>
                </div>
            </div>

            {{-- Loading State --}}
            <div x-show="uploading" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-blue-800 dark:text-blue-200">Processing file, please wait...</span>
                </div>
            </div>

            {{-- Error Messages --}}
            <div x-show="errors.length > 0" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <h4 class="font-semibold text-red-800 dark:text-red-200 mb-2 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    Errors Found
                </h4>
                <ul class="text-sm text-red-700 dark:text-red-300 space-y-1 list-disc list-inside">
                    <template x-for="error in errors.slice(0, 10)" :key="error.row">
                        <li>Row <span x-text="error.row"></span>: <span x-text="error.errors?.join(', ')"></span></li>
                    </template>
                </ul>
                <p x-show="errors.length > 10" class="text-xs text-red-600 dark:text-red-400 mt-2">
                    And <span x-text="errors.length - 10"></span> more errors...
                </p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button @click="close()" class="btn btn-outline">Cancel</button>
            </div>
        </div>

        {{-- Step 2: Preview & Confirm --}}
        <div x-show="step === 2" class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                        Review & Confirm Import
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Step 2 of 2: Choose import mode and confirm</p>
                </div>
                <button @click="close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="text-3xl font-bold text-green-700 dark:text-green-300" x-text="stats.new || 0"></div>
                    <div class="text-sm text-green-600 dark:text-green-400">New Products</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                    <div class="text-3xl font-bold text-orange-700 dark:text-orange-300" x-text="stats.existing || 0"></div>
                    <div class="text-sm text-orange-600 dark:text-orange-400">Existing Products</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="text-3xl font-bold text-blue-700 dark:text-blue-300" x-text="stats.total || 0"></div>
                    <div class="text-sm text-blue-600 dark:text-blue-400">Total Rows</div>
                </div>
            </div>

            {{-- Import Mode Selection --}}
            <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                <label class="block text-sm font-semibold text-indigo-900 dark:text-indigo-100 mb-3">
                    <i data-lucide="settings" class="w-4 h-4 inline mr-1"></i>
                    Select Import Mode
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                           :class="mode === 'add' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/40' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400'">
                        <input type="radio" name="mode" value="add" x-model="mode" class="mt-1">
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900 dark:text-gray-100">Add to Stock</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Adds Excel quantity to existing stock. Example: 10 + 5 = 15
                            </div>
                        </div>
                    </label>
                    <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                           :class="mode === 'replace' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/40' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400'">
                        <input type="radio" name="mode" value="replace" x-model="mode" class="mt-1">
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900 dark:text-gray-100">Replace Stock</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Sets stock to exact Excel value. Example: 10 → 5
                            </div>
                        </div>
                    </label>
                    <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                           :class="mode === 'full_replace' ? 'border-red-600 bg-red-50 dark:bg-red-900/40' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400'">
                        <input type="radio" name="mode" value="full_replace" x-model="mode" class="mt-1">
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900 dark:text-gray-100">Full Replace</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Wipes products not in Excel. Sets stock to Excel value.
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Preview Table --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
                <div class="bg-gray-50 dark:bg-gray-900/40 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Preview (first 10 rows)</h3>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold">Product Name</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold">Category</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold">Price</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold">Current Stock</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold">Excel Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="item in parsed" :key="item.row">
                                <tr>
                                    <td class="px-3 py-2">
                                        <span x-show="item.status === 'new'" class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">New</span>
                                        <span x-show="item.status === 'existing'" class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300">Update</span>
                                    </td>
                                    <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100" x-text="item.data.name"></td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300" x-text="item.data.category"></td>
                                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300" x-text="'RWF ' + parseFloat(item.data.price).toFixed(2)"></td>
                                    <td class="px-3 py-2 text-right font-medium" x-text="item.current_stock || 0"></td>
                                    <td class="px-3 py-2 text-right font-semibold text-indigo-700 dark:text-indigo-300" x-text="item.data.stock"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-between items-center gap-3">
                <button @click="step = 1; errors = []" class="btn btn-outline flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Back
                </button>
                <div class="flex gap-3">
                    <button @click="close()" class="btn btn-outline">Cancel</button>
                    <button
                        @click="executeImport()"
                        :disabled="!mode || importing"
                        class="btn btn-success flex items-center gap-2"
                        :class="{'opacity-50 cursor-not-allowed': !mode || importing}"
                    >
                        <span x-show="!importing">
                            <i data-lucide="check" class="w-4 h-4 inline"></i>
                            Confirm Import
                        </span>
                        <span x-show="importing" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/20 00/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importing...
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
                        this.errors = data.errors;
                        this.step = 2;
                        setTimeout(() => lucide.createIcons(), 100);
                    } else {
                        this.errors = [{ row: 0, errors: [data.message] }];
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
                         // Optional: show a success message before reloading
                        window.location.reload();
                    } else {
                        alert(data.message || 'Import failed. Please try again.');
                        this.importing = false;
                    }
                } catch (error) {
                    console.error(error); // Log for debugging
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