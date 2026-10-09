<aside
    class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col
           bg-slate-900 border-r border-slate-800 shadow-xl
           lg:static lg:translate-x-0 transition-transform duration-200 ease-out
           overflow-hidden"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    @click.away="if (window.innerWidth < 1024) sidebarOpen = false"
>
    {{-- Brand Header --}}
    <div class="flex-shrink-0 flex items-center justify-between px-4 py-4 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 flex-shrink-0">
                <i data-lucide="wine" class="w-4 h-4"></i>
            </div>
            <div class="min-w-0">
                <h1 class="text-sm font-semibold text-white tracking-tight truncate">
                    {{ config('app.name', 'KingWine') }}
                </h1>
                <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase">Inventory & POS</p>
            </div>
        </div>
        <button class="lg:hidden text-slate-400 hover:text-white p-1 rounded-md hover:bg-slate-800 transition" @click="sidebarOpen = false">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    {{-- Scrollable Navigation --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        <nav class="px-3 py-3 space-y-1">

            @php
                use Illuminate\Support\Facades\Auth;

                $user = Auth::user();
                $role = $user?->getRoleNames()->first() ?? 'user';

                // Auto-expand based on current route
                $salesActive = request()->routeIs('sales.*') || request()->routeIs('customers.*');
                $inventoryActive = request()->routeIs('products.*') || request()->routeIs('stock.history*');
                $purchasesActive = request()->routeIs('purchases.*') || request()->routeIs('suppliers.*');
                $financeActive = request()->routeIs('transactions.*')
                    || request()->routeIs('debits-credits.*')
                    || request()->routeIs('expenses.*')
                    || request()->routeIs('loans.*')
                    || request()->routeIs('item-loans.*');
                $reportsActive = request()->routeIs('reports.*');
                $settingsActive = request()->routeIs('users.*')
                    || request()->routeIs('roles.*')
                    || request()->routeIs('categories.*')
                    || request()->routeIs('payment-channels.*')
                    || request()->routeIs('system.reset');
            @endphp

            {{-- 🏠 Dashboard --}}
            @can('reports.view')
                <x-sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-slate-400 {{ request()->routeIs('dashboard') ? 'text-white' : '' }}"></i>
                    <span>Dashboard</span>
                </x-sidebar-link>
            @endcan

            {{-- 💰 Sales & Customers --}}
            @canany(['sales.view', 'customers.view'])
                <div x-data="{ open: {{ $salesActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="open ? 'text-white bg-slate-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/40'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="shopping-cart" class="w-4 h-4 text-slate-400" :class="open ? 'text-indigo-400' : ''"></i>
                            <span>Sales</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-white' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-slate-800 space-y-0.5 mt-1">
                        @can('sales.view')
                            <a href="{{ route('sales.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('sales.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="receipt" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('sales.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                All Sales
                            </a>
                        @endcan

                        @can('customers.view')
                            <a href="{{ route('customers.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('customers.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="users" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('customers.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Customers
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 📦 Inventory --}}
            @canany(['products.view', 'stock.view'])
                <div x-data="{ open: {{ $inventoryActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="open ? 'text-white bg-slate-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/40'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="package" class="w-4 h-4 text-slate-400" :class="open ? 'text-indigo-400' : ''"></i>
                            <span>Inventory</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-white' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-slate-800 space-y-0.5 mt-1">
                        @can('products.view')
                            <a href="{{ route('products.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('products.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="box" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('products.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Products
                            </a>
                        @endcan

                        @can('stock.view')
                            <a href="{{ route('stock.history') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('stock.history*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="history" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('stock.history*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Stock Movements
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 🛒 Purchases & Suppliers --}}
            @canany(['purchases.view', 'suppliers.view'])
                <div x-data="{ open: {{ $purchasesActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="open ? 'text-white bg-slate-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/40'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="shopping-bag" class="w-4 h-4 text-slate-400" :class="open ? 'text-indigo-400' : ''"></i>
                            <span>Purchases</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-white' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-slate-800 space-y-0.5 mt-1">
                        @can('purchases.view')
                            <a href="{{ route('purchases.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('purchases.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('purchases.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                All Purchases
                            </a>
                        @endcan

                        @can('suppliers.view')
                            <a href="{{ route('suppliers.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('suppliers.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="truck" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('suppliers.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Suppliers
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 💵 Finance --}}
            @canany(['transactions.view', 'debits-credits.view', 'loans.view'])
                <div x-data="{ open: {{ $financeActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="open ? 'text-white bg-slate-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/40'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="wallet" class="w-4 h-4 text-slate-400" :class="open ? 'text-indigo-400' : ''"></i>
                            <span>Finance</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-white' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-slate-800 space-y-0.5 mt-1">
                        @can('transactions.view')
                            <a href="{{ route('expenses.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('expenses.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="receipt" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('expenses.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Expenses
                            </a>

                            <a href="{{ route('transactions.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('transactions.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="activity" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('transactions.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Transactions
                            </a>
                        @endcan

                        @can('debits-credits.view')
                            <a href="{{ route('debits-credits.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('debits-credits.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="scale" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('debits-credits.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Debits & Credits
                            </a>
                        @endcan

                        @can('loans.view')
                            <a href="{{ route('loans.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('loans.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="hand-coins" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('loans.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Loans
                            </a>

                            <a href="{{ route('item-loans.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('item-loans.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="handshake" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('item-loans.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Inter-Company
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- 📊 Reports & Statements --}}
            @can('reports.view')
                <div x-data="{ open: {{ $reportsActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="open ? 'text-white bg-slate-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/40'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-slate-400" :class="open ? 'text-indigo-400' : ''"></i>
                            <span>Reports</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-white' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-slate-800 space-y-0.5 mt-1">
                        @if(Route::has('reports.index'))
                            <a href="{{ route('reports.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('reports.index') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="file-bar-chart" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('reports.index') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Overview
                            </a>
                        @endif

                        <a href="{{ route('reports.daily-collection') }}"
                           class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                  {{ request()->routeIs('reports.daily-collection*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                            <i data-lucide="calendar-check" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('reports.daily-collection*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                            Daily Collection
                        </a>

                        <a href="{{ route('reports.suppliers.statement') }}"
                           class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                  {{ request()->routeIs('reports.suppliers.statement') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                            <i data-lucide="truck" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('reports.suppliers.statement') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                            Supplier Statement
                        </a>

                        <a href="{{ route('reports.customers.statement') }}"
                           class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                  {{ request()->routeIs('reports.customers.statement') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                            <i data-lucide="users" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('reports.customers.statement') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                            Customer Statement
                        </a>

                        @can('audit.view')
                            <div class="border-t border-slate-800 my-1"></div>

                            <a href="{{ route('audit.financials') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('audit.financials') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('audit.financials') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Financials (Pro)
                            </a>

                            <a href="{{ route('audit.reconciliation') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('audit.reconciliation') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('audit.reconciliation') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Mistake Detection
                            </a>

                            <a href="{{ route('audit.logs') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('audit.logs') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="clipboard-list" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('audit.logs') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Audit Logs
                            </a>
                        @endcan
                    </div>
                </div>
            @endcan

            {{-- ⚙️ Settings --}}
            @canany(['users.view', 'roles.view', 'categories.view'])
                <div x-data="{ open: {{ $settingsActive ? 'true' : 'false' }} }" class="mt-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="open ? 'text-white bg-slate-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/40'">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="settings" class="w-4 h-4 text-slate-400" :class="open ? 'text-indigo-400' : ''"></i>
                            <span>Settings</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-white' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse class="ml-4 pl-3 border-l border-slate-800 space-y-0.5 mt-1">
                        @can('users.view')
                            <a href="{{ route('users.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('users.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="user" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('users.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Users
                            </a>
                        @endcan

                        @can('roles.view')
                            <a href="{{ route('roles.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('roles.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="shield" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('roles.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Roles
                            </a>
                        @endcan

                        @can('categories.view')
                            <a href="{{ route('categories.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('categories.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="folder" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('categories.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Categories
                            </a>
                        @endcan

                        @can('payment-channels.view')
                            <a href="{{ route('payment-channels.index') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('payment-channels.*') ? 'bg-indigo-600/15 text-indigo-300 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                                <i data-lucide="credit-card" class="w-3.5 h-3.5 mr-2 {{ request()->routeIs('payment-channels.*') ? 'text-indigo-400' : 'text-slate-400' }}"></i>
                                Payment Channels
                            </a>
                        @endcan

                        @if(Auth::user()->hasRole(['admin', 'boss']))
                            <div class="border-t border-slate-800 my-1"></div>
                            <a href="{{ route('system.reset') }}"
                               class="flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors
                                      {{ request()->routeIs('system.reset') ? 'bg-red-500/15 text-red-300 font-semibold' : 'text-slate-400 hover:text-red-400 hover:bg-red-500/10' }}">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 mr-2"></i>
                                System Reset
                            </a>
                        @endif
                    </div>
                </div>
            @endcanany

        </nav>
    </div>

    {{-- User Profile & Footer Bar --}}
    <div class="flex-shrink-0 p-3 border-t border-slate-800">
        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800/50 border border-slate-800">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-600/20 border border-indigo-500/30 text-indigo-300 flex items-center justify-center font-semibold text-xs flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-slate-200 truncate">{{ $user->name }}</p>
                    <p class="text-[10px] text-slate-400 capitalize tracking-wide">{{ $role }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                @csrf
                <button type="submit" title="Logout"
                        class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-md transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
        <p class="text-[10px] text-slate-500 text-center mt-2.5">
            v1.0.0 &bull; {{ config('app.name', 'KingWine') }}
        </p>
    </div>
</aside>

{{-- Custom Scrollbar Styles --}}
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(148, 163, 184, 0.2);
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(148, 163, 184, 0.35);
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background-color: transparent;
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
