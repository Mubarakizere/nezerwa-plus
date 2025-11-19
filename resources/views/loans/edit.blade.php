@extends('layouts.app')
@section('title', "Edit Loan #{$loan->id}")

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $paid      = (float) $loan->payments()->sum('amount');
    $remaining = max((float)($loan->amount ?? 0) - $paid, 0);
    $progress  = ($loan->amount ?? 0) > 0 ? round(($paid / $loan->amount) * 100) : 0;
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="loanEdit(@js([
        'initType'     => old('type', $loan->type),
        'initLoanDate' => old('loan_date', optional($loan->loan_date)->format('Y-m-d')),
        'initDueDate'  => old('due_date', optional($loan->due_date)->format('Y-m-d')),
        'initAmount'   => old('amount', number_format($loan->amount, 2, '.', '')),
        'initStatus'   => old('status', $loan->status),
     ]))"
     x-init="init()"
>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 mb-6">
        <div class="flex items-center gap-2">
            <i data-lucide="wallet" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Edit Loan #{{ $loan->id }}
            </h1>
        </div>
        <div class="flex items-center gap-2">
            @can('loans.view')
                <a href="{{ route('loans.show', $loan) }}" class="btn btn-secondary text-sm flex items-center gap-1">
                    <i data-lucide="eye" class="w-4 h-4"></i> View
                </a>
                <a href="{{ route('loans.index') }}" class="btn btn-outline text-sm flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
            @endcan
        </div>
    </div>

    {{-- Top summary --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Amount</p>
                <p class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                    {{ number_format($loan->amount, 2) }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Paid</p>
                <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                    {{ number_format($paid, 2) }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Remaining</p>
                <p class="text-lg font-semibold {{ $remaining <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($remaining, 2) }}
                </p>
            </div>
        </div>
        <div class="mt-3">
            <div class="flex justify-between items-center mb-1 text-xs text-gray-600 dark:text-gray-400">
                <span>Repayment Progress</span><span>{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </section>

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

    {{-- Form --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
        @can('loans.edit')
            <form method="POST" action="{{ route('loans.update', $loan) }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                {{-- Type --}}
                <div>
                    <label for="loan_type" class="form-label">
                        Loan Type <span class="text-red-500">*</span>
                    </label>
                    <select name="type" id="loan_type" class="form-select w-full"
                            required
                            x-model="type"
                            @change="onTypeChange()">
                        <option value="given">Given (We lend / customer owes us)</option>
                        <option value="taken">Taken (We owe supplier)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span x-show="type==='given'">This loan belongs to a Customer.</span>
                        <span x-show="type==='taken'">This loan belongs to a Supplier.</span>
                    </p>
                    @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Parties --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Customer --}}
                    <div x-show="type==='given'" x-cloak>
                        <label for="customer_id" class="form-label">
                            Customer <span class="text-red-500" x-show="type==='given'">*</span>
                        </label>
                        <select name="customer_id" id="customer_id"
                                class="form-select w-full"
                                :required="type==='given'"
                                x-ref="customer">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}"
                                    {{ (string)old('customer_id', (string)$loan->customer_id) === (string)$c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="$refs.customer?.value">
                            Selected:
                            <span class="font-medium" x-text="$refs.customer?.selectedOptions[0]?.text || ''"></span>
                        </p>
                        @error('customer_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Supplier --}}
                    <div x-show="type==='taken'" x-cloak>
                        <label for="supplier_id" class="form-label">
                            Supplier <span class="text-red-500" x-show="type==='taken'">*</span>
                        </label>
                        <select name="supplier_id" id="supplier_id"
                                class="form-select w-full"
                                :required="type==='taken'"
                                x-ref="supplier">
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}"
                                    {{ (string)old('supplier_id', (string)$loan->supplier_id) === (string)$s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="$refs.supplier?.value">
                            Selected:
                            <span class="font-medium" x-text="$refs.supplier?.selectedOptions[0]?.text || ''"></span>
                        </p>
                        @error('supplier_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Amount --}}
                <div>
                    <label for="amount" class="form-label">
                        Amount <span class="text-red-500">*</span>
                    </label>
                    <input id="amount"
                           type="number"
                           inputmode="decimal"
                           step="0.01"
                           min="0.01"
                           name="amount"
                           class="form-input w-full"
                           x-model="amount"
                           @blur="formatAmount"
                           placeholder="0.00"
                           required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Principal amount. Payments you record will reduce the remaining balance.
                    </p>
                    @error('amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="loan_date" class="form-label">
                            Loan Date <span class="text-red-500">*</span>
                        </label>
                        <input id="loan_date"
                               type="date"
                               name="loan_date"
                               class="form-input w-full"
                               x-model="loanDate"
                               @change="syncDueMin()"
                               required>
                        @error('loan_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="due_date" class="form-label">Due Date</label>
                            <div class="flex items-center gap-1">
                                <button type="button"
                                        class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="setDue(0)">
                                    Today
                                </button>
                                <button type="button"
                                        class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="setDue(7)">
                                    +7
                                </button>
                                <button type="button"
                                        class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="setDue(14)">
                                    +14
                                </button>
                                <button type="button"
                                        class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="setDue(30)">
                                    +30
                                </button>
                                <button type="button"
                                        class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="setDue(60)">
                                    +60
                                </button>
                                <button type="button"
                                        class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="clearDue()">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <input id="due_date"
                               type="date"
                               name="due_date"
                               class="form-input w-full"
                               x-model="dueDate"
                               :min="loanDate || undefined">
                        <p class="mt-1 text-xs"
                           :class="(dueDate && loanDate && dueDate < loanDate)
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-gray-500 dark:text-gray-400'">
                            <span x-show="!dueDate">Optional. Use quick chips for convenience.</span>
                            <span x-show="dueDate && loanDate && dueDate < loanDate">
                                Due date is before loan date — please review.
                            </span>
                            <span x-show="dueDate && (!loanDate || dueDate >= loanDate)">
                                Expected repayment deadline.
                            </span>
                        </p>
                        @error('due_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select w-full" x-model="status">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span x-show="status==='pending'">
                            Keep as pending; loan auto-closes when fully paid.
                        </span>
                        <span x-show="status==='paid'">
                            Marks as settled (no payment entry is created automatically).
                        </span>
                    </p>
                    @error('status') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="form-textarea w-full"
                              placeholder="Reference / comment…">{{ old('notes', $loan->notes) }}</textarea>
                    @error('notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i>
                            Linked sale/purchase (if any) will reflect status via observers.
                        </span>
                    </div>
                    <div class="flex justify-end gap-3">
                        @can('loans.view')
                            <a href="{{ route('loans.index') }}" class="btn btn-outline">Cancel</a>
                        @endcan
                        <button type="submit" class="btn btn-success">Update Loan</button>
                    </div>
                </div>
            </form>
        @else
            <div class="p-6 text-sm text-amber-700 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-xl">
                You do not have permission to edit loans. Please contact your administrator if you believe this is an error.
                @can('loans.view')
                    <div class="mt-3">
                        <a href="{{ route('loans.show', $loan) }}" class="btn btn-secondary btn-sm">
                            <i data-lucide="eye" class="w-4 h-4"></i> View Loan
                        </a>
                        <a href="{{ route('loans.index') }}" class="btn btn-outline btn-sm">
                            Back to Loans
                        </a>
                    </div>
                @endcan
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

function loanEdit(config) {
    return {
        type: config.initType || 'given',
        loanDate: config.initLoanDate || '',
        dueDate: config.initDueDate || '',
        amount: config.initAmount || '',
        status: config.initStatus || 'pending',

        init() {
            this.$nextTick(() => this.syncDueMin());
        },

        onTypeChange() {
            // Clear opposite party to avoid invalid combos
            if (this.type === 'given' && this.$refs?.supplier) {
                this.$refs.supplier.value = '';
            }
            if (this.type === 'taken' && this.$refs?.customer) {
                this.$refs.customer.value = '';
            }
        },

        setDue(days) {
            if (!this.loanDate) {
                this.loanDate = (new Date()).toISOString().slice(0, 10);
            }
            const base = new Date(this.loanDate + 'T00:00:00');
            base.setDate(base.getDate() + Number(days || 0));
            const y = base.getFullYear();
            const m = String(base.getMonth() + 1).padStart(2, '0');
            const d = String(base.getDate()).padStart(2, '0');
            this.dueDate = `${y}-${m}-${d}`;
        },

        clearDue() {
            this.dueDate = '';
        },

        syncDueMin() {
            const due = document.getElementById('due_date');
            if (due && this.loanDate) {
                due.min = this.loanDate;
            }
        },

        formatAmount() {
            if (this.amount === '' || isNaN(this.amount)) return;
            const num = parseFloat(this.amount);
            if (isFinite(num)) {
                this.amount = num.toFixed(2);
            }
        }
    }
}
</script>
@endpush
@endsection
