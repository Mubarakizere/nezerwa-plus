@extends('layouts.app')
@section('title', 'Add New Entry')

@section('content')
@php
    // Inline fetch to avoid controller changes
    $customers = \App\Models\Customer::select('id','name')->orderBy('name')->get();
    $suppliers = \App\Models\Supplier::select('id','name')->orderBy('name')->get();
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{
        type: @js(old('type', '')),
        customerId: @js(old('customer_id')),
        supplierId: @js(old('supplier_id')),
        onTypeChange() {
            // Soft nudge: clear opposite party if it doesn't match the type
            if (this.type === 'credit' && this.supplierId) this.supplierId = '';
            if (this.type === 'debit'  && this.customerId) this.customerId = '';
        },
        onCustomerPick() {
            if (this.customerId) this.supplierId = '';
        },
        onSupplierPick() {
            if (this.supplierId) this.customerId = '';
        }
     }"
>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            Add New Entry
        </h1>

        @can('debits-credits.view')
            <a href="{{ route('debits-credits.index') }}" class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
        @endcan
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="mb-6 p-3 bg-red-100 dark:bg-red-900/40 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        @can('debits-credits.create')
            <form action="{{ route('debits-credits.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Type --}}
                    <div>
                        <label class="form-label">Type <span class="text-red-500">*</span></label>
                        <select name="type" class="form-select" x-model="type" @change="onTypeChange()" required>
                            <option value="">Select Type</option>
                            <option value="debit">Debit (Money Out)</option>
                            <option value="credit">Credit (Money In)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1" x-show="type==='debit'">
                            Tip: Debits usually relate to a Supplier.
                        </p>
                        <p class="text-xs text-gray-500 mt-1" x-show="type==='credit'">
                            Tip: Credits usually relate to a Customer.
                        </p>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label class="form-label">Amount <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount"
                               value="{{ old('amount') }}" class="form-input" placeholder="Enter amount" required>
                        @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="form-label">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="date"
                               value="{{ old('date', now()->toDateString()) }}"
                               class="form-input" required>
                        @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Optional: Link to Transaction --}}
                    <div>
                        <label class="form-label">Link to Transaction (optional)</label>
                        <input type="number" min="1" name="transaction_id"
                               value="{{ old('transaction_id') }}"
                               class="form-input" placeholder="Transaction ID">
                        <p class="text-xs text-gray-500 mt-1">
                            If this entry corresponds to an existing Transaction, put its ID here.
                        </p>
                        @error('transaction_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Customer --}}
                    <div>
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" x-model="customerId" @change="onCustomerPick()">
                            <option value="">None</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs mt-1"
                           :class="customerId ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500'">
                           Choose a customer OR a supplier (not both).
                        </p>
                        @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Supplier --}}
                    <div>
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select" x-model="supplierId" @change="onSupplierPick()">
                            <option value="">None</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs mt-1"
                           :class="supplierId ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500'">
                           Choose a supplier OR a customer (not both).
                        </p>
                        @error('supplier_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-textarea" placeholder="Optional note...">{{ old('description') }}</textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    @can('debits-credits.view')
                        <a href="{{ route('debits-credits.index') }}" class="btn btn-outline">Cancel</a>
                    @endcan
                    <button type="submit" class="btn btn-primary flex items-center gap-1">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Entry
                    </button>
                </div>
            </form>
        @else
            <div class="text-sm text-amber-700 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                You do not have permission to create debit/credit entries. Please contact your administrator if you think this is a mistake.
            </div>
        @endcan
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
