@extends('layouts.app')
@section('title', "Add Payment for Loan #{$loan->id}")

@section('content')
@php
    use Carbon\Carbon;
    /** Server-side safe math */
    $totalPaid  = (float) ($loan->payments()->sum('amount') ?? 0);
    $remaining  = max((float) ($loan->amount ?? 0) - $totalPaid, 0);
    $progress   = ($loan->amount ?? 0) > 0 ? (int) round(($totalPaid / $loan->amount) * 100) : 0;

    $dueDate    = $loan->due_date ? Carbon::parse($loan->due_date) : null;
    $isOverdue  = $dueDate && $dueDate->isPast() && ($loan->status !== 'paid');
    $overdueDays= $isOverdue ? $dueDate->diffInDays(today()) : 0;


@endphp

<div
    x-data="paymentForm({
        remaining: {{ json_encode($remaining) }},
        initialAmount: {{ json_encode((float) old('amount', 0)) }},
        methods: {{ json_encode($paymentChannels->pluck('slug')) }},
        initialMethod: {{ json_encode(old('method', 'cash')) }}
    })"
    class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i data-lucide="credit-card" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Add Payment for Loan #{{ $loan->id }}
            </h1>
        </div>
        <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- Flash --}}
    @if (session('error'))
        <div class="rounded-lg border border-rose-200 dark:border-rose-900/40 bg-rose-50 dark:bg-rose-950/40 p-3 text-sm text-rose-800 dark:text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation --}}
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 dark:border-rose-900/40 bg-rose-50 dark:bg-rose-950/40 p-3 text-sm text-rose-800 dark:text-rose-300">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Loan Summary --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Loan Summary</h3>
            <div class="flex flex-wrap gap-2">
                @if($loan->status === 'paid')
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">
                        Paid
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300">
                        Pending
                    </span>
                    @if($isOverdue)
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                            Overdue by {{ $overdueDays }} day{{ $overdueDays!==1 ? 's' : '' }}
                        </span>
                    @endif
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Total Amount</p>
                <p class="font-semibold text-indigo-600 dark:text-indigo-400 text-lg">{{ number_format($loan->amount, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Total Paid</p>
                <p class="font-semibold text-green-600 dark:text-green-400 text-lg">{{ number_format($totalPaid, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Remaining</p>
                <p class="font-semibold" :class="remaining <= 0 ? 'text-green-600 dark:text-green-400' : 'text-rose-600 dark:text-rose-400'">
                    RWF <span x-text="fmt(remaining)"></span>
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Due Date</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $dueDate ? $dueDate->format('Y-m-d') : '—' }}
                </p>
            </div>
        </div>

        <div class="pt-2">
            <div class="flex justify-between items-center mb-1 text-xs text-gray-600 dark:text-gray-400">
                <span>Repayment Progress</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </section>

    {{-- Guard if fully paid --}}
    @if ($remaining <= 0)
        <div class="rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 p-4 text-sm text-green-800 dark:text-green-200">
            This loan is fully paid. No additional payments are allowed.
        </div>
    @else
        {{-- Payment Form --}}
        <form method="POST" action="{{ route('loan-payments.store', $loan) }}"
              x-on:submit="return onSubmit()"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
            @csrf

            {{-- Amount --}}
            <div>
                <div class="flex items-center justify-between">
                    <label for="amount" class="form-label">Payment Amount <span class="text-rose-500">*</span></label>
                    <button type="button" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                            x-on:click="fillRemaining()" x-show="remaining > 0">
                        Pay remaining (RWF <span x-text="fmt(remaining)"></span>)
                    </button>
                </div>
                <input
                    x-model.number="amount"
                    x-on:input="validate()"
                    type="number" step="0.01" min="0.01"
                    :max="remaining"
                    name="amount" id="amount"
                    value="{{ old('amount') }}"
                    class="form-input w-full"
                    placeholder="Enter amount..." required>
                <p class="mt-1 text-xs"
                   :class="hintClass"
                   x-text="hint"></p>
            </div>

            {{-- Payment Date --}}
            <div>
                <label for="payment_date" class="form-label">Payment Date <span class="text-rose-500">*</span></label>
                <input type="date" name="payment_date" id="payment_date"
                       value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                       class="form-input w-full" required>
            </div>

            {{-- Method (strict select) --}}
            <div>
                <label for="method" class="form-label">Payment Method <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach($paymentChannels as $channel)
                        @php
                            $label = strtoupper($channel->name);
                            $icon  = match($channel->slug) {
                                'cash' => 'banknote',
                                'bank' => 'landmark',
                                'momo' => 'smartphone',
                                default => 'wallet'
                            };
                        @endphp
                        <label class="flex items-center gap-2 rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 bg-white/70 dark:bg-gray-900/60 px-3 py-2 cursor-pointer hover:ring-indigo-300">
                            <input type="radio" name="method" value="{{ $channel->slug }}"
                                   x-model="method"
                                   class="accent-indigo-600"
                                   @checked(old('method','cash')===$channel->slug)>
                            <i data-lucide="{{ $icon }}" class="w-4 h-4 text-indigo-500"></i>
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <input type="hidden" :value="method">
            </div>

            {{-- Notes --}}
            <div>
                <label for="notes" class="form-label">Notes (optional)</label>
                <textarea name="notes" id="notes" rows="3"
                          class="form-textarea w-full"
                          placeholder="Enter remarks or reference...">{{ old('notes') }}</textarea>
            </div>

            {{-- Calculated preview --}}
            <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 ring-1 ring-gray-200 dark:ring-gray-800 p-3 text-sm">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="text-gray-600 dark:text-gray-300">
                        Remaining after this payment:
                    </div>
                    <div class="font-semibold"
                         :class="nextRemaining <= 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                        RWF <span x-text="fmt(Math.max(nextRemaining, 0))"></span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-success" :disabled="!canSubmit">
                    Record Payment
                </button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

/** Alpine helper */
function paymentForm({ remaining, initialAmount, methods, initialMethod }) {
    return {
        remaining: Number(remaining ?? 0),
        amount: Number(initialAmount ?? 0),
        methods,
        method: initialMethod || (methods?.[0] ?? 'cash'),

        hint: '',
        hintClass: 'text-gray-500 dark:text-gray-400',
        canSubmit: false,

        get nextRemaining() {
            const amt = isFinite(this.amount) ? this.amount : 0;
            return (this.remaining - Math.max(amt, 0));
        },

        fmt(n) {
            try { return Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            catch { return (n ?? 0).toFixed(2); }
        },

        validate() {
            const amt = Number(this.amount);
            this.canSubmit = false;

            if (!isFinite(amt) || amt <= 0) {
                this.hint = 'Enter a positive amount.';
                this.hintClass = 'text-rose-600 dark:text-rose-400';
                return;
            }

            if (this.remaining <= 0) {
                this.hint = 'This loan is already fully paid.';
                this.hintClass = 'text-rose-600 dark:text-rose-400';
                return;
            }

            if (amt > this.remaining) {
                this.hint = `Amount exceeds remaining (max RWF ${this.fmt(this.remaining)}).`;
                this.hintClass = 'text-rose-600 dark:text-rose-400';
                return;
            }

            this.hint = 'Looks good.';
            this.hintClass = 'text-emerald-600 dark:text-emerald-400';
            this.canSubmit = true;
        },

        fillRemaining() {
            this.amount = this.remaining;
            this.validate();
        },

        onSubmit() {
            this.validate();
            return this.canSubmit;
        },

        init() {
            this.validate();
        }
    }
}
</script>
@endpush
@endsection
