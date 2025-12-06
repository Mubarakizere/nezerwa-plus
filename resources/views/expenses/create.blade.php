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
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- Permission gate --}}
    @cannot('expenses.create')
        <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
            <div class="flex items-start gap-2">
                <i data-lucide="shield-off" class="w-5 h-5 mt-0.5 text-amber-600 dark:text-amber-300"></i>
                <div class="text-sm text-amber-800 dark:text-amber-100">
                    <p class="font-semibold">You don’t have permission to record expenses.</p>
                    <p class="mt-1">Please contact an administrator if you believe this is a mistake.</p>
                </div>
            </div>
        </div>
    @else
        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('expenses.store') }}"
              method="POST"
              class="space-y-5 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Date --}}
                <div>
                    <label class="form-label" for="date">Date <span class="text-red-500">*</span></label>
                    <input
                        id="date"
                        type="date"
                        name="date"
                        value="{{ old('date', now()->toDateString()) }}"
                        class="form-input w-full"
                        required>
                    @error('date')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Amount --}}
                <div>
                    <label class="form-label" for="amount">Amount (RWF) <span class="text-red-500">*</span></label>
                    <input
                        id="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        name="amount"
                        value="{{ old('amount') }}"
                        class="form-input w-full"
                        required>
                    @error('amount')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category (only Expense/Both) --}}
                <div>
                    <label class="form-label" for="category_id">Category <span class="text-red-500">*</span></label>
                    <select id="category_id" name="category_id" class="form-select w-full" required>
                        <option value="">Select…</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Supplier (optional) --}}
                <div>
                    <label class="form-label" for="supplier_id">Supplier (optional)</label>
                    <select id="supplier_id" name="supplier_id" class="form-select w-full">
                        <option value="">None</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Method --}}
                <div>
                    <label class="form-label" for="method">Method <span class="text-red-500">*</span></label>
                    <select id="method" name="method" class="form-select w-full" required>
                        @foreach($channels as $ch)
                            <option value="{{ $ch->slug }}" @selected(old('method') == $ch->slug)>
                                {{ $ch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('method')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reference --}}
                <div>
                    <label class="form-label" for="reference">Reference (optional)</label>
                    <input
                        id="reference"
                        type="text"
                        name="reference"
                        value="{{ old('reference') }}"
                        class="form-input w-full"
                        placeholder="Receipt / Txn ID">
                    @error('reference')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Note --}}
            <div>
                <label class="form-label" for="note">Note</label>
                <textarea
                    id="note"
                    name="note"
                    rows="3"
                    class="form-textarea w-full"
                    placeholder="Details…">{{ old('note') }}</textarea>
                @error('note')
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('expenses.index') }}" class="btn btn-outline">
                    Cancel
                </a>
                <button class="btn btn-primary flex items-center gap-1">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Expense
                </button>
            </div>
        </form>
    @endcannot
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
});
</script>
@endpush
