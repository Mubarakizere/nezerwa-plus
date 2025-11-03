@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div x-data="{
        deleteUser: false,
        preview: '{{ $user->photo ? asset('storage/'.$user->photo) : ($user->profile_image ? asset('storage/'.$user->profile_image) : '') }}'
    }"
    class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="user-cog" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Edit User</span>
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

    @if(session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="flex flex-col sm:flex-row items-center gap-6">
            <div class="relative group">
                <img
                    :src="preview || '{{
                        ($user->photo && file_exists(public_path('storage/'.$user->photo)))
                            ? asset('storage/'.$user->photo)
                            : (($user->profile_image && file_exists(public_path('storage/'.$user->profile_image)))
                                ? asset('storage/'.$user->profile_image)
                                : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=ffffff&background=4f46e5')
                    }}'"
                    alt="{{ $user->name }}"
                    class="w-28 h-28 rounded-xl object-cover border border-gray-300 dark:border-gray-600 shadow-md transition duration-300 group-hover:brightness-90"
                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=ffffff&background=4f46e5'">

                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center transition">
                    <span class="text-white text-xs font-medium">Preview</span>
                </div>
            </div>

            <div class="w-full space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Change Profile Photo</label>
                <input type="file" name="profile_image" accept="image/*"
                       @change="preview = URL.createObjectURL($event.target.files[0])"
                       class="block w-full text-sm text-gray-700 dark:text-gray-200
                              bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm
                              focus:ring-indigo-500 focus:border-indigo-500 transition file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100
                              dark:file:bg-gray-700 dark:file:text-gray-200">
                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, JPEG up to 30 MB</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Enter full name" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="user@example.com" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password (optional)</label>
                <input type="password" name="password"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Leave blank to keep current">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation"
                       class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Re-enter new password">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                <select name="role" class="input input-bordered w-full dark:bg-gray-700 dark:text-gray-100" required>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ $user->hasRole($role) ? 'selected' : '' }}>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-between flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            @if($user->email !== 'admin@stockmanagement.com')
                <button type="button"
                        @click="deleteUser = true"
                        class="btn btn-danger text-sm flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Delete User
                </button>
            @endif

            <div class="flex gap-3">
                <a href="{{ route('users.index') }}" class="btn btn-outline text-sm px-4 py-2">Cancel</a>
                <button type="submit" class="btn btn-primary text-sm px-4 py-2 flex items-center gap-1">
                    <i data-lucide="save" class="w-4 h-4"></i> Update User
                </button>
            </div>
        </div>
    </form>

    <div x-show="deleteUser" x-cloak x-transition.opacity
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div @click.away="deleteUser = false"
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 mx-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Confirm Deletion</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
                Are you sure you want to delete this user? This action cannot be undone.
            </p>
            <div class="flex justify-end gap-3">
                <button @click="deleteUser = false"
                        type="button"
                        class="btn btn-outline text-sm px-4 py-2">
                    Cancel
                </button>
                <form action="{{ route('users.destroy', $user) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger text-sm px-4 py-2">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
@endsection
