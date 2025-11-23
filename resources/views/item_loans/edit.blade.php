@extends('layouts.app')
@section('title', "Edit Loan #{$loan->id}")

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="file-edit" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Loan #{{ $loan->id }}</span>
        </h1>
        <a href="{{ route('item-loans.show', $loan) }}" class="btn btn-outline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($hasReturns)
        <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-200">
            Quantity is locked because returns exist for this loan.
        </div>
    @endif

    <form method="POST" action="{{ route('item-loans.update', $loan) }}" class="space-y-5 rounded-xl border dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Partner Company <span class="text-red-600">*</span></label>
            <select name="partner_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($partners as $p)
                    <option value="{{ $p->id }}" @selected(old('partner_id', $loan->partner_id)==$p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Direction <span class="text-red-600">*</span></label>
                <select name="direction" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="given" @selected(old('direction',$loan->direction)==='given')>We LENT to them (given)</option>
                    <option value="taken" @selected(old('direction',$loan->direction)==='taken')>We BORROWED from them (taken)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Unit <span class="text-xs text-gray-500">(optional)</span></label>
                <input type="text" name="unit" value="{{ old('unit', $loan->unit) }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="20">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Linked Product <span class="text-xs text-gray-500">(Inventory Tracking)</span></label>
                <select name="product_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">-- No Link (Manual Item) --</option>
                    @foreach ($products as $prod)
                        <option value="{{ $prod->id }}" @selected(old('product_id', $loan->product_id) == $prod->id)>
                            {{ $prod->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Item Name <span class="text-red-600">*</span></label>
                <input type="text" name="item_name" value="{{ old('item_name', $loan->item_name) }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="255">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
            <div>
                <label class="block text-sm font-medium mb-1">Quantity <span class="text-red-600">*</span></label>
                <input type="number" step="0.01" min="0.01" name="quantity"
                       value="{{ old('quantity', $loan->quantity) }}" {{ $hasReturns ? 'readonly' : '' }}
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 {{ $hasReturns ? 'opacity-60 cursor-not-allowed' : '' }}">
                @if($hasReturns)
                    <p class="text-xs text-gray-500 mt-1">Locked. Already returned: {{ number_format($loan->quantity_returned,2) }} {{ $loan->unit }}</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Loan Date <span class="text-red-600">*</span></label>
                <input type="date" name="loan_date" value="{{ old('loan_date', \Carbon\Carbon::parse($loan->loan_date)->format('Y-m-d')) }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Due Date <span class="text-xs text-gray-500">(optional)</span></label>
                <input type="date" name="due_date" value="{{ old('due_date', optional($loan->due_date)->format('Y-m-d')) }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Notes</label>
            <textarea name="notes" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('notes', $loan->notes) }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('item-loans.show', $loan) }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection
