@extends('layouts.app')
@section('title', "Add Payment for Loan #{$loan->id}")

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

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

    {{-- Flash Messages --}}
    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg p-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg p-4">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $totalPaid = $loan->payments->sum('amount');
        $remaining = max(($loan->amount ?? 0) - $totalPaid, 0);
        $progress = $loan->amount > 0 ? round(($totalPaid / $loan->amount) * 100) : 0;
    @endphp

    {{-- Loan Summary --}}
    <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Loan Summary</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Total Amount</p>
                <p class="font-semibold text-indigo-600 dark:text-indigo-400 text-lg">{{ number_format($loan->amount, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Total Paid</p>
                <p class="font-semibold text-green-600 dark:text-green-400 text-lg">{{ number_format($totalPaid, 2) }}</p>
            </div>
        </div>
        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
            <p><strong>Remaining:</strong>
                <span class="{{ $remaining <= 0 ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-red-600 dark:text-red-400 font-semibold' }}">
                    {{ number_format($remaining, 2) }}
                </span>
            </p>
            <p><strong>Status:</strong>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                    {{ $loan->status === 'paid'
                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </p>
        </div>

        {{-- Progress Bar --}}
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

    {{-- Payment Form --}}
    <form method="POST" action="{{ route('loan-payments.store', $loan) }}"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
        @csrf

        {{-- Amount --}}
        <div>
            <label for="amount" class="form-label">Payment Amount <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                   value="{{ old('amount') }}"
                   class="form-input w-full" placeholder="Enter amount..." required>
        </div>

        {{-- Payment Date --}}
        <div>
            <label for="payment_date" class="form-label">Payment Date <span class="text-red-500">*</span></label>
            <input type="date" name="payment_date" id="payment_date"
                   value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                   class="form-input w-full" required>
        </div>

        {{-- Method --}}
        <div>
            <label for="method" class="form-label">Payment Method <span class="text-red-500">*</span></label>
            <input type="text" name="method" id="method"
                   value="{{ old('method', 'cash') }}"
                   class="form-input w-full" placeholder="cash / momo / bank" required>
        </div>

        {{-- Notes --}}
        <div>
            <label for="notes" class="form-label">Notes (optional)</label>
            <textarea name="notes" id="notes" rows="3"
                      class="form-textarea w-full"
                      placeholder="Enter remarks or reference...">{{ old('notes') }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-4">
            <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-success">Record Payment</button>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
