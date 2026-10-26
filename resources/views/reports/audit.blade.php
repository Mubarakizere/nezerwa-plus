@extends('layouts.app')
@section('title', 'Audit Trail Logs')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $currentAction = request('action');
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('reports.index') }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Back to Reports Overview">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="history" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Audit Trail &amp; System Logs</span>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                    {{ $fmt0($logs->total()) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Security journal capturing user actions, record additions, attribute modifications, and deletions.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('audit.financials') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="landmark" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Financial Overview</span>
            </a>

            @if(Route::has('audit.reconciliation'))
                <a href="{{ route('audit.reconciliation') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 rounded-lg shadow-2xs hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span>Reconciliation Audit</span>
                </a>
            @endif
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Audit Records --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Audit Events</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                    {{ $fmt0($stats['total_logs'] ?? $logs->total()) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Immutable audit events logged</div>
            </div>
        </div>

        {{-- Record Additions --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Creations (Insertions)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $fmt0($stats['creates'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">New business objects recorded</div>
            </div>
        </div>

        {{-- Modifications / Updates --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modifications (Updates)</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-sky-600 dark:text-sky-400 font-mono">
                    {{ $fmt0($stats['updates'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Entity modifications tracked</div>
            </div>
        </div>

        {{-- Deletions --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Deletions (Removed)</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                    {{ $fmt0($stats['deletes'] ?? 0) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Records removed from database</div>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs space-y-4">
        
        {{-- Quick Action Segmented Pills --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i> Action Type:
                </span>
                <a href="{{ route('audit.logs', array_merge(request()->except('page', 'action'), ['action' => null])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ !$currentAction ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    All Actions
                </a>
                <a href="{{ route('audit.logs', array_merge(request()->except('page', 'action'), ['action' => 'create'])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentAction === 'create' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Create</span>
                </a>
                <a href="{{ route('audit.logs', array_merge(request()->except('page', 'action'), ['action' => 'update'])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentAction === 'update' ? 'bg-sky-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    <span>Update</span>
                </a>
                <a href="{{ route('audit.logs', array_merge(request()->except('page', 'action'), ['action' => 'delete'])) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ $currentAction === 'delete' ? 'bg-rose-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    <span>Delete</span>
                </a>
            </div>

            @if(request('date'))
                <div class="text-xs text-slate-400 font-mono">
                    Filtered Date: <strong class="text-slate-700 dark:text-slate-300">{{ request('date') }}</strong>
                </div>
            @endif
        </div>

        {{-- Filter Inputs Form --}}
        <form method="GET" action="{{ route('audit.logs') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            {{-- User Select --}}
            <div class="lg:col-span-4">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    User / Operator
                </label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <select name="user_id"
                            class="w-full pl-9 pr-8 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="">All Users / Cashiers</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((int)request('user_id') === (int)$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Subject / Entity Type --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Entity / Subject Type
                </label>
                <input type="text" name="subject_type" value="{{ request('subject_type') }}" placeholder="Sale, Product, Loan..."
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            {{-- Specific Date --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Event Date
                </label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <div class="lg:col-span-2 flex items-center gap-1.5">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-white transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('audit.logs') }}"
                   class="p-1.5 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                   title="Reset Filters">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            @if(request('action'))
                <input type="hidden" name="action" value="{{ request('action') }}">
            @endif
        </form>
    </div>

    {{-- Main Logs Table --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                    Audit Event History
                </h2>
                <span class="text-xs text-slate-400">
                    (Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }})
                </span>
            </div>

            <span class="text-xs text-slate-500 dark:text-slate-400">
                Displaying <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $logs->count() }}</strong> entries
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Timestamp &amp; IP</th>
                        <th class="py-3 px-4">User / Operator</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Subject Entity</th>
                        <th class="py-3 px-4">Attribute Changes / Modification Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($logs as $log)
                        @php
                            $actionClasses = match($log->action) {
                                'create' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
                                'update' => 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800/60',
                                'delete' => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60',
                                default  => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $subjectClass = class_basename($log->subject_type);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- Timestamp & IP --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-mono font-medium text-slate-800 dark:text-slate-200">
                                    {{ $log->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-[11px] font-mono text-slate-400 flex items-center gap-1.5 mt-0.5">
                                    <span>{{ $log->created_at->format('H:i:s') }}</span>
                                    @if($log->ip_address)
                                        <span>• {{ $log->ip_address }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- User / Operator --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                        {{ strtoupper(substr($log->user->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                            {{ $log->user->name ?? 'System Automated' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Action --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $actionClasses }}">
                                    @if($log->action === 'create')
                                        <i data-lucide="plus" class="w-3 h-3"></i>
                                    @elseif($log->action === 'update')
                                        <i data-lucide="pencil" class="w-3 h-3"></i>
                                    @else
                                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                                    @endif
                                    <span>{{ ucfirst($log->action) }}</span>
                                </span>
                            </td>

                            {{-- Subject Entity --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $subjectClass }}
                                </span>
                                @if($log->subject_id)
                                    <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400 ml-1">
                                        #{{ $log->subject_id }}
                                    </span>
                                @endif
                            </td>

                            {{-- Changes Payload --}}
                            <td class="py-3 px-4 text-xs">
                                @if($log->action === 'update')
                                    @php
                                        $newVals = $log->new_values ?? [];
                                        $oldVals = $log->old_values ?? [];
                                    @endphp
                                    <div class="flex flex-wrap gap-1.5 max-w-xl">
                                        @forelse($newVals as $key => $val)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60">
                                                <strong class="font-medium text-slate-500">{{ $key }}:</strong>
                                                <span class="font-mono font-semibold">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                            </span>
                                        @empty
                                            <span class="text-slate-400 italic">No specific values captured</span>
                                        @endforelse
                                    </div>
                                @elseif($log->action === 'create')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">
                                        New {{ $subjectClass }} record initialized
                                    </span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-medium">
                                        Record permanently purged from database
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                                        <i data-lucide="history" class="w-6 h-6"></i>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No audit logs found</h3>
                                    <p class="text-xs text-slate-400 mt-1">
                                        No system changes recorded matching your active filter criteria.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="px-5 py-3 border-t border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900">
                {{ $logs->links() }}
            </div>
        @endif
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
