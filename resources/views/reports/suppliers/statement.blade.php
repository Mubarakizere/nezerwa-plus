@extends('layouts.app')
@section('title', 'Supplier Statement')

@section('content')
@php
  $fmt = fn($n) => number_format((float)($n ?? 0), 2);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="file-text" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                Supplier Statement
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Purchases (net), payments, refunds & balance.</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reports.suppliers.statement') }}"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 grid grid-cols-1 md:grid-cols-12 gap-3">
        <div class="md:col-span-6">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Supplier</label>
            <select name="supplier_id" class="form-select w-full">
                <option value="">Select supplier…</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected((int)$supplierId === (int)$s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}" class="form-input w-full">
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}" class="form-input w-full">
        </div>
        <div class="md:col-span-12 flex justify-end gap-2">
            <button class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="filter" class="w-4 h-4"></i> Apply
            </button>
            <a href="{{ route('reports.suppliers.statement.pdf', request()->all()) }}" class="btn btn-primary flex items-center gap-1" target="_blank">
                <i data-lucide="download" class="w-4 h-4"></i> PDF
            </a>
            <a href="{{ route('reports.suppliers.statement') }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset
            </a>
        </div>
    </form>

    @if($supplierId)
        {{-- KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <x-stat-card title="Purchases (net)" value="RWF {{ $fmt($kpis['purchases_total']) }}" color="indigo" />
            <x-stat-card title="Paid to Supplier" value="RWF {{ $fmt($kpis['paid_total']) }}" color="emerald" />
            <x-stat-card title="Supplier Refunds" value="RWF {{ $fmt($kpis['refunds_total']) }}" color="blue" />
            <x-stat-card title="Balance" value="RWF {{ $fmt($kpis['balance']) }}"
                color="{{ $kpis['balance'] > 0 ? 'amber' : 'emerald' }}" />
        </div>

        {{-- Timeline --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-indigo-500"></i>
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Statement Timeline</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1000px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Type</th>
                            <th class="px-5 py-3 text-left">Reference</th>
                            <th class="px-5 py-3 text-left">Method</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3 text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($events as $e)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $e['date']->format('Y-m-d') }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold
                                        {{ $e['type']==='Purchase'
                                            ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                        {{ $e['type'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @if($e['link'])
                                        <a href="{{ $e['link'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $e['ref'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-700 dark:text-gray-300">{{ $e['ref'] }}</span>
                                    @endif
                                    @if(!empty($e['note']))
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $e['note'] }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $e['method'] }}</td>
                                <td class="px-5 py-3 text-right font-medium
                                    {{ $e['amount'] >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-emerald-700 dark:text-emerald-300' }}">
                                    {{ $e['amount'] >= 0 ? 'RWF '.$fmt($e['amount']) : '- RWF '.$fmt(abs($e['amount'])) }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold
                                    {{ $e['balance'] > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                                    RWF {{ $fmt($e['balance']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No activity for the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Returns (informational) --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="u-turn-left" class="w-4 h-4 text-indigo-500"></i>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Returns to Supplier (Info)</h2>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Shown for transparency — already reflected in purchase totals.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Ref</th>
                            <th class="px-5 py-3 text-left">Channel</th>
                            <th class="px-5 py-3 text-right">Returned Value</th>
                            <th class="px-5 py-3 text-right">Cash Refund</th>
                            <th class="px-5 py-3 text-right">Items</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($returns as $r)
                            <tr>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($r->return_date)->format('Y-m-d') }}
                                </td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('purchases.show', $r->purchase_id) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Purchase #{{ $r->purchase_id }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ strtoupper($r->payment_channel ?? '-') }}</td>
                                <td class="px-5 py-3 text-right text-gray-900 dark:text-gray-100">RWF {{ $fmt($r->total_amount) }}</td>
                                <td class="px-5 py-3 text-right text-emerald-700 dark:text-emerald-300">RWF {{ $fmt($r->refund_amount) }}</td>
                                <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ $r->items_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No returns in the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>document.addEventListener('DOMContentLoaded', () => window.lucide && lucide.createIcons());</script>
@endpush
@endsection
