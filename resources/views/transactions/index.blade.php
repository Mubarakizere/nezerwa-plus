@extends('layouts.app')
@section('title', 'Transactions')

@section('content')
@php
    use Carbon\Carbon;

    $fmt    = fn($n) => number_format((float)$n, 2);
    $today  = Carbon::today()->toDateString();
    $last7  = Carbon::today()->subDays(6)->toDateString();
    $last30 = Carbon::today()->subDays(29)->toDateString();
    $monthS = Carbon::today()->startOfMonth()->toDateString();

    // If controller selected a running_balance window column (PG only)
    $hasRunning = optional($transactions->first())->running_balance !== null;
@endphp

@cannot('transactions.view')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-off" class="w-6 h-6 text-amber-600 dark:text-amber-300 mt-0.5"></i>
                <div>
                    <h1 class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                        You don’t have permission to view transactions.
                    </h1>
                    <p class="mt-1 text-sm text-amber-800/80 dark:text-amber-100/80">
                        Please contact an administrator if you believe this is a mistake.
                    </p>
                </div>
            </div>
        </div>
    </div>
@else
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="banknote" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Transactions</span>
        </h1>

        <div class="flex flex-wrap gap-2">
            @can('transactions.create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary text-sm flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Transaction
                </a>
                <a href="{{ route('transactions.withdrawal') }}" class="btn btn-danger text-sm flex items-center gap-1">
                    <i data-lucide="send" class="w-4 h-4"></i> Send to Boss
                </a>
                <a href="{{ route('transactions.transfer') }}" class="btn btn-secondary text-sm flex items-center gap-1">
                    <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Transfer
                </a>
            @endcan

            {{-- Exports keep current filters (query string) --}}
            <a href="{{ route('transactions.export.csv', request()->query()) }}"
               class="btn btn-success text-sm flex items-center gap-1">
                <i data-lucide="file-text" class="w-4 h-4"></i> Export CSV
            </a>
            <a href="{{ route('transactions.export.pdf', request()->query()) }}"
               class="btn btn-danger text-sm flex items-center gap-1">
                <i data-lucide="file-down" class="w-4 h-4"></i> Export PDF
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="p-3 rounded-md bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 text-sm border border-green-200 dark:border-green-800">
            {{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="p-3 rounded-md bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 text-sm border border-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('transactions.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                <div>
                    <label class="form-label" for="type">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All</option>
                        <option value="credit" @selected(request('type')=='credit')>Credit</option>
                        <option value="debit"  @selected(request('type')=='debit')>Debit</option>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="method">Method</label>
                    <select id="method" name="method" class="form-select">
                        <option value="">All</option>
                        @foreach(['cash','bank','momo'] as $m)
                            <option value="{{ $m }}" @selected(request('method') == $m)>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="date_from">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-input">
                </div>

                <div>
                    <label class="form-label" for="date_to">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-input">
                </div>

                <div>
                    <label class="form-label" for="customer_id">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Models\Customer::select('id','name')->orderBy('name')->get() as $customer)
                            <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Models\Supplier::select('id','name')->orderBy('name')->get() as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="q">Search</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="Method, notes, user, customer, supplier..." class="form-input">
                </div>
            </div>

            {{-- Quick ranges --}}
            <div class="flex flex-wrap gap-2 pt-2">
                <a class="btn btn-outline btn-sm"
                   href="{{ route('transactions.index', array_merge(request()->query(), ['date_from'=>$today,'date_to'=>$today])) }}">
                    Today
                </a>
                <a class="btn btn-outline btn-sm"
                   href="{{ route('transactions.index', array_merge(request()->query(), ['date_from'=>$last7,'date_to'=>$today])) }}">
                    Last 7d
                </a>
                <a class="btn btn-outline btn-sm"
                   href="{{ route('transactions.index', array_merge(request()->query(), ['date_from'=>$last30,'date_to'=>$today])) }}">
                    Last 30d
                </a>
                <a class="btn btn-outline btn-sm"
                   href="{{ route('transactions.index', array_merge(request()->query(), ['date_from'=>$monthS,'date_to'=>$today])) }}">
                    This Month
                </a>

                <div class="ml-auto">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline ml-1">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Totals (filter-aware) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Total Credits" value="{{ $fmt($totalCredits) }}" color="green" />
        <x-stat-card title="Total Debits"  value="{{ $fmt($totalDebits)  }}" color="red" />
        <x-stat-card
            title="Cash P&L (filtered)"
            value="{{ $fmt($totalCredits - $totalDebits) }}"
            color="{{ ($totalCredits - $totalDebits) >= 0 ? 'green' : 'red' }}"
        />
    </div>

    @if(isset($channelBalances) && count($channelBalances) > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
        @foreach($channelBalances as $name => $bal)
            <div class="p-3 bg-white dark:bg-gray-800 border {{ $bal < 0 ? 'border-red-200 dark:border-red-900/50' : 'border-gray-200 dark:border-gray-700' }} rounded-xl shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">{{ $name }}</div>
                <div class="text-sm font-bold {{ $bal < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                    {{ number_format($bal, 0) }} RWF
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        @if($hasRunning)
                            <th class="px-4 py-3 text-right">Running</th>
                        @endif
                        <th class="px-4 py-3 text-left">Method</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Supplier</th>
                        <th class="px-4 py-3 text-left">Source</th>
                        <th class="px-4 py-3 text-left">Notes</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($transactions as $transaction)
                        @php 
                            $id = data_get($transaction, 'id'); 
                            $rowClass = 'hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all';
                            if ($transaction->is_withdrawal) {
                                $rowClass = 'bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/10 dark:hover:bg-rose-900/20';
                            } elseif ($transaction->is_transfer) {
                                $rowClass = 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/10 dark:hover:bg-blue-900/20';
                            }
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i') }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $transaction->type === 'credit'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-semibold">
                                {{ $fmt($transaction->amount) }}
                            </td>

                            @if($hasRunning)
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100">
                                    {{ $fmt($transaction->running_balance) }}
                                </td>
                            @endif

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $transaction->method ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $transaction->user->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $transaction->customer->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $transaction->supplier->name ?? '-' }}
                            </td>

                            {{-- Source --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                @if($transaction->is_withdrawal)
                                    <span class="inline-flex items-center gap-1 font-bold text-rose-600 dark:text-rose-400 uppercase text-xs">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                        Boss Withdrawal
                                    </span>
                                @elseif($transaction->is_transfer)
                                    <span class="inline-flex items-center gap-1 font-bold text-blue-600 dark:text-blue-400 uppercase text-xs">
                                        <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                                        Internal Transfer
                                    </span>
                                @elseif($transaction->sale_id && !$transaction->purchase_id)
                                    <span class="inline-flex items-center gap-1 text-green-700 dark:text-green-300">
                                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                        Sale #{{ $transaction->sale_id }}
                                    </span>
                                @elseif($transaction->purchase_id && !$transaction->sale_id)
                                    <span class="inline-flex items-center gap-1 text-amber-700 dark:text-amber-300">
                                        <i data-lucide="package" class="w-4 h-4"></i>
                                        Purchase #{{ $transaction->purchase_id }}
                                    </span>
                                @elseif($transaction->sale_id && $transaction->purchase_id)
                                    <span class="inline-flex items-center gap-1 text-indigo-700 dark:text-indigo-300">
                                        <i data-lucide="git-merge" class="w-4 h-4"></i>
                                        Mixed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                        Manual
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $transaction->notes ?? '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-center">
                                <div x-data="{ delId: 'tx-del-{{ $id }}' }" class="flex justify-center gap-2">
                                    @if($id)
                                        @can('transactions.view')
                                            <a href="{{ route('transactions.show', $id) }}"
                                               class="btn btn-secondary btn-sm"
                                               title="View">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('transactions.edit')
                                            <a href="{{ route('transactions.edit', $id) }}"
                                               class="btn btn-outline btn-sm"
                                               title="Edit">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </a>
                                        @endcan

                                        @can('transactions.delete')
                                            <button type="button"
                                                    @click="$store.confirm.open(delId)"
                                                    class="btn btn-danger btn-sm"
                                                    title="Delete">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>

                                            <form id="tx-del-{{ $id }}"
                                                  action="{{ route('transactions.destroy', $id) }}"
                                                  method="POST"
                                                  class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endcan
                                    @else
                                        <span class="text-xs text-amber-500">Row has no id</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $hasRunning ? 11 : 10 }}"
                                class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Page totals footer --}}
                <tfoot class="bg-gray-50 dark:bg-gray-900/40">
                    <tr class="text-sm font-semibold">
                        <td class="px-4 py-3" colspan="2">Page totals</td>
                        <td class="px-4 py-3 text-right">{{ $fmt($pageCredits + $pageDebits) }}</td>
                        @if($hasRunning)
                            <td class="px-4 py-3 text-right">—</td>
                        @endif
                        <td class="px-4 py-3 text-left" colspan="7">
                            <span class="mr-4">
                                Credits:
                                <span class="text-green-600 dark:text-green-400">{{ $fmt($pageCredits) }}</span>
                            </span>
                            <span>
                                Debits:
                                <span class="text-red-600 dark:text-red-400">{{ $fmt($pageDebits) }}</span>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endcannot

{{-- Global Delete Confirmation Modal --}}
<div x-data x-show="$store.confirm.open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.close()" x-transition>
    <div @click.outside="$store.confirm.close()"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this transaction? This action cannot be undone.
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
    if (window.lucide) {
        lucide.createIcons();
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        formId: null,
        open(id){ this.formId = id; this.open = true },
        close(){ this.open = false; this.formId = null },
        confirm(){
            const f = this.formId ? document.getElementById(this.formId) : null;
            if (f) f.submit();
            this.close();
        },
    });
});
</script>
@endpush
@endsection
