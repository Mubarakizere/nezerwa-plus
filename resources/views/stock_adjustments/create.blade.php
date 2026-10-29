@extends('layouts.app')
@section('title', 'Adjust Stock: ' . $product->name)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Header --}}
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('products.index') }}" class="hover:text-indigo-600 transition-colors">Products</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <a href="{{ route('products.show', $product) }}" class="hover:text-indigo-600 transition-colors">{{ $product->name }}</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-slate-700 dark:text-slate-200 font-medium">Stock Adjustment</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2.5">
                <i data-lucide="sliders" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Adjust Physical Stock</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('products.show', $product) }}" class="btn btn-outline text-xs flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    @cannot('products.edit')
        {{-- Permission error state --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        Permission Restricted
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        You do not possess the required permission to perform stock adjustments. Please contact your administrator.
                    </p>
                </div>
            </div>
        </div>
    @else
        {{-- Adjustment Form Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            {{-- Security Banner --}}
            <div class="px-6 py-4 bg-slate-50/80 dark:bg-slate-800/40 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        Admin Protected Operation
                    </span>
                </div>
                <span class="inline-flex items-center gap-1 text-[11px] font-mono text-slate-400 dark:text-slate-500">
                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    Requires Authorization
                </span>
            </div>

            <form action="{{ route('products.adjust.store', $product) }}" method="POST" class="p-6 space-y-6"
                  x-data="{
                      current: {{ (float) $product->currentStock() }},
                      actual: {{ old('actual_stock', (float) $product->currentStock()) }},
                      get diff() {
                          const c = parseFloat(this.current) || 0;
                          const a = parseFloat(this.actual) || 0;
                          return a - c;
                      }
                  }">
                @csrf

                {{-- Product Overview Card --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="package" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">
                                {{ $product->name }}
                            </div>
                            <div class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                SKU: {{ $product->sku ?? ('#' . $product->id) }} &bull; Category: {{ $product->category->name ?? 'Uncategorized' }}
                            </div>
                        </div>
                    </div>

                    <div class="text-right sm:border-l sm:border-slate-200/80 sm:dark:border-slate-700/80 sm:pl-6">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Current System Stock</span>
                        <div class="text-lg font-bold font-mono text-slate-900 dark:text-slate-100">
                            {{ number_format((float)$product->currentStock(), 0) }} <span class="text-xs font-normal text-slate-400">units</span>
                        </div>
                    </div>
                </div>

                {{-- Quantity Inputs --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Current Stock (Readonly) --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Current Recorded Stock
                        </label>
                        <div class="relative">
                            <input type="text"
                                   value="{{ number_format((float)$product->currentStock(), 0) }} units"
                                   readonly
                                   class="form-input w-full bg-slate-100 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-mono font-bold cursor-not-allowed border-slate-200 dark:border-slate-700">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Actual Physical Stock --}}
                    <div>
                        <label for="actual_stock" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Actual Physical Count <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="actual_stock"
                                   type="number"
                                   step="1"
                                   min="0"
                                   name="actual_stock"
                                   x-model="actual"
                                   required
                                   autofocus
                                   placeholder="e.g. 150"
                                   class="form-input w-full font-mono font-bold text-slate-900 dark:text-slate-100 border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">
                                Units
                            </div>
                        </div>
                        @error('actual_stock')
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Live Difference Calculation Badge --}}
                <div class="p-3.5 rounded-xl border transition-colors flex items-center justify-between"
                     :class="{
                         'bg-emerald-500/10 border-emerald-500/20 text-emerald-700 dark:text-emerald-400': diff > 0,
                         'bg-rose-500/10 border-rose-500/20 text-rose-700 dark:text-rose-400': diff < 0,
                         'bg-slate-100/70 border-slate-200 dark:bg-slate-800/50 dark:border-slate-700 text-slate-500': diff === 0
                     }">
                    <div class="flex items-center gap-2">
                        <template x-if="diff > 0">
                            <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
                        </template>
                        <template x-if="diff < 0">
                            <i data-lucide="trending-down" class="w-4 h-4 text-rose-600"></i>
                        </template>
                        <template x-if="diff === 0">
                            <i data-lucide="minus" class="w-4 h-4 text-slate-400"></i>
                        </template>
                        <span class="text-xs font-semibold">
                            <span x-text="diff > 0 ? 'Stock Surplus (IN movement created)' : (diff < 0 ? 'Stock Deficit (OUT movement created)' : 'No stock change detected')"></span>
                        </span>
                    </div>

                    <div class="font-mono text-xs font-bold">
                        <span x-text="diff > 0 ? '+' + diff : diff"></span> units
                    </div>
                </div>

                {{-- Reason / Audit Notes --}}
                <div>
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Adjustment Reason & Audit Notes
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="2"
                              class="form-input w-full text-xs text-slate-900 dark:text-slate-100"
                              placeholder="e.g. Month-end physical audit recount, damaged goods written off...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Admin Password Security Box --}}
                <div class="p-4 rounded-xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/80 dark:border-amber-800/40 space-y-3">
                    <div class="flex items-start gap-2.5">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <h4 class="text-xs font-bold text-amber-900 dark:text-amber-200 uppercase tracking-wider">
                                Required Security Authorization
                            </h4>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                                Physical stock adjustment changes company asset valuation. Please enter an authorized <strong>Admin</strong> or <strong>Boss</strong> password to verify this action.
                            </p>
                        </div>
                    </div>

                    <div class="pt-1" x-data="{ show: false }">
                        <label for="admin_password" class="block text-xs font-semibold text-amber-900 dark:text-amber-200 mb-1">
                            Admin Authorization Password <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative max-w-md">
                            <input id="admin_password"
                                   :type="show ? 'text' : 'password'"
                                   name="admin_password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="Enter admin password to confirm"
                                   class="form-input w-full pr-10 text-xs border-amber-300 dark:border-amber-800/80 focus:border-amber-500 focus:ring-amber-500/20">
                            <button type="button"
                                    @click="show = !show"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-amber-600 hover:text-amber-800 transition-colors">
                                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                            </button>
                        </div>
                        @error('admin_password')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200/80 dark:border-slate-800">
                    <a href="{{ route('products.show', $product) }}" class="btn btn-outline text-xs">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary text-xs flex items-center gap-1.5">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Apply & Authorize Adjustment</span>
                    </button>
                </div>
            </form>
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
