@extends('layouts.app')
@section('title', 'Create Loan')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Create New Loan</h1>
        </div>
        <a href="{{ route('loans.index') }}" class="btn btn-outline flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg p-4 mb-6">
            <p class="font-medium">There were some problems with your input:</p>
            <ul class="list-disc pl-5 mt-2 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Card --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('loans.store') }}" class="space-y-6">
            @csrf

            {{-- Loan Type --}}
            <div>
                <label for="loan_type" class="form-label">Loan Type <span class="text-red-500">*</span></label>
                <select name="type" id="loan_type" class="form-select w-full" required>
                    <option value="">Select Type</option>
                    <option value="given" {{ old('type') === 'given' ? 'selected' : '' }}>Given (We lend / customer owes us)</option>
                    <option value="taken" {{ old('type') === 'taken' ? 'selected' : '' }}>Taken (We owe supplier)</option>
                </select>
            </div>

            {{-- Customer --}}
            <div id="customer_field" class="hidden">
                <label for="customer_id" class="form-label">Customer</label>
                <select name="customer_id" id="customer_id" class="form-select w-full">
                    <option value="">Select Customer</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Supplier --}}
            <div id="supplier_field" class="hidden">
                <label for="supplier_id" class="form-label">Supplier</label>
                <select name="supplier_id" id="supplier_id" class="form-select w-full">
                    <option value="">Select Supplier</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Amount --}}
            <div>
                <label for="amount" class="form-label">Amount <span class="text-red-500">*</span></label>
                <input id="amount" type="number" step="0.01" name="amount"
                       class="form-input w-full" value="{{ old('amount') }}" required>
            </div>

            {{-- Dates (Grid) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="loan_date" class="form-label">Loan Date <span class="text-red-500">*</span></label>
                    <input id="loan_date" type="date" name="loan_date"
                           class="form-input w-full"
                           value="{{ old('loan_date', date('Y-m-d')) }}" required>
                </div>

                <div>
                    <label for="due_date" class="form-label">Due Date</label>
                    <input id="due_date" type="date" name="due_date"
                           class="form-input w-full"
                           value="{{ old('due_date') }}">
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>

            {{-- Notes --}}
            <div>
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="form-textarea w-full"
                          placeholder="Optional...">{{ old('notes') }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('loans.index') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-success">Save Loan</button>
            </div>
        </form>
    </div>
</div>

{{-- JS Logic to toggle Customer/Supplier --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('loan_type');
    const customerField = document.getElementById('customer_field');
    const supplierField = document.getElementById('supplier_field');

    function toggleFields() {
        customerField.classList.add('hidden');
        supplierField.classList.add('hidden');
        if (typeSelect.value === 'given') customerField.classList.remove('hidden');
        if (typeSelect.value === 'taken') supplierField.classList.remove('hidden');
    }

    typeSelect.addEventListener('change', toggleFields);
    toggleFields(); // initial state
});
</script>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
