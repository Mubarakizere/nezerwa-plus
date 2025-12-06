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
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>

            @can('expenses.view')
                <a href="{{ route('expenses.show', $expense) }}" class="btn btn-outline flex items-center gap-1 text-sm">
                    <i data-lucide="eye" class="w-4 h-4"></i> View
                </a>
            @endcan
        </div>
    </div>

    {{-- Permission gate --}}
    @cannot('expenses.edit')
        <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
            <div class="flex items-start gap-2">
                <i data-lucide="shield-off" class="w-5 h-5 mt-0.5 text-amber-600 dark:text-amber-300"></i>
                <div class="text-sm text-amber-800 dark:text-amber-100">
                    <p class="font-semibold">You don’t have permission to edit expenses.</p>
                    <p class="mt-1">Please contact an administrator if you believe this is a mistake.</p>
                </div>
            </div>
        </div>
    @else
        {{-- Alerts --}}
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 dark:border-rose-900/40 bg-rose-50 dark:bg-rose-950/40 p-3 text-sm text-rose-800 dark:text-rose-300">
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50 dark:bg-emerald-950/40 p-3 text-sm text-emerald-800 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('expenses.update', $expense) }}"
              method="POST"
              class="space-y-5 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 bg-white dark:bg-gray-900 p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Date --}}
                <div>
                    <label class="form-label" for="date">Date <span class="text-red-500">*</span></label>
                    <input
                        id="date"
                        type="date"
                        name="date"
                        value="{{ old('date', optional($expense->date)->toDateString()) }}"
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
                        value="{{ old('amount', $expense->amount) }}"
                        class="form-input w-full"
                        required>
                    @error('amount')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label class="form-label" for="category_id">Category <span class="text-red-500">*</span></label>
                    <select id="category_id" name="category_id" class="form-select w-full" required>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}"
                                @selected(old('category_id', $expense->category_id) == $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Supplier --}}
                <div>
                    <label class="form-label" for="supplier_id">Supplier (optional)</label>
                    <select id="supplier_id" name="supplier_id" class="form-select w-full">
                        <option value="">None</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}"
                                @selected(old('supplier_id', $expense->supplier_id) == $s->id)>
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
                            <option value="{{ $ch->slug }}"
                                @selected(old('method', $expense->method) == $ch->slug)>
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
                        value="{{ old('reference', $expense->reference) }}"
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
                    placeholder="Details…">{{ old('note', $expense->note) }}</textarea>
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
                    Save Changes
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
