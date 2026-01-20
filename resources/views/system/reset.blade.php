@extends('layouts.app')
@section('title', 'System Reset')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="flex items-center gap-3 text-3xl font-bold text-gray-900 dark:text-gray-100">
            <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
            System Reset
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Permanently delete all data and start fresh. Use with extreme caution!
        </p>
    </div>

    {{-- Danger Warning --}}
    <div class="mb-6 p-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-800 rounded-xl">
        <div class="flex items-start gap-4">
            <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600 dark:text-red-400 flex-shrink-0 mt-1"></i>
            <div>
                <h2 class="text-xl font-bold text-red-900 dark:text-red-100 mb-2">
                    ⚠️ DANGER ZONE - READ CAREFULLY
                </h2>
                <p class="text-red-800 dark:text-red-200 mb-3">
                    This action will <strong>permanently delete ALL data</strong> from your application. 
                    This action <strong>CANNOT be undone</strong>!
                </p>
                <p class="text-sm text-red-700 dark:text-red-300">
                    <strong>Before proceeding:</strong> Make sure you have a backup if you need to restore any data later.
                </p>
            </div>
        </div>
    </div>

    {{-- What Will Be Deleted --}}
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        {{-- Will Be Deleted --}}
        <div class="bg-white dark:bg-gray-800 border-2 border-red-200 dark:border-red-800 rounded-xl p-6">
            <h3 class="flex items-center gap-2 text-lg font-bold text-red-700 dark:text-red-300 mb-4">
                <i data-lucide="x-circle" class="w-5 h-5"></i>
                Will Be DELETED
            </h3>
            <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Products
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Stock Movements
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Sales & Purchases
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Transactions
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Loans & Payments
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Expenses
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Customers & Suppliers
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    All Notifications
                </li>
            </ul>
        </div>

        {{-- Will Be Preserved --}}
        <div class="bg-white dark:bg-gray-800 border-2 border-green-200 dark:border-green-800 rounded-xl p-6">
            <h3 class="flex items-center gap-2 text-lg font-bold text-green-700 dark:text-green-300 mb-4">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                Will Be PRESERVED
            </h3>
            <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-green-600"></i>
                    User Accounts
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-green-600"></i>
                    Roles & Permissions
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-green-600"></i>
                    Categories
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-green-600"></i>
                    System Settings
                </li>
            </ul>
            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p class="text-xs text-green-800 dark:text-green-200">
                    <strong>Good news:</strong> You can immediately start adding products again using the Excel import feature with your preserved categories!
                </p>
            </div>
        </div>
    </div>

    {{-- Reset Form --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
            Confirm System Reset
        </h3>

        <form method="POST" action="{{ route('system.reset.execute') }}" x-data="{ password: '', confirmText: '' }">
            @csrf

            {{-- Confirmation Text --}}
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Type "RESET" to confirm (all caps)
                </label>
                <input
                    type="text"
                    name="confirm_text"
                    x-model="confirmText"
                    placeholder="Type RESET here"
                    class="form-input w-full text-lg font-mono"
                    required
                >
                @error('confirm_text')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Confirmation --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Enter your password to verify
                </label>
                <input
                    type="password"
                    name="password"
                    x-model="password"
                    placeholder="Your account password"
                    class="form-input w-full"
                    required
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Final Warning Checkbox --}}
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800 rounded-lg">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" required class="mt-1" x-model="understood">
                    <span class="text-sm text-yellow-900 dark:text-yellow-100">
                        <strong>I understand that:</strong>
                        <ul class="mt-2 space-y-1 list-disc list-inside text-yellow-800 dark:text-yellow-200">
                            <li>All my data will be permanently deleted</li>
                            <li>This action cannot be undone</li>
                            <li>I have a backup if needed</li>
                        </ul>
                    </span>
                </label>
            </div>

            {{-- Buttons --}}
            <div class="flex justify-between items-center gap-4">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline mr-1"></i>
                    Cancel & Go Back
                </a>
                <button
                    type="submit"
                    class="btn btn-danger flex items-center gap-2"
                    :disabled="confirmText !== 'RESET' || password.length < 1"
                    :class="{'opacity-50 cursor-not-allowed': confirmText !== 'RESET' || password.length < 1}"
                >
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Reset Everything
                </button>
            </div>
        </form>
    </div>

    {{-- Help Section --}}
    <div class="mt-8 p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
        <h3 class="flex items-center gap-2 font-bold text-blue-900 dark:text-blue-100 mb-3">
            <i data-lucide="info" class="w-5 h-5"></i>
            After Reset - Quick Start Guide
        </h3>
        <ol class="space-y-2 text-sm text-blue-800 dark:text-blue-200 list-decimal list-inside">
            <li>Your categories will still be available</li>
            <li>Download the Excel template from Products page</li>
            <li>Fill in your products with existing category names</li>
            <li>Upload and import - you'll be back in business!</li>
        </ol>
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
