<x-app-layout>
    <div x-data="dashboardCustomizer()" x-init="init()" class="min-h-screen">
        {{-- Header + Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                    Dashboard
                </h2>
                
                <div class="flex items-center gap-3">
                    {{-- Customize Button --}}
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" 
                                class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i data-lucide="layout" class="w-4 h-4"></i>
                            Customize
                        </button>
                        
                        <div x-show="open" x-transition 
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg py-2 z-50">
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Visible Sections
                            </div>
                            <label class="flex items-center px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input type="checkbox" x-model="showKpis" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">KPIs</span>
                            </label>
                            <label class="flex items-center px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input type="checkbox" x-model="showCharts" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Charts</span>
                            </label>
                            <label class="flex items-center px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                <input type="checkbox" x-model="showInsights" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Insights</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            {{-- Sections with transitions --}}
            <div x-show="showKpis" x-collapse>
                @include('dashboard._kpis')
            </div>
            
            <div x-show="showCharts" x-collapse>
                @include('dashboard._charts')
            </div>
            
            <div x-show="showInsights" x-collapse>
                @include('dashboard._insights')
            </div>
        </div>
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
