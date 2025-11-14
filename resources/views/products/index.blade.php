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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Products</span>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Inventory overview, sales price, WAC, and stock health.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('products.create') }}" class="btn btn-primary flex items-center gap-1 text-sm">
                <i data-lucide="plus" class="w-4 h-4"></i> Add Product
            </a>
            <a href="{{ route('stock.history', request()->only('product_id')) }}" class="btn btn-outline text-sm flex items-center gap-1">
                <i data-lucide="history" class="w-4 h-4"></i> Stock Movements
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('products.index') }}"
          x-data="{ qcat: '' }"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3">
        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
            <input name="search" value="{{ request('search') }}" placeholder="Name, SKU…"
                   class="form-input w-full">
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Category</label>

            {{-- tiny inline filter for category options --}}
            <div class="relative mb-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                <input x-model="qcat" type="text" placeholder="Filter categories…"
                       class="form-input w-full pl-8" aria-label="Filter categories">
            </div>

            <select name="category_id" class="form-select w-full"
                    x-init="$watch('qcat', v => {
                        const opts = $el.querySelectorAll('option[data-name]');
                        const k = (v||'').toLowerCase();
                        opts.forEach(o => { o.hidden = !o.dataset.name.includes(k) && o.value !== ''; });
                    })">
                <option value="">All categories</option>
                @foreach($usableCategories as $c)
                    @php
                        $label = trim($c->name.' '.($c->code ? "({$c->code})" : ''));
                    @endphp
                    <option value="{{ $c->id }}"
                            data-name="{{ Str::lower($label) }}"
                            @selected(request('category_id') == $c->id)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                Only <span class="font-medium">active</span> categories with kind <em>Product</em>/<em>Both</em> are listed.
                <a href="{{ route('categories.create', ['kind'=>'product']) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create new</a>.
            </p>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Stock Status</label>
            <select name="stock_status" class="form-select w-full">
                <option value="">Any</option>
                <option value="in"  @selected(request('stock_status')==='in')>In stock</option>
                <option value="low" @selected(request('stock_status')==='low')>Low (≤ {{ $threshold }})</option>
                <option value="out" @selected(request('stock_status')==='out')>Out of stock</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Per page</label>
            <select name="per_page" class="form-select w-full">
                @foreach([10,20,50,100] as $pp)
                    <option value="{{ $pp }}" @selected((int)request('per_page', 20)===$pp)>{{ $pp }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-6 flex flex-wrap gap-2 justify-end">
            <button class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="filter" class="w-4 h-4"></i> Apply
            </button>
            <a href="{{ route('products.index') }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset
            </a>
        </div>
    </form>

    {{-- Page Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <x-stat-card title="Products (page)" value="{{ $fmt0($pageCount) }}" color="indigo" />
        <x-stat-card title="Units in Stock (page)" value="{{ $fmt0($pageUnits) }}" color="blue" />
        <x-stat-card title="Stock Value @ cost (page)" value="RWF {{ $fmt2($pageValue) }}" color="emerald" />
        <x-stat-card title="Potential Revenue (page)" value="RWF {{ $fmt2($pageRevenue) }}" color="amber" />
    </div>
    @if($isPaginated && $totalCount !== $pageCount)
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Showing {{ $pageCount }} of {{ $fmt0($totalCount) }} matching products.
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">SKU</th>
                        <th class="px-4 py-3 text-right">Cost (WAC)</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Margin</th>
                        <th class="px-4 py-3 text-right">In</th>
                        <th class="px-4 py-3 text-right">Out</th>
                        <th class="px-4 py-3 text-right">Returned</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3 text-left">Last Moved</th>
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

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                <a href="{{ route('products.show', $p) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                    {{ $p->name }}
                                </a>
                                @if($ret > 0)
                                    <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <i data-lucide="u-turn-left" class="w-3 h-3"></i> returns
                                    </span>
                                @endif
                            </td>

                            {{-- Category with status --}}
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
                                                not usable
                                            </span>
                                        @endunless
                                    </span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-300">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $p->sku ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ $fmt2($cost) }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                RWF {{ $fmt2($price) }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <span class="font-medium {{ $margin >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                                    {{ $fmt2($margin) }}%
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-300 font-semibold">
                                {{ $fmt0($in) }}
                            </td>

                            <td class="px-4 py-3 text-right text-rose-700 dark:text-rose-300 font-semibold">
                                {{ $fmt0($out) }}
                            </td>

                            <td class="px-4 py-3 text-right text-amber-700 dark:text-amber-300">
                                {{ $fmt0($ret) }}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold
                                {{ $zero ? 'text-rose-600 dark:text-rose-300'
                                         : ($low ? 'text-amber-700 dark:text-amber-300'
                                                 : 'text-gray-900 dark:text-gray-100') }}">
                                {{ $fmt0($stk) }}
                                @if($zero)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">Out</span>
                                @elseif($low)
                                    <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">Low</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $last }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                                <a href="{{ route('products.show', $p) }}" class="btn btn-secondary text-xs inline-flex items-center gap-1">
                                    <i data-lucide="eye" class="w-4 h-4"></i> View
                                </a>
                                <a href="{{ route('products.edit', $p) }}" class="btn btn-outline text-xs inline-flex items-center gap-1">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
                                </a>
                                <a href="{{ route('stock.history', ['product_id' => $p->id]) }}" class="btn btn-outline text-xs inline-flex items-center gap-1">
                                    <i data-lucide="history" class="w-4 h-4"></i> Movements
                                </a>
                                <form action="{{ route('products.destroy', $p) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
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
                            <td colspan="12" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($isPaginated)
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    </div>
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
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        submitEl: null,
        openWith(form) { this.submitEl = form; this.open = true; }
    });
});
</script>
@endpush
@endsection
