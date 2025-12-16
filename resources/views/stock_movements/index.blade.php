@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
@php
    use App\Models\Purchase;
    use App\Models\Sale;
    use App\Models\PurchaseReturn;
    use App\Models\SaleReturn;

    $fmt = fn($n) => number_format((float)$n, 2);
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="package" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Stock Movements</span>
        </h1>

        @can('stock.view')
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('stock.history.export.csv', request()->query()) }}"
                   class="btn btn-success text-sm flex items-center gap-1">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Export CSV</span>
                </a>
                <a href="{{ route('stock.history.export.pdf', request()->query()) }}"
                   target="_blank"
                   class="btn btn-primary text-sm flex items-center gap-1">
                    <i data-lucide="file-down" class="w-4 h-4"></i>
                    <span>Export PDF</span>
                </a>
            </div>
        @endcan
    </div>

    @cannot('stock.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to view stock history.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card title="Total In"  value="{{ $fmt($totals['in'] ?? 0)  }}" color="green" />
            <x-stat-card title="Total Out" value="{{ $fmt($totals['out'] ?? 0) }}" color="red" />
            <x-stat-card
                title="Net Movement"
                value="{{ $fmt($totals['net'] ?? 0) }}"
                color="{{ ($totals['net'] ?? 0) >= 0 ? 'blue' : 'red' }}" />
        </div>

        {{-- Out/In breakdown --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Out Breakdown</div>
                <div class="flex flex-wrap gap-1.5 text-[11px]">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                        <i data-lucide="shopping-bag" class="w-3 h-3"></i>
                        Sales OUT: {{ $fmt($breakdown['out_sales'] ?? 0) }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        <i data-lucide="u-turn-left" class="w-3 h-3"></i>
                        Returns to Supplier: {{ $fmt($breakdown['out_returns'] ?? 0) }}
                    </span>
                </div>
            </div>

            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">In Breakdown</div>
                <div class="flex flex-wrap gap-1.5 text-[11px]">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <i data-lucide="truck" class="w-3 h-3"></i>
                        Purchases IN: {{ $fmt($breakdown['in_purchases'] ?? 0) }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">
                        <i data-lucide="u-turn-right" class="w-3 h-3"></i>
                        Customer Return (IN): {{ $fmt($breakdown['in_sale_returns'] ?? 0) }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">
                        <i data-lucide="arrow-right-circle" class="w-3 h-3"></i>
                        Loans OUT: {{ $fmt($breakdown['out_loans'] ?? 0) }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                        <i data-lucide="arrow-left-circle" class="w-3 h-3"></i>
                        Loan Returns (IN): {{ $fmt($breakdown['in_loan_returns'] ?? 0) }}
                    </span>
                </div>
            </div>

            <div class="rounded-xl p-4 ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Records</div>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing
                    <span class="font-medium">
                        {{ method_exists($movements, 'total') ? $movements->total() : ($movements->count() ?? 0) }}
                    </span>
                    movements
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form
            method="GET"
            action="{{ route('stock.history') }}"
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4"
        >
            <div class="md:col-span-2">
                <label class="form-label text-xs">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    <option value="in"  @selected(request('type') == 'in')>In</option>
                    <option value="out" @selected(request('type') == 'out')>Out</option>
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Origin</label>
                <select name="origin" class="form-select">
                    <option value="">Any</option>
                    <option value="purchase"        @selected(request('origin')==='purchase')>Purchase (IN)</option>
                    <option value="sale"            @selected(request('origin')==='sale')>Sale (OUT)</option>
                    <option value="purchase_return" @selected(request('origin')==='purchase_return')>Return to Supplier (OUT)</option>
                    <option value="sale_return"     @selected(request('origin')==='sale_return')>Customer Return (IN)</option>
                    <option value="item_loan"       @selected(request('origin')==='item_loan')>Item Loan (OUT)</option>
                    <option value="item_loan_return"@selected(request('origin')==='item_loan_return')>Loan Return (IN)</option>
                </select>
            </div>

            <div>
                <label class="form-label text-xs">From</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input">
            </div>

            <div>
                <label class="form-label text-xs">To</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input">
            </div>

            @can('stock.delete')
            <div class="md:col-span-6">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="show_deleted" value="1" @checked(request('show_deleted')) class="rounded border-gray-300 dark:border-gray-600">
                    <span>Show deleted movements</span>
                </label>
            </div>
            @endcan

            <div class="md:col-span-6 flex justify-end mt-1">
                <button type="submit" class="btn btn-secondary flex items-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Filter</span>
                </button>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[1250px] w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Product</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Unit Cost</th>
                            <th class="px-4 py-3 text-right">Total Cost</th>
                            <th class="px-4 py-3 text-left">Reference / Note</th>
                            <th class="px-4 py-3 text-left">Recorded By</th>
                            <th class="px-4 py-3 text-left">Source</th>
                            @can('stock.delete')
                            <th class="px-4 py-3 text-center">Actions</th>
                            @endcan
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($movements as $m)
                            @php
                                $isIn  = ($m->type ?? '') === 'in';
                                $isOut = ($m->type ?? '') === 'out';

                                $badge = $isIn
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300';

                                $sourceLabel = '';
                                $sourceLink  = null;
                                $sourceChip  = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                                $icon        = 'circle';

                                // Map source types to labels/icons/links
                                if (($m->source_type ?? null) === Purchase::class) {
                                    $sourceLabel = "Purchase #".($m->source_id ?? '');
                                    $sourceLink  = route('purchases.show', $m->source_id);
                                    $sourceChip  = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
                                    $icon        = 'truck';
                                } elseif (($m->source_type ?? null) === Sale::class) {
                                    $sourceLabel = "Sale #".($m->source_id ?? '');
                                    $sourceLink  = route('sales.show', $m->source_id);
                                    $sourceChip  = 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
                                    $icon        = 'shopping-bag';
                                } elseif (($m->source_type ?? null) === PurchaseReturn::class) {
                                    $purchaseId  = data_get($m->source, 'purchase_id');
                                    $sourceLabel = "Return to Supplier #".($m->source_id ?? '');
                                    $sourceLink  = $purchaseId ? route('purchases.show', $purchaseId) . '#returns' : null;
                                    $sourceChip  = 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
                                    $icon        = 'u-turn-left';
                                } elseif (($m->source_type ?? null) === SaleReturn::class) {
                                    $saleId      = data_get($m->source, 'sale_id');
                                    $sourceLabel = "Customer Return #".($m->source_id ?? '');
                                    $sourceLink  = $saleId ? route('sales.show', $saleId) . '#returns' : null;
                                    $sourceChip  = 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300';
                                    $icon        = 'u-turn-right';
                                } elseif (($m->source_type ?? null) === \App\Models\ItemLoan::class) {
                                    $sourceLabel = "Item Loan #".($m->source_id ?? '');
                                    $sourceLink  = route('item-loans.show', $m->source_id);
                                    $sourceChip  = 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300';
                                    $icon        = 'arrow-right-circle';
                                } elseif (($m->source_type ?? null) === \App\Models\ItemLoanReturn::class) {
                                    $loanId      = data_get($m->source, 'item_loan_id');
                                    $sourceLabel = "Loan Return #".($m->source_id ?? '');
                                    $sourceLink  = $loanId ? route('item-loans.show', $loanId) : null;
                                    $sourceChip  = 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
                                    $icon        = 'arrow-left-circle';
                                }

                                // Reference / Note (movement first, then source fallbacks)
                                $reference = $m->reference
                                    ?? $m->note
                                    ?? data_get($m->source, 'reference')
                                    ?? data_get($m->source, 'note')
                                    ?? data_get($m->source, 'remarks')
                                    ?? data_get($m->source, 'return_reason')
                                    ?? null;

                                // Guard product/user for non-eager loads
                                $productName = data_get($m, 'product.name', '—');
                                $userName    = data_get($m, 'user.name', 'System');
                                $createdAt   = $m->created_at ?? null;
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all {{ $m->trashed() ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $createdAt ? $createdAt->format('d M Y, H:i') : '—' }}
                                    @if($m->trashed())
                                        <span class="block text-xs text-red-600 dark:text-red-400 font-semibold">DELETED</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                    {{ $productName }}
                                </td>

                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                                        {{ strtoupper($m->type ?? '—') }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-semibold">
                                    {{ $fmt($m->quantity ?? 0) }}
                                </td>

                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                    {{ isset($m->unit_cost) ? $fmt($m->unit_cost) : '—' }}
                                </td>

                                <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100 font-semibold">
                                    {{ isset($m->total_cost) ? $fmt($m->total_cost) : '—' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    @if($reference)
                                        <span class="block max-w-[22rem] truncate" title="{{ $reference }}">
                                            {{ $reference }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $userName }}
                                </td>

                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-semibold {{ $sourceChip }}">
                                        <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                                        @if($sourceLink)
                                            <a href="{{ $sourceLink }}" class="hover:underline">{{ $sourceLabel }}</a>
                                        @else
                                            {{ $sourceLabel ?: 'N/A' }}
                                        @endif
                                    </span>
                                </td>

                                @can('stock.delete')
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        @if($m->trashed())
                                            {{-- Restore button --}}
                                            <form action="{{ route('stock.history.restore', $m->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Restore">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                            {{-- Permanent delete button --}}
                                            <form action="{{ route('stock.history.forceDestroy', $m->id) }}" method="POST" class="inline" id="force-delete-form-{{ $m->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    @click="$dispatch('open-delete-modal', {
                                                        formId: 'force-delete-form-{{ $m->id }}',
                                                        title: 'Permanently Delete Movement?',
                                                        message: 'This action cannot be undone. The record will be gone forever.',
                                                        confirmText: 'Delete Forever',
                                                        confirmColor: 'btn-danger'
                                                    })"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Delete Permanently">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            {{-- Soft delete button --}}
                                            <form action="{{ route('stock.history.destroy', $m->id) }}" method="POST" class="inline" id="delete-form-{{ $m->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    @click="$dispatch('open-delete-modal', {
                                                        formId: 'delete-form-{{ $m->id }}',
                                                        title: 'Delete Stock Movement?',
                                                        message: 'This will move the record to the trash. You can restore it later if needed.',
                                                        confirmText: 'Delete',
                                                        confirmColor: 'btn-danger'
                                                    })"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Delete">
                                                    <i data-lucide="trash" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('stock.delete') ? '10' : '9' }}" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No movements found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                {{ method_exists($movements, 'withQueryString') ? $movements->withQueryString()->links() : '' }}
            </div>
        </div>

    @endcannot
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
