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
    <title>@yield('title', config('app.name', 'Stock Manager'))</title>

    @vite(['resources/css/app.css','resources/css/theme.css','resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4F46E5">

    {{-- ✅ PWA Essentials --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/icons/icon-192x192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Stock Management">
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
    <div class="fixed inset-0 z-30 bg-black/50 lg:hidden"
         x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- ====== MAIN SECTION ====== --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- ====== HEADER ====== --}}
        <header class="flex items-center justify-between bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm px-4 lg:px-6 py-3">
            <div class="flex items-center gap-3">
                <button class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 lg:hidden" @click="sidebarOpen = true">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100 tracking-tight">
                    @yield('title', 'Dashboard')
                </h1>
            </div>

            <div class="flex items-center gap-4">
                {{-- 🌙 Dark Mode Toggle --}}
                <button
                    @click="
                        const isDark = document.documentElement.classList.toggle('dark');
                        localStorage.theme = isDark ? 'dark' : 'light';
                    "
                    class="w-9 h-9 flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    title="Toggle dark mode">
                    <i data-lucide="moon" class="w-4 h-4 text-gray-600 dark:hidden"></i>
                    <i data-lucide="sun" class="w-4 h-4 text-yellow-400 hidden dark:block"></i>
                </button>

                {{-- 🌐 Connection Indicator --}}
                <div id="connection-indicator" class="hidden sm:flex items-center gap-1 text-sm font-medium">
                    <svg id="wifi-icon" xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5 13a10 10 0 0114 0M8.5 16.5a5 5 0 017 0M12 20h.01" />
                    </svg>
                    <span id="connection-text" class="text-green-600 dark:text-green-400">Online</span>
                </div>

                {{-- 🌍 Language Switcher (Google Translate) --}}
                <div class="relative" x-data="{ open:false }" @keydown.escape="open=false" @click.away="open=false">
                    <button @click="open=!open"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            title="Change language">
                        <i data-lucide="languages" class="w-4 h-4 text-gray-500"></i>
                        <span id="lang-badge" class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300">EN</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                    </button>

                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-1 z-50">
                        <button type="button" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="window.__setLanguage('en'); open=false">English</button>
                        <button type="button" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="window.__setLanguage('fr'); open=false">Français</button>
                        <button type="button" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="window.__setLanguage('rw'); open=false">Kinyarwanda</button>
                        <div class="mt-1 px-4 pb-2 text-[10px] text-gray-400">Powered by Google Translate</div>
                    </div>
                </div>

                {{-- 👤 User Dropdown --}}
                <div class="relative" x-data="{ userMenu: false }" @click.away="userMenu = false">
                    <button @click="userMenu = !userMenu"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        @php
                            $user = Auth::user();
                            $photo = $user?->photo ? asset('storage/' . $user->photo) : null;
                        @endphp

                        @if($photo)
                            <img src="{{ $photo }}" alt="{{ $user->name }}"
                                 class="w-8 h-8 rounded-full object-cover border border-gray-300 dark:border-gray-600">
                        @else
                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                <span class="text-indigo-700 dark:text-indigo-300 text-sm font-semibold">
                                    {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                        @endif

                        <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300 truncate max-w-[120px]">
                            {{ $user->name ?? 'User' }}
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                    </button>

                    <div x-show="userMenu" x-cloak x-transition
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-1 z-50">
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i data-lucide="user" class="w-4 h-4 inline mr-2"></i> Profile
                        </a>
                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700">
                                <i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- ====== MAIN CONTENT ====== --}}
        <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
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
