<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      x-init="
          if (localStorage.theme === 'dark') document.documentElement.classList.add('dark');
          else document.documentElement.classList.remove('dark');
          $watch(() => localStorage.theme, v => document.documentElement.classList.toggle('dark', v === 'dark'));
      ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>@yield('title', 'POS Terminal | ' . config('app.name', 'KingWine'))</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4F46E5">

    {{-- PWA Essentials --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" href="/favicon.ico">

    {{-- Assets --}}
    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }
        html.dark body { background: #090d16; color: #f1f5f9; }
        
        /* Custom scrollbars for POS columns */
        .pos-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .pos-scroll::-webkit-scrollbar-track {
            background: rgba(100, 116, 139, 0.05);
        }
        .pos-scroll::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.25);
            border-radius: 9999px;
        }
        .pos-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.45);
        }

        /* Keyboard hint badge */
        .kbd-badge {
            font-size: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            padding: 2px 5px;
            border-radius: 4px;
            background: rgba(148, 163, 184, 0.2);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: inherit;
        }
    </style>
    @stack('styles')
</head>

<body class="h-screen w-screen overflow-hidden flex flex-col bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans antialiased transition-colors select-none"
      x-data="posNavigation()">

    {{-- POS Header Bar --}}
    <header class="h-14 px-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between shrink-0 z-30">
        {{-- Left: Logo, Brand & Status --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group" title="Return to Dashboard">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-1.5 leading-none">
                        <span class="font-bold text-slate-900 dark:text-white text-base tracking-tight">King Wine</span>
                        <span class="px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/50">POS</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">Quick Checkout Station</span>
                </div>
            </a>

            <div class="hidden sm:flex items-center gap-2 ml-4 pl-4 border-l border-slate-200 dark:border-slate-800">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Terminal Active
                </span>
            </div>
        </div>

        {{-- Center: Live Clock & Date --}}
        <div class="hidden md:flex flex-col items-center justify-center text-center">
            <div class="text-sm font-semibold tracking-wide text-slate-800 dark:text-slate-200" x-text="currentTime"></div>
            <div class="text-[11px] text-slate-400" x-text="currentDate"></div>
        </div>

        {{-- Right: Cashier, Controls & Exit --}}
        <div class="flex items-center gap-2 sm:gap-3">
            {{-- Dark / Light toggle --}}
            <button type="button"
                    @click="toggleTheme()"
                    class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Toggle Theme">
                <i data-lucide="moon" class="w-4 h-4 hidden dark:block"></i>
                <i data-lucide="sun" class="w-4 h-4 block dark:hidden"></i>
            </button>

            {{-- Fullscreen button --}}
            <button type="button"
                    @click="toggleFullscreen()"
                    class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors hidden sm:block"
                    title="Toggle Fullscreen (F11)">
                <i data-lucide="maximize" class="w-4 h-4" x-show="!isFullscreen"></i>
                <i data-lucide="minimize" class="w-4 h-4" x-show="isFullscreen" x-cloak></i>
            </button>

            {{-- Keyboard shortcuts helper --}}
            <button type="button"
                    @click="$dispatch('open-shortcuts-modal')"
                    class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors hidden sm:flex items-center gap-1.5 text-xs font-medium"
                    title="Keyboard Shortcuts (F1)">
                <i data-lucide="help-circle" class="w-4 h-4"></i>
                <span class="kbd-badge">F1</span>
            </button>

            {{-- Cashier Badge --}}
            <div class="flex items-center gap-2 pl-2 border-l border-slate-200 dark:border-slate-800">
                <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center font-bold text-xs text-indigo-600 dark:text-indigo-400">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="hidden lg:block text-left">
                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight">
                        {{ auth()->user()->name ?? 'Cashier' }}
                    </div>
                    <div class="text-[10px] text-slate-400 capitalize">
                        {{ auth()->user()->roles->first()->name ?? 'User' }}
                    </div>
                </div>
            </div>

            {{-- Exit to Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="ml-1 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20 transition-colors"
               title="Exit to Dashboard">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">Exit</span>
            </a>
        </div>
    </header>

    {{-- Main Workspace (Fill remaining screen) --}}
    <main class="flex-1 overflow-hidden">
        @yield('content')
    </main>

    {{-- Global POS Toast / Notification component --}}
    <div x-data="{
             toasts: [],
             add(toast) {
                 const id = Date.now();
                 this.toasts.push({ id, ...toast });
                 setTimeout(() => {
                     this.toasts = this.toasts.filter(t => t.id !== id);
                 }, toast.duration || 3500);
             }
         }"
         @pos-toast.window="add($event.detail)"
         class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="pointer-events-auto p-3.5 rounded-xl shadow-xl flex items-center gap-3 border text-sm"
                 :class="{
                     'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-100': !t.type || t.type === 'info',
                     'bg-emerald-600 text-white border-emerald-500 shadow-emerald-500/20': t.type === 'success',
                     'bg-rose-600 text-white border-rose-500 shadow-rose-500/20': t.type === 'error',
                     'bg-amber-600 text-white border-amber-500 shadow-amber-500/20': t.type === 'warning'
                 }">
                <i :data-lucide="t.icon || (t.type === 'success' ? 'check-circle-2' : (t.type === 'error' ? 'alert-triangle' : 'info'))" class="w-5 h-5 shrink-0"></i>
                <div class="flex-1">
                    <div class="font-bold text-xs" x-show="t.title" x-text="t.title"></div>
                    <div class="text-xs opacity-95 leading-snug" x-text="t.message"></div>
                </div>
            </div>
        </template>
    </div>

    {{-- Header script for clock and fullscreen --}}
    <script>
        function posNavigation() {
            return {
                currentTime: '',
                currentDate: '',
                isFullscreen: false,
                init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });
                },
                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    this.currentDate = now.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
                },
                toggleTheme() {
                    const current = localStorage.theme === 'dark' ? 'light' : 'dark';
                    localStorage.theme = current;
                    if (current === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },
                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        }
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
