@extends('layouts.app')
@section('title', 'Add Transaction')

@section('content')
@php
    use Illuminate\Support\Carbon;

    $dateVal = old('transaction_date', now()->format('Y-m-d'));

    // Method select + "Other" handling
    // We assume $channels is passed from controller
    $knownSlugs = $channels->pluck('slug')->toArray();
    $oldMethod  = strtolower((string) old('method', ''));
    
    // Default to first channel if available and no old input, else 'other' or empty
    $defaultMethod = $channels->first()?->slug ?? 'other';
    
    $isKnown    = in_array($oldMethod, $knownSlugs, true);
    // If old input exists and is known, use it. If no old input, use default. If old input is unknown, use 'other'.
    $methodSel  = $oldMethod ? ($isKnown ? $oldMethod : 'other') : $defaultMethod;
    $otherValue = $isKnown ? '' : $oldMethod;
@endphp

@cannot('transactions.create')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-off" class="w-6 h-6 text-amber-600 dark:text-amber-300 mt-0.5"></i>
                <div>
                    <h1 class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                        You don’t have permission to create transactions.
                    </h1>
                    <p class="mt-1 text-sm text-amber-800/80 dark:text-amber-100/80">
                        Please contact an administrator if you believe this is a mistake.
                    </p>
                    <a href="{{ route('transactions.index') }}"
                       class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-amber-900 dark:text-amber-100 underline">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        Back to Transactions
                    </a>
                </div>
            </div>
        </div>
    </div>
@else
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="banknote" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Add Transaction
        </h1>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- Card --}}
    <div
        x-data="{
            type: @js(old('type','credit')),
            methodSel: @js($methodSel),
            otherMethod: @js($otherValue),
            onTypeChange(){
                if (this.type === 'credit') {
                    const s = document.querySelector('select[name=supplier_id]');
                    if (s) s.value = '';
                } else if (this.type === 'debit') {
                    const c = document.querySelector('select[name=customer_id]');
                    if (c) c.value = '';
                }
            }
        }"
        x-init="$nextTick(()=>{ onTypeChange(); })"
        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6"
    >
        <form action="{{ route('transactions.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Type --}}
                <div>
                    <label class="form-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" class="form-select" x-model="type" @change="onTypeChange()" required>
                        <option value="">Select Type</option>
                        <option value="credit">Credit (Money In)</option>
                        <option value="debit">Debit (Money Out)</option>
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Transaction Date --}}
                <div>
                    <label class="form-label">Transaction Date <span class="text-red-500">*</span></label>
                    <input type="date" name="transaction_date" class="form-input" value="{{ $dateVal }}" required>
                    @error('transaction_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Amount --}}
                <div>
                    <label class="form-label">Amount <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-input" required>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Method (Dynamic) --}}
                <div>
                    <label class="form-label">Method</label>
                    <select name="method" class="form-select" x-model="methodSel">
                        @foreach($channels as $ch)
                            <option value="{{ $ch->slug }}">{{ $ch->name }}</option>
                        @endforeach
                        <option value="other">Other…</option>
                    </select>
                    @error('method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                    <div class="mt-2" x-show="methodSel === 'other'">
                        <input type="text" class="form-input"
                               placeholder="Type the custom method (e.g., Cheque #123, POS Ref …)"
                               x-model="otherMethod">
                        <p class="text-xs text-gray-500 mt-1">This will be saved into <em>method</em>.</p>
                    </div>
                </div>

                {{-- Customer (for Credit) --}}
                <div x-show="type === 'credit'">
                    <label class="form-label">Customer (for Credit)</label>
                    <select name="customer_id" class="form-select">
                        <option value="">None</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Supplier (for Debit) --}}
                <div x-show="type === 'debit'">
                    <label class="form-label">Supplier (for Debit)</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">None</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

            </div>

            {{-- Notes --}}
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-textarea">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('transactions.index') }}" class="btn btn-outline">Cancel</a>
                <button type="submit"
                        class="btn btn-primary flex items-center gap-1"
                        @click.prevent="
                            const form = $el.closest('form');
                            // Ensure correct method value before submit
                            if (methodSel === 'other') {
                                let m = form.querySelector('input[name=method]');
                                if (!m) {
                                    m = document.createElement('input');
                                    m.type = 'hidden';
                                    m.name = 'method';
                                    form.appendChild(m);
                                }
                                m.value = otherMethod || '';
                            } else {
                                // Normal case: send the selected known method
                                let m = form.querySelector('input[name=method]');
                                if (m) m.remove();
                                const sel = form.querySelector('select[name=method]');
                                if (sel) sel.value = methodSel;
                            }
                            form.submit();
                        ">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Transaction
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endcannot
@endsection
