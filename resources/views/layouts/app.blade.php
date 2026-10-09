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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'KingWine'))</title>

    @vite(['resources/css/app.css','resources/css/theme.css','resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4F46E5">

    {{-- ✅ PWA Essentials --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/icons/icon-192x192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KingWine">
    <link rel="apple-touch-startup-image" href="{{ asset('images/icons/icon-512x512.png') }}">

    {{-- Prevent white flash + hide Google banner --}}
    <style>
        [x-cloak]{display:none!important}
        html.dark body{background:#0f172a;color:#f8fafc}

        /* ===== Google Translate cleanup ===== */
        .goog-te-banner-frame,
        .goog-te-banner-frame.skiptranslate,
        #goog-gt-tt,
        .goog-te-balloon-frame,
        .VIpgJd-ZVi9od-ORHb-OEVmcd { display: none !important; }

        /* Google injects top offset on html/body; force reset */
        html, body { top: 0 !important; }

        /* Hide default gadget chrome (we use our own dropdown) */
        .goog-te-gadget { color: transparent !important; }
        .goog-te-gadget .goog-te-combo { margin:0 !important; }
        .goog-logo-link, .goog-te-gadget span { display:none !important; }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans antialiased transition-colors duration-300"
      x-data="{ sidebarOpen: window.innerWidth >= 1024 }"
      @resize.window="sidebarOpen = window.innerWidth >= 1024">
    <x-loader />

{{-- 🔔 Offline Banner --}}
<div id="connection-status"
     class="hidden fixed top-0 inset-x-0 z-[9999] bg-red-600 text-white text-center py-2 text-sm font-semibold shadow-md">
    ⚠️ You’re offline. Some actions will be synced later.
</div>

<div class="flex h-screen overflow-hidden">

    {{-- Overlay (mobile) --}}
    <div class="fixed inset-0 z-40 bg-black/50 lg:hidden"
         x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- ====== MAIN SECTION ====== --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- ====== HEADER ====== --}}
        <header class="sticky top-0 z-30 flex items-center justify-between h-14 px-4 lg:px-6 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm border-b border-slate-200/80 dark:border-slate-800 transition-colors">
            {{-- Left: Mobile Toggle & Page Title Breadcrumb --}}
            <div class="flex items-center gap-3 min-w-0">
                <button class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 lg:hidden p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        @click="sidebarOpen = true"
                        aria-label="Open navigation sidebar">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-xs font-medium text-slate-400 hidden sm:inline-block">KingWine</span>
                    <span class="text-slate-300 dark:text-slate-600 hidden sm:inline-block">/</span>
                    <h1 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-slate-100 tracking-tight truncate">
                        @yield('title', 'Dashboard')
                    </h1>
                </div>
            </div>

            {{-- Center: Sleek Command Palette / Search Trigger --}}
            <div class="hidden md:flex items-center">
                <button type="button"
                        @click="$dispatch('open-command-palette')"
                        class="flex items-center gap-2 px-3 py-1.5 text-xs text-slate-400 bg-slate-100/80 dark:bg-slate-800/60 hover:bg-slate-200/70 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300 border border-slate-200/80 dark:border-slate-700/60 rounded-lg transition-colors">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Search or jump to...</span>
                    <kbd class="ml-2 px-1.5 py-0.5 text-[10px] font-medium text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-700/60 rounded border border-slate-200 dark:border-slate-600 shadow-2xs">Ctrl K</kbd>
                </button>
            </div>

            {{-- Right: Status, Language, Dark Mode, Profile --}}
            <div class="flex items-center gap-2 sm:gap-2.5">
                {{-- Live Connection Dot --}}
                <div id="connection-indicator" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="connection-text">Online</span>
                </div>

                {{-- Language Switcher --}}
                <div class="relative" x-data="{ open: false }" @keydown.escape="open = false" @click.away="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-1.5 px-2 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            title="Change language">
                        <i data-lucide="globe" class="w-4 h-4 text-slate-400"></i>
                        <span id="lang-badge" class="hidden sm:inline uppercase font-semibold text-[11px] text-slate-700 dark:text-slate-300">EN</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-1.5 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1 z-50">
                        <button type="button" class="w-full text-left px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors"
                                @click="window.__setLanguage('en'); open = false">
                            <span>English</span>
                            <span class="text-[10px] text-slate-400 font-mono">EN</span>
                        </button>
                        <button type="button" class="w-full text-left px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors"
                                @click="window.__setLanguage('fr'); open = false">
                            <span>Français</span>
                            <span class="text-[10px] text-slate-400 font-mono">FR</span>
                        </button>
                        <button type="button" class="w-full text-left px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors"
                                @click="window.__setLanguage('rw'); open = false">
                            <span>Kinyarwanda</span>
                            <span class="text-[10px] text-slate-400 font-mono">RW</span>
                        </button>
                    </div>
                </div>

                {{-- Dark Mode Toggle --}}
                <button
                    @click="
                        const isDark = document.documentElement.classList.toggle('dark');
                        localStorage.theme = isDark ? 'dark' : 'light';
                    "
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Toggle dark mode">
                    <i data-lucide="moon" class="w-4 h-4 dark:hidden"></i>
                    <i data-lucide="sun" class="w-4 h-4 hidden dark:block text-amber-400"></i>
                </button>

                <div class="h-4 w-px bg-slate-200 dark:bg-slate-800 mx-0.5"></div>

                {{-- User Dropdown --}}
                <div class="relative" x-data="{ userMenu: false }" @click.away="userMenu = false">
                    <button @click="userMenu = !userMenu"
                            class="flex items-center gap-2 p-1 pl-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        @php
                            $user = Auth::user();
                            $photo = $user?->photo ? asset('storage/' . $user->photo) : null;
                        @endphp

                        @if($photo)
                            <img src="{{ $photo }}" alt="{{ $user->name }}"
                                 class="w-7 h-7 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                        @else
                            <div class="w-7 h-7 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-semibold text-xs">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif

                        <span class="hidden sm:block text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[120px]">
                            {{ $user->name ?? 'User' }}
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150" :class="userMenu ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="userMenu" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-1.5 w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1.5 z-50">
                        <div class="px-3.5 py-2 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $user->name }}</p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $user->email }}</p>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                Profile & Settings
                            </a>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- ====== MAIN CONTENT ====== --}}
        <main class="relative z-10 flex-1 overflow-y-auto p-4 lg:p-6 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>

{{-- ✅ Toast Alerts --}}
@if(session('success') || session('error') || session('warning') || session('info'))
    <x-alert-toast />
@endif

{{-- ✅ Lucide Icons --}}
<script type="module">
import { createIcons, icons } from "https://cdn.jsdelivr.net/npm/lucide@0.454.0/+esm";
document.addEventListener("DOMContentLoaded", () => createIcons({ icons }));
</script>

<x-confirm-delete />
@stack('scripts')

{{-- ===== Hidden Google Translate mount point ===== --}}
<div id="google_translate_element" class="absolute -z-50 opacity-0 pointer-events-none"></div>

{{-- ✅ Google Translate: init + helpers --}}
<script>
/**
 * Initialize Google Translate with the three languages we want.
 * pageLanguage uses the current Laravel locale to hint the source.
 */
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: "{{ str_replace('_','-', app()->getLocale()) ?: 'en' }}",
        includedLanguages: 'en,fr,rw',
        autoDisplay: false
    }, 'google_translate_element');

    // Restore saved language after widget injects the <select>
    const saved = localStorage.getItem('gt_lang') || 'en';
    setTimeout(() => window.__setLanguage(saved, /*silent*/ true), 400);
}

/**
 * Programmatically change Google Translate language and persist to localStorage.
 * Also update the top-nav badge.
 */
window.__setLanguage = function(lang, silent = false) {
    try {
        const combo = document.querySelector('#google_translate_element select.goog-te-combo');
        if (!combo) {
            return setTimeout(() => window.__setLanguage(lang, silent), 200);
        }
        if (combo.value !== lang) {
            combo.value = lang;
            combo.dispatchEvent(new Event('change'));
        }
        localStorage.setItem('gt_lang', lang);
        const badge = document.getElementById('lang-badge');
        if (badge) badge.textContent = (lang || 'en').toUpperCase();
        if (!silent && window.createIcons) { /* no-op */ }
    } catch(e) { console.error('Translate switch failed', e); }
};
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

{{-- 🚫 Remove/guard the blue top banner reliably --}}
<script>
(function () {
  function killBar() {
    const banner = document.querySelector('.goog-te-banner-frame');
    if (banner) banner.style.display = 'none';
    const balloon = document.querySelector('.VIpgJd-ZVi9od-ORHb-OEVmcd');
    if (balloon) balloon.style.display = 'none';
    document.documentElement.style.top = '0px';
    document.body.style.top = '0px';
  }

  // Run now, on load, on resize and whenever DOM mutates (Google injects later)
  killBar();
  window.addEventListener('load', killBar, { passive: true });
  window.addEventListener('resize', killBar, { passive: true });
  new MutationObserver(killBar).observe(document.documentElement, { childList: true, subtree: true });

  // Also run right after we switch languages via the custom dropdown
  const prev = window.__setLanguage;
  window.__setLanguage = function(lang, silent) {
    if (typeof prev === 'function') prev(lang, silent);
    setTimeout(killBar, 120);
  };
})();
</script>

{{-- ✅ PWA Service Worker --}}
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(reg => console.log('✅ Service Worker registered:', reg.scope))
            .catch(err => console.error('❌ Service Worker registration failed:', err));
    });
}
</script>

{{-- 🍎 iPhone Install Tip (shows only once) --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    const hasSeenTip = localStorage.getItem('pwa_tip_seen');

    if (isIOS && !isStandalone && !hasSeenTip) {
        const banner = document.createElement('div');
        banner.innerHTML = `
            <div class="fixed bottom-5 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2">
                <i data-lucide='download' class='w-4 h-4'></i>
                <span>Install this app: Tap <strong>Share</strong> → <strong>Add to Home Screen</strong></span>
                <button id='closeTip' class='ml-3 text-white/70 hover:text-white text-xs'>×</button>
            </div>
        `;
        document.body.appendChild(banner);
        document.getElementById('closeTip').addEventListener('click', () => {
            banner.remove();
            localStorage.setItem('pwa_tip_seen', 'true');
        });
        if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
    }
});
</script>

<x-command-palette />
</body>
</html>
