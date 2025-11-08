@extends('layouts.app')
@section('title', 'New Expense')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Record Expense</span>
        </h1>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
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
    <form action="{{ route('expenses.store') }}" method="POST" class="space-y-5 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
            </div>

            {{-- Amount --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount (RWF)</label>
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select name="category_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                    <option value="">Select…</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Supplier (optional) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier (optional)</label>
                <select name="supplier_id" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">None</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Method --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Method</label>
                <select name="method" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                    @foreach($methods as $m)
                        <option value="{{ $m }}" @selected(old('method')==$m)>{{ strtoupper($m) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Reference --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference (optional)</label>
                <input type="text" name="reference" value="{{ old('reference') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="Receipt / Txn ID">
            </div>
        </div>

        {{-- Note --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note</label>
            <textarea name="note" rows="3"
                      class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                      placeholder="Details…">{{ old('note') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('expenses.index') }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i> Save Expense
            </button>
        </div>
    </form>
</div>
@endsection
