@extends('layouts.app')
@section('title', 'Add Customer')

@section('content')
@php
    $user = auth()->user();
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <i data-lucide="user-plus" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Add Customer
                </h1>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Store basic details so you can easily find and contact this customer later.
            </p>
        </div>

        <a href="{{ route('customers.index') }}" class="btn btn-secondary flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back</span>
        </a>
    </div>

    {{-- Validation summary --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-4 text-red-700 dark:text-red-200 text-sm">
            <div class="font-medium">Please fix the following errors:</div>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Permission gate (UI level, real protection is in routes middleware) --}}
    @can('customers.create')

        {{-- Card --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <form action="{{ route('customers.store') }}" method="POST" novalidate>
                @csrf

                <div class="p-6 space-y-6">

                    {{-- Small note --}}
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Fields marked with <span class="text-red-600">*</span> are required.
                    </p>

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Name <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autocomplete="name"
                            placeholder="e.g. John Doe"
                            class="mt-1 w-full form-input"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email & Phone --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                placeholder="e.g. customer@example.com"
                                class="mt-1 w-full form-input"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                Optional, but useful for sending invoices or receipts.
                            </p>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Phone
                            </label>
                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                value="{{ old('phone') }}"
                                autocomplete="tel"
                                placeholder="e.g. +250 7xx xxx xxx"
                                class="mt-1 w-full form-input"
                            >
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                Optional, but helps when you need to follow up.
                            </p>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Address
                        </label>
                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            placeholder="e.g. Kigali, Remera – near XYZ"
                            class="mt-1 w-full form-textarea"
                        >{{ old('address') }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Optional, but can be handy for deliveries or visits.
                        </p>
                    </div>
                </div>

                {{-- Footer actions --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                    <a href="{{ route('customers.index') }}" class="btn btn-outline">
                        Cancel
                    </a>

                    {{-- submit button also guarded by permission, for consistency --}}
                    @can('customers.create')
                        <button type="submit" class="btn btn-success flex items-center gap-1">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Customer</span>
                        </button>
                    @endcan
                </div>
            </form>
        </div>

    @else
        {{-- Fallback if somehow user hits this page without permission --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5"></i>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        You don’t have permission to add customers.
                    </h2>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        Please contact your administrator if you believe this is a mistake.
                    </p>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection

@push('scripts')
<script defer src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>
@endpush
