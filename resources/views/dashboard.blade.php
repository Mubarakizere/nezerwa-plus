<x-app-layout>
    {{-- Include Header + Quick Actions --}}
    @include('dashboard._header')

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        {{-- Include KPI, Charts, and Insights --}}
        @includeWhen($sections['kpis'] ?? false, 'dashboard._kpis')
        @includeWhen($sections['charts'] ?? false, 'dashboard._charts')
        @includeWhen($sections['insights'] ?? false, 'dashboard._insights')
    </div>
</x-app-layout>
