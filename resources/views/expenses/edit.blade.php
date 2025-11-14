@extends('layouts.app')
@section('title', "Edit Expense #{$expense->id}")

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="file-edit" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Expense #{{ $expense->id }}</span>
        </h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
            <a href="{{ route('expenses.show', $expense) }}" class="btn btn-outline flex items-center gap-1">
                <i data-lucide="eye" class="w-4 h-4"></i> View
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 dark:border-rose-900/40 bg-rose-50 dark:bg-rose-950/40 p-3 text-sm text-rose-800 dark:text-rose-300">
            {{ $errors->first() }}
        </div>
    @endif
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50 dark:bg-emerald-950/40 p-3 text-sm text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('expenses.update', $expense) }}" method="POST" class="space-y-5 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Date</label>
                <input type="date" name="date" value="{{ old('date', optional($expense->date)->toDateString()) }}" class="form-input w-full" required>
            </div>

            <div>
                <label class="form-label">Amount (RWF)</label>
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $expense->amount) }}" class="form-input w-full" required>
            </div>

            <div>
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select w-full" required>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $expense->category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Supplier (optional)</label>
                <select name="supplier_id" class="form-select w-full">
                    <option value="">None</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id', $expense->supplier_id) == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Method</label>
                <select name="method" class="form-select w-full" required>
                    @foreach($methods as $m)
                        <option value="{{ $m }}" @selected(old('method', $expense->method) == $m)>{{ strtoupper($m) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Reference (optional)</label>
                <input type="text" name="reference" value="{{ old('reference', $expense->reference) }}" class="form-input w-full" placeholder="Receipt / Txn ID">
            </div>
        </div>

        <div>
            <label class="form-label">Note</label>
            <textarea name="note" rows="3" class="form-textarea w-full" placeholder="Details…">{{ old('note', $expense->note) }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('expenses.index') }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i> Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
