@extends('layouts.app')
@section('title', 'Send to Boss (Withdrawal)')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="send" class="w-6 h-6 text-rose-600 dark:text-rose-400"></i>
            Send to Boss (Withdrawal)
        </h1>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- Card --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <form action="{{ route('transactions.withdrawal.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Date --}}
            <div>
                <label class="form-label">Date <span class="text-red-500">*</span></label>
                <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}"
                       class="form-input" required>
                @error('transaction_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Amount --}}
            <div>
                <label class="form-label">Amount (RWF) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}"
                       class="form-input text-lg font-semibold" required>
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- From Channel --}}
            <div>
                <label class="form-label">From Channel <span class="text-red-500">*</span></label>
                <select name="method" class="form-select" required>
                    <option value="">Select Source Channel</option>
                    @foreach($channels as $ch)
                        <option value="{{ $ch->slug }}" @selected(old('method') == $ch->slug)>
                            {{ $ch->name }}
                        </option>
                    @endforeach
                </select>
                @error('method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-500 mt-1">Money will be deducted from this channel.</p>
            </div>

            {{-- Notes --}}
            <div>
                <label class="form-label">Notes / Reason</label>
                <textarea name="notes" rows="3" class="form-textarea" placeholder="Optional details...">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('transactions.index') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-danger flex items-center gap-1">
                    <i data-lucide="send" class="w-4 h-4"></i> Confirm Withdrawal
                </button>
            </div>
        </form>
    </div>
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
