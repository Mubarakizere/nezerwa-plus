<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Error') &mdash; {{ config('app.name', 'KingWine') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        :root {
            --bg-base: #f8fafc;
            --bg-card: #ffffff;
            --border-card: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #475569;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --accent-glow: rgba(79, 70, 229, 0.12);
        }

        html.dark {
            --bg-base: #0a0f1d;
            --bg-card: #111827;
            --border-card: #1f2937;
            --text-heading: #f8fafc;
            --text-body: #94a3b8;
            --text-muted: #64748b;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: rgba(99, 102, 241, 0.15);
            --accent-glow: rgba(99, 102, 241, 0.18);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        .bg-mesh {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image: 
                radial-gradient(at 15% 15%, var(--accent-glow) 0px, transparent 55%),
                radial-gradient(at 85% 85%, rgba(14, 165, 233, 0.1) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(236, 72, 153, 0.05) 0px, transparent 60%);
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            opacity: 0.035;
            background-size: 32px 32px;
            background-image: 
                linear-gradient(to right, currentColor 1px, transparent 1px),
                linear-gradient(to bottom, currentColor 1px, transparent 1px);
        }

        .main-container {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.25rem;
        }

        .error-card {
            width: 100%;
            max-width: 640px;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 1.5rem;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07), 0 0 1px 1px rgba(0, 0, 0, 0.02);
            text-align: center;
            position: relative;
            backdrop-filter: blur(8px);
        }

        html.dark .error-card {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            margin-bottom: 1.5rem;
        }

        .brand-pill svg {
            width: 14px;
            height: 14px;
        }

        .icon-wrapper {
            position: relative;
            width: 88px;
            height: 88px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1.25rem;
            background: var(--primary-light);
            color: var(--primary);
        }

        .icon-glow {
            position: absolute;
            inset: -8px;
            border-radius: 1.5rem;
            background: var(--primary);
            opacity: 0.2;
            filter: blur(14px);
            z-index: -1;
        }

        .status-badge {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            background: rgba(100, 116, 139, 0.12);
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .error-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            line-height: 1.25;
            margin-bottom: 0.75rem;
        }

        .error-description {
            font-size: 0.975rem;
            color: var(--text-body);
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        .actions-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px -2px rgba(79, 70, 229, 0.35);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            box-shadow: 0 6px 16px -2px rgba(79, 70, 229, 0.45);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-heading);
            border-color: var(--border-card);
        }

        .btn-secondary:hover {
            background: rgba(100, 116, 139, 0.08);
            border-color: var(--text-muted);
        }

        .quick-links {
            border-top: 1px solid var(--border-card);
            padding-top: 1.5rem;
            margin-top: 1rem;
        }

        .quick-links-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 0.875rem;
        }

        .quick-links-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
        }

        .quick-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-muted);
            background: rgba(100, 116, 139, 0.06);
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .quick-pill:hover {
            color: var(--primary);
            background: var(--primary-light);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .footer-bar {
            position: relative;
            z-index: 10;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
            font-size: 0.75rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(100, 116, 139, 0.1);
        }

        .theme-toggle-btn {
            background: transparent;
            border: 1px solid var(--border-card);
            padding: 0.35rem 0.65rem;
            border-radius: 0.5rem;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .theme-toggle-btn:hover {
            color: var(--text-heading);
            border-color: var(--primary);
        }

        @media (max-width: 640px) {
            .error-card {
                padding: 2rem 1.25rem;
                border-radius: 1.25rem;
            }
            .error-title {
                font-size: 1.5rem;
            }
            .actions-group {
                flex-direction: column;
                width: 100%;
            }
            .btn {
                width: 100%;
            }
            .footer-bar {
                flex-direction: column;
                gap: 0.75rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="bg-mesh"></div>
    <div class="bg-grid"></div>

    <main class="main-container">
        <div class="error-card">
            {{-- Brand Badge --}}
            <div class="brand-pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span>{{ config('app.name', 'KingWine') }} &bull; Inventory &amp; POS</span>
            </div>

            {{-- Status Badge --}}
            <div>
                <span class="status-badge">HTTP @yield('code', 'ERROR')</span>
            </div>

            {{-- Icon Graphic --}}
            <div class="icon-wrapper">
                <div class="icon-glow"></div>
                @yield('icon')
            </div>

            {{-- Main Headings --}}
            <h1 class="error-title">@yield('headline', View::getSection('title', 'An Unexpected Error Occurred'))</h1>
            <p class="error-description">
                @yield('message', 'We ran into an unexpected obstacle while handling your request.')
            </p>

            {{-- Action Buttons --}}
            <div class="actions-group">
                @section('actions')
                    <a href="{{ url('/') }}" class="btn btn-primary" id="btn-go-dashboard">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Return to Dashboard
                    </a>

                    <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/') }}'" class="btn btn-secondary" id="btn-go-back">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"/>
                            <polyline points="12 19 5 12 12 5"/>
                        </svg>
                        Previous Page
                    </button>

                    <button onclick="window.location.reload()" class="btn btn-secondary" id="btn-reload">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                            <path d="M16 21h5v-5"/>
                        </svg>
                        Refresh
                    </button>
                @show
            </div>

            {{-- Quick Links (Only if authenticated or safe) --}}
            <div class="quick-links">
                <div class="quick-links-title">Quick Workspace Destinations</div>
                <div class="quick-links-grid">
                    <a href="{{ route('dashboard') }}" class="quick-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('sales.index') }}" class="quick-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                        Sales
                    </a>
                    <a href="{{ route('products.index') }}" class="quick-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        Products
                    </a>
                    <a href="{{ route('customers.index') }}" class="quick-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Customers
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-bar">
        <div>
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'KingWine') }}. All rights reserved.</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span>Ref: <code>{{ request()->ip() }} &bull; {{ date('H:i:s T') }}</code></span>
            <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                <span id="theme-text">Theme</span>
            </button>
        </div>
    </footer>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                html.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
</body>
</html>
