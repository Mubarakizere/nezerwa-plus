<x-app-layout>
    <div x-data="dashboardCustomizer()" x-init="init()" class="max-w-7xl mx-auto space-y-6">
        
        {{-- Top Overview Bar (In page flow, not sticky, strictly behind top navigation) --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                    Dashboard Overview
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Welcome back, <span class="font-medium text-slate-700 dark:text-slate-200">{{ Auth::user()->name }}</span>. Real-time operations and inventory snapshot.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                {{-- Date Pill --}}
                <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>{{ now()->format('l, M j, Y') }}</span>
                </div>

                @if(isset($permissions) && (
                    ($permissions['sales'] ?? false) || 
                    ($permissions['purchases'] ?? false) || 
                    ($permissions['loans'] ?? false) || 
                    ($permissions['expenses'] ?? false)
                ))
                {{-- Customize View Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" 
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800/70 transition-colors">
                        <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Customize View</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-1.5 w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-2 z-20">
                        <div class="px-3.5 py-1 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                            Visible Sections
                        </div>
                        @if($permissions['sales'] ?? false)
                        <label class="flex items-center px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300 transition-colors">
                            <input type="checkbox" x-model="showKpis" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2.5">Key Performance Indicators</span>
                        </label>
                        @endif
                        @if(($permissions['sales'] ?? false) || ($permissions['purchases'] ?? false))
                        <label class="flex items-center px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300 transition-colors">
                            <input type="checkbox" x-model="showCharts" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2.5">Performance Charts</span>
                        </label>
                        @endif
                        @if(($permissions['sales'] ?? false) && (($permissions['products'] ?? false) || ($permissions['customers'] ?? false)))
                        <label class="flex items-center px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300 transition-colors">
                            <input type="checkbox" x-model="showInsights" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2.5">Insights & Top Performers</span>
                        </label>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Permission Warning (if any) --}}
        @if(!isset($permissions) || empty(array_filter($permissions)))
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-6 text-center">
                <i data-lucide="lock" class="w-10 h-10 mx-auto text-amber-500 mb-2.5"></i>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1">No Dashboard Access</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400">You don't have permissions to view any dashboard sections. Please contact your administrator.</p>
            </div>
        @endif

        {{-- Sections --}}
        @if($permissions['sales'] ?? false)
        <div x-show="showKpis" x-collapse>
            @include('dashboard._kpis')
        </div>
        @endif
        
        @if(($permissions['sales'] ?? false) || ($permissions['purchases'] ?? false))
        <div x-show="showCharts" x-collapse>
            @include('dashboard._charts')
        </div>
        @endif
        
        @if(($permissions['sales'] ?? false) && (($permissions['products'] ?? false) || ($permissions['customers'] ?? false)))
        <div x-show="showInsights" x-collapse>
            @include('dashboard._insights')
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function dashboardCustomizer() {
            return {
                showKpis: JSON.parse(localStorage.getItem('dash_kpis') ?? 'true'),
                showCharts: JSON.parse(localStorage.getItem('dash_charts') ?? 'true'),
                showInsights: JSON.parse(localStorage.getItem('dash_insights') ?? 'true'),

                init() {
                    this.$watch('showKpis', v => localStorage.setItem('dash_kpis', v));
                    this.$watch('showCharts', v => localStorage.setItem('dash_charts', v));
                    this.$watch('showInsights', v => localStorage.setItem('dash_insights', v));
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
