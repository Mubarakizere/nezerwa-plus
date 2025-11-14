@extends('layouts.app')
@section('title', 'Inter-Company Item Loans')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $filters = $filters ?? [];
    $dir = $filters['direction'] ?? '';
    $status = $filters['status'] ?? '';
    $overdue = (bool)($filters['overdue'] ?? false);
    $fmt = fn($n) => number_format((float)$n, 2);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="handshake" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Inter-Company Item Lending</span>
        </h1>

        <a href="{{ route('item-loans.create') }}" class="btn btn-primary inline-flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Loan
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="rounded-xl border dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Item or partner..."
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-xs text-gray-500">Direction</label>
                <select name="direction" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All</option>
                    <option value="given" @selected($dir==='given')>We LENT (given)</option>
                    <option value="taken" @selected($dir==='taken')>We BORROWED (taken)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All</option>
                    @foreach (['pending','partial','returned','overdue'] as $s)
                        <option value="{{ $s }}" @selected($status===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-xs text-gray-500">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div class="flex items-center justify-between mt-3">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="overdue" value="1" @checked($overdue)
                       class="rounded border-gray-300 dark:border-gray-700">
                <span>Overdue only</span>
            </label>

            <div class="flex items-center gap-2">
                <a href="{{ route('item-loans.index') }}" class="btn btn-outline">Reset</a>
                <button class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-auto rounded-xl border dark:border-gray-700 bg-white dark:bg-gray-800">
        <table class="min-w-full text-sm">
            <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Partner</th>
                    <th class="px-4 py-3 text-left">Item</th>
                    <th class="px-4 py-3 text-left">Direction</th>
                    <th class="px-4 py-3 text-right">Qty</th>
                    <th class="px-4 py-3 text-right">Returned</th>
                    <th class="px-4 py-3 text-right">Remaining</th>
                    <th class="px-4 py-3 text-left">Due</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
            @forelse ($loans as $loan)
                @php
                    $remaining = (float)$loan->quantity - (float)$loan->quantity_returned;
                    $remaining = $remaining > 0 ? $remaining : 0;
                    $isOverdue = $loan->due_date && $remaining > 0 && \Carbon\Carbon::parse($loan->due_date)->isBefore(today());
                @endphp
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-900/40">
                    <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($loan->loan_date)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">{{ $loan->partner->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $loan->item_name }}</div>
                        @if ($loan->unit)
                            <div class="text-xs text-gray-500">Unit: {{ $loan->unit }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($loan->direction === 'given')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i> We LENT
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                <i data-lucide="arrow-down-left" class="w-3.5 h-3.5"></i> We BORROWED
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">{{ $fmt($loan->quantity) }} {{ $loan->unit }}</td>
                    <td class="px-4 py-3 text-right">{{ $fmt($loan->quantity_returned) }} {{ $loan->unit }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ $fmt($remaining) }} {{ $loan->unit }}</td>
                    <td class="px-4 py-3">
                        @if($loan->due_date)
                            <div class="flex items-center gap-2">
                                <span>{{ \Carbon\Carbon::parse($loan->due_date)->format('Y-m-d') }}</span>
                                @if($isOverdue)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Overdue</span>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $badge = [
                                'pending'  => 'bg-gray-100 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
                                'partial'  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                'returned' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                                'overdue'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                            ][$loan->status] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs {{ $badge }}">
                            <i data-lucide="circle" class="w-3 h-3"></i> {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('item-loans.show', $loan) }}" class="btn btn-outline text-xs">
                            <i data-lucide="eye" class="w-4 h-4"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">No loans found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div>
        {{ $loans->links() }}
    </div>
</div>
@endsection
