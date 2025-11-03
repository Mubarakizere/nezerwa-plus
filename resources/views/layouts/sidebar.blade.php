<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col
           bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm
           lg:static lg:translate-x-0 transition-transform duration-200 ease-out
           overflow-hidden"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    @click.away="if (window.innerWidth < 1024) sidebarOpen = false">

    <!-- Header -->
    <div class="flex-shrink-0 flex items-center justify-between px-4 py-4 border-b border-gray-100 dark:border-gray-700">
        <h1 class="text-lg font-bold text-indigo-600 tracking-tight">Stock Manager</h1>
        <button class="lg:hidden text-gray-500 hover:text-gray-700" @click="sidebarOpen = false">✕</button>
    </div>

    <!-- ✅ Scrollable Navigation -->
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        <nav class="px-3 py-3 space-y-1 text-gray-700 dark:text-gray-200">

            @php
                use Illuminate\Support\Facades\Auth;
                $user = Auth::user();
            @endphp

            {{-- 🔹 Dashboard --}}
            <x-sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-sidebar-link>

            {{-- 🔹 Admin --}}
            @if($user->hasRole('admin'))
                <x-sidebar-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')">Users</x-sidebar-link>
                <x-sidebar-link href="{{ route('roles.index') }}" :active="request()->routeIs('roles.*')">Roles</x-sidebar-link>
                <x-sidebar-link href="{{ route('categories.index') }}" :active="request()->routeIs('categories.*')">Categories</x-sidebar-link>
                <x-sidebar-link href="{{ route('products.index') }}" :active="request()->routeIs('products.*')">Products</x-sidebar-link>
                <x-sidebar-link href="{{ route('suppliers.index') }}" :active="request()->routeIs('suppliers.*')">Suppliers</x-sidebar-link>
                <x-sidebar-link href="{{ route('purchases.index') }}" :active="request()->routeIs('purchases.*')">Purchases</x-sidebar-link>
                <x-sidebar-link href="{{ route('sales.index') }}" :active="request()->routeIs('sales.*')">Sales</x-sidebar-link>
                <x-sidebar-link href="{{ route('transactions.index') }}" :active="request()->routeIs('transactions.*')">Transactions</x-sidebar-link>
                <x-sidebar-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers.*')">Customers</x-sidebar-link>
                <x-sidebar-link href="{{ route('loans.index') }}" :active="request()->routeIs('loans.*')">Loans</x-sidebar-link>
                <x-sidebar-link href="{{ route('debits-credits.index') }}" :active="request()->routeIs('debits-credits.*')">Debits & Credits</x-sidebar-link>
                <x-sidebar-link href="{{ route('stock.history') }}" :active="request()->routeIs('stock.history*')">Stock Movements</x-sidebar-link>
                <x-sidebar-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')">Reports</x-sidebar-link>
            @endif

            {{-- 🔹 Manager --}}
            @if($user->hasRole('manager'))
                <x-sidebar-link href="{{ route('sales.index') }}" :active="request()->routeIs('sales.*')">Sales</x-sidebar-link>
                <x-sidebar-link href="{{ route('products.index') }}" :active="request()->routeIs('products.*')">Products</x-sidebar-link>
                <x-sidebar-link href="{{ route('suppliers.index') }}" :active="request()->routeIs('suppliers.*')">Suppliers</x-sidebar-link>
                <x-sidebar-link href="{{ route('transactions.index') }}" :active="request()->routeIs('transactions.*')">Transactions</x-sidebar-link>
                <x-sidebar-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers.*')">Customers</x-sidebar-link>
                <x-sidebar-link href="{{ route('stock.history') }}" :active="request()->routeIs('stock.history*')">Stock Movements</x-sidebar-link>
            @endif

            {{-- 🔹 Cashier --}}
            @if($user->hasRole('cashier'))
                <x-sidebar-link href="{{ route('sales.index') }}" :active="request()->routeIs('sales.*')">Sales</x-sidebar-link>
                <x-sidebar-link href="{{ route('customers.index') }}" :active="request()->routeIs('customers.*')">Customers</x-sidebar-link>
                <x-sidebar-link href="{{ route('stock.history') }}" :active="request()->routeIs('stock.history*')">Stock Movements</x-sidebar-link>
            @endif

            {{-- 🔹 Accountant --}}
            @if($user->hasRole('accountant'))
                <x-sidebar-link href="{{ route('debits-credits.index') }}" :active="request()->routeIs('debits-credits.*')">Debits & Credits</x-sidebar-link>
                <x-sidebar-link href="{{ route('loans.index') }}" :active="request()->routeIs('loans.*')">Loans</x-sidebar-link>
                <x-sidebar-link href="{{ route('transactions.index') }}" :active="request()->routeIs('transactions.*')">Transactions</x-sidebar-link>
                <x-sidebar-link href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')">Reports</x-sidebar-link>
            @endif

            <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full text-left flex items-center px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-all">
                    <i data-lucide="log-out" class="w-4 h-4 mr-2 text-gray-400"></i>
                    Logout
                </button>
            </form>

            <p class="text-xs text-gray-400 mt-4 px-3 pb-4">
                Logged in as
                <span class="font-medium text-gray-700 dark:text-gray-200">
                    {{ ucfirst($user->getRoleNames()->first() ?? 'User') }}
                </span>
            </p>
        </nav>
    </div>
</aside>

<!--  Custom Scrollbar -->
<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.7);
}
</style>
