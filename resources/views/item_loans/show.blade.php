@extends('layouts.app')
@section('title', "Loan #{$loan->id}")

@section('content')
@php
    $fmt = fn($n) => number_format((float)$n, 2);
    $qty = (float)$loan->quantity;
    $ret = (float)$loan->quantity_returned;
    $rem = $qty - $ret; $rem = $rem > 0 ? $rem : 0;
    $pct = $qty > 0 ? floor(($ret / $qty) * 100) : 0;
    $isOverdue = $loan->due_date && $rem > 0 && \Carbon\Carbon::parse($loan->due_date)->isBefore(today());
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('item-loans.index') }}" class="btn btn-outline">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="handshake" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Loan #{{ $loan->id }}</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('item-loans.edit', $loan) }}" class="btn btn-outline">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit
            </a>
            @if (! $loan->returns()->exists())
                <form action="{{ route('item-loans.destroy', $loan) }}" method="POST" onsubmit="return confirm('Delete this loan?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">
                        <i data-lucide="trash" class="w-4 h-4"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
            <div class="text-xs text-gray-500">Partner</div>
            <div class="font-medium">{{ $loan->partner->name ?? '—' }}</div>
            @if($loan->direction === 'given')
                <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i> We LENT
                </div>
            @else
                <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                    <i data-lucide="arrow-down-left" class="w-3.5 h-3.5"></i> We BORROWED
                </div>
            @endif
        </div>

        <div class="rounded-xl border dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
            <div class="text-xs text-gray-500">Item</div>
            <div class="font-medium">{{ $loan->item_name }}</div>
            @if($loan->product)
                <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">
                    <i data-lucide="link" class="w-3 h-3 inline"></i> {{ $loan->product->name }}
                </div>
            @endif
            <div class="text-xs text-gray-500 mt-1">{{ $fmt($loan->quantity) }} {{ $loan->unit }}</div>
        </div>

        <div class="rounded-xl border dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
            <div class="text-xs text-gray-500">Dates</div>
            <div class="text-sm">Loan: {{ \Carbon\Carbon::parse($loan->loan_date)->format('Y-m-d') }}</div>
            <div class="text-sm">
                Due: {{ $loan->due_date ? \Carbon\Carbon::parse($loan->due_date)->format('Y-m-d') : '—' }}
                @if($isOverdue)
                    <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Overdue</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Progress --}}
    <div class="rounded-xl border dark:border-gray-700 p-5 bg-white dark:bg-gray-800">
        <div class="flex items-center justify-between text-sm mb-2">
            <div>Returned: {{ $fmt($ret) }} / {{ $fmt($qty) }} {{ $loan->unit }}</div>
            <div>Remaining: <span class="font-semibold">{{ $fmt($rem) }} {{ $loan->unit }}</span></div>
        </div>
        <div class="w-full h-3 bg-gray-200 dark:bg-gray-900 rounded-full overflow-hidden">
            <div class="h-3 bg-indigo-500 dark:bg-indigo-400" style="width: {{ $pct }}%"></div>
        </div>
        <div class="mt-2 text-xs text-gray-500">Status:
            <span class="font-medium">{{ ucfirst($loan->status) }}</span>
        </div>
    </div>

    {{-- Notes --}}
    @if($loan->notes)
    <div class="rounded-xl border dark:border-gray-700 p-5 bg-white dark:bg-gray-800">
        <div class="text-sm text-gray-500 mb-1">Notes</div>
        <div class="whitespace-pre-wrap">{{ $loan->notes }}</div>
    </div>
    @endif

    {{-- Record Return + Returns table --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Record Return --}}
        <div class="rounded-xl border dark:border-gray-700 p-5 bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Record Return</h2>
                @if($rem <= 0)
                    <span class="text-xs text-emerald-600">Fully returned</span>
                @endif
            </div>

            @if (session('success'))
                <div class="mt-3 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 p-2 text-sm text-emerald-800 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-3 rounded-lg border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 p-2 text-sm text-red-800 dark:text-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($rem > 0)
            <form method="POST" action="{{ route('item-loans.return', $loan) }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Return Date <span class="text-red-600">*</span></label>
                        <input type="date" name="return_date" value="{{ old('return_date', now()->toDateString()) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Returned Qty <span class="text-red-600">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $rem }}" name="returned_qty" value="{{ old('returned_qty') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                        <p class="text-xs text-gray-500 mt-1">Max: {{ $fmt($rem) }} {{ $loan->unit }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Note</label>
                    <textarea name="note" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Optional note...">{{ old('note') }}</textarea>
                </div>
                <div class="flex items-center justify-end">
                    <button class="btn btn-primary">
                        <i data-lucide="check" class="w-4 h-4"></i> Save Return
                    </button>
                </div>
            </form>
            @else
                <div class="mt-3 text-sm text-gray-500">No remaining quantity to return.</div>
            @endif
        </div>

        {{-- Returns Table --}}
        <div class="rounded-xl border dark:border-gray-700 p-5 bg-white dark:bg-gray-800">
            <h2 class="font-semibold mb-3">Returns</h2>
            <div class="overflow-auto rounded-lg border dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2 text-left">Note</th>
                            <th class="px-3 py-2 text-left">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @forelse($loan->returns as $r)
                            <tr>
                                <td class="px-3 py-2">{{ \Carbon\Carbon::parse($r->return_date)->format('Y-m-d') }}</td>
                                <td class="px-3 py-2 text-right">{{ $fmt($r->returned_qty) }} {{ $loan->unit }}</td>
                                <td class="px-3 py-2">{{ $r->note }}</td>
                                <td class="px-3 py-2">{{ $r->user->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-500">No returns recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
