@extends('layouts.app')
@section('title', 'Add Transaction')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- 🔹 Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="banknote" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Add Transaction
        </h1>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- 🔸 Card --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">

        <form action="{{ route('transactions.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Type --}}
                <div>
                    <label class="form-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="credit" @selected(old('type') == 'credit')>Credit (Money In)</option>
                        <option value="debit" @selected(old('type') == 'debit')>Debit (Money Out)</option>
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Transaction Date --}}
                <div>
                    <label class="form-label">Transaction Date <span class="text-red-500">*</span></label>
                    <input type="date" name="transaction_date" class="form-input" value="{{ old('transaction_date') }}" required>
                    @error('transaction_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Amount --}}
                <div>
                    <label class="form-label">Amount <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="form-input" required>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Method --}}
                <div>
                    <label class="form-label">Method</label>
                    <input type="text" name="method" value="{{ old('method') }}" placeholder="Cash, Bank, MoMo..." class="form-input">
                    @error('method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Customer --}}
                <div>
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

                {{-- Supplier --}}
                <div>
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
                <button type="submit" class="btn btn-primary flex items-center gap-1">
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
@endsection
