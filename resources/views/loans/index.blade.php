@extends('layouts.app')
@section('title', 'Loans Overview')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $fmt = fn($n) => number_format((float)$n, 2);

    // Compute page totals safely (works with paginator)
    $collection = $loans instanceof \Illuminate\Pagination\LengthAwarePaginator ? $loans->getCollection() : collect($loans);
    $pageAmountTotal   = $collection->sum(fn($l) => (float)($l->amount ?? 0));
    $pagePaidTotal     = $collection->sum(function ($l) {
        if (property_exists($l, 'payments_sum_amount')) return (float) $l->payments_sum_amount;
        try { return (float) $l->payments()->sum('amount'); } catch (\Throwable $e) { return 0; }
    });
    $pageRemainingTotal = max($pageAmountTotal - $pagePaidTotal, 0);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Loans Overview</span>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Track given/taken loans, progress, and due dates.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @canany(['loans.export','loans.view'])
                <a href="{{ route('loans.export.pdf') }}" class="btn btn-outline flex items-center gap-1 text-sm">
                    <i data-lucide="file-down" class="w-4 h-4"></i> PDF Summary
                </a>
            @endcanany

            @can('loans.create')
                <a href="{{ route('loans.create') }}" class="btn btn-primary flex items-center gap-1 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Loan
                </a>
            @endcan
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- KPI Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <x-stat-card title="Total Loans" value="{{ number_format($stats['total_loans'], 2) }}" color="indigo" />
        <x-stat-card title="Paid Loans" value="{{ number_format($stats['paid_loans'], 2) }}" color="green" />
        <x-stat-card title="Pending Loans" value="{{ number_format($stats['pending_loans'], 2) }}" color="yellow" />
        <x-stat-card title="Loans Given" value="{{ $stats['count_given'] }}" color="blue" />
        <x-stat-card title="Loans Taken" value="{{ $stats['count_taken'] }}" color="purple" />
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('loans.index') }}"
          class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            {{-- Type --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Type</label>
                <select name="type" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Any</option>
                    <option value="given" @selected(request('type')==='given')>Given</option>
                    <option value="taken" @selected(request('type')==='taken')>Taken</option>
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Any</option>
                    <option value="pending" @selected(request('status')==='pending')>Pending</option>
                    <option value="paid" @selected(request('status')==='paid')>Paid</option>
                </select>
            </div>

            {{-- From --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            </div>

            {{-- To --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            </div>

            {{-- Party search --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Party</label>
                <input type="text" name="party" value="{{ request('party') }}" placeholder="Customer/Supplier name"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            </div>

            {{-- Free text search --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Notes, #id…"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            {{-- Quick chips --}}
            <div class="flex flex-wrap gap-2">
                @php $period = request('period'); @endphp
                <a href="{{ route('loans.index', array_merge(request()->except('page'), ['period'=>null])) }}"
                   class="px-3 py-1 rounded-full text-xs ring-1
                          {{ $period ? 'ring-gray-200 dark:ring-gray-800 text-gray-600 dark:text-gray-300' : 'ring-indigo-300 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' }}">
                    All time
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page'), ['period'=>'today'])) }}"
                   class="px-3 py-1 rounded-full text-xs ring-1 {{ $period==='today' ? 'ring-indigo-300 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'ring-gray-200 dark:ring-gray-800 text-gray-600 dark:text-gray-300' }}">
                    Today
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page'), ['period'=>'week'])) }}"
                   class="px-3 py-1 rounded-full text-xs ring-1 {{ $period==='week' ? 'ring-indigo-300 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'ring-gray-200 dark:ring-gray-800 text-gray-600 dark:text-gray-300' }}">
                    This week
                </a>
                <a href="{{ route('loans.index', array_merge(request()->except('page'), ['period'=>'month'])) }}"
                   class="px-3 py-1 rounded-full text-xs ring-1 {{ $period==='month' ? 'ring-indigo-300 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'ring-gray-200 dark:ring-gray-800 text-gray-600 dark:text-gray-300' }}">
                    This month
                </a>

                {{-- Overdue toggle --}}
                <label class="ml-2 inline-flex items-center gap-2 text-xs">
                    <input type="checkbox" name="overdue" value="1" @checked(request('overdue'))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-gray-700 dark:text-gray-300">Overdue only</span>
                </label>
            </div>

            <div class="flex flex-wrap gap-2">
                <button class="btn btn-secondary flex items-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i> Apply
                </button>
                <a href="{{ route('loans.index') }}" class="btn btn-outline flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
            <i data-lucide="list" class="w-4 h-4 text-indigo-500"></i>
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Results</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                        <th class="px-5 py-3">#</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Client / Supplier</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">Paid</th>
                        <th class="px-5 py-3 text-right">Remaining</th>
                        <th class="px-5 py-3">Loan Date</th>
                        <th class="px-5 py-3">Due Date</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($loans as $loan)
                        @php
                            // Prefer withSum('payments','amount') if controller eager-loads it
                            $paid = property_exists($loan, 'payments_sum_amount')
                                    ? (float) $loan->payments_sum_amount
                                    : (float) $loan->payments()->sum('amount');

                            $remaining = round(max(($loan->amount ?? 0) - $paid, 0), 2);
                            $progress  = ($loan->amount ?? 0) > 0 ? (int) round(($paid / $loan->amount) * 100) : 0;

                            $due       = $loan->due_date ? Carbon::parse($loan->due_date) : null;
                            $isOverdue = $due && $remaining > 0 && $due->isPast();
                            $daysLeft  = $due ? Carbon::now()->startOfDay()->diffInDays($due, false) : null;
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all {{ $isOverdue ? 'bg-rose-50/40 dark:bg-rose-900/10' : '' }}">
                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-100">#{{ $loan->id }}</td>

                            <td class="px-5 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $loan->type === 'given'
                                        ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
                                        : 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' }}">
                                    {{ ucfirst($loan->type) }}
                                </span>
                            </td>

                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                @if ($loan->type === 'given')
                                    {{ $loan->customer->name ?? '—' }}
                                @else
                                    {{ $loan->supplier->name ?? '—' }}
                                @endif
                            </td>

                            <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ $fmt($loan->amount) }}
                            </td>

                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-2 min-w-[140px] justify-end">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $fmt($paid) }}</span>
                                </div>
                                <div class="mt-1 w-32 ml-auto bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="{{ $remaining <= 0 ? 'bg-green-500' : 'bg-indigo-500' }} h-1.5" style="width: {{ $progress }}%"></div>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-right font-semibold {{ $remaining > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $fmt($remaining) }}
                            </td>

                            <td class="px-5 py-3 text-gray-600 dark:text-gray-400">
                                {{ optional($loan->loan_date)->format('Y-m-d') }}
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <span>{{ optional($loan->due_date)->format('Y-m-d') ?? '—' }}</span>
                                    @if($due)
                                        @if($isOverdue)
                                            <span class="px-1.5 py-0.5 rounded text-[11px] bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">Overdue</span>
                                        @elseif(!is_null($daysLeft))
                                            <span class="px-1.5 py-0.5 rounded text-[11px] bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                                {{ $daysLeft >= 0 ? "D-{$daysLeft}" : "D+".abs($daysLeft) }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $loan->status === 'paid'
                                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                                        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>

                            <td class="px-5 py-3">
                                @canany(['loans.view','loans.edit','loans.delete'])
                                    <div class="flex justify-end gap-1">
                                        @can('loans.view')
                                            <a href="{{ route('loans.show', $loan) }}"
                                               class="btn btn-secondary text-xs flex items-center gap-1">
                                                <i data-lucide="eye" class="w-4 h-4"></i> View
                                            </a>
                                        @endcan

                                        @if($loan->status !== 'paid')
                                            @can('loans.view') {{-- using loans.view for payments as per routes --}}
                                                <a href="{{ route('loan-payments.create', $loan) }}"
                                                   class="btn btn-success text-xs flex items-center gap-1">
                                                    <i data-lucide="plus" class="w-4 h-4"></i> Add Payment
                                                </a>
                                            @endcan
                                        @endif

                                        @can('loans.edit')
                                            <a href="{{ route('loans.edit', $loan) }}"
                                               class="btn btn-outline text-xs flex items-center gap-1">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
                                            </a>
                                        @endcan

                                        @can('loans.delete')
                                            <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="button" class="btn btn-danger text-xs flex items-center gap-1"
                                                        @click="$store.confirm.openWith($el.closest('form'))">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                                                </button>
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
                            <td colspan="10" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">No loans found.</td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Page totals --}}
                <tfoot class="bg-gray-50 dark:bg-gray-800/50 text-sm">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">Page Totals</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">RWF {{ $fmt($pageAmountTotal) }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">RWF {{ $fmt($pagePaidTotal) }}</td>
                        <td class="px-5 py-3 text-right font-semibold {{ $pageRemainingTotal>0 ? 'text-rose-600 dark:text-rose-400' : 'text-green-600 dark:text-green-400' }}">
                            RWF {{ $fmt($pageRemainingTotal) }}
                        </td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($loans instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-800">
                {{ $loans->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Global Delete Confirmation Modal --}}
<div x-data
     x-show="$store.confirm.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="$store.confirm.open=false">
    <div @click.outside="$store.confirm.open=false"
         class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Deletion</h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this loan? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-outline" @click="$store.confirm.open=false">Cancel</button>
            @can('loans.delete')
                <button type="button" class="btn btn-danger"
                        @click="$store.confirm.submitEl?.submit(); $store.confirm.open=false;">
                    Delete
                </button>
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
        openWith(form) { this.submitEl = form; this.open = true; }
    });
});
</script>
@endpush
@endsection
