@extends('layouts.app')
@section('title', 'Users Management')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $currentRole = request('role');
@endphp

<div class="max-w-7xl mx-auto space-y-6" x-data="{ deleteUserId: null, deleteUserName: '' }">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="users" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Users &amp; Team Management</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($users->total()) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Manage team accounts, POS cashiers, administrative privileges, and security role assignments.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('roles.view')
                <a href="{{ route('roles.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Manage Roles</span>
                </a>
            @endcan

            <a href="{{ route('users.create') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New User</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Team Members --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Accounts</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($stats['total_users'] ?? $users->total()) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active team member credentials</div>
            </div>
        </div>

        {{-- Administrators --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Administrators</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400 font-mono">
                    {{ $fmt0($stats['admins'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Full administrative privilege</div>
            </div>
        </div>

        {{-- Cashiers & Operators --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cashiers / POS</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($stats['cashiers'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Point of Sale &amp; Register staff</div>
            </div>
        </div>

        {{-- Roles Configured --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Access Roles</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="key-round" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-sky-600 dark:text-sky-400 font-mono">
                    {{ $fmt0($stats['roles_count'] ?? count($roles)) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Permission profiles defined</div>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4">
        
        {{-- Quick Role Segmented Pills --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="shield" class="w-3.5 h-3.5"></i> Role:
                </span>
                <a href="{{ route('users.index', array_merge(request()->except('page', 'role'), ['role' => null])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ !$currentRole ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All Roles
                </a>
                @foreach($roles as $roleName)
                    <a href="{{ route('users.index', array_merge(request()->except('page', 'role'), ['role' => $roleName])) }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentRole === $roleName ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        <span>{{ ucfirst($roleName) }}</span>
                    </a>
                @endforeach
            </div>

            @if(request('search'))
                <div class="text-xs text-slate-400 font-mono">
                    Keyword: <strong class="text-slate-700 dark:text-slate-300">"{{ request('search') }}"</strong>
                </div>
            @endif
        </div>

        {{-- Filter Inputs Form --}}
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-7">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Search Name or Email
                </label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by user name, email..."
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Filter by Role
                </label>
                <select name="role"
                        class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <option value="">All Roles</option>
                    @foreach($roles as $roleOption)
                        <option value="{{ $roleOption }}" @selected(request('role') === $roleOption)>
                            {{ ucfirst($roleOption) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2 flex items-center gap-1.5">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('users.index') }}"
                   class="p-1.5 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                   title="Reset Filters">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Main Users Table --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                    Active Team Members &amp; Credentials
                </h2>
                <span class="text-xs text-slate-400">
                    (Page {{ $users->currentPage() }} of {{ $users->lastPage() }})
                </span>
            </div>

            <span class="text-xs text-slate-500 dark:text-slate-400">
                Showing <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $users->count() }}</strong> accounts
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4"># ID</th>
                        <th class="py-3 px-4">User Profile</th>
                        <th class="py-3 px-4">Email Address</th>
                        <th class="py-3 px-4">Assigned Role</th>
                        <th class="py-3 px-4">Joined Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($users as $user)
                        @php
                            $imagePath = null;
                            if ($user->photo && file_exists(public_path('storage/' . $user->photo))) {
                                $imagePath = asset('storage/' . $user->photo);
                            } elseif ($user->profile_image && file_exists(public_path('storage/' . $user->profile_image))) {
                                $imagePath = asset('storage/' . $user->profile_image);
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- ID --}}
                            <td class="py-3 px-4 font-mono font-bold text-slate-400">
                                #{{ $user->id }}
                            </td>

                            {{-- Profile + Name --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if($imagePath)
                                        <img src="{{ $imagePath }}"
                                             alt="{{ $user->name }}"
                                             class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=ffffff&background=4f46e5'">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $user->name }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            ID: {{ $user->id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $user->email }}</span>
                                </div>
                            </td>

                            {{-- Role --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                @forelse($user->getRoleNames() as $rName)
                                    @php
                                        $roleBadgeClasses = match($rName) {
                                            'admin'      => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60 dot-bg-rose-500',
                                            'manager'    => 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60 dot-bg-indigo-500',
                                            'cashier'    => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60 dot-bg-emerald-500',
                                            'accountant' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60 dot-bg-amber-500',
                                            default      => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dot-bg-slate-400',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $roleBadgeClasses }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ str_contains($roleBadgeClasses, 'dot-bg-rose') ? 'bg-rose-500' : (str_contains($roleBadgeClasses, 'dot-bg-emerald') ? 'bg-emerald-500' : (str_contains($roleBadgeClasses, 'dot-bg-indigo') ? 'bg-indigo-500' : (str_contains($roleBadgeClasses, 'dot-bg-amber') ? 'bg-amber-500' : 'bg-slate-400'))) }}"></span>
                                        <span>{{ ucfirst($rName) }}</span>
                                    </span>
                                @empty
                                    <span class="text-slate-400 italic">No role assigned</span>
                                @endforelse
                            </td>

                            {{-- Created Date --}}
                            <td class="py-3 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ optional($user->created_at)->format('M d, Y') ?? '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                       title="Edit User">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>

                                    @if(auth()->id() !== $user->id)
                                        <button type="button"
                                                @click="deleteUserId = {{ $user->id }}; deleteUserName = '{{ addslashes($user->name) }}'"
                                                class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                                title="Delete User">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                                        <i data-lucide="users" class="w-6 h-6"></i>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No users found</h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        No team accounts match your active search or role criteria.
                                    </p>
                                    <a href="{{ route('users.create') }}"
                                       class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Create First User</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="px-5 py-3 border-t border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteUserId"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
         @keydown.escape.window="deleteUserId = null">
        <div @click.outside="deleteUserId = null"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 w-full max-w-md transform transition-all">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                        Confirm User Deletion
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Are you sure you want to permanently revoke access and delete user account <strong class="text-slate-800 dark:text-slate-200" x-text="deleteUserName"></strong>? This action cannot be undone.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2.5">
                <button type="button"
                        class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                        @click="deleteUserId = null">
                    Cancel
                </button>
                <form :action="`/users/${deleteUserId}`" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors">
                        Yes, Delete Account
                    </button>
                </form>
            </div>
        </div>
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
