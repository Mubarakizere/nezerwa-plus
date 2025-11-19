{{-- resources/views/categories/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Category')

@section('content')
@php
    $parents = $parents
        ?? \App\Models\Category::where('id', '!=', $category->id)
            ->orderBy('name')
            ->get(['id','name','kind']);
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
            <i data-lucide="edit-3" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Category</span>
        </h1>
        <a href="{{ route('categories.index') }}"
           class="btn btn-secondary flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- Validation summary --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-4 text-red-700 dark:text-red-200 text-sm">
            <div class="font-medium">Please fix the following errors:</div>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @can('categories.edit')
        {{-- Form Card --}}
        <div x-data="{
                kind: @js(old('kind', $category->kind)),
                color: @js(old('color', $category->color ?? '#22c55e')),
                icon: @js(old('icon', $category->icon ?? 'folder')),
                name: @js(old('name', $category->name))
            }"
             class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <form action="{{ route('categories.update', $category) }}"
                  method="POST"
                  class="space-y-5"
                  novalidate>
                @csrf
                @method('PUT')

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Fields marked with <span class="text-red-600">*</span> are required.
                </p>

                {{-- Row: Name + Kind --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               x-model="name"
                               value="{{ old('name', $category->name) }}"
                               required
                               class="form-input w-full">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">
                            Kind <span class="text-red-500">*</span>
                        </label>
                        <select name="kind"
                                x-model="kind"
                                class="form-select w-full">
                            <option value="both">Both</option>
                            <option value="product">Product</option>
                            <option value="expense">Expense</option>
                        </select>
                        @error('kind')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Parent (optional) --}}
                <div>
                    <label class="form-label">Parent Category (optional)</label>
                    <select name="parent_id" class="form-select w-full">
                        <option value="">— None —</option>
                        @foreach($parents as $p)
                            <option value="{{ $p->id }}"
                                    @selected(old('parent_id', $category->parent_id) == $p->id)>
                                {{ $p->name }} @if($p->kind) ({{ $p->kind }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description"
                              rows="3"
                              class="form-textarea w-full">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Code + Sort --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Code (optional)</label>
                        <input type="text"
                               name="code"
                               value="{{ old('code', $category->code) }}"
                               class="form-input w-full">
                        @error('code')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Sort Order</label>
                        <input type="number"
                               name="sort_order"
                               value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                               class="form-input w-full">
                        @error('sort_order')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Color + Icon (+ preview) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label class="form-label">Color</label>
                            <div class="flex gap-2">
                                <input type="color"
                                       x-model="color"
                                       class="h-9 w-12 rounded border border-gray-300 dark:border-gray-700">
                                <input type="text"
                                       name="color"
                                       x-model="color"
                                       class="form-input w-full"
                                       placeholder="#22c55e or Tailwind token">
                            </div>
                            @error('color')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex-1">
                            <label class="form-label">Icon (Lucide name)</label>
                            <input type="text"
                                   name="icon"
                                   x-model="icon"
                                   class="form-input w-full"
                                   placeholder="e.g. package, wallet, folder">
                            @error('icon')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Preview chip --}}
                    <div class="flex items-end">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                             :style="`background: ${color}22; color: ${color}`">
                            <i :data-lucide="icon" class="w-3.5 h-3.5"></i>
                            <span x-text="name || 'Preview'"></span>
                        </div>
                    </div>
                </div>

                {{-- Active --}}
                <div class="flex items-center gap-2">
                    <input id="is_active"
                           type="checkbox"
                           name="is_active"
                           class="form-checkbox"
                           {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                    <label for="is_active"
                           class="text-sm text-gray-700 dark:text-gray-300">
                        Active
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-4">
                    <a href="{{ route('categories.index') }}"
                       class="btn btn-outline flex items-center gap-1">
                        <i data-lucide="x" class="w-4 h-4"></i> Cancel
                    </a>
                    <button type="submit"
                            class="btn btn-primary flex items-center gap-1">
                        <i data-lucide="save" class="w-4 h-4"></i> Update Category
                    </button>
                </div>
            </form>
        </div>
    @else
        {{-- No permission --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 p-4 text-sm text-amber-800 dark:text-amber-100">
            <div class="flex items-start gap-2">
                <i data-lucide="shield-alert" class="w-5 h-5 mt-0.5"></i>
                <div>
                    <p class="font-medium">You don’t have permission to edit categories.</p>
                    <p class="mt-1 text-xs">
                        Contact your system administrator if you think this is a mistake.
                    </p>
                    <div class="mt-3">
                        <a href="{{ route('categories.index') }}"
                           class="btn btn-secondary btn-sm inline-flex items-center gap-1">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Back to Categories</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });

    // Re-render icons when the icon/color inputs change
    document.addEventListener('input', () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>
@endpush
