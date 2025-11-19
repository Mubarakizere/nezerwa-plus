@extends('layouts.app')
@section('title', 'Add Product')

@section('content')
@php
    // In case the controller ever forgets to filter:
    $usableCategories = collect($categories ?? [])
        ->filter(fn($c) => ($c->is_active ?? true) && in_array($c->kind ?? 'product', ['product','both']));
@endphp

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-2">
            <i data-lucide="package-plus" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Add Product
            </h1>
        </div>

        <div class="flex items-center gap-2">
            @can('products.view')
                <a href="{{ route('products.index') }}"
                   class="btn btn-outline flex items-center gap-1 text-xs sm:text-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Back</span>
                </a>
            @endcan
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Only active categories with kind <span class="font-medium">Product</span> or <span class="font-medium">Both</span> are selectable.
        @can('categories.create')
            <a href="{{ route('categories.create', ['kind' => 'product']) }}"
               class="text-indigo-600 dark:text-indigo-400 hover:underline">
                Create category
            </a>
        @endcan
    </p>

    {{-- Permission gate --}}
    @cannot('products.create')
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to add products.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg p-4 text-sm">
                <p class="font-medium">Please fix the following:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Empty state if no usable categories --}}
        @if($usableCategories->isEmpty())
            <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
                <div class="flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 text-amber-600 dark:text-amber-300"></i>
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-semibold">No usable categories found.</p>
                        <p class="mt-1">
                            Create an active category with kind <em>Product</em> or <em>Both</em> first.
                        </p>
                        @can('create categories')
                            <a href="{{ route('categories.create', ['kind'=>'product']) }}"
                               class="btn btn-primary btn-sm mt-3 inline-flex items-center gap-1">
                                <i data-lucide="folder-plus" class="w-4 h-4"></i>
                                Create Category
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Card --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <form action="{{ route('products.store') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Product Name --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="name">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="form-input w-full"
                        placeholder="e.g. Coca-Cola 500ml"
                    >
                    @error('name')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category --}}
                <div x-data="{ q: '' }">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="category_id">
                        Category <span class="text-red-500">*</span>
                    </label>

                    {{-- tiny client-side filter for long lists --}}
                    <div class="relative mb-2">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400"></i>
                        <input
                            x-model="q"
                            type="text"
                            placeholder="Filter categories…"
                            class="form-input w-full pl-8 text-xs"
                            aria-label="Filter categories"
                        >
                    </div>

                    <select
                        id="category_id"
                        name="category_id"
                        class="form-select w-full text-sm"
                        required
                        x-init="
                            $watch('q', v => {
                                const opts = $el.querySelectorAll('option[data-name]');
                                const k = (v || '').toLowerCase();
                                opts.forEach(o => {
                                    const visible = o.dataset.name.includes(k);
                                    o.hidden = !visible && o.value !== '';
                                });
                            })
                        "
                    >
                        <option value="">Select category</option>
                        @foreach ($usableCategories as $category)
                            @php
                                $label = trim($category->name.' '.($category->code ? "({$category->code})" : ''));
                            @endphp
                            <option
                                value="{{ $category->id }}"
                                data-name="{{ \Illuminate\Support\Str::lower($label) }}"
                                {{ old('category_id') == $category->id ? 'selected' : '' }}
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    @can('categories.create')
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Can’t find it?
                            <a href="{{ route('categories.create', ['kind'=>'product']) }}"
                               class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                Create a category
                            </a>.
                        </p>
                    @endcan
                </div>

                {{-- Selling Price --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="price">
                        Selling Price (RWF) <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="price"
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        value="{{ old('price') }}"
                        required
                        class="form-input w-full"
                        placeholder="e.g. 1200"
                    >
                    @error('price')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Initial Stock --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1" for="stock">
                        Initial Stock
                    </label>
                    <input
                        id="stock"
                        type="number"
                        min="0"
                        step="1"
                        name="stock"
                        value="{{ old('stock', 0) }}"
                        class="form-input w-full"
                    >
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        If greater than 0, an initial <span class="font-medium">IN</span> movement will be recorded.
                    </p>
                    @error('stock')
                        <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    @can('products.view')
                        <a href="{{ route('products.index') }}" class="btn btn-outline text-sm">
                            Cancel
                        </a>
                    @endcan
                    <button type="submit" class="btn btn-success text-sm">
                        Save Product
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
