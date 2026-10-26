@extends('layouts.app')
@section('title', 'Data Integrity & Reconciliation')

@section('content')
<style>[x-cloak]{display:none!important}</style>
@php
    $issueCount    = count($issues ?? []);
    $criticalCount = collect($issues)->where('severity', 'critical')->count();
    $highCount     = collect($issues)->where('severity', 'high')->count();
    $mediumCount   = collect($issues)->where('severity', 'medium')->count();

    // Categorized counts
    $salesIssuesCount = collect($issues)->filter(fn($i) => str_contains($i['type'], 'Sale'))->count();
    $loanIssuesCount  = collect($issues)->filter(fn($i) => str_contains($i['type'], 'Loan'))->count();
    $stockIssuesCount = collect($issues)->filter(fn($i) => str_contains($i['type'], 'Stock'))->count();

    // Health rating calculation
    $healthScore = max(0, 100 - ($criticalCount * 15) - ($highCount * 8) - ($mediumCount * 3));
@endphp

<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeSeverity: 'all', activeCategory: 'all' }">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('reports.index') }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Back to Reports Overview">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                    <i data-lucide="shield-alert" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    <span>Data Integrity &amp; Reconciliation Audit</span>
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $issueCount === 0 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/60' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $issueCount === 0 ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse' }}"></span>
                    <span>{{ $issueCount === 0 ? 'All Systems Healthy' : $issueCount . ' Issues Flagged' }}</span>
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Automated background scanner detecting payment mismatches, overpaid invoices, loan status errors, and negative inventory balances.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('audit.financials') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="landmark" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Financial Overview</span>
            </a>

            @if(Route::has('audit.index'))
                <a href="{{ route('audit.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="history" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Audit Trail Logs</span>
                </a>
            @endif

            <button type="button"
                    onclick="window.location.reload()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                <span>Re-Scan System</span>
            </button>
        </div>
    </div>

    {{-- System Health KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Flagged Discrepancies --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Discrepancies</span>
                <div class="w-8 h-8 rounded-lg {{ $issueCount === 0 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600' }} flex items-center justify-center flex-shrink-0">
                    <i data-lucide="{{ $issueCount === 0 ? 'check-circle-2' : 'alert-circle' }}" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight font-mono {{ $issueCount === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $issueCount }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $issueCount === 0 ? 'Zero mismatches found' : 'Discrepancies needing review' }}
                </div>
            </div>
        </div>

        {{-- Critical / High Severity --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">High &amp; Critical</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                    {{ $criticalCount + $highCount }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Stock deficits &amp; unpaid invoices
                </div>
            </div>
        </div>

        {{-- Medium Warnings --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Warnings &amp; Status</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="info" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                    {{ $mediumCount }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Overpaid sales &amp; status flags
                </div>
            </div>
        </div>

        {{-- Integrity Health Score --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Integrity Score</span>
                <div class="w-8 h-8 rounded-lg {{ $healthScore >= 90 ? 'bg-emerald-500/10 text-emerald-600' : ($healthScore >= 70 ? 'bg-amber-500/10 text-amber-600' : 'bg-rose-500/10 text-rose-600') }} flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold tracking-tight font-mono {{ $healthScore >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($healthScore >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                    {{ $healthScore }}%
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Automated ledger confidence index
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Filter Toolbar --}}
    @if($issueCount > 0)
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs flex flex-wrap items-center justify-between gap-4">
        {{-- Category Pills --}}
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                <i data-lucide="layers" class="w-3.5 h-3.5"></i> Category:
            </span>
            <button type="button"
                    @click="activeCategory = 'all'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeCategory === 'all' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700'">
                All Categories ({{ $issueCount }})
            </button>
            <button type="button"
                    @click="activeCategory = 'sale'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeCategory === 'sale' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700'">
                Sales Ledger ({{ $salesIssuesCount }})
            </button>
            <button type="button"
                    @click="activeCategory = 'loan'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeCategory === 'loan' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700'">
                Loans &amp; Debt ({{ $loanIssuesCount }})
            </button>
            <button type="button"
                    @click="activeCategory = 'stock'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeCategory === 'stock' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700'">
                Inventory Stock ({{ $stockIssuesCount }})
            </button>
        </div>

        {{-- Severity Filter --}}
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs font-medium text-slate-400 mr-1 flex items-center gap-1">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i> Severity:
            </span>
            <button type="button"
                    @click="activeSeverity = 'all'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeSeverity === 'all' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                All
            </button>
            <button type="button"
                    @click="activeSeverity = 'critical'"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeSeverity === 'critical' ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40'">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>Critical</span>
            </button>
            <button type="button"
                    @click="activeSeverity = 'high'"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeSeverity === 'high' ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40'">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>High</span>
            </button>
            <button type="button"
                    @click="activeSeverity = 'medium'"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-colors"
                    :class="activeSeverity === 'medium' ? 'bg-amber-600 text-white shadow-2xs' : 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40'">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span>Medium</span>
            </button>
        </div>
    </div>
    @endif

    {{-- Main Issues List / Feed --}}
    @if($issueCount > 0)
        <div class="space-y-3">
            @foreach($issues as $issue)
                @php
                    $sev = $issue['severity'];
                    $type = $issue['type'];
                    $cat = str_contains($type, 'Sale') ? 'sale' : (str_contains($type, 'Loan') ? 'loan' : 'stock');
                @endphp
                <div x-show="(activeSeverity === 'all' || activeSeverity === '{{ $sev }}') && (activeCategory === 'all' || activeCategory === '{{ $cat }}')"
                     x-transition
                     class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-4 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5
                            {{ $sev === 'critical' ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/20' : ($sev === 'high' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400') }}">
                            @if($sev === 'critical')
                                <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                            @elseif($sev === 'high')
                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            @else
                                <i data-lucide="info" class="w-4 h-4"></i>
                            @endif
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    {{ $issue['type'] }}
                                </h3>

                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider
                                    {{ $sev === 'critical' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' : ($sev === 'high' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/40' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40') }}">
                                    {{ ucfirst($sev) }}
                                </span>

                                <span class="text-xs font-mono text-slate-400">
                                    Ref: #{{ $issue['item_id'] }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 font-medium">
                                {{ $issue['message'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:self-center self-end flex-shrink-0">
                        @if(!empty($issue['link']) && $issue['link'] !== '#')
                            <a href="{{ $issue['link'] }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 border border-indigo-200/60 dark:border-indigo-800/40 rounded-lg transition-colors">
                                <span>Inspect &amp; Resolve</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        @else
                            <span class="text-[11px] text-slate-400 italic">Audit record only</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Celebration / All Clear State --}}
        <div class="bg-white dark:bg-slate-900 border border-emerald-500/20 dark:border-emerald-500/30 rounded-2xl p-10 text-center shadow-2xs">
            <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4 border border-emerald-500/20">
                <i data-lucide="shield-check" class="w-8 h-8"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                All Ledgers Reconciled &amp; In Balance
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                No discrepancies detected across completed sales invoices, debt collections, active loans, or inventory stock levels.
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <a href="{{ route('audit.financials') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <i data-lucide="landmark" class="w-3.5 h-3.5"></i>
                    <span>View Financial Overview</span>
                </a>
                <a href="{{ route('reports.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="bar-chart-3" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Reports Dashboard</span>
                </a>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
@endsection
