@extends('layouts.app')
@section('title', 'Edit Role')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="shield" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Edit Role: {{ ucfirst($role->name) }}</span>
            </h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Update the role name and adjust which permissions it has.
            </p>
        </div>

        <a href="{{ route('roles.index') }}" class="btn btn-outline text-sm flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back</span>
        </a>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700
                    text-green-800 dark:text-green-300 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700
                    text-red-800 dark:text-red-300 rounded-lg px-4 py-3 text-sm">
            <div class="font-medium mb-1">Please fix the following errors:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('roles.update', $role->id) }}"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                 rounded-xl shadow-sm p-6 space-y-8">
        @csrf
        @method('PUT')

        {{-- Role Info --}}
        <div class="space-y-2">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Role Name <span class="text-red-600">*</span>
            </label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $role->name) }}"
                required
                autocomplete="off"
                class="w-full form-input"
                placeholder="e.g. manager, cashier, accountant"
            >
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Name will be stored in lowercase (e.g. <code>manager</code>).
            </p>
        </div>

        {{-- Permissions Partial --}}
        @include('roles._permissions', [
            'permissions'     => $permissions,
            'rolePermissions' => old('permissions', $rolePermissions),
        ])

        {{-- Buttons --}}
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('roles.index') }}" class="btn btn-outline text-sm px-4 py-2">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary text-sm px-4 py-2 flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>
@endpush
@endsection
