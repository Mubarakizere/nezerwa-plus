@extends('layouts.app')
@section('title', 'Create Loan')

@section('content')
<style>[x-cloak]{display:none!important}</style>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="loanCreate({
        initType: '{{ old('type') }}',
        initLoanDate: '{{ old('loan_date', now()->format('Y-m-d')) }}',
        initDueDate: '{{ old('due_date') }}',
        initAmount: '{{ old('amount') }}'
     })"
     x-init="init()"
>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 mb-6">
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
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
        <form method="POST" action="{{ route('loans.store') }}" class="p-6 space-y-6">
            @csrf

            {{-- Loan Type --}}
            <div>
                <label for="loan_type" class="form-label">Loan Type <span class="text-red-500">*</span></label>
                <select name="type" id="loan_type" class="form-select w-full" required x-model="type" @change="onTypeChange">
                    <option value="">Select Type</option>
                    <option value="given">Given (We lend / customer owes us)</option>
                    <option value="taken">Taken (We owe supplier)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span x-show="type==='given'">Select the customer who will owe us back.</span>
                    <span x-show="type==='taken'">Select the supplier we owe.</span>
                </p>
            </div>

            {{-- Parties --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Customer --}}
                <div x-show="type==='given'" x-cloak>
                    <label for="customer_id" class="form-label">Customer <span class="text-red-500" x-show="type==='given'">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select w-full" :required="type==='given'" x-ref="customer">
                        <option value="">Select Customer</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="$refs.customer?.value">
                        Selected: <span class="font-medium" x-text="$refs.customer?.selectedOptions[0]?.text || ''"></span>
                    </p>
                </div>

                {{-- Supplier --}}
                <div x-show="type==='taken'" x-cloak>
                    <label for="supplier_id" class="form-label">Supplier <span class="text-red-500" x-show="type==='taken'">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select w-full" :required="type==='taken'" x-ref="supplier">
                        <option value="">Select Supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="$refs.supplier?.value">
                        Selected: <span class="font-medium" x-text="$refs.supplier?.selectedOptions[0]?.text || ''"></span>
                    </p>
                </div>
            </div>

            {{-- Amount --}}
            <div>
                <label for="amount" class="form-label">Amount <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    <input id="amount" type="number" inputmode="decimal" step="0.01" min="0.01" name="amount"
                           class="form-input w-full" x-model="amount" @blur="formatAmount" placeholder="0.00" required>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    The principal amount of the loan. We’ll log the initial transaction automatically.
                </p>
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="loan_date" class="form-label">Loan Date <span class="text-red-500">*</span></label>
                    <input id="loan_date" type="date" name="loan_date"
                           class="form-input w-full"
                           x-model="loanDate"
                           @change="syncDueMin"
                           required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">When the money was given/taken.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="due_date" class="form-label">Due Date</label>
                        <div class="flex items-center gap-1">
                            <button type="button" class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="setDue(0)">Today</button>
                            <button type="button" class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="setDue(7)">+7</button>
                            <button type="button" class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="setDue(14)">+14</button>
                            <button type="button" class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="setDue(30)">+30</button>
                            <button type="button" class="px-2 py-1 text-xs rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="setDue(60)">+60</button>
                        </div>
                    </div>
                    <input id="due_date" type="date" name="due_date"
                           class="form-input w-full"
                           x-model="dueDate"
                           :min="loanDate || undefined">
                    <p class="mt-1 text-xs"
                       :class="(dueDate && loanDate && dueDate < loanDate) ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                        <span x-show="!dueDate">Optional. Use the quick chips above.</span>
                        <span x-show="dueDate && loanDate && dueDate < loanDate">Due date is before loan date — please review.</span>
                        <span x-show="dueDate && (!loanDate || dueDate >= loanDate)">Expected repayment deadline.</span>
                    </p>
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
                    <span x-show="status==='pending'">You can record payments later; the loan will auto-close when fully paid.</span>
                    <span x-show="status==='paid'">Marks this loan as fully settled immediately.</span>
                </p>
            </div>

            {{-- Notes --}}
            <div>
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="form-textarea w-full"
                          placeholder="Reference / comment…">{{ old('notes') }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        Initial accounting entries are created automatically and linked to this loan.
                    </span>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('loans.index') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-success">Save Loan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Alpine helpers --}}
<script>
function loanCreate({initType, initLoanDate, initDueDate, initAmount}) {
    return {
        type: initType || '',
        loanDate: initLoanDate || '',
        dueDate: initDueDate || '',
        amount: initAmount || '',
        status: '{{ old('status','pending') }}',
        init() {
            // Ensure min(due) respects current loan date
            this.$nextTick(() => this.syncDueMin());
        },
        onTypeChange() {
            // Nothing heavy here, but you can auto-clear party if switching
            if (this.type === 'given' && this.$refs.supplier) this.$refs.supplier.value = '';
            if (this.type === 'taken' && this.$refs.customer) this.$refs.customer.value = '';
        },
        setDue(days) {
            if (!this.loanDate) { this.loanDate = (new Date()).toISOString().slice(0,10); }
            const base = new Date(this.loanDate + 'T00:00:00');
            base.setDate(base.getDate() + Number(days || 0));
            const y = base.getFullYear();
            const m = String(base.getMonth()+1).padStart(2,'0');
            const d = String(base.getDate()).padStart(2,'0');
            this.dueDate = `${y}-${m}-${d}`;
        },
        syncDueMin() {
            const due = document.getElementById('due_date');
            if (due && this.loanDate) due.min = this.loanDate;
        },
        formatAmount() {
            if (this.amount === '' || isNaN(this.amount)) return;
            const num = parseFloat(this.amount);
            if (isFinite(num)) this.amount = num.toFixed(2);
        }
    }
}
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
