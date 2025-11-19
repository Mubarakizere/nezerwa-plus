@extends('layouts.app')
@section('title', 'Edit Product')

@section('content')
@php
    use Illuminate\Support\Str;

    // Safety: ensure we only show usable categories here
    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']))
        ->values();

    $currentCat = $product->category ?? null; // controller should eager-load withTrashed() for show/edit
    $currentUsable = $currentCat
        ? $usableCategories->contains(fn($c) => (int)$c->id === (int)$product->category_id)
        : false;

    // Text to show when the current category is not usable anymore
    $currentBadge = $currentCat
        ? ($currentCat->name . (
            (method_exists($currentCat,'trashed') && $currentCat->trashed()) ? ' (deleted)' :
            (($currentCat->is_active ?? true) ? '' : ' (inactive)')
        ))
        : 'Unknown (missing)';
@endphp

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
            <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Edit Product
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products.index') }}"
               class="btn btn-outline flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </a>
            @can('products.view')
                <a href="{{ route('products.show', $product) }}"
                   class="btn btn-secondary flex items-center gap-1 text-sm">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>View</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Category warning --}}
    @if(!$currentUsable && $currentCat)
        <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
            <div class="flex items-start gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 text-amber-600 dark:text-amber-300"></i>
                <div class="text-sm text-amber-800 dark:text-amber-200">
                    <p class="font-semibold">Category needs attention</p>
                    <p class="mt-1">
                        Current category
                        <span class="font-medium">{{ $currentBadge }}</span>
                        is not usable for products. Please select an active category with kind
                        <em>Product</em> or <em>Both</em>.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg p-4">
            <p class="font-medium">Please fix the following:</p>
            <ul class="list-disc pl-5 mt-2 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @cannot('products.edit')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to edit products.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        You can still view product details, but changes are restricted. Please contact your administrator if you need edit access.
                    </p>
                </div>
            </div>
        </div>
    @else
        {{-- Form Card --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Product Name --}}
                <div>
                    <label class="form-label" for="name">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $product->name) }}"
                        required
                        class="form-input w-full"
                        placeholder="e.g. Coca-Cola 500ml"
                    >
                    @error('name')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category (with inline filter + fallback for unusable current) --}}
                <div x-data="{ q: '' }">
                    <label class="form-label" for="category_id">
                        Category <span class="text-red-500">*</span>
                    </label>

                    {{-- Tiny client-side filter for long lists --}}
                    <div class="relative mb-2">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            x-model="q"
                            type="text"
                            placeholder="Filter categories…"
                            class="form-input w-full pl-8"
                            aria-label="Filter categories"
                        >
                    </div>

                    <select
                        id="category_id"
                        name="category_id"
                        class="form-select w-full"
                        required
                        x-init="$watch('q', v => {
                            const opts = $el.querySelectorAll('option[data-name]');
                            const k = (v || '').toLowerCase();
                            opts.forEach(o => {
                                const visible = o.dataset.name.includes(k);
                                o.hidden = !visible && o.value !== '';
                            });
                        })"
                    >
                        @if(!$currentUsable)
                            {{-- Force user to choose a new valid category --}}
                            <option value="" selected disabled>
                                Current: {{ $currentBadge }} — select a new category —
                            </option>
                        @else
                            <option value="">Select category</option>
                        @endif

                        @foreach ($usableCategories as $category)
                            @php
                                $label = trim($category->name.' '.($category->code ? "({$category->code})" : ''));
                            @endphp
                            <option
                                value="{{ $category->id }}"
                                data-name="{{ Str::lower($label) }}"
                                {{ (string)old('category_id', $product->category_id) === (string)$category->id ? 'selected' : '' }}
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Can’t find it?
                        <a href="{{ route('categories.create', ['kind'=>'product']) }}"
                           class="text-indigo-600 dark:text-indigo-400 hover:underline">
                            Create a category
                        </a>.
                    </p>
                </div>

                {{-- Price & Stock --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="price">
                            Selling Price (RWF) <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="price"
                            type="number"
                            step="0.01"
                            min="0"
                            name="price"
                            value="{{ old('price', $product->price) }}"
                            required
                            class="form-input w-full"
                            placeholder="e.g. 1200"
                        >
                        @error('price')
                            <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="stock">
                            Stock <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="stock"
                            type="number"
                            step="1"
                            min="0"
                            name="stock"
                            value="{{ old('stock', $product->stock) }}"
                            required
                            class="form-input w-full"
                        >
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Changing this value will create a stock
                            <span class="font-medium">adjustment</span>
                            (IN for increase, OUT for decrease) using the product’s cost/price as unit cost.
                        </p>
                        @error('stock')
                            <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('products.index') }}" class="btn btn-outline">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        Update Product
                    </button>
                </div>
            </form>
        </div>
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
