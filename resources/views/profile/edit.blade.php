@extends('layouts.app')
@section('title', 'Profile Settings')

@section('content')
<div class="max-w-3xl mx-auto space-y-10">

    {{-- 🔹 Header --}}
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="user-circle" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Profile Settings</span>
        </h1>
    </div>

    {{-- 🔹 Update Profile Info --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6 transition-colors duration-300"
         x-data="{ preview: '{{ $user->photo ? asset('storage/'.$user->photo) : '' }}' }">

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            {{-- Profile Photo --}}
<div class="flex flex-col sm:flex-row items-center gap-6">
    {{-- 🖼️ Live Preview --}}
    <div class="relative group">
        <img
            :src="preview || '{{ $user->photo ? asset('storage/'.$user->photo) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=ffffff&background=4f46e5' }}'"
            alt="Profile Photo"
            class="w-32 h-32 rounded-xl object-cover border border-gray-300 dark:border-gray-600 shadow-md transition duration-300 group-hover:brightness-90"
            onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=ffffff&background=4f46e5'">

        {{-- Hover Overlay --}}
        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center transition">
            <span class="text-white text-xs font-medium">Preview</span>
        </div>
    </div>

    <div class="space-y-2 w-full">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Photo</label>

        <input
            type="file"
            name="photo"
            accept="image/*"
            @change="preview = URL.createObjectURL($event.target.files[0])"
            class="block w-full text-sm text-gray-700 dark:text-gray-200
                   bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm
                   focus:ring-indigo-500 focus:border-indigo-500 transition" />

        <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, JPEG up to 30 MB</p>
        @error('photo')
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
        @enderror

        @if($user->photo)
            <button
                type="submit"
                name="remove_photo"
                value="1"
                class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 text-xs font-medium hover:underline">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Remove Photo
            </button>
        @endif
    </div>
</div>


            {{-- Basic Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-gray-700 dark:text-gray-300">Name</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        class="form-input bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700
                               text-gray-800 dark:text-gray-200"
                        required>
                </div>

                <div>
                    <label class="form-label text-gray-700 dark:text-gray-300">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        class="form-input bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700
                               text-gray-800 dark:text-gray-200"
                        required>
                </div>
            </div>

            {{-- Save --}}
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary inline-flex items-center gap-1">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- 🔹 Update Password --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6 transition-colors duration-300">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="lock" class="w-5 h-5 text-indigo-500 dark:text-indigo-400"></i>
            Change Password
        </h2>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-gray-700 dark:text-gray-300">Current Password</label>
                    <input
                        type="password"
                        name="current_password"
                        class="form-input bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700
                               text-gray-800 dark:text-gray-200"
                        required>
                </div>
                <div>
                    <label class="form-label text-gray-700 dark:text-gray-300">New Password</label>
                    <input
                        type="password"
                        name="password"
                        class="form-input bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700
                               text-gray-800 dark:text-gray-200"
                        required>
                </div>
            </div>

            <div>
                <label class="form-label text-gray-700 dark:text-gray-300">Confirm Password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    class="form-input bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700
                           text-gray-800 dark:text-gray-200"
                    required>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary inline-flex items-center gap-1">
                    <i data-lucide="key" class="w-4 h-4"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
