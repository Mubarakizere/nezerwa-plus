@extends('layouts.app')
@section('title', 'Roles Management')

@section('content')
<div
    x-data="{ deleteRoleId: null, showDrawer: false, selectedRole: null }"
    @keydown.escape.window="deleteRoleId = null; showDrawer = false"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
>


    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="shield" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                <span>Roles Management</span>
            </h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Control who can access what in the system. Click a role to preview its users and permissions.
            </p>
        </div>

        <a href="{{ route('roles.create') }}" class="btn btn-primary flex items-center gap-2 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New Role
        </a>
    </div>

    {{-- Search / filters --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm space-y-3">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-3">
                <label class="form-label text-xs">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ $query ?? request('q') }}"
                        placeholder="Search by role name, permission or user..."
                        class="form-input w-full pl-9 pr-9"
                    >
                    @if(($query ?? request('q')) !== null && ($query ?? request('q')) !== '')
                        <a href="{{ route('roles.index') }}"
                           class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                           title="Clear search">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="md:col-span-1 flex gap-2">
                <button type="submit" class="btn btn-secondary w-full flex items-center justify-center gap-1">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply</span>
                </button>
            </div>
        </form>

        @if(($query ?? request('q')) !== null && ($query ?? request('q')) !== '')
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Showing results for:
                <span class="font-semibold text-gray-700 dark:text-gray-200">“{{ $query ?? request('q') }}”</span>
            </p>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 text-xs font-medium uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Role Name</th>
                        <th class="px-4 py-3 text-left">Users</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-4 py-3 text-gray-500">
                                {{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}
                            </td>

                            {{-- Clickable role badge (opens drawer) --}}
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    @click="
                                        selectedRole = {
                                            id: {{ $role->id }},
                                            name: '{{ ucfirst($role->name) }}',
                                            created_at: '{{ $role->created_at?->format('Y-m-d') ?? '—' }}',
                                            users: @js($role->users->pluck('name')),
                                            permissions: @js($role->permissions->pluck('name')),
                                        };
                                        showDrawer = true;
                                    "
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition
                                        {{ match($role->name) {
                                            'admin' => 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300',
                                            'manager' => 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300',
                                            'cashier' => 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/40 dark:text-green-300',
                                            'accountant' => 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/40 dark:text-yellow-300',
                                            default => 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-900/40 dark:text-gray-300'
                                        } }}"
                                >
                                    {{ ucfirst($role->name) }}
                                    @if(in_array($role->name, ['admin','manager']))
                                        <span class="ml-1 text-[10px] uppercase tracking-wide text-gray-500">
                                            system
                                        </span>
                                    @endif
                                </button>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $role->users_count ?? 0 }}
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $role->created_at?->format('Y-m-d') ?? '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2 flex-wrap">
                                    <a href="{{ route('roles.edit', $role) }}"
                                       class="btn btn-outline text-xs flex items-center gap-1"
                                       title="Edit this role">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit
                                    </a>

                                    @if(!in_array($role->name, ['admin', 'manager']))
                                        <button
                                            @click="deleteRoleId = {{ $role->id }}"
                                            type="button"
                                            class="btn btn-danger text-xs flex items-center gap-1"
                                            title="Delete this role">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                <i data-lucide="shield-off" class="w-5 h-5 inline text-gray-400 mr-1"></i>
                                No roles found.
                                <a href="{{ route('roles.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Create one now.
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($roles instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-4">
            {{ $roles->onEachSide(1)->links() }}
        </div>
    @endif

    {{-- Delete Modal --}}
    <div
        x-show="deleteRoleId"
        x-cloak
        x-transition.opacity.duration.150ms
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
    >
        <div
            x-transition.scale.duration.200ms
            class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-sm w-full"
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Confirm Deletion
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Are you sure you want to delete this role? Users with this role will lose their assigned access.
            </p>

            <div class="flex justify-end gap-3">
                <button @click="deleteRoleId = null" class="btn btn-outline text-sm">Cancel</button>
                <form :action="`{{ url('roles') }}/${deleteRoleId}`" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger text-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Role Preview Drawer --}}
    <div
        x-show="showDrawer"
        x-cloak
        class="fixed inset-0 z-50 flex justify-end"
        x-transition.opacity.duration.200ms
    >
        {{-- Overlay --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDrawer = false"></div>

        {{-- Drawer Panel --}}
        <div
            x-show="showDrawer"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="relative bg-white dark:bg-gray-800 w-full sm:w-96 shadow-xl h-full overflow-y-auto rounded-l-xl"
        >
            <div class="p-6 space-y-5">
                {{-- Drawer Header --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1">
                            <i data-lucide="shield" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                            <span x-text="selectedRole?.name"></span>
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Created: <span x-text="selectedRole?.created_at"></span>
                        </p>
                    </div>
                    <button @click="showDrawer = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Users --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-1">
                        <i data-lucide="users" class="w-4 h-4 text-indigo-500"></i>
                        Assigned Users (<span x-text="selectedRole?.users?.length"></span>)
                    </h3>
                    <template x-if="selectedRole?.users?.length">
                        <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <template x-for="user in selectedRole.users" :key="user">
                                <li x-text="user"></li>
                            </template>
                        </ul>
                    </template>
                    <template x-if="!selectedRole?.users?.length">
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No users assigned.</p>
                    </template>
                </div>

                {{-- Permissions --}}
                <div>
                    <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-1">
                        <i data-lucide="key" class="w-4 h-4 text-indigo-500"></i>
                        Permissions (<span x-text="selectedRole?.permissions?.length"></span>)
                    </h3>
                    <template x-if="selectedRole?.permissions?.length">
                        <ul class="text-sm text-gray-700 dark:text-gray-300 grid grid-cols-1 gap-1">
                            <template x-for="perm in selectedRole.permissions" :key="perm">
                                <li class="flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                                    <span x-text="perm.replace('.', ' → ')"></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                    <template x-if="!selectedRole?.permissions?.length">
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No permissions assigned.</p>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
document.addEventListener('alpine:init', () => {
    // Recreate icons when Alpine updates DOM (drawer, modal, etc.)
    Alpine.effect(() => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
});
</script>
@endpush
@endsection
