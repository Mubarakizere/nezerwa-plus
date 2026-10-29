@extends('layouts.app')
@section('title', 'Edit Product: ' . $product->name)

@section('content')
@php
    use Illuminate\Support\Str;

    // Filter usable categories
    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']))
        ->values();

    $currentCat = $product->category ?? null;
    $currentUsable = $currentCat
        ? $usableCategories->contains(fn($c) => (int)$c->id === (int)$product->category_id)
        : false;

    $currentBadge = $currentCat
        ? ($currentCat->name . (
            (method_exists($currentCat,'trashed') && $currentCat->trashed()) ? ' (Archived)' :
            (($currentCat->is_active ?? true) ? '' : ' (Inactive)')
        ))
        : 'Unknown / Deleted';

    $currentStockVal = (float) ($currentStock ?? $product->currentStock());
    $totalInVal      = (float) ($totalIn ?? $product->stockMovements()->where('type', 'in')->sum('quantity'));
    $totalOutVal     = (float) ($totalOut ?? $product->stockMovements()->where('type', 'out')->sum('quantity'));
    $threshold       = (int) config('inventory.low_stock', 5);

    $isStockLow  = $currentStockVal > 0 && $currentStockVal <= $threshold;
    $isStockZero = $currentStockVal <= 0;

    $stockHasError = $errors->has('admin_password') || $errors->has('stock');
    $oldStockDiffers = old('stock') !== null && (float)old('stock') !== $currentStockVal;
    $initialStockUnlocked = $stockHasError || $oldStockDiffers;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
     x-data="{
         cost: {{ old('cost_price', $product->cost_price) !== null ? (float) old('cost_price', $product->cost_price) : 0 }},
         margin: {{ old('profit_margin', $product->profit_margin) !== null ? (float) old('profit_margin', $product->profit_margin) : 0 }},
         price: {{ old('price', $product->price) !== null ? (float) old('price', $product->price) : 0 }},
         currentStock: {{ $currentStockVal }},
         stock: {{ old('stock', $currentStockVal) }},
         stockUnlocked: {{ $initialStockUnlocked ? 'true' : 'false' }},
         showAdminPassword: false,

         get profit() {
             const c = parseFloat(this.cost) || 0;
             const p = parseFloat(this.price) || 0;
             return p - c;
         },
         get effectiveMargin() {
             const c = parseFloat(this.cost) || 0;
             const p = parseFloat(this.price) || 0;
             if (c <= 0) return 0;
             return (((p - c) / c) * 100);
         },
         get stockDiff() {
             const curr = parseFloat(this.currentStock) || 0;
             const n = parseFloat(this.stock) || 0;
             return n - curr;
         },
         get inventoryValue() {
             const s = parseFloat(this.stock) || 0;
             const c = parseFloat(this.cost) || 0;
             return s * c;
         },
         updatePriceFromMargin() {
             const c = parseFloat(this.cost) || 0;
             const m = parseFloat(this.margin) || 0;
             if (c > 0) {
                 this.price = Math.round((c * (1 + (m / 100))) * 100) / 100;
             }
         },
         updateMarginFromPrice() {
             const c = parseFloat(this.cost) || 0;
             const p = parseFloat(this.price) || 0;
             if (c > 0) {
                 this.margin = Math.round((((p - c) / c) * 100) * 10) / 10;
             }
         },
         resetStock() {
             this.stock = this.currentStock;
             this.stockUnlocked = false;
         }
     }">

    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                <a href="{{ route('products.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">
                    <i data-lucide="package" class="w-3.5 h-3.5"></i>
                    <span>Products</span>
                </a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium truncate max-w-[200px] sm:max-w-md">{{ $product->name }}</span>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Edit</span>
            </nav>

            <div class="flex items-center gap-3">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                    Edit Product
                </h1>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-mono font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                    #{{ $product->id }}
                </span>
            </div>
        </div>

        {{-- Top Action Buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('products.index') }}"
               class="btn btn-outline text-xs flex items-center gap-1.5 px-3 py-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Catalog</span>
            </a>

            @can('products.view')
                <a href="{{ route('products.show', $product) }}"
                   class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>View Product</span>
                </a>
            @endcan

            @can('stock.view')
                <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
                   class="btn btn-outline text-xs flex items-center gap-1.5 px-3 py-1.5 text-slate-600 dark:text-slate-300">
                    <i data-lucide="history" class="w-4 h-4 text-sky-500"></i>
                    <span>Movements</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Category Warning Notice if applicable --}}
    @if(!$currentUsable && $currentCat)
        <div class="rounded-xl border border-amber-200/90 dark:border-amber-800/80 bg-amber-50/80 dark:bg-amber-950/30 p-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
                <div class="text-xs text-amber-900 dark:text-amber-200">
                    <p class="font-bold">Category Needs Resolution</p>
                    <p class="mt-0.5 text-amber-700 dark:text-amber-300 leading-relaxed">
                        The current category <span class="font-semibold underline decoration-amber-400">{{ $currentBadge }}</span>
                        is no longer active or designated for products. Please assign an active product category below.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Global Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/90 dark:bg-rose-950/30 p-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
                <div class="text-xs text-rose-800 dark:text-rose-200">
                    <p class="font-bold">Please correct the following errors before saving:</p>
                    <ul class="list-disc pl-4 mt-1.5 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @cannot('products.edit')
        {{-- Restricted Permission State --}}
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center max-w-xl mx-auto shadow-sm">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="shield-alert" class="w-6 h-6"></i>
            </div>
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                Product Editing Restricted
            </h2>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-sm mx-auto">
                Your role does not have authorization to edit catalog products. You can review details or reach out to your administrator for access.
            </p>
            <div class="mt-4">
                <a href="{{ route('products.show', $product) }}" class="btn btn-primary text-xs">
                    View Product Details
                </a>
            </div>
        </div>
    @else
        {{-- Main Form Container --}}
        <form action="{{ route('products.update', $product) }}" method="POST" id="edit-product-form" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                {{-- ======================================================== --}}
                {{-- LEFT COLUMN (Cols 1-7): General Info & Pricing Structure --}}
                {{-- ======================================================== --}}
                <div class="lg:col-span-7 space-y-6">

                    {{-- 1. General Identification Card --}}
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Product Identification
                                </h3>
                            </div>
                            <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">
                                Required fields mark *
                            </span>
                        </div>

                        <div class="p-6 space-y-5">
                            {{-- Product Name --}}
                            <div>
                                <label for="name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Product Title / Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="name"
                                           type="text"
                                           name="name"
                                           value="{{ old('name', $product->name) }}"
                                           required
                                           placeholder="e.g. Heinken 330ml Can, Red Wine Reserve"
                                           class="form-input w-full text-sm font-medium text-slate-900 dark:text-slate-100 placeholder:text-slate-400 border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500/20">
                                </div>
                                @error('name')
                                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Category Selector with Search --}}
                            <div x-data="{
                                filter: '',
                                get count() {
                                    return $el.querySelectorAll('select option:not([hidden])').length;
                                }
                            }">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="category_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        Assigned Category <span class="text-rose-500">*</span>
                                    </label>
                                    <a href="{{ route('categories.create', ['kind' => 'product']) }}"
                                       target="_blank"
                                       class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                        <i data-lucide="plus-circle" class="w-3 h-3"></i>
                                        <span>New Category</span>
                                    </a>
                                </div>

                                <div class="space-y-2">
                                    <div class="relative">
                                        <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-3 text-slate-400 pointer-events-none"></i>
                                        <input type="text"
                                               x-model="filter"
                                               placeholder="Quick search category list…"
                                               class="form-input w-full pl-9 py-1.5 text-xs bg-slate-50/60 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 placeholder:text-slate-400">
                                    </div>

                                    <select id="category_id"
                                            name="category_id"
                                            required
                                            class="form-select w-full text-xs font-medium border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500/20 text-slate-900 dark:text-slate-100"
                                            x-init="$watch('filter', val => {
                                                const opts = $el.querySelectorAll('option[data-search]');
                                                const q = (val || '').toLowerCase().trim();
                                                opts.forEach(o => {
                                                    const match = o.dataset.search.includes(q);
                                                    o.hidden = !match && o.value !== '';
                                                });
                                            })">
                                        @if(!$currentUsable)
                                            <option value="" selected disabled>
                                                ⚠️ Current: {{ $currentBadge }} — Select a replacement category —
                                            </option>
                                        @else
                                            <option value="" disabled>Choose a product category...</option>
                                        @endif

                                        @foreach ($usableCategories as $category)
                                            @php
                                                $isSelected = (string)old('category_id', $product->category_id) === (string)$category->id;
                                                $searchLabel = Str::lower($category->name . ' ' . ($category->code ?? ''));
                                            @endphp
                                            <option value="{{ $category->id }}"
                                                    data-search="{{ $searchLabel }}"
                                                    {{ $isSelected ? 'selected' : '' }}>
                                                {{ $category->name }} {{ $category->code ? "({$category->code})" : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @error('category_id')
                                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- 2. Pricing & Profit Margin Calculator Card --}}
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Pricing & Margin Engine
                                </h3>
                            </div>
                            <span class="inline-flex items-center gap-1 text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
                                Live Calculator
                            </span>
                        </div>

                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                {{-- Cost Price --}}
                                <div>
                                    <label for="cost_price" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                        Cost Price (RWF)
                                    </label>
                                    <div class="relative">
                                        <input id="cost_price"
                                               type="number"
                                               step="0.01"
                                               min="0"
                                               name="cost_price"
                                               x-model.number="cost"
                                               @input="updatePriceFromMargin()"
                                               placeholder="0.00"
                                               class="form-input w-full font-mono text-sm font-semibold border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500/20 pr-12">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[11px] font-mono text-slate-400">
                                            RWF
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Acquisition cost per unit</p>
                                    @error('cost_price')
                                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Margin % --}}
                                <div>
                                    <label for="profit_margin" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                        Profit Markup (%)
                                    </label>
                                    <div class="relative">
                                        <input id="profit_margin"
                                               type="number"
                                               step="0.1"
                                               min="0"
                                               max="1000"
                                               name="profit_margin"
                                               x-model.number="margin"
                                               @input="updatePriceFromMargin()"
                                               placeholder="20.0"
                                               class="form-input w-full font-mono text-sm font-semibold border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500/20 pr-8">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-mono text-slate-400">
                                            %
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Desired margin on cost</p>
                                    @error('profit_margin')
                                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Selling Price --}}
                                <div>
                                    <label for="price" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                        Selling Price (RWF) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input id="price"
                                               type="number"
                                               step="0.01"
                                               min="0"
                                               name="price"
                                               x-model.number="price"
                                               @input="updateMarginFromPrice()"
                                               required
                                               placeholder="0.00"
                                               class="form-input w-full font-mono text-sm font-bold text-slate-900 dark:text-slate-100 border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500/20 pr-12">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[11px] font-mono text-slate-400">
                                            RWF
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Final POS retail price</p>
                                    @error('price')
                                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Dynamic Margin / Profit Breakdown Summary --}}
                            <div class="rounded-xl p-4 bg-slate-50/80 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Gross Profit / Unit</span>
                                    <div class="font-mono font-bold mt-0.5 text-sm"
                                         :class="profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        <span x-text="(profit >= 0 ? '+' : '') + profit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                        <span class="text-[10px] font-normal text-slate-400">RWF</span>
                                    </div>
                                </div>

                                <div>
                                    <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Effective Margin</span>
                                    <div class="font-mono font-bold mt-0.5 text-sm"
                                         :class="effectiveMargin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        <span x-text="(effectiveMargin >= 0 ? '+' : '') + effectiveMargin.toFixed(1) + '%'"></span>
                                    </div>
                                </div>

                                <div class="col-span-2 sm:col-span-1 border-t sm:border-t-0 sm:border-l border-slate-200/80 dark:border-slate-700/80 pt-2 sm:pt-0 sm:pl-3">
                                    <span class="text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500">Est. Stock Valuation</span>
                                    <div class="font-mono font-bold mt-0.5 text-slate-800 dark:text-slate-200">
                                        <span x-text="inventoryValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                        <span class="text-[10px] font-normal text-slate-400">RWF</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Submission Actions (Desktop / Mobile) --}}
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 rounded-2xl">
                        <a href="{{ route('products.index') }}" class="btn btn-outline text-xs px-4 py-2">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary text-xs px-5 py-2 flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>

                </div>

                {{-- ======================================================== --}}
                {{-- RIGHT COLUMN (Cols 8-12): Inventory & Protected Stock --}}
                {{-- ======================================================== --}}
                <div class="lg:col-span-5 space-y-6">

                    {{-- 3. Stock Authority Card (Protected with Admin Password) --}}
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        {{-- Card Header --}}
                        <div class="px-6 py-4 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Inventory & Stock Authority
                                </h3>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/50">
                                Protected
                            </span>
                        </div>

                        <div class="p-6 space-y-5">

                            {{-- Live Current Stock Gauge --}}
                            <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                        Current System Balance
                                    </span>
                                    <div class="text-2xl font-black font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                                        {{ number_format($currentStockVal, 0) }} <span class="text-xs font-normal text-slate-400">units</span>
                                    </div>
                                </div>

                                <div>
                                    @if($isStockZero)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            Out of Stock
                                        </span>
                                    @elseif($isStockLow)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Low Stock (≤ {{ $threshold }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Normal Stock
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Lifetime Movement Counters --}}
                            <div class="grid grid-cols-2 gap-3 text-center">
                                <div class="p-3 rounded-lg bg-emerald-500/5 border border-emerald-500/10">
                                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Total Received (IN)</span>
                                    <div class="font-mono font-bold text-sm text-slate-800 dark:text-slate-200 mt-0.5">
                                        ↓ {{ number_format($totalInVal, 0) }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800">
                                    <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Dispatched (OUT)</span>
                                    <div class="font-mono font-bold text-sm text-slate-800 dark:text-slate-200 mt-0.5">
                                        ↑ {{ number_format($totalOutVal, 0) }}
                                    </div>
                                </div>
                            </div>

                            {{-- ======================================================== --}}
                            {{-- SECURITY LOCK SECTION: Modifying Stock Requires Password --}}
                            {{-- ======================================================== --}}
                            <div class="rounded-xl border transition-all"
                                 :class="stockUnlocked
                                    ? 'border-indigo-300 dark:border-indigo-800 bg-indigo-50/20 dark:bg-indigo-950/20 p-4'
                                    : 'border-slate-200 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-800/30 p-4'">

                                {{-- LOCKED STATE --}}
                                <div x-show="!stockUnlocked" class="space-y-3">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-200/70 dark:bg-slate-800 text-slate-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                Direct Stock Edit is Locked
                                            </h4>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                                To safeguard inventory records, stock cannot be edited freely. Changing stock requires an <strong>Admin Password</strong>.
                                            </p>
                                        </div>
                                    </div>

                                    <button type="button"
                                            @click="stockUnlocked = true; $nextTick(() => document.getElementById('stock-input').focus())"
                                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:border-indigo-500 dark:hover:border-indigo-500 text-slate-700 dark:text-slate-200 transition-colors shadow-2xs">
                                        <i data-lucide="key-round" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                        <span>Unlock & Modify Stock</span>
                                    </button>

                                    {{-- Hidden input keeps current stock submitted if not unlocked --}}
                                    <input type="hidden" name="stock" :value="currentStock">
                                </div>

                                {{-- UNLOCKED STATE --}}
                                <div x-show="stockUnlocked" x-cloak class="space-y-4">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/80 dark:border-slate-700/80">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                            <i data-lucide="unlock" class="w-4 h-4 text-indigo-600"></i>
                                            <span>Stock Alteration Active</span>
                                        </div>
                                        <button type="button"
                                                @click="resetStock()"
                                                class="text-[11px] font-medium text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                                            Lock & Reset
                                        </button>
                                    </div>

                                    {{-- New Stock Quantity Input --}}
                                    <div>
                                        <label for="stock-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            New Stock Quantity <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input id="stock-input"
                                                   type="number"
                                                   step="1"
                                                   min="0"
                                                   name="stock"
                                                   x-model.number="stock"
                                                   required
                                                   placeholder="Enter new quantity"
                                                   class="form-input w-full font-mono text-sm font-bold border-indigo-300 dark:border-indigo-700 focus:border-indigo-500 focus:ring-indigo-500/20">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-mono text-slate-400">
                                                units
                                            </div>
                                        </div>
                                        @error('stock')
                                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Live Delta Preview --}}
                                    <div class="p-2.5 rounded-lg text-xs font-mono flex items-center justify-between"
                                         :class="{
                                             'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20': stockDiff > 0,
                                             'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20': stockDiff < 0,
                                             'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400': stockDiff === 0
                                         }">
                                        <span class="text-[11px] font-sans font-medium"
                                              x-text="stockDiff > 0 ? 'Surplus (will log IN movement)' : (stockDiff < 0 ? 'Deficit (will log OUT movement)' : 'No stock change')">
                                        </span>
                                        <span class="font-bold" x-text="(stockDiff > 0 ? '+' : '') + stockDiff + ' units'"></span>
                                    </div>

                                    {{-- Admin Authorization Password Input --}}
                                    <div class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/20 space-y-2">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-300">
                                            <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-amber-600"></i>
                                            <span>Admin Password Required</span>
                                        </div>
                                        <p class="text-[10px] text-amber-700 dark:text-amber-400">
                                            Enter an authorized <strong>Admin</strong> or <strong>Boss</strong> password to confirm this adjustment.
                                        </p>

                                        <div class="relative">
                                            <input :type="showAdminPassword ? 'text' : 'password'"
                                                   name="admin_password"
                                                   id="admin_password"
                                                   placeholder="Enter Admin password"
                                                   autocomplete="current-password"
                                                   class="form-input w-full pr-9 text-xs border-amber-300 dark:border-amber-700 focus:border-amber-500 focus:ring-amber-500/20">
                                            <button type="button"
                                                    @click="showAdminPassword = !showAdminPassword"
                                                    class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-amber-600 hover:text-amber-800 transition-colors">
                                                <i :data-lucide="showAdminPassword ? 'eye-off' : 'eye'" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                        @error('admin_password')
                                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-semibold flex items-center gap-1">
                                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                                <span>{{ $message }}</span>
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Audit Link --}}
                            <div class="pt-1 text-center">
                                <a href="{{ route('products.adjust.create', $product) }}"
                                   class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">
                                    <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                                    <span>Prefer full physical audit form? Click here</span>
                                </a>
                            </div>

                        </div>
                    </div>

                    {{-- 4. Quick Product Metadata Card --}}
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Audit Metadata
                        </h4>
                        <dl class="text-xs divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-300">
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-400">Created</dt>
                                <dd class="font-mono">{{ $product->created_at ? $product->created_at->format('M d, Y H:i') : '—' }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-400">Last Modified</dt>
                                <dd class="font-mono">{{ $product->updated_at ? $product->updated_at->diffForHumans() : '—' }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-slate-400">System ID</dt>
                                <dd class="font-mono font-bold">#{{ $product->id }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>
            </div>
        </form>
    @endcannot
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
