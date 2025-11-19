{{-- resources/views/categories/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Categories')

@section('content')
@php
    // Tabs
    $kinds = [
        'all'      => 'All',
        'product'  => 'Product',
        'expense'  => 'Expense',
        'both'     => 'Both',
        'inactive' => 'Inactive',
        'trash'    => 'Trash',
    ];

    $current     = $filterKind ?? request('kind'); // 'product'|'expense'|'both'|'inactive'|'trash'|null
    $isTrashView = ($current === 'trash');
    $tabClass = function($key) use ($current) {
        $active = ($current === $key) || ($key === 'all' && ! $current);
        return $active
            ? 'bg-indigo-600 text-white'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';
    };

    // Pills / helpers
    $pill = fn($active) => $active
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';

    $q        = trim((string) request('q', ''));
    $showAll  = request('per_page') === 'all';
    $toggleParams = array_merge(request()->except('page','per_page'), ['per_page' => $showAll ? null : 'all']);
    $toggleHref   = route('categories.index', array_filter($toggleParams, fn($v) => $v !== null));

    // Permissions for actions column
    $user = auth()->user();
    $canEdit   = $user?->can('categories.edit');
    $canDelete = $user?->can('categories.delete');
    $canManage = $canEdit || $canDelete;

    // Column count for empty state
    $baseCols = $isTrashView ? 9 : 8; // with Actions
    $emptyColspan = $canManage ? $baseCols : $baseCols - 1;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Flash / errors --}}
    @if (session('success'))
        <div class="rounded-md bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 px-3 py-2 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-md bg-rose-50 dark:bg-rose-900/30 text-rose-800 dark:text-rose-200 px-3 py-2 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-md bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 px-3 py-2 text-sm">
            <strong>{{ $errors->count() }} error(s):</strong>
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i data-lucide="folder-tree" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Categories</h1>
        </div>

        <div class="flex items-center gap-2">
            {{-- Search --}}
            <form method="GET" action="{{ route('categories.index') }}" class="hidden sm:block">
                {{-- Preserve other params except page & q --}}
                @foreach(request()->except('page','q') as $key => $val)
                    @if(is_array($val))
                        @foreach($val as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach

                <div class="relative">
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Search by name, code, description, or parent…"
                        class="pl-9 pr-24 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5"></i>

                    <div class="absolute right-1 top-1 flex items-center gap-1">
                        @if($q !== '')
                            @php $clearParams = array_filter(array_merge(request()->except('page','q'))); @endphp
                            <a href="{{ route('categories.index', $clearParams) }}"
                               class="px-2 py-1 text-xs rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">
                                Clear
                            </a>
                        @endif
                        <button type="submit"
                                class="px-2 py-1 text-xs rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                            Go
                        </button>
                    </div>
                </div>
            </form>

            {{-- Show All / Paginate toggle --}}
            <a href="{{ $toggleHref }}"
               class="btn btn-outline text-xs">
                @if($showAll)
                    <i data-lucide="list" class="w-4 h-4"></i> Paginate
                @else
                    <i data-lucide="list-check" class="w-4 h-4"></i> Show All
                @endif
            </a>

            {{-- Add --}}
            @unless($isTrashView)
                @can('categories.create')
                    <a href="{{ route('categories.create') }}"
                       class="btn btn-primary flex items-center gap-1 text-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Category
                    </a>
                @endcan
            @endunless
        </div>
    </div>

    {{-- Tabs / Filters --}}
    <div class="flex flex-wrap gap-2">
        @foreach($kinds as $key => $label)
            @php
                $target = $key === 'all' ? null : $key;
                $params = array_filter(array_merge(request()->except('page'), ['kind' => $target]));
            @endphp
            <a href="{{ route('categories.index', $params) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $tabClass($key) }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Small recaps --}}
    <div class="flex flex-wrap items-center gap-3 text-xs">
        @if($q !== '')
            <div class="text-gray-500 dark:text-gray-400">
                Searching for <span class="font-medium text-gray-700 dark:text-gray-200">“{{ $q }}”</span>
            </div>
        @endif
        @if($isTrashView)
            <div class="px-2 py-0.5 rounded bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">
                Trash view — restore or delete forever
            </div>
        @endif
        @if($showAll)
            <div class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                Showing all results
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Kind</th>
                        <th class="px-4 py-3 text-left">Parent</th>
                        <th class="px-4 py-3 text-center">Active</th>
                        @if($isTrashView)
                            <th class="px-4 py-3 text-left">Deleted</th>
                        @endif
                        <th class="px-4 py-3 text-left">Description</th>
                        <th class="px-4 py-3 text-right">Usage</th>
                        @if($canManage)
                            <th class="px-4 py-3 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($categories as $category)
                        @php $dot = $category->color ?: '#6b7280'; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $category->id }}</td>

                            {{-- Name + code chip --}}
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $dot }}"></span>
                                    @if($category->icon)
                                        <i data-lucide="{{ $category->icon }}" class="w-4 h-4 text-gray-500 dark:text-gray-400"></i>
                                    @endif
                                    {{ $category->name }}
                                    @if($category->code)
                                        <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                            {{ $category->code }}
                                        </span>
                                    @endif
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 capitalize">{{ $category->kind }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ optional($category->parent)->name ?? '—' }}</td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $pill($category->is_active) }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            @if($isTrashView)
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ optional($category->deleted_at)->format('d M Y, H:i') ?? '—' }}
                                </td>
                            @endif

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $category->description ?? '—' }}
                            </td>

                            {{-- Usage counts --}}
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                <span class="inline-flex items-center gap-1 mr-2">
                                    <i data-lucide="package" class="w-4 h-4 text-gray-400"></i>
                                    <span class="font-semibold">{{ $category->products_count ?? 0 }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="wallet" class="w-4 h-4 text-gray-400"></i>
                                    <span class="font-semibold">{{ $category->expenses_count ?? 0 }}</span>
                                </span>
                            </td>

                            {{-- Actions --}}
                            @if($canManage)
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                                    @if($isTrashView)
                                        @can('categories.delete')
                                            {{-- Restore --}}
                                            <form action="{{ route('categories.restore', $category->id) }}"
                                                  method="POST"
                                                  class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-success text-xs inline-flex items-center gap-1">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Restore
                                                </button>
                                            </form>

                                            {{-- Delete Forever --}}
                                            <form action="{{ route('categories.forceDestroy', $category->id) }}"
                                                  method="POST"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        class="btn btn-danger text-xs inline-flex items-center gap-1"
                                                        @click="$store.confirm.openWith($el.closest('form'))">
                                                    <i data-lucide="skull" class="w-4 h-4"></i> Delete Forever
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        @can('categories.edit')
                                            <a href="{{ route('categories.edit', $category) }}"
                                               class="btn btn-outline text-xs inline-flex items-center gap-1">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
                                            </a>
                                        @endcan

                                        @can('categories.delete')
                                            <form action="{{ route('categories.destroy', $category) }}"
                                                  method="POST"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        class="btn btn-danger text-xs inline-flex items-center gap-1"
                                                        @click="$store.confirm.openWith($el.closest('form'))">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $emptyColspan }}"
                                class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                @if($q !== '')
                                    No results for “{{ $q }}”.
                                @else
                                    No categories found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($categories instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                {{ $categories->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Global Confirm Modal (delete / force delete) --}}
@can('categories.delete')
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.close()"
     x-transition>
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Action</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to proceed? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button"
                    class="btn btn-outline"
                    @click="$store.confirm.close()">
                Cancel
            </button>
            <button type="button"
                    class="btn btn-danger"
                    @click="$store.confirm.confirm()">
                Confirm
            </button>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });

    document.addEventListener('alpine:init', () => {
        if (! Alpine.store('confirm')) {
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
                    if (this.submitEl) {
                        this.submitEl.submit();
                    }
                    this.close();
                },
            });
        }
    });
</script>
@endpush
@endsection
