@extends('layouts.app')
@section('title', 'New Inter-Company Loan')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Record New Loan</span>
        </h1>
        <a href="{{ route('item-loans.index') }}" class="btn btn-outline">
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

    <form method="POST" action="{{ route('item-loans.store') }}" class="space-y-5 rounded-xl border dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Partner Company <span class="text-red-600">*</span></label>
            <select name="partner_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                <option value="">Select partner...</option>
                @foreach ($partners as $p)
                    <option value="{{ $p->id }}" @selected(old('partner_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Direction <span class="text-red-600">*</span></label>
                <select name="direction" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="given" @selected(old('direction')==='given')>We LENT to them (given)</option>
                    <option value="taken" @selected(old('direction')==='taken')>We BORROWED from them (taken)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Unit <span class="text-xs text-gray-500">(optional)</span></label>
                <input type="text" name="unit" value="{{ old('unit') }}" placeholder="pcs, bottles, boxes"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="20">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Item Name <span class="text-red-600">*</span></label>
            <input type="text" name="item_name" value="{{ old('item_name') }}" required
                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="255" placeholder="e.g., Plastic Chair">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Quantity <span class="text-red-600">*</span></label>
                <input type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity') }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Loan Date <span class="text-red-600">*</span></label>
                <input type="date" name="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Due Date <span class="text-xs text-gray-500">(optional)</span></label>
                <input type="date" name="due_date" value="{{ old('due_date') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Notes</label>
            <textarea name="notes" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Any additional details...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('item-loans.index') }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
