@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $suppliers */
    $totalCount = $totalSuppliers ?? ($suppliers instanceof LengthAwarePaginator ? $suppliers->total() : $suppliers->count());
    $q = $query ?? request('q');

    $coll = $suppliers instanceof LengthAwarePaginator ? $suppliers->getCollection() : collect($suppliers);
    $withPhone = $coll->filter(fn($s) => !empty($s->phone))->count();
    $withEmail = $coll->filter(fn($s) => !empty($s->email))->count();
    $totalOrdersOnPage = $coll->sum(fn($s) => (int)($s->purchases_count ?? 0));
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="truck" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Suppliers Directory</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ number_format($totalCount) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Maintain vendor profiles, contact details, procurement history, and store obligations.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('purchases.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="shopping-bag" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Purchases List</span>
            </a>

            @can('suppliers.create')
                <a href="{{ route('suppliers.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Supplier</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- KPI Summary Cards (Dashboard Grade) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- 1. Total Suppliers --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Suppliers</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="truck" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ number_format($totalCount) }}
                    <span class="text-xs font-normal text-slate-400 font-sans">vendors</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Active Network</span>
                <span class="font-medium text-indigo-600 dark:text-indigo-400">All registered</span>
            </div>
        </div>

        {{-- 2. Contact Reachability --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Phone Contacts</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $withPhone }}
                    <span class="text-xs font-normal text-slate-400 font-sans">on page</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>With active phone</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">Direct dialing</span>
            </div>
        </div>

        {{-- 3. Email Contacts --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email Addresses</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-sky-600 dark:text-sky-400 font-mono">
                    {{ $withEmail }}
                    <span class="text-xs font-normal text-slate-400 font-sans">on page</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>With email listed</span>
                <span class="font-medium text-sky-600 dark:text-sky-400">Invoicing ready</span>
            </div>
        </div>

        {{-- 4. Procurement Orders --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Procurements</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                    {{ number_format($totalOrdersOnPage) }}
                    <span class="text-xs font-normal text-slate-400 font-sans">orders</span>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Orders across page</span>
                <a href="{{ route('purchases.index') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
            </div>
        </div>
    </div>

    {{-- Search & Filter Panel --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4">
        <form method="GET" action="{{ route('suppliers.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-10">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Directory</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Search by vendor name, phone, email, or physical address…"
                        class="w-full pl-9 pr-9 py-2 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                    >
                    @if($q !== null && $q !== '')
                        <a href="{{ route('suppliers.index') }}"
                           class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                           title="Clear search">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Search</span>
                </button>
                @if($q !== null && $q !== '')
                    <a href="{{ route('suppliers.index') }}"
                       title="Reset search"
                       class="inline-flex items-center justify-center p-2 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                @endif
            </div>
        </form>

        @if($q !== null && $q !== '')
            <div class="mt-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>Showing search results matching: <strong class="text-slate-800 dark:text-slate-200">“{{ $q }}”</strong></span>
                <a href="{{ route('suppliers.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Clear search filter</a>
            </div>
        @endif
    </div>

    {{-- Suppliers Table Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Supplier Name</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Email Address</th>
                        <th class="px-4 py-3">Location / Address</th>
                        <th class="px-4 py-3 text-center">Orders</th>
                        @canany(['suppliers.edit', 'suppliers.delete'])
                            <th class="px-4 py-3 text-right">Actions</th>
                        @endcanany
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- 1. Name & Partner Info --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="building" class="w-4 h-4"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 text-sm line-clamp-1">
                                            {{ $supplier->name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            @if($supplier->created_at)
                                                Partner since {{ $supplier->created_at->format('M j, Y') }}
                                            @else
                                                Vendor #{{ $supplier->id }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- 2. Phone --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($supplier->phone)
                                    <a href="tel:{{ $supplier->phone }}"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors">
                                        <i data-lucide="phone" class="w-3 h-3"></i>
                                        <span>{{ $supplier->phone }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- 3. Email --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($supplier->email)
                                    <a href="mailto:{{ $supplier->email }}"
                                       class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                        <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $supplier->email }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- 4. Address --}}
                            <td class="px-4 py-3">
                                @if($supplier->address)
                                    <div class="flex items-start gap-1 text-slate-600 dark:text-slate-400 line-clamp-2 max-w-xs" title="{{ $supplier->address }}">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mt-0.5"></i>
                                        <span>{{ $supplier->address }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- 5. Orders Count & Link --}}
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @php
                                    $orderCount = (int)($supplier->purchases_count ?? 0);
                                @endphp
                                <a href="{{ route('purchases.index', ['supplier_id' => $supplier->id]) }}"
                                   title="View supplier purchases"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $orderCount > 0 ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 hover:bg-indigo-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }} transition-colors">
                                    <i data-lucide="shopping-bag" class="w-3 h-3"></i>
                                    <span>{{ $orderCount }} orders</span>
                                </a>
                            </td>

                            {{-- 6. Actions --}}
                            @canany(['suppliers.edit', 'suppliers.delete'])
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        @can('suppliers.edit')
                                            <a href="{{ route('suppliers.edit', $supplier) }}"
                                               title="Edit Supplier"
                                               class="p-1.5 text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors">
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
                                                        title="Delete Supplier"
                                                        class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors"
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
                            <td colspan="@canany(['suppliers.edit','suppliers.delete']) 6 @else 5 @endcanany" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                        <i data-lucide="truck" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">No suppliers found</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        @if($q)
                                            No suppliers matched your search query. Try clearing the query to see all vendors.
                                        @else
                                            No suppliers are registered in the system yet. Add your first supplier to start logging purchases.
                                        @endif
                                    </p>
                                    @can('suppliers.create')
                                        <a href="{{ route('suppliers.create') }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors mt-3">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            <span>Add New Supplier</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if($suppliers instanceof LengthAwarePaginator && $suppliers->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Showing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $suppliers->firstItem() ?? 0 }}</span>
                    to <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $suppliers->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format($suppliers->total()) }}</span> suppliers
                </div>
                <div>
                    {{ $suppliers->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Global Delete Modal --}}
@can('suppliers.delete')
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-xs p-4"
     @keydown.escape.window="$store.confirm.close()">
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl p-6 w-full max-w-md transform transition-all">
        <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 mb-1">Delete Supplier</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5 leading-relaxed">
            Are you sure you want to delete this supplier? This action cannot be undone if past purchase orders or debts are linked.
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
                Delete Supplier
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
        if (!Alpine.store('confirm')) {
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
