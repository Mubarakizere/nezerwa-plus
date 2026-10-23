<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Error') — {{ config('app.name', 'KingWine') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    @stack('head')
    <script>
        (() => {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        /* ── Design tokens ─────────────────────────────── */
        :root {
            --c-bg:        #f4f6fb;
            --c-surface:   #ffffff;
            --c-border:    #e4e9f0;
            --c-text:      #1e293b;
            --c-muted:     #64748b;
            --c-faint:     #94a3b8;

            /* per-page accent — default indigo */
            --accent:      #4f46e5;
            --accent-2:    #6366f1;
            --accent-bg:   rgba(79, 70, 229, 0.08);
            --accent-ring: rgba(79, 70, 229, 0.2);
            --accent-glow: rgba(99, 102, 241, 0.25);
        }

        html.dark {
            --c-bg:      #09101e;
            --c-surface: #111827;
            --c-border:  #1e2c3f;
            --c-text:    #f1f5f9;
            --c-muted:   #94a3b8;
            --c-faint:   #475569;

            --accent-bg:   rgba(99, 102, 241, 0.12);
            --accent-ring: rgba(99, 102, 241, 0.3);
            --accent-glow: rgba(99, 102, 241, 0.3);
        }

        /* ── Reset ─────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--c-bg);
            color: var(--c-text);
            min-height: 100vh;
            display: grid;
            grid-template-rows: 1fr auto;
            overflow-x: hidden;
            transition: background 0.2s, color 0.2s;
        }

        /* ── Ambient background ─────────────────────────── */
        .scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .scene-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
        }
        html.dark .scene-blob { opacity: 0.22; }

        .blob-1 {
            width: 600px; height: 600px;
            top: -180px; left: -150px;
            background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
            animation: drift 14s ease-in-out infinite alternate;
        }
        .blob-2 {
            width: 420px; height: 420px;
            bottom: -100px; right: -80px;
            background: radial-gradient(circle, var(--accent-2) 0%, transparent 70%);
            animation: drift 18s ease-in-out infinite alternate-reverse;
        }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(30px, 20px) scale(1.05); }
        }

        .scene-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,0,0,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,0.04) 1px, transparent 1px);
            background-size: 36px 36px;
        }
        html.dark .scene-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        }

        /* ── Main layout ────────────────────────────────── */
        .page-center {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem;
        }

        /* ── Card ───────────────────────────────────────── */
        .card {
            width: 100%;
            max-width: 600px;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 20px;
            padding: 2.75rem 2.5rem;
            box-shadow:
                0 1px 2px rgba(0,0,0,0.04),
                0 8px 24px rgba(0,0,0,0.06),
                0 32px 64px rgba(0,0,0,0.04);
            text-align: center;
        }
        html.dark .card {
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04),
                0 8px 32px rgba(0,0,0,0.4),
                0 32px 80px rgba(0,0,0,0.3);
        }

        /* ── Brand strip ────────────────────────────────── */
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--accent);
            background: var(--accent-bg);
            border: 1px solid var(--accent-ring);
            padding: 5px 14px;
            border-radius: 100px;
            margin-bottom: 1.75rem;
        }
        .brand svg { width: 13px; height: 13px; flex-shrink: 0; }

        /* ── Illustration wrapper ───────────────────────── */
        .illus-wrap {
            position: relative;
            width: 96px;
            height: 96px;
            margin: 0 auto 1.5rem;
        }
        .illus-glow {
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            background: var(--accent);
            opacity: 0.18;
            filter: blur(18px);
            animation: pulse-glow 3s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.18; transform: scale(1); }
            50%       { opacity: 0.28; transform: scale(1.08); }
        }
        .illus-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 1.5px solid var(--accent-ring);
            animation: spin-slow 12s linear infinite;
        }
        @keyframes spin-slow {
            to { transform: rotate(360deg); }
        }
        .illus-icon {
            position: relative;
            z-index: 1;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: var(--accent-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }
        .illus-icon svg { width: 42px; height: 42px; }

        /* ── Code badge ─────────────────────────────────── */
        .code-badge {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
            background: var(--accent-bg);
            border: 1px solid var(--accent-ring);
            padding: 3px 10px;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        /* ── Headings & body ────────────────────────────── */
        .err-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--c-text);
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-bottom: 0.6rem;
        }
        .err-msg {
            font-size: 0.95rem;
            color: var(--c-muted);
            line-height: 1.65;
            margin-bottom: 2rem;
            max-width: 440px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Buttons ────────────────────────────────────── */
        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }
        .btn:active { transform: scale(0.97); }
        .btn svg { width: 15px; height: 15px; flex-shrink: 0; }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 14px -2px var(--accent-glow);
        }
        .btn-primary:hover {
            filter: brightness(1.1);
            box-shadow: 0 6px 20px -2px var(--accent-glow);
        }
        .btn-ghost {
            background: transparent;
            color: var(--c-muted);
            border: 1px solid var(--c-border);
        }
        .btn-ghost:hover {
            color: var(--c-text);
            background: rgba(100,116,139,0.07);
            border-color: var(--c-muted);
        }

        /* ── Quick links ────────────────────────────────── */
        .divider {
            border: none;
            border-top: 1px solid var(--c-border);
            margin: 0 0 1.5rem;
        }
        .ql-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--c-faint);
            margin-bottom: 0.75rem;
        }
        .ql-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
        }
        .ql-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--c-muted);
            background: rgba(100,116,139,0.06);
            border: 1px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.14s ease;
        }
        .ql-pill svg { width: 12px; height: 12px; }
        .ql-pill:hover {
            color: var(--accent);
            background: var(--accent-bg);
            border-color: var(--accent-ring);
        }

        /* ── Footer bar ─────────────────────────────────── */
        footer.foot {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 720px;
            margin: 0 auto;
            width: 100%;
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(100,116,139,0.12);
            font-size: 0.73rem;
            color: var(--c-faint);
        }
        .foot-meta code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
        }
        .theme-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            font-size: 0.72rem;
            font-weight: 600;
            font-family: inherit;
            color: var(--c-faint);
            background: transparent;
            border: 1px solid var(--c-border);
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .theme-btn:hover { color: var(--c-text); border-color: var(--accent); }
        .theme-btn svg { width: 12px; height: 12px; }

        /* ── Responsive ─────────────────────────────────── */
        @media (max-width: 600px) {
            .card { padding: 2rem 1.25rem; border-radius: 16px; }
            .err-title { font-size: 1.5rem; }
            .actions { flex-direction: column; width: 100%; }
            .btn { width: 100%; justify-content: center; }
            footer.foot { flex-direction: column; gap: 0.5rem; text-align: center; }
        }
    </style>
</head>
<body>
    {{-- Ambient background --}}
    <div class="scene" aria-hidden="true">
        <div class="scene-grid"></div>
        <div class="scene-blob blob-1"></div>
        <div class="scene-blob blob-2"></div>
    </div>

    <main class="page-center">
        <div class="card" role="main">

            {{-- Brand pill --}}
            <div>
                <span class="brand">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2v-4M9 21H5a2 2 0 0 1-2-2v-4m0 0h18"/>
                    </svg>
                    {{ config('app.name', 'KingWine') }}
                </span>
            </div>

            {{-- Illustration --}}
            <div class="illus-wrap" aria-hidden="true">
                <div class="illus-glow"></div>
                <div class="illus-ring"></div>
                <div class="illus-icon">
                    @yield('icon')
                </div>
            </div>

            {{-- Code badge --}}
            <div>
                <span class="code-badge">HTTP @yield('code', 'ERR')</span>
            </div>

            {{-- Title & message --}}
            <h1 class="err-title">@yield('title', 'Something went wrong')</h1>
            <p class="err-msg">@yield('message', 'We hit an unexpected snag. Try going back or heading to the dashboard.')</p>

            @stack('after-message')

            {{-- Actions --}}
            <div class="actions">
                @section('actions')
                    <a href="{{ url('/') }}" class="btn btn-primary" id="btn-go-dashboard">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Back to Dashboard
                    </a>
                    <button onclick="window.history.length > 1 ? window.history.back() : (window.location.href = '{{ url('/') }}')" class="btn btn-ghost" id="btn-go-back">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        Go Back
                    </button>
                @show
            </div>

            {{-- Quick links --}}
            <hr class="divider">
            <p class="ql-label">Jump to</p>
            <div class="ql-row">
                <a href="{{ route('dashboard') }}" class="ql-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('sales.index') }}" class="ql-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    Sales
                </a>
                <a href="{{ route('products.index') }}" class="ql-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    Products
                </a>
                <a href="{{ route('customers.index') }}" class="ql-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Customers
                </a>
                <a href="{{ route('pos.index') }}" class="ql-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                    POS
                </a>
            </div>

        </div>
    </main>

    <footer class="foot">
        <span class="foot-meta">
            &copy; {{ date('Y') }} {{ config('app.name', 'KingWine') }}
            &nbsp;&bull;&nbsp;
            <code>{{ request()->ip() }} &bull; {{ date('H:i T') }}</code>
        </span>
        <button class="theme-btn" onclick="toggleTheme()" title="Toggle theme" id="btn-toggle-theme">
            <svg id="ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
            </svg>
            <svg id="ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
            <span id="theme-label">Light</span>
        </button>
    </footer>

    <script>
        function syncThemeBtn() {
            const dark = document.documentElement.classList.contains('dark');
            document.getElementById('ico-sun').style.display  = dark ? 'none' : 'block';
            document.getElementById('ico-moon').style.display = dark ? 'block' : 'none';
            document.getElementById('theme-label').textContent = dark ? 'Dark' : 'Light';
        }
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            syncThemeBtn();
        }
        syncThemeBtn();
    </script>
    @stack('scripts')
</body>
</html>
