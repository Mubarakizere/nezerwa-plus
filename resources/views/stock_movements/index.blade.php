@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
@php
    use App\Models\Purchase;
    use App\Models\Sale;
    use App\Models\PurchaseReturn;
    use App\Models\SaleReturn;
    use App\Models\ItemLoan;
    use App\Models\ItemLoanReturn;

    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);

    $totalMovements = method_exists($movements, 'total') ? $movements->total() : $movements->count();
    $activeProduct  = request('product_id') ? $products->firstWhere('id', request('product_id')) : null;
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="history" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>
                        @if($activeProduct)
                            {{ $activeProduct->name }} Stock Audit
                        @else
                            Stock Movements Audit
                        @endif
                    </span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($totalMovements) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                @if($activeProduct)
                    Complete timeline and running inventory balance for this product.
                @else
                    Trace stock inflows, purchase receipts, sales dispatches, returns, and loans.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($activeProduct)
                <a href="{{ route('stock.history') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>All Products</span>
                </a>
                <a href="{{ route('products.show', $activeProduct) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="package" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Product Card</span>
                </a>
            @else
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="boxes" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Products Catalog</span>
                </a>
            @endif

            @can('stock.view')
                <a href="{{ route('stock.history.export.csv', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    <span>Export CSV</span>
                </a>

                <a href="{{ route('stock.history.export.pdf', request()->query()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Export PDF</span>
                </a>
            @endcan
        </div>
    </div>

    @cannot('stock.view')
        {{-- No permission state --}}
        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                        You don’t have permission to view stock history.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator to request access.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- KPI Summary Cards (Clean Dashboard Style) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Total Inflow --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Inflow</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ $fmt0($totals['in'] ?? 0) }}
                        <span class="text-xs font-normal text-slate-400 font-sans">units</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Purchases: <strong class="text-slate-700 dark:text-slate-200">{{ $fmt0($breakdown['in_purchases'] ?? 0) }}</strong></span>
                    <span>Returns: <strong class="text-sky-600 dark:text-sky-400">{{ $fmt0($breakdown['in_sale_returns'] ?? 0) }}</strong></span>
                </div>
            </div>

            {{-- 2. Total Outflow --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Outflow</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                        {{ $fmt0($totals['out'] ?? 0) }}
                        <span class="text-xs font-normal text-slate-400 font-sans">units</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Sales: <strong class="text-slate-700 dark:text-slate-200">{{ $fmt0($breakdown['out_sales'] ?? 0) }}</strong></span>
                    <span>Supplier Ret: <strong class="text-amber-600 dark:text-amber-400">{{ $fmt0($breakdown['out_returns'] ?? 0) }}</strong></span>
                </div>
            </div>

            {{-- 3. Net Movement --}}
            @php
                $net = (float)($totals['net'] ?? 0);
            @endphp
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Movement</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight font-mono {{ $net >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $net >= 0 ? '+' : '' }}{{ $fmt0($net) }}
                        <span class="text-xs font-normal text-slate-400 font-sans">units</span>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Inflow vs Outflow</span>
                    <span class="font-medium {{ $net >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $net >= 0 ? 'Net Expansion' : 'Net Depletion' }}
                    </span>
                </div>
            </div>

            {{-- 4. Activity / Running Stock --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        @if($activeProduct)
                            Current Stock
                        @else
                            Loans & Activity
                        @endif
                    </span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $activeProduct ? 'boxes' : 'git-pull-request' }}" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                        @if($activeProduct)
                            {{ $fmt0($activeProduct->stock) }}
                            <span class="text-xs font-normal text-slate-400 font-sans">in inventory</span>
                        @else
                            {{ $fmt0($totalMovements) }}
                            <span class="text-xs font-normal text-slate-400 font-sans">movements</span>
                        @endif
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    @if($activeProduct)
                        <span>Live Available Stock</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">Current Balance</span>
                    @else
                        <span>Loan Out / In:</span>
                        <span class="font-medium text-amber-600 dark:text-amber-400">
                            {{ $fmt0($breakdown['out_loans'] ?? 0) }} / {{ $fmt0($breakdown['in_loan_returns'] ?? 0) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Streamlined Filter Panel --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs p-4 space-y-4">
            {{-- Quick Filter Pills --}}
            <div class="flex flex-wrap items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800/80">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Quick:</span>

                @php
                    $curType = request('type', '');
                    $curOrigin = request('origin', '');
                @endphp

                <a href="{{ request()->fullUrlWithQuery(['type' => null, 'origin' => null, 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ empty($curType) && empty($curOrigin) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>All Records</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['type' => 'in', 'origin' => null, 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curType === 'in' && empty($curOrigin) ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Inflow (IN)</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['type' => 'out', 'origin' => null, 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curType === 'out' && empty($curOrigin) ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>Outflow (OUT)</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['origin' => 'sale', 'type' => 'out', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curOrigin === 'sale' ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <i data-lucide="shopping-bag" class="w-3 h-3 text-rose-500"></i>
                    <span>Sales (OUT)</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['origin' => 'purchase', 'type' => 'in', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-all {{ $curOrigin === 'purchase' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <i data-lucide="truck" class="w-3 h-3 text-emerald-500"></i>
                    <span>Purchases (IN)</span>
                </a>
            </div>

            <form
                method="GET"
                action="{{ route('stock.history') }}"
                class="space-y-3"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">
                    {{-- Product Select --}}
                    <div class="md:col-span-4">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Product</label>
                        <select name="product_id"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Type Select --}}
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Type</label>
                        <select name="type"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">All Types</option>
                            <option value="in"  @selected(request('type') == 'in')>IN (Inflow)</option>
                            <option value="out" @selected(request('type') == 'out')>OUT (Outflow)</option>
                        </select>
                    </div>

                    {{-- Origin Select --}}
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Origin</label>
                        <select name="origin"
                                class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            <option value="">Any Origin</option>
                            <option value="purchase"        @selected(request('origin')==='purchase')>Purchase (IN)</option>
                            <option value="sale"            @selected(request('origin')==='sale')>Sale (OUT)</option>
                            <option value="purchase_return" @selected(request('origin')==='purchase_return')>Supplier Return (OUT)</option>
                            <option value="sale_return"     @selected(request('origin')==='sale_return')>Customer Return (IN)</option>
                            <option value="item_loan"       @selected(request('origin')==='item_loan')>Item Loan (OUT)</option>
                            <option value="item_loan_return"@selected(request('origin')==='item_loan_return')>Loan Return (IN)</option>
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">From Date</label>
                        <input type="date"
                               name="from_date"
                               value="{{ request('from_date') }}"
                               class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>

                    {{-- Date To --}}
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">To Date</label>
                        <input type="date"
                               name="to_date"
                               value="{{ request('to_date') }}"
                               class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>
                </div>

                {{-- Bottom Row: Show deleted + Action Buttons --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                    <div>
                        @can('stock.delete')
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-600 dark:text-slate-300 select-none">
                                <input type="checkbox"
                                       name="show_deleted"
                                       value="1"
                                       @checked(request('show_deleted'))
                                       class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500/20">
                                <span>Show deleted movement records</span>
                            </label>
                        @endcan
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Apply Filter</span>
                        </button>
                        <a href="{{ route('stock.history') }}"
                           title="Reset all filters"
                           class="inline-flex items-center justify-center p-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Movements Table Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Date & Time</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3 text-center">Type</th>
                            <th class="px-4 py-3 text-right">Quantity</th>
                            <th class="px-4 py-3 text-right">Unit Cost</th>
                            <th class="px-4 py-3 text-right">Total Cost</th>
                            <th class="px-4 py-3">Source & Reference</th>
                            <th class="px-4 py-3">Recorded By</th>
                            @if(isset($runningBalance))
                                <th class="px-4 py-3 text-right">Running Balance</th>
                            @endif
                            @can('stock.delete')
                                <th class="px-4 py-3 text-right">Actions</th>
                            @endcan
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                        @forelse($movements as $m)
                            @php
                                $isIn  = ($m->type ?? '') === 'in';
                                $isOut = ($m->type ?? '') === 'out';

                                $sourceLabel = '';
                                $sourceLink  = null;
                                $sourceBadge = 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                                $icon        = 'circle';

                                // Map source types
                                if (($m->source_type ?? null) === Purchase::class) {
                                    $sourceLabel = "Purchase #".($m->source_id ?? '');
                                    $sourceLink  = route('purchases.show', $m->source_id);
                                    $sourceBadge = 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
                                    $icon        = 'truck';
                                } elseif (($m->source_type ?? null) === Sale::class) {
                                    $sourceLabel = "Sale #".($m->source_id ?? '');
                                    $sourceLink  = route('sales.show', $m->source_id);
                                    $sourceBadge = 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20';
                                    $icon        = 'shopping-bag';
                                } elseif (($m->source_type ?? null) === PurchaseReturn::class) {
                                    $purchaseId  = data_get($m->source, 'purchase_id');
                                    $sourceLabel = "Supplier Return #".($m->source_id ?? '');
                                    $sourceLink  = $purchaseId ? route('purchases.show', $purchaseId) . '#returns' : null;
                                    $sourceBadge = 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20';
                                    $icon        = 'u-turn-left';
                                } elseif (($m->source_type ?? null) === SaleReturn::class) {
                                    $saleId      = data_get($m->source, 'sale_id');
                                    $sourceLabel = "Customer Return #".($m->source_id ?? '');
                                    $sourceLink  = $saleId ? route('sales.show', $saleId) . '#returns' : null;
                                    $sourceBadge = 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border border-sky-500/20';
                                    $icon        = 'u-turn-right';
                                } elseif (($m->source_type ?? null) === ItemLoan::class) {
                                    $sourceLabel = "Item Loan #".($m->source_id ?? '');
                                    $sourceLink  = route('item-loans.show', $m->source_id);
                                    $sourceBadge = 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20';
                                    $icon        = 'arrow-right-circle';
                                } elseif (($m->source_type ?? null) === ItemLoanReturn::class) {
                                    $loanId      = data_get($m->source, 'item_loan_id');
                                    $sourceLabel = "Loan Return #".($m->source_id ?? '');
                                    $sourceLink  = $loanId ? route('item-loans.show', $loanId) : null;
                                    $sourceBadge = 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20';
                                    $icon        = 'arrow-left-circle';
                                }

                                // Reference / Note fallback
                                $reference = $m->reference
                                    ?? $m->note
                                    ?? data_get($m->source, 'reference')
                                    ?? data_get($m->source, 'note')
                                    ?? data_get($m->source, 'remarks')
                                    ?? data_get($m->source, 'return_reason')
                                    ?? null;

                                $productName = data_get($m, 'product.name', '—');
                                $userName    = data_get($m, 'user.name', 'System');
                                $createdAt   = $m->created_at ?? null;
                            @endphp

                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors {{ $m->trashed() ? 'bg-rose-50/40 dark:bg-rose-950/20' : '' }}">
                                {{-- 1. Date & Time --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ $createdAt ? $createdAt->format('M j, Y') : '—' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-mono">
                                        {{ $createdAt ? $createdAt->format('H:i') : '' }}
                                    </div>
                                    @if($m->trashed())
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                            DELETED
                                        </span>
                                    @endif
                                </td>

                                {{-- 2. Product --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            @if($m->product)
                                                <a href="{{ route('stock.history', ['product_id' => $m->product_id]) }}"
                                                   title="Filter by this product"
                                                   class="font-semibold text-slate-900 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors line-clamp-1">
                                                    {{ $productName }}
                                                </a>
                                            @else
                                                <span class="font-semibold text-slate-900 dark:text-slate-100 line-clamp-1">
                                                    {{ $productName }}
                                                </span>
                                            @endif

                                            @if(!empty($m->product?->sku))
                                                <span class="text-[10px] font-mono text-slate-400">SKU: {{ $m->product->sku }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 3. Type --}}
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($isIn)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            IN
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            OUT
                                        </span>
                                    @endif
                                </td>

                                {{-- 4. Quantity --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="font-bold font-mono text-xs {{ $isIn ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200' }}">
                                        {{ $isIn ? '+' : '-' }}{{ $fmt0($m->quantity ?? 0) }}
                                    </span>
                                </td>

                                {{-- 5. Unit Cost --}}
                                <td class="px-4 py-3 text-right font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    @if(isset($m->unit_cost) && $m->unit_cost > 0)
                                        {{ $fmt2($m->unit_cost) }}
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- 6. Total Cost --}}
                                <td class="px-4 py-3 text-right font-mono font-medium text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                    @if(isset($m->total_cost) && $m->total_cost > 0)
                                        {{ $fmt2($m->total_cost) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- 7. Source & Reference --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        @if($sourceLabel)
                                            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium w-fit {{ $sourceBadge }}">
                                                <i data-lucide="{{ $icon }}" class="w-3 h-3 flex-shrink-0"></i>
                                                @if($sourceLink)
                                                    <a href="{{ $sourceLink }}" class="hover:underline font-semibold">{{ $sourceLabel }}</a>
                                                @else
                                                    <span class="font-semibold">{{ $sourceLabel }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if($reference)
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1" title="{{ $reference }}">
                                                {{ $reference }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- 8. Recorded By --}}
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $userName }}</span>
                                    </div>
                                </td>

                                {{-- 9. Running Balance (if active) --}}
                                @if(isset($runningBalance))
                                    @php
                                        $currentRowBalance = $runningBalance;
                                        $change = $isIn ? $m->quantity : -($m->quantity);
                                        $runningBalance = $runningBalance - $change;
                                    @endphp
                                    <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                            {{ $fmt0($currentRowBalance) }} units
                                        </span>
                                    </td>
                                @endif

                                {{-- 10. Actions --}}
                                @can('stock.delete')
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            @if($m->trashed())
                                                {{-- Restore --}}
                                                <form action="{{ route('stock.history.restore', $m->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            title="Restore Movement"
                                                            class="p-1.5 text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg transition-colors">
                                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                    </button>
                                                </form>

                                                {{-- Force Delete --}}
                                                <form action="{{ route('stock.history.forceDestroy', $m->id) }}" method="POST" class="inline" id="force-delete-form-{{ $m->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            title="Permanently Delete"
                                                            @click="$dispatch('open-delete-modal', {
                                                                formId: 'force-delete-form-{{ $m->id }}',
                                                                title: 'Permanently Delete Movement?',
                                                                message: 'This action cannot be undone. The inventory record will be permanently deleted.',
                                                                confirmText: 'Delete Permanently',
                                                                confirmColor: 'btn-danger'
                                                            })"
                                                            class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Soft Delete --}}
                                                <form action="{{ route('stock.history.destroy', $m->id) }}" method="POST" class="inline" id="delete-form-{{ $m->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            title="Delete Movement"
                                                            @click="$dispatch('open-delete-modal', {
                                                                formId: 'delete-form-{{ $m->id }}',
                                                                title: 'Delete Stock Movement?',
                                                                message: 'This will move the stock movement record to trash. You can restore it later if needed.',
                                                                confirmText: 'Delete',
                                                                confirmColor: 'btn-danger'
                                                            })"
                                                            class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('stock.delete') ? (isset($runningBalance) ? '10' : '9') : (isset($runningBalance) ? '9' : '8') }}" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                            <i data-lucide="history" class="w-6 h-6"></i>
                                        </div>
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">No stock movements found</h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            No movements match your selected filter criteria. Try selecting another product, widening the date range, or resetting filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            @if(method_exists($movements, 'links') && $movements->hasPages())
                <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Showing <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $movements->firstItem() ?? 0 }}</span>
                        to <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $movements->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fmt0($movements->total()) }}</span> records
                    </div>
                    <div>
                        {{ $movements->withQueryString()->links() }}
                    </div>
                </div>
            @endif
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
