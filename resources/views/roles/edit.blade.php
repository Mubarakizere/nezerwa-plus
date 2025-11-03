@extends('layouts.app')
@section('title', 'Edit Role')

@section('content')
<div x-data="{ expanded: true }" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex justify-between items-center flex-wrap gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="shield" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit Role: {{ ucfirst($role->name) }}</span>
        </h1>
        <a href="{{ route('roles.index') }}" class="btn btn-outline text-sm flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
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
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('roles.update', $role->id) }}"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                 rounded-xl shadow-sm p-6 space-y-8">
        @csrf
        @method('PUT')

        {{-- Role Info --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role Name</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                   class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                   placeholder="Enter role name">
        </div>

        {{-- Include Permissions Partial --}}
        @include('roles._permissions', [
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ])

        {{-- Buttons --}}
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('roles.index') }}" class="btn btn-outline text-sm px-4 py-2">Cancel</a>
            <button type="submit" class="btn btn-primary text-sm px-4 py-2 flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i> Save Changes
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
@endsection
