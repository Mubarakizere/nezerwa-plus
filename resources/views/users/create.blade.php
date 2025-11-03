@extends('layouts.app')
@section('title', 'Add New User')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Add New User</span>
        </h1>
        <a href="{{ route('users.index') }}" class="btn btn-outline flex items-center gap-1 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
        @csrf

        <div x-data="{ preview: null }" class="flex flex-col sm:flex-row items-center gap-6">
            <div class="relative group">
                <img
                    :src="preview || 'https://ui-avatars.com/api/?name=User&color=ffffff&background=4f46e5'"
                    alt="User Preview"
                    class="w-28 h-28 rounded-xl object-cover border border-gray-300 dark:border-gray-600 shadow-md transition duration-300 group-hover:brightness-90"
                    onerror="this.src='https://ui-avatars.com/api/?name=User&color=ffffff&background=4f46e5'">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center transition">
                    <span class="text-white text-xs font-medium">Preview</span>
                </div>
            </div>

            <div class="w-full space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Photo (optional)</label>
                <input type="file" name="profile_image" accept="image/*"
                       @change="preview = URL.createObjectURL($event.target.files[0])"
                       class="block w-full text-sm text-gray-700 dark:text-gray-200
                              bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm
                              focus:ring-indigo-500 focus:border-indigo-500 transition
                              file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                              file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100
                              dark:file:bg-gray-700 dark:file:text-gray-200">
                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, JPEG up to 30 MB</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Enter full name" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="user@example.com" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" name="password"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Enter password" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Re-enter password" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign Role</label>
                <select name="role" class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100" required>
                    <option value="">Select role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('users.index') }}" class="btn btn-outline text-sm px-4 py-2">Cancel</a>
            <button type="submit" class="btn btn-primary text-sm px-4 py-2 flex items-center gap-1">
                <i data-lucide="save" class="w-4 h-4"></i> Save User
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
