@extends('layouts.app')
@section('title','Customer Statement')

@section('content')
@php $fmt = fn($n) => 'RWF '.number_format((float)$n,2); @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-center gap-2">
        <i data-lucide="user-round" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Customer Statement</h1>
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400 -mt-2">Sales (net), payments received, refunds & balance.</p>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reports.customers.statement') }}"
          class="rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-800 p-4 grid grid-cols-1 md:grid-cols-12 gap-3">
        <div class="md:col-span-6">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Customer</label>
            <select name="customer_id" class="form-select w-full">
                <option value="">Select customer</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input w-full">
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input w-full">
        </div>
        <div class="md:col-span-12 flex justify-end gap-2">
            <button class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="filter" class="w-4 h-4"></i> Apply
            </button>
            <a href="{{ route('reports.customers.statement.pdf', request()->all()) }}" class="btn btn-primary flex items-center gap-1" target="_blank">
                <i data-lucide="download" class="w-4 h-4"></i> PDF
            </a>
            <a href="{{ route('reports.customers.statement') }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset
            </a>
        </div>
    </form>

    @if(request('customer_id'))
    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <x-stat-card title="Sales (net)" value="{{ $fmt($kpis['sales_total']) }}" color="indigo" />
        <x-stat-card title="Paid by Customer" value="{{ $fmt($kpis['paid_total']) }}" color="emerald" />
        <x-stat-card title="Customer Refunds" value="{{ $fmt($kpis['refunds_total']) }}" color="amber" />
        <x-stat-card title="Balance" value="{{ $fmt($kpis['balance']) }}" color="{{ $kpis['balance']>0?'red':'green' }}" />
    </div>

    {{-- Timeline --}}
    <div class="rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center gap-2">
            <i data-lucide="list" class="w-4 h-4 text-indigo-500"></i>
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Statement Timeline</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1000px] w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-left">Reference</th>
                        <th class="px-4 py-2 text-left">Method</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($events as $row)
                        @php
                            $badge = match($row['type']) {
                                'Sale'            => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                'Payment'         => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                'Customer Refund' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                default           => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-300',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="px-4 py-2">{{ $row['date']->format('Y-m-d') }}</td>
                            <td class="px-4 py-2"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badge }}">{{ $row['type'] }}</span></td>
                            <td class="px-4 py-2">
                                @if(!empty($row['link']))
                                    <a href="{{ $row['link'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $row['ref'] }}</a>
                                @else
                                    {{ $row['ref'] }}
                                @endif
                                @if(!empty($row['note']))
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $row['note'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $row['method'] }}</td>
                            <td class="px-4 py-2 text-right">
                                <span class="{{ $row['amount']<0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ $fmt($row['amount']) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <span class="{{ $row['balance']>0 ? 'text-rose-600 dark:text-rose-300':'text-emerald-700 dark:text-emerald-300' }}">
                                    {{ $fmt($row['balance']) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No data for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Returns from Customer (Info) --}}
    @if($returns->count())
        <div class="rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
            <div class="flex items-center gap-2">
                <i data-lucide="u-turn-left" class="w-5 h-5 text-indigo-600 dark:text-indigo-300"></i>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Returns from Customer (Info)</h3>
            </div>
            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Ref</th>
                            <th class="px-4 py-2">Channel</th>
                            <th class="px-4 py-2 text-right">Returned Value</th>
                            <th class="px-4 py-2 text-right">Cash Refund</th>
                            <th class="px-4 py-2 text-right">Items</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($returns as $ret)
                            <tr>
                                <td class="px-4 py-2">{{ $ret->return_date ? \Carbon\Carbon::parse($ret->return_date)->format('Y-m-d') : '—' }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('sales.show', $ret->sale_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Sale #{{ $ret->sale_id }}
                                    </a>
                                </td>
                                <td class="px-4 py-2">{{ strtoupper($ret->payment_channel ?? '-') }}</td>
                                <td class="px-4 py-2 text-right">{{ $fmt($ret->total_amount ?? 0) }}</td>
                                <td class="px-4 py-2 text-right text-emerald-700 dark:text-emerald-300">{{ $fmt($ret->refund_amount ?? 0) }}</td>
                                <td class="px-4 py-2 text-right">{{ $ret->items_count ?? $ret->items->count() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Shown for transparency — refunds issued are already reflected via transactions.
            </p>
        </div>
    @endif
    @endif
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{ if (window.lucide) lucide.createIcons(); });</script>
@endpush
@endsection
