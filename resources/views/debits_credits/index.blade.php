@extends('layouts.app')
@section('title', 'Debits & Credits')

@section('content')
@php
    use Carbon\Carbon;

    // Quick date ranges
    $today  = Carbon::today()->toDateString();
    $last7  = Carbon::today()->subDays(6)->toDateString();
    $last30 = Carbon::today()->subDays(29)->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    // Page-only totals from current paginator page
    $pageCredits = $records->getCollection()->where('type','credit')->sum('amount');
    $pageDebits  = $records->getCollection()->where('type','debit')->sum('amount');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Flash --}}
    @if(session('success'))
        <div class="p-3 rounded-md bg-green-100 text-green-700 text-sm border border-green-200">
            {{ session('success') }}
        </div>
    @elseif($errors->any())
        <div class="p-3 rounded-md bg-red-100 text-red-700 text-sm border border-red-200">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Debits & Credits</span>
        </h1>

        @can('debits-credits.create')
            <a href="{{ route('debits-credits.create') }}"
               class="btn btn-primary flex items-center gap-1 text-sm">
                <i data-lucide="plus" class="w-4 h-4"></i> New Entry
            </a>
        @endcan
    </div>

    {{-- Totals Summary (filter-aware) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total Debits"  value="{{ number_format($debitsTotal,  2) }}" color="red"   />
        <x-stat-card title="Total Credits" value="{{ number_format($creditsTotal, 2) }}" color="green" />
        <x-stat-card
            title="Net Balance"
            value="{{ number_format($creditsTotal - $debitsTotal, 2) }}"
            color="{{ ($creditsTotal - $debitsTotal) > 0 ? 'green' : (($creditsTotal - $debitsTotal) < 0 ? 'red' : 'gray') }}" />
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-3">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-7 gap-3 items-end">
            <div>
                <label class="form-label text-xs">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    <option value="debit"  @selected(request('type')=='debit')>Debits</option>
                    <option value="credit" @selected(request('type')=='credit')>Credits</option>
                </select>
            </div>

            <div>
                <label class="form-label text-xs">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-input">
            </div>

            <div>
                <label class="form-label text-xs">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-input">
            </div>

            <div>
                <label class="form-label text-xs">Customer</label>
                <select name="customer_id" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Models\Customer::select('id','name')->orderBy('name')->get() as $c)
                        <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Models\Supplier::select('id','name')->orderBy('name')->get() as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-1">
                <label class="form-label text-xs">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Description, names..." class="form-input">
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-secondary w-full flex items-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i> Filter
                </button>
            </div>
        </form>

        {{-- Quick ranges --}}
        <div class="flex flex-wrap gap-2">
            <a class="btn btn-outline btn-sm"
               href="{{ route('debits-credits.index', array_merge(request()->query(), ['from'=>$today,'to'=>$today])) }}">Today</a>
            <a class="btn btn-outline btn-sm"
               href="{{ route('debits-credits.index', array_merge(request()->query(), ['from'=>$last7,'to'=>$today])) }}">Last 7d</a>
            <a class="btn btn-outline btn-sm"
               href="{{ route('debits-credits.index', array_merge(request()->query(), ['from'=>$last30,'to'=>$today])) }}">Last 30d</a>
            <a class="btn btn-outline btn-sm"
               href="{{ route('debits-credits.index', array_merge(request()->query(), ['from'=>$monthS,'to'=>$today])) }}">This Month</a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Description</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Supplier</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Linked Txn</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($records as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->date }}</td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $item->type === 'debit'
                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                                        : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' }}">
                                    {{ ucfirst($item->type) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($item->amount, 2) }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->description ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->customer->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item->user->name ?? '—' }}</td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($item->transaction_id)
                                    @can('transactions.view')
                                        <a href="{{ route('transactions.show', $item->transaction_id) }}"
                                           class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                            <i data-lucide="banknote" class="w-4 h-4"></i>
                                            #{{ $item->transaction_id }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-500">#{{ $item->transaction_id }}</span>
                                    @endcan
                                @else
                                    <span class="text-xs text-gray-500">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                @canany(['debits-credits.edit','debits-credits.delete'])
                                    <div class="flex justify-center gap-2">
                                        @can('debits-credits.edit')
                                            <a href="{{ route('debits-credits.edit', $item->id) }}"
                                               class="btn btn-outline btn-sm" title="Edit">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('debits-credits.delete')
                                            <button type="button"
                                                    @click="$store.confirm.openWith($el.nextElementSibling)"
                                                    class="btn btn-danger btn-sm" title="Delete">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>

                                            <form action="{{ route('debits-credits.destroy', $item->id) }}"
                                                  method="POST" class="hidden">
                                                @csrf @method('DELETE')
                                            </form>
                                        @endcan
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endcanany
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Page totals footer --}}
                <tfoot class="bg-gray-50 dark:bg-gray-900/40">
                    <tr class="text-sm font-semibold">
                        <td class="px-4 py-3" colspan="2">Page totals</td>
                        <td class="px-4 py-3 text-right">{{ number_format($pageCredits + $pageDebits, 2) }}</td>
                        <td class="px-4 py-3 text-left" colspan="6">
                            <span class="mr-4">
                                Credits:
                                <span class="text-green-600 dark:text-green-400">{{ number_format($pageCredits, 2) }}</span>
                            </span>
                            <span>
                                Debits:
                                <span class="text-red-600 dark:text-red-400">{{ number_format($pageDebits, 2) }}</span>
                            </span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
            {{ $records->links() }}
        </div>
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
            Are you sure you want to delete this entry? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.close()">Cancel</button>
            @can('debits-credits.delete')
                <button type="button" class="btn btn-danger" @click="$store.confirm.confirm()">Delete</button>
            @endcan
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
