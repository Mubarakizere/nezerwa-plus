<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white dark:bg-[#0f172a]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KingWine') }}</title>

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-white dark:bg-[#0f172a] text-gray-900 dark:text-gray-100 antialiased">
    <x-loader />
    <div class="min-h-full flex">
        
        <!-- Left Side: Clean Brand Panel -->
        <div class="hidden lg:flex flex-1 relative text-white overflow-hidden"
             style="background: radial-gradient(circle at 25% 25%, #312e81 0%, #1e1b4b 55%, #0f172a 100%);">
            
            {{-- Subtle geometric accent lines (clean, minimalist) --}}
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none"
                 style="background-image: linear-gradient(to right, #ffffff 1px, transparent 1px), linear-gradient(to bottom, #ffffff 1px, transparent 1px); background-size: 40px 40px;">
            </div>

            <div class="relative z-10 flex flex-col justify-between p-12 xl:p-16 w-full">
                {{-- Brand Header --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 border border-indigo-400/20 flex items-center justify-center text-indigo-200">
                        <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-xl font-semibold tracking-tight text-white">{{ config('app.name', 'KingWine') }}</span>
                </div>
                
                {{-- Clean Workspace Statement --}}
                <div class="max-w-md space-y-3">
                    <h2 class="text-2xl xl:text-3xl font-semibold text-white tracking-tight leading-snug">
                        Inventory &amp; Sales Management
                    </h2>
                    <p class="text-sm text-indigo-200/75 leading-relaxed">
                        Real-time stock tracking, sales reporting, and point-of-sale operations in one unified workspace.
                    </p>
                </div>
                
                {{-- Understated Footer --}}
                <div class="text-xs text-indigo-300/40">
                    &copy; {{ date('Y') }} {{ config('app.name', 'KingWine') }}. All rights reserved.
                </div>
            </div>
        </div>

        <!-- Right Side: Clean Login Form Container -->
        <div class="flex-1 flex flex-col justify-center py-12 px-6 sm:px-12 lg:flex-none lg:w-[480px] xl:w-[540px] bg-white dark:bg-[#0f172a]">
            <div class="mx-auto w-full max-w-sm">
                {{-- Mobile Brand Header (only visible on mobile screens where left split is hidden) --}}
                <div class="lg:hidden flex items-center gap-2.5 mb-8">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ config('app.name', 'KingWine') }}</span>
                </div>

                {{ $slot }}
            </div>
        </div>
        
    </div>
</body>
</html>
