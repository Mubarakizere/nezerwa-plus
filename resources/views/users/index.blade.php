@extends('layouts.app')
@section('title', 'Users Management')

@section('content')
<div x-data="{ deleteUserId: null }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="users" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Users Management</span>
        </h1>

        <a href="{{ route('users.create') }}" class="btn btn-primary flex items-center gap-2 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New User
        </a>
    </div>

    {{-- 🔹 Search + Filter --}}
    <form method="GET"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4 flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..."
               class="w-full md:w-1/3 input input-bordered dark:bg-gray-700 dark:text-gray-100">
        <select name="role" class="w-full md:w-1/4 input input-bordered dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Roles</option>
            @foreach($roles as $role)
                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                    {{ ucfirst($role) }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary flex items-center gap-1">
            <i data-lucide="search" class="w-4 h-4"></i> Filter
        </button>
        @if(request('search') || request('role'))
            <a href="{{ route('users.index') }}" class="btn btn-outline text-sm">Reset</a>
        @endif
    </form>

    {{-- 🔹 Users Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 text-xs font-medium uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            {{-- ID --}}
                            <td class="px-4 py-3 text-gray-500">{{ $user->id }}</td>

                            {{-- Profile + Name --}}
                            <td class="px-4 py-3 flex items-center gap-3">
                                @php
                                    $imagePath = null;
                                    if ($user->photo && file_exists(public_path('storage/' . $user->photo))) {
                                        $imagePath = asset('storage/' . $user->photo);
                                    } elseif ($user->profile_image && file_exists(public_path('storage/' . $user->profile_image))) {
                                        $imagePath = asset('storage/' . $user->profile_image);
                                    }
                                @endphp

                                {{-- 🖼️ Profile Image or Avatar --}}
                                @if ($imagePath)
                                    <img src="{{ $imagePath }}"
                                         alt="{{ $user->name }}"
                                         class="w-11 h-11 rounded-full object-cover border border-gray-300 dark:border-gray-700 shadow-sm"
                                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=ffffff&background=4f46e5'">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=ffffff&background=4f46e5"
                                         alt="{{ $user->name }}"
                                         class="w-11 h-11 rounded-full object-cover border border-gray-300 dark:border-gray-700 shadow-sm">
                                @endif

                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $user->email }}</td>

                            {{-- Role --}}
                            <td class="px-4 py-3">
                                @foreach($user->getRoleNames() as $role)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ match($role) {
                                            'admin' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                            'manager' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                            'cashier' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                            'accountant' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300'
                                        } }}">
                                        {{ ucfirst($role) }}
                                    </span>
                                @endforeach
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right flex justify-end gap-2 flex-wrap">
                                <a href="{{ route('users.edit', $user) }}"
                                   class="btn btn-outline text-xs flex items-center gap-1">
                                   <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit
                                </a>

                                <button @click="deleteUserId = {{ $user->id }}" type="button"
                                        class="btn btn-danger text-xs flex items-center gap-1">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🔹 Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- 🔸 Delete Confirmation Modal --}}
    <div x-show="deleteUserId" x-cloak
         class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-sm w-full">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Confirm Deletion
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Are you sure you want to delete this user? This action cannot be undone.
            </p>
            <div class="flex justify-end gap-3">
                <button @click="deleteUserId = null" class="btn btn-outline text-sm">Cancel</button>
                <form :action="`/users/${deleteUserId}`" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger text-sm">Delete</button>
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
