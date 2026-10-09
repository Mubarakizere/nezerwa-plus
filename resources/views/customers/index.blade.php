@extends('layouts.app')
@section('title', 'Customers')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $customers */
    $totalCustomers = $totalCustomers
        ?? ($customers instanceof LengthAwarePaginator ? $customers->total() : $customers->count());

    $user = auth()->user();
    $canCreate  = $user?->can('customers.create');
    $canEdit    = $user?->can('customers.edit');
    $canDelete  = $user?->can('customers.delete');
    $showActions = true;

    $currentQuery  = $query ?? request('q');
    $currentFilter = $filter ?? request('filter', 'all');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2.5">
                <i data-lucide="users" class="w-7 h-7 text-indigo-600 dark:text-indigo-400"></i>
                <span>Customer Accounts & Credit</span>
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Manage customer directory, track credit sales, and oversee installment debt repayments.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @can('customers.create')
                <a href="{{ route('customers.create') }}" class="btn btn-primary flex items-center gap-1.5 text-sm">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Add Customer
                </a>
            @endcan
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Customers</p>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">{{ number_format($totalCustomers) }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Customers with Debt</p>
                <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-0.5">{{ number_format($customersWithDebt ?? 0) }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <i data-lucide="banknote" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Outstanding Debt</p>
                <h3 class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-0.5">{{ number_format($totalOutstandingDebt ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>

    {{-- Search & Filter Controls --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            <input type="hidden" name="filter" value="{{ $currentFilter }}">

            {{-- Quick Filter Chips --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                <a href="{{ route('customers.index', ['filter' => 'all', 'q' => $currentQuery]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $currentFilter === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    All Customers
                </a>
                <a href="{{ route('customers.index', ['filter' => 'debt', 'q' => $currentQuery]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5 {{ $currentFilter === 'debt' ? 'bg-amber-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    <span>Has Debt</span>
                    @if(!empty($customersWithDebt))
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentFilter === 'debt' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200' }}">
                            {{ $customersWithDebt }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('customers.index', ['filter' => 'clear', 'q' => $currentQuery]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $currentFilter === 'clear' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    Clear (No Debt)
                </a>
            </div>

            {{-- Search Bar --}}
            <div class="flex items-center gap-2 md:w-80">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ $currentQuery }}"
                        placeholder="Search name, phone, email..."
                        class="form-input pl-9 pr-9 text-xs w-full"
                    >
                    @if(!empty($currentQuery))
                        <a href="{{ route('customers.index', ['filter' => $currentFilter]) }}"
                           class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                           title="Clear search">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
                <button type="submit" class="btn btn-secondary text-xs shrink-0 px-3">
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- Customers Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-5 py-3 text-left">Customer</th>
                        <th class="px-5 py-3 text-left">Contact</th>
                        <th class="px-5 py-3 text-left">Address</th>
                        <th class="px-5 py-3 text-right">Outstanding Debt</th>
                        <th class="px-5 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($customers as $customer)
                        @php
                            $debt = $customer->total_debt;
                            $pendingCount = $customer->pending_loans_count;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3.5 text-gray-900 dark:text-gray-100">
                                <a href="{{ route('customers.show', $customer) }}" class="group block">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 flex items-center gap-1.5">
                                        <span>{{ $customer->name }}</span>
                                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-indigo-500"></i>
                                    </div>
                                    @if($customer->created_at)
                                        <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                            Added {{ $customer->created_at->format('d M Y') }}
                                        </span>
                                    @endif
                                </a>
                            </td>

                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300">
                                <div class="space-y-1">
                                    @if($customer->phone)
                                        <div>
                                            <a href="tel:{{ $customer->phone }}"
                                               class="inline-flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400">
                                                <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-400"></i>
                                                <span>{{ $customer->phone }}</span>
                                            </a>
                                        </div>
                                    @endif
                                    @if($customer->email)
                                        <div>
                                            <a href="mailto:{{ $customer->email }}"
                                               class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400"></i>
                                                <span>{{ $customer->email }}</span>
                                            </a>
                                        </div>
                                    @endif
                                    @if(!$customer->phone && !$customer->email)
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-400 text-xs">
                                {{ $customer->address ?? '—' }}
                            </td>

                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                @if($debt > 0.009)
                                    <div class="inline-flex flex-col items-end">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            {{ number_format($debt, 2) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">
                                            {{ $pendingCount }} {{ \Illuminate\Support\Str::plural('pending loan', $pendingCount) }}
                                        </span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <i data-lucide="check" class="w-3 h-3"></i> Clear
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('customers.show', $customer) }}"
                                       class="btn btn-outline btn-sm text-xs flex items-center gap-1"
                                       title="View Account & Loans">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Account</span>
                                    </a>

                                    @can('customers.edit')
                                        <a href="{{ route('customers.edit', $customer) }}"
                                           class="btn btn-outline btn-sm text-xs"
                                           title="Edit Details">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        </a>
                                    @endcan

                                    @can('customers.delete')
                                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-danger btn-sm text-xs"
                                                    title="Delete"
                                                    @click="$store.confirm.openWith($el.closest('form'))">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i data-lucide="users" class="w-8 h-8 text-gray-300 dark:text-gray-600"></i>
                                    <p>No customers found matching your criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers instanceof LengthAwarePaginator)
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                {{ $customers->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Global Delete Modal --}}
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
            Are you sure you want to delete this customer? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.close()">Cancel</button>
            <button type="button" class="btn btn-danger" @click="$store.confirm.confirm()">Delete</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        submitEl: null,
        openWith(form) { this.submitEl = form; this.open = true },
        close() { this.open = false; this.submitEl = null },
        confirm() { if (this.submitEl) this.submitEl.submit(); this.close() },
    });
});
</script>
@endpush
@endsection
