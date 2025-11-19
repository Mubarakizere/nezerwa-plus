{{-- resources/views/suppliers/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $suppliers */
    $totalSuppliers = $totalSuppliers ?? ($suppliers instanceof LengthAwarePaginator ? $suppliers->total() : $suppliers->count());
    $q = $query ?? request('q');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <i data-lucide="truck" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    Suppliers
                </h1>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Keep track of who you buy from – names, phones and contacts in one place.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 text-xs text-gray-600 dark:text-gray-300">
                <i data-lucide="circle-dot" class="w-3 h-3"></i>
                <span>Total:</span>
                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $totalSuppliers }}</span>
            </span>

            @can('suppliers.create')
                <a href="{{ route('suppliers.create') }}"
                   class="btn btn-primary flex items-center gap-1 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Supplier</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Search --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-3">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-3">
                <label class="form-label text-xs">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Search by name, email, phone or address..."
                        class="form-input w-full pl-9 pr-9"
                    >
                    @if($q !== null && $q !== '')
                        <a href="{{ route('suppliers.index') }}"
                           class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                           title="Clear search">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="md:col-span-1 flex gap-2">
                <button type="submit"
                        class="btn btn-secondary w-full flex items-center justify-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply</span>
                </button>
            </div>
        </form>

        @if($q !== null && $q !== '')
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Showing results for:
                <span class="font-semibold text-gray-700 dark:text-gray-200">“{{ $q }}”</span>
            </p>
        @endif
    </div>

    {{-- Suppliers Table --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Phone</th>
                        <th class="px-4 py-3 text-left">Address</th>
                        @canany(['suppliers.edit', 'suppliers.delete'])
                            <th class="px-4 py-3 text-center">Actions</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all">
                            {{-- Name --}}
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100 font-medium">
                                <div class="flex flex-col">
                                    <span>{{ $supplier->name }}</span>
                                    @if($supplier->created_at)
                                        <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                            Since {{ $supplier->created_at->format('d M Y') }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($supplier->email)
                                    <a href="mailto:{{ $supplier->email }}"
                                       class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-300 hover:underline">
                                        <i data-lucide="mail" class="w-3 h-3"></i>
                                        <span>{{ $supplier->email }}</span>
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Phone --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($supplier->phone)
                                    <a href="tel:{{ $supplier->phone }}"
                                       class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-300 hover:underline">
                                        <i data-lucide="phone" class="w-3 h-3"></i>
                                        <span>{{ $supplier->phone }}</span>
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Address --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $supplier->address ?: '—' }}
                            </td>

                            {{-- Actions --}}
                            @canany(['suppliers.edit', 'suppliers.delete'])
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        @can('suppliers.edit')
                                            <a href="{{ route('suppliers.edit', $supplier) }}"
                                               class="btn btn-outline btn-sm inline-flex items-center gap-1"
                                               title="Edit">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('suppliers.delete')
                                            <form action="{{ route('suppliers.destroy', $supplier) }}"
                                                  method="POST"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        class="btn btn-danger btn-sm inline-flex items-center gap-1"
                                                        title="Delete"
                                                        @click="$store.confirm.openWith($el.closest('form'))">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            @endcanany
                        </tr>
                    @empty
                        <tr>
                            <td colspan="@canany(['suppliers.edit','suppliers.delete']) 5 @else 4 @endcanany"
                                class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="truck" class="w-8 h-8 text-gray-300 dark:text-gray-600"></i>
                                    <p>No suppliers found.</p>
                                    @can('suppliers.create')
                                        <a href="{{ route('suppliers.create') }}"
                                           class="btn btn-primary btn-sm inline-flex items-center gap-1 mt-1">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            <span>Add your first supplier</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($suppliers instanceof LengthAwarePaginator)
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                {{ $suppliers->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Global Delete Modal – only if someone can delete --}}
@can('suppliers.delete')
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.close()"
     x-transition>
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this supplier? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.close()">Cancel</button>
            <button type="button" class="btn btn-danger" @click="$store.confirm.confirm()">Delete</button>
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
