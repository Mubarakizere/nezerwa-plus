@extends('layouts.app')
@section('title', 'Edit Transaction')

@section('content')
@php
    use Illuminate\Support\Carbon;

    $dateVal   = $transaction->transaction_date
        ? Carbon::parse($transaction->transaction_date)->format('Y-m-d')
        : now()->format('Y-m-d');

    $isLinked  = (bool) ($transaction->sale_id || $transaction->purchase_id);
    $methodVal = strtolower((string) ($transaction->method ?? ''));

    // We assume $channels is passed from controller
    $knownSlugs = $channels->pluck('slug')->toArray();
    $isKnown    = in_array($methodVal, $knownSlugs, true);
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="edit-3" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Edit Transaction #{{ $transaction->id }}
        </h1>

        <div class="flex gap-2">
            @can('transactions.view')
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
            @endcan

            {{-- Delete (guarded by permission + optional linked rule) --}}
            @can('transactions.delete')
                <form action="{{ route('transactions.destroy', $transaction->id) }}" method="POST"
                      onsubmit="return confirm('Delete this transaction? This cannot be undone.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Linked badges --}}
    @if($transaction->sale_id || $transaction->purchase_id)
        <div class="mb-4 flex flex-wrap gap-2 text-sm">
            @if($transaction->sale_id)
                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 px-2.5 py-1 dark:bg-green-900/40 dark:text-green-300">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i> Linked to Sale #{{ $transaction->sale_id }}
                </span>
            @endif
            @if($transaction->purchase_id)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 px-2.5 py-1 dark:bg-amber-900/40 dark:text-amber-300">
                    <i data-lucide="package" class="w-4 h-4"></i> Linked to Purchase #{{ $transaction->purchase_id }}
                </span>
            @endif
        </div>
    @endif

    {{-- Card --}}
    <div
        x-data="{
            type: @js(old('type', $transaction->type)),
            methodSel: @js(old('method', $isKnown ? $methodVal : ($methodVal ?: 'cash'))),
            otherMethod: @js(old('other_method', $isKnown ? '' : $methodVal)),
            lock: @js($isLinked),
            onTypeChange(){
                // When switching type, clear the opposite party select for safety
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

        @can('transactions.edit')
            <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Type --}}
                    <div>
                        <label class="form-label">Type <span class="text-red-500">*</span></label>
                        <select name="type" class="form-select" x-model="type" @change="onTypeChange()" :disabled="lock" required>
                            <option value="credit">Credit (Money In)</option>
                            <option value="debit">Debit (Money Out)</option>
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <template x-if="lock">
                            <p class="text-xs text-amber-600 mt-1">Linked record detected: Type is locked.</p>
                        </template>
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="form-label">Transaction Date <span class="text-red-500">*</span></label>
                        <input type="date" name="transaction_date" value="{{ old('transaction_date', $dateVal) }}"
                               class="form-input" required>
                        @error('transaction_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label class="form-label">Amount <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount"
                               value="{{ old('amount', $transaction->amount) }}"
                               class="form-input" :disabled="lock" required>
                        @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <template x-if="lock">
                            <p class="text-xs text-amber-600 mt-1">Linked record detected: Amount is locked.</p>
                        </template>
                    </div>

                    {{-- Method --}}
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
                            <input type="text" name="other_method" class="form-input"
                                   placeholder="Type the custom method (e.g., Cheque #123, POS Ref ...)"
                                   x-model="otherMethod">
                            <p class="text-xs text-gray-500 mt-1">Saved into <em>method</em> field.</p>
                        </div>
                    </div>

                    {{-- Customer (for credit) --}}
                    <div x-show="type === 'credit'">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" :disabled="lock && {{ $transaction->customer_id ? 'false' : 'true' }}">
                            <option value="">None</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    @selected(old('customer_id', $transaction->customer_id) == $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Supplier (for debit) --}}
                    <div x-show="type === 'debit'">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select" :disabled="lock && {{ $transaction->supplier_id ? 'false' : 'true' }}">
                            <option value="">None</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    @selected(old('supplier_id', $transaction->supplier_id) == $supplier->id)>
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
                    <textarea name="notes" rows="3" class="form-textarea">{{ old('notes', $transaction->notes) }}</textarea>
                    @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Hidden helper: if methodSel == other, send otherMethod as method (handled in controller) --}}
                <input type="hidden" name="__js_patch_method"
                       x-model="(methodSel==='other' && otherMethod) ? otherMethod : ''">

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary flex items-center gap-1"
                            @click.prevent="
                                if (methodSel === 'other') {
                                    let m = document.querySelector('input[name=method]');
                                    if (!m) {
                                        m = document.createElement('input');
                                        m.type = 'hidden'; m.name = 'method';
                                        document.currentScript.closest('form')?.appendChild(m);
                                    }
                                    m.value = otherMethod || '';
                                }
                                $el.closest('form').submit();
                            ">
                        <i data-lucide="save" class="w-4 h-4"></i> Update Transaction
                    </button>
                </div>
            </form>
        @else
            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3 text-sm text-amber-800 dark:text-amber-100">
                You do not have permission to edit this transaction. Please contact your administrator if you think this is a mistake.
            </div>
        @endcan

        {{-- Audit --}}
        <div class="mt-6 text-xs text-gray-500 dark:text-gray-400">
            <div>Created: {{ optional($transaction->created_at)->format('d M Y H:i') ?? '—' }}</div>
            <div>Updated: {{ optional($transaction->updated_at)->format('d M Y H:i') ?? '—' }}</div>
        </div>
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
@endsection
