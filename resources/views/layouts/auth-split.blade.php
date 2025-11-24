<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KingWine') }}</title>

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
<body class="h-full">
    <div class="min-h-full flex">
        
        <!-- Left Side: Branding & Visuals -->
        <div class="hidden lg:flex flex-1 relative bg-indigo-900 text-white overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-indigo-900 opacity-90"></div>
            <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop" 
                 alt="Background" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-20">
            
            <div class="relative z-10 flex flex-col justify-between p-12 w-full">
                <div>
                    <div class="flex items-center gap-3">
                        <!-- Logo Icon -->
                        <div class="bg-white/10 p-2 rounded-lg backdrop-blur-sm">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold tracking-tight">KingWine</span>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <blockquote class="text-2xl font-medium leading-relaxed text-indigo-100">
                        "Manage your stock, track sales, and grow your business with confidence. The premium solution for modern commerce."
                    </blockquote>
                    <div class="flex items-center gap-4">
                        <div class="flex -space-x-2">
                            <img class="inline-block h-10 w-10 rounded-full ring-2 ring-indigo-900" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=64&h=64" alt=""/>
                            <img class="inline-block h-10 w-10 rounded-full ring-2 ring-indigo-900" src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=64&h=64" alt=""/>
                            <img class="inline-block h-10 w-10 rounded-full ring-2 ring-indigo-900" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=64&h=64" alt=""/>
                        </div>
                        <div class="text-indigo-200 text-sm font-medium">
                            Trusted by 500+ businesses
                        </div>
                    </div>
                </div>
                
                <div class="text-indigo-300 text-xs">
                    &copy; {{ date('Y') }} KingWine. All rights reserved.
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-gray-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                {{ $slot }}
            </div>
        </div>
        
    </div>
</body>
</html>
