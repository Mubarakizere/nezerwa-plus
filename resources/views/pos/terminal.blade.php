@extends('layouts.pos')

@section('title', 'POS Terminal | ' . config('app.name', 'KingWine'))

@section('content')
<div x-data="posApp({
        initialProducts: {{ Js::from($products) }},
        categories: {{ Js::from($categories) }},
        customers: {{ Js::from($customers) }},
        suppliers: {{ Js::from($suppliers) }},
        paymentChannels: {{ Js::from($paymentChannels) }},
        checkoutUrl: '{{ route('pos.checkout') }}',
        csrfToken: '{{ csrf_token() }}'
     })"
     @keydown.window="handleGlobalKeydown($event)"
     class="h-full w-full flex overflow-hidden">

    {{-- =========================================================================
     | PANEL 1: CATEGORIES & QUICK FILTERS (Left Sidebar)
     |======================================================================== --}}
    <aside class="w-56 shrink-0 bg-white/70 dark:bg-slate-900/60 backdrop-blur-sm border-r border-slate-200/80 dark:border-slate-800 flex flex-col justify-between hidden md:flex">
        {{-- Categories List --}}
        <div class="p-3 flex-1 overflow-y-auto pos-scroll space-y-1">
            <div class="px-2 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                Categories
            </div>

            {{-- All Products Pill --}}
            <button type="button"
                    @click="selectedCategory = null"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all"
                    :class="selectedCategory === null
                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
                <span class="flex items-center gap-2">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                    <span>All Items</span>
                </span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold"
                      :class="selectedCategory === null ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                      x-text="products.length">
                </span>
            </button>

            {{-- Quick Filter: In Stock Only --}}
            <button type="button"
                    @click="toggleInStockOnly()"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all"
                    :class="inStockOnly
                        ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
                <span class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                    <span>In Stock Only</span>
                </span>
                <span class="w-2 h-2 rounded-full" :class="inStockOnly ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"></span>
            </button>

            {{-- Divider --}}
            <div class="my-2 border-t border-slate-200/80 dark:border-slate-800"></div>

            {{-- Individual Category Pills --}}
            <template x-for="cat in categories" :key="cat.id">
                <button type="button"
                        @click="selectedCategory = cat.id"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="selectedCategory === cat.id
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60'">
                    <span class="truncate text-left" x-text="cat.name"></span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold"
                          :class="selectedCategory === cat.id ? 'bg-white/20 text-white' : 'bg-slate-200/60 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                          x-text="countCategoryProducts(cat.id)">
                    </span>
                </button>
            </template>
        </div>

        {{-- Held Orders Bar --}}
        <div class="p-3 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50" x-show="heldOrders.length > 0" x-cloak>
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-500 flex items-center gap-1">
                    <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                    Held Carts (<span x-text="heldOrders.length"></span>)
                </span>
            </div>
            <div class="space-y-1">
                <template x-for="(held, idx) in heldOrders" :key="held.id">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs">
                        <div class="truncate">
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="held.name || ('Cart #' + (idx + 1))"></span>
                            <div class="text-[10px] text-slate-400" x-text="formatMoney(held.total) + ' RWF • ' + held.items.length + ' items'"></div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="restoreOrder(held.id)" class="p-1 rounded bg-amber-500 text-white hover:bg-amber-600" title="Resume">
                                <i data-lucide="play" class="w-3 h-3"></i>
                            </button>
                            <button type="button" @click="discardHeldOrder(held.id)" class="p-1 rounded text-rose-500 hover:bg-rose-500/10" title="Delete">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </aside>

    {{-- =========================================================================
     | PANEL 2: PRODUCT SEARCH & GRID (Center Area)
     |======================================================================== --}}
    <section class="flex-1 flex flex-col min-w-0 bg-slate-50/50 dark:bg-slate-950/40">
        {{-- Search & Mobile Category Bar --}}
        <div class="p-3 sm:p-4 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 flex flex-col gap-2.5 shrink-0">
            <div class="flex items-center gap-2 sm:gap-3">
                {{-- Search Bar --}}
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text"
                           x-ref="searchInput"
                           x-model="searchQuery"
                           @keydown.enter.prevent="handleSearchEnter()"
                           placeholder="Search products by name or scan barcode... (Press / or F2)"
                           autocomplete="off"
                           class="w-full pl-10 pr-24 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700/80 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    
                    {{-- Quick Action / Shortcut hint --}}
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                        <button type="button"
                                x-show="searchQuery"
                                @click="searchQuery = ''; $refs.searchInput.focus()"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                        <span class="kbd-badge hidden sm:inline-block">/</span>
                    </div>
                </div>

                {{-- View Layout Toggle (Grid vs Compact) --}}
                <div class="flex items-center rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700/80">
                    <button type="button"
                            @click="viewMode = 'grid'"
                            class="p-1.5 rounded-lg transition"
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-400 hover:text-slate-600'"
                            title="Grid View">
                        <i data-lucide="grid" class="w-4 h-4"></i>
                    </button>
                    <button type="button"
                            @click="viewMode = 'compact'"
                            class="p-1.5 rounded-lg transition"
                            :class="viewMode === 'compact' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-slate-400 hover:text-slate-600'"
                            title="Compact View">
                        <i data-lucide="list" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            {{-- Mobile Categories Horizontal Scroll (visible on small/medium screens) --}}
            <div class="flex md:hidden items-center gap-1.5 overflow-x-auto pos-scroll py-1">
                <button type="button"
                        @click="selectedCategory = null"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold"
                        :class="selectedCategory === null ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                    All Items (<span x-text="products.length"></span>)
                </button>
                <template x-for="cat in categories" :key="cat.id">
                    <button type="button"
                            @click="selectedCategory = cat.id"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold"
                            :class="selectedCategory === cat.id ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                        <span x-text="cat.name"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Product Cards Grid / List Area --}}
        <div class="flex-1 overflow-y-auto p-3 sm:p-4 pos-scroll">
            {{-- Loading / Empty state --}}
            <div x-show="filteredProducts.length === 0" x-cloak class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                <div class="w-16 h-16 rounded-2xl bg-slate-200/50 dark:bg-slate-800/50 flex items-center justify-center mb-3 text-slate-400">
                    <i data-lucide="package-search" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-semibold text-slate-700 dark:text-slate-300">No matching products</h3>
                <p class="text-xs text-slate-400 max-w-sm mt-1">Try a different search term or select another category from the sidebar.</p>
                <button type="button"
                        @click="searchQuery = ''; selectedCategory = null; inStockOnly = false"
                        class="mt-4 px-3.5 py-1.5 rounded-lg text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    Reset Filters
                </button>
            </div>

            {{-- GRID VIEW --}}
            <div x-show="viewMode === 'grid' && filteredProducts.length > 0"
                 class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div @click="addToCart(product)"
                         class="group relative flex flex-col justify-between p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/90 shadow-sm hover:shadow-md hover:border-indigo-500/50 dark:hover:border-indigo-500/50 transition-all duration-150 cursor-pointer select-none active:scale-[0.98]"
                         :class="{ 'opacity-60 grayscale-[40%] cursor-not-allowed': product.stock <= 0 }">

                        {{-- Top Badge Row: Category & Stock --}}
                        <div class="flex items-start justify-between gap-1.5 mb-2">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 truncate max-w-[120px]"
                                  x-text="product.category_name">
                            </span>
                            
                            {{-- Stock Indicator --}}
                            <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-bold"
                                  :class="{
                                      'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20': product.stock > 5,
                                      'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20': product.stock > 0 && product.stock <= 5,
                                      'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/20': product.stock <= 0
                                  }"
                                  x-text="product.stock > 0 ? (product.stock + ' in stock') : 'Out of stock'">
                            </span>
                        </div>

                        {{-- Product Name --}}
                        <div class="my-1.5 flex-1">
                            <h4 class="font-bold text-sm text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 leading-tight"
                                x-text="product.name">
                            </h4>
                        </div>

                        {{-- Bottom Row: Price & Quick Add Button --}}
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white"
                                      x-text="formatMoney(product.price)">
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">RWF</span>
                            </div>

                            <button type="button"
                                    class="w-7 h-7 rounded-xl flex items-center justify-center transition-all"
                                    :class="product.stock > 0
                                        ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white shadow-sm'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>

                        {{-- In-cart count badge (if already in cart) --}}
                        <div x-show="getItemCartQty(product.id) > 0"
                             class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shadow-lg border-2 border-white dark:border-slate-900 animate-in zoom-in-50 duration-150"
                             x-text="getItemCartQty(product.id)">
                        </div>
                    </div>
                </template>
            </div>

            {{-- COMPACT LIST VIEW --}}
            <div x-show="viewMode === 'compact' && filteredProducts.length > 0" class="space-y-1.5">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div @click="addToCart(product)"
                         class="group flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-500/50 cursor-pointer transition select-none"
                         :class="{ 'opacity-60 grayscale-[40%] cursor-not-allowed': product.stock <= 0 }">
                        
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/80 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                                <i data-lucide="package" class="w-4 h-4"></i>
                            </div>
                            <div class="truncate">
                                <div class="font-bold text-xs text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate"
                                     x-text="product.name">
                                </div>
                                <div class="text-[10px] text-slate-400" x-text="product.category_name"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 shrink-0">
                            {{-- Stock --}}
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md"
                                  :class="product.stock > 5 ? 'text-emerald-600 dark:text-emerald-400' : (product.stock > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-500')"
                                  x-text="product.stock + ' in stock'">
                            </span>

                            {{-- Price --}}
                            <div class="text-right w-24">
                                <span class="font-bold text-xs text-slate-900 dark:text-white" x-text="formatMoney(product.price)"></span>
                                <span class="text-[10px] text-slate-400">RWF</span>
                            </div>

                            <button type="button"
                                    class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 group-hover:bg-indigo-600 group-hover:text-white flex items-center justify-center transition">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>

    {{-- =========================================================================
     | PANEL 3: CART & ORDER CHECKOUT (Right Sidebar)
     |======================================================================== --}}
    <aside class="w-full sm:w-96 md:w-[410px] xl:w-[440px] shrink-0 bg-white dark:bg-slate-900 border-l border-slate-200/80 dark:border-slate-800 flex flex-col justify-between shadow-xl z-20">
        
        {{-- Cart Top: Customer Selector & Quick Info --}}
        <div class="p-3.5 border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 space-y-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="shopping-cart" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Current Cart</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
                          x-text="cartItemsCount + ' items'">
                    </span>
                </div>

                {{-- Clear Cart Button --}}
                <button type="button"
                        x-show="cart.length > 0"
                        @click="clearCart()"
                        class="text-[11px] font-medium text-rose-500 hover:text-rose-600 dark:text-rose-400 flex items-center gap-1 transition"
                        title="Clear Cart (F4)">
                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                    <span>Clear</span>
                    <span class="kbd-badge text-[9px]">F4</span>
                </button>
            </div>

            {{-- Customer Mode Tabs: Walk-in vs Existing vs New vs Supplier --}}
            <div class="grid grid-cols-4 gap-1 p-1 rounded-xl bg-slate-200/70 dark:bg-slate-800 text-xs font-semibold">
                <button type="button"
                        @click="custMode = 'walkin'; selectedCustomerId = null; selectedSupplierId = null"
                        class="py-1 px-1 rounded-lg transition text-center truncate"
                        :class="custMode === 'walkin' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    Walk-in
                </button>
                <button type="button"
                        @click="custMode = 'existing'; selectedSupplierId = null"
                        class="py-1 px-1 rounded-lg transition text-center truncate"
                        :class="custMode === 'existing' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    Customer
                </button>
                <button type="button"
                        @click="openNewCustomerModal(); selectedSupplierId = null"
                        class="py-1 px-1 rounded-lg transition text-center truncate"
                        :class="custMode === 'new' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    + New
                </button>
                <button type="button"
                        @click="custMode = 'supplier'; selectedCustomerId = null; onSupplierSelected()"
                        class="py-1 px-1 rounded-lg transition text-center truncate flex items-center justify-center gap-1"
                        :class="custMode === 'supplier' ? 'bg-amber-500 text-white shadow-sm font-bold' : 'text-amber-600 dark:text-amber-400 hover:bg-amber-500/10'">
                    <span>Supplier</span>
                    <span x-show="suppliersWithDebt.length > 0"
                          class="px-1 py-0.2 rounded-full text-[9px] font-bold"
                          :class="custMode === 'supplier' ? 'bg-white/30 text-white' : 'bg-amber-500/20 text-amber-600 dark:text-amber-400'"
                          x-text="suppliersWithDebt.length">
                    </span>
                </button>
            </div>

            {{-- Customer Details depending on Mode --}}
            <div x-show="custMode === 'existing'" class="relative" x-cloak>
                <select x-model="selectedCustomerId"
                        class="w-full text-xs py-2 px-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Choose Customer --</option>
                    <template x-for="c in customers" :key="c.id">
                        <option :value="c.id" x-text="c.name + (c.phone ? ' (' + c.phone + ')' : '')"></option>
                    </template>
                </select>
            </div>

            <div x-show="custMode === 'new' && newCustomer.name" class="p-2 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/50 flex items-center justify-between text-xs" x-cloak>
                <div>
                    <span class="font-bold text-indigo-900 dark:text-indigo-200" x-text="newCustomer.name"></span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block" x-text="newCustomer.phone || 'No phone'"></span>
                </div>
                <button type="button" @click="openNewCustomerModal()" class="text-indigo-600 dark:text-indigo-400 text-xs font-semibold hover:underline">
                    Edit
                </button>
            </div>

            {{-- Supplier Selection & Debt Card --}}
            <div x-show="custMode === 'supplier'" class="space-y-2" x-cloak>
                <div class="relative">
                    <select x-model="selectedSupplierId"
                            @change="onSupplierSelected()"
                            class="w-full text-xs py-2 px-3 rounded-xl bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">-- Choose Supplier (Store Debt) --</option>
                        <template x-for="s in suppliers" :key="s.id">
                            <option :value="s.id"
                                    x-text="s.name + ' (Store owes: ' + formatMoney(s.total_owed || 0) + ' RWF)'">
                            </option>
                        </template>
                    </select>
                </div>

                {{-- Selected Supplier Debt Info Card --}}
                <div x-show="selectedSupplier" class="p-2.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1.5 truncate">
                            <i data-lucide="handshake" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i>
                            <span class="truncate" x-text="selectedSupplier ? selectedSupplier.name : ''"></span>
                        </span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-md font-bold bg-amber-200/80 dark:bg-amber-900 text-amber-800 dark:text-amber-200 shrink-0"
                              x-text="selectedSupplierLoansCount + ' loan(s) pending'">
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] pt-1 border-t border-amber-200/60 dark:border-amber-800/40">
                        <span class="text-slate-600 dark:text-slate-400">Total Store Debt:</span>
                        <span class="font-black text-amber-700 dark:text-amber-300" x-text="formatMoney(selectedSupplierDebt) + ' RWF'"></span>
                    </div>

                    <div class="text-[10px] leading-snug pt-0.5"
                         :class="cartTotal <= selectedSupplierDebt ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-300'">
                        <template x-if="cartTotal > 0 && cartTotal <= selectedSupplierDebt">
                            <span class="flex items-center gap-1">
                                <i data-lucide="check" class="w-3 h-3 inline"></i>
                                Cart total is fully covered by store debt offset (FIFO).
                            </span>
                        </template>
                        <template x-if="cartTotal > selectedSupplierDebt">
                            <span class="flex items-center gap-1">
                                <i data-lucide="alert-triangle" class="w-3 h-3 inline"></i>
                                Cart exceeds debt by <span class="font-bold" x-text="formatMoney(cartTotal - selectedSupplierDebt)"></span> RWF. Balance can be split with Cash/MoMo.
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cart Items Scrollable List --}}
        <div class="flex-1 overflow-y-auto p-3 pos-scroll space-y-2">
            {{-- Empty Cart Illustration --}}
            <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800/80 flex items-center justify-center mb-3 text-slate-400">
                    <i data-lucide="shopping-basket" class="w-7 h-7"></i>
                </div>
                <h4 class="font-bold text-sm text-slate-700 dark:text-slate-300">Cart is Empty</h4>
                <p class="text-xs text-slate-400 max-w-[200px] mt-1">Tap any product in the catalog or scan barcode to add to this order.</p>
            </div>

            {{-- Cart Item Rows --}}
            <template x-for="(item, index) in cart" :key="item.product_id">
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 flex flex-col gap-2 hover:border-slate-300 dark:hover:border-slate-700 transition">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h5 class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate" x-text="item.name"></h5>
                            <div class="text-[11px] text-slate-400" x-text="formatMoney(item.unit_price) + ' RWF / unit'"></div>
                        </div>

                        {{-- Line Total --}}
                        <div class="text-right">
                            <span class="font-bold text-xs text-slate-900 dark:text-white" x-text="formatMoney(item.quantity * item.unit_price)"></span>
                            <span class="text-[10px] text-slate-400">RWF</span>
                        </div>
                    </div>

                    {{-- Stepper & Actions --}}
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                        {{-- Quantity Stepper --}}
                        <div class="flex items-center bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 p-0.5">
                            <button type="button"
                                    @click="decrementQty(index)"
                                    class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:hover:text-white rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <i data-lucide="minus" class="w-3 h-3"></i>
                            </button>

                            <input type="number"
                                   x-model.number="item.quantity"
                                   @change="validateItemQty(index)"
                                   step="1"
                                   min="1"
                                   :max="item.stock"
                                   class="w-12 text-center text-xs font-bold bg-transparent text-slate-900 dark:text-white focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">

                            <button type="button"
                                    @click="incrementQty(index)"
                                    :disabled="item.quantity >= item.stock"
                                    class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:hover:text-white rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition disabled:opacity-30 disabled:cursor-not-allowed">
                                <i data-lucide="plus" class="w-3 h-3"></i>
                            </button>
                        </div>

                        {{-- Stock Limit Warn & Remove --}}
                        <div class="flex items-center gap-2">
                            <span x-show="item.quantity >= item.stock" class="text-[10px] font-semibold text-amber-500">Max stock</span>
                            <button type="button"
                                    @click="removeFromCart(index)"
                                    class="text-slate-400 hover:text-rose-500 p-1 rounded-md transition"
                                    title="Remove item">
                                <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Cart Bottom: Totals & Checkout Trigger --}}
        <div class="p-3.5 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-sm space-y-3">
            
            {{-- Quick Order Options: Hold & Boss Gift --}}
            <div class="flex items-center justify-between text-xs">
                <button type="button"
                        @click="holdCurrentOrder()"
                        :disabled="cart.length === 0"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800 transition disabled:opacity-40"
                        title="Hold cart for later (F8)">
                    <i data-lucide="pause-circle" class="w-4 h-4 text-amber-500"></i>
                    <span>Hold Order</span>
                    <span class="kbd-badge text-[9px]">F8</span>
                </button>

                <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-purple-600 dark:text-purple-400">
                    <input type="checkbox" x-model="isGift" class="rounded text-purple-600 focus:ring-purple-500">
                    <span>Boss Gift</span>
                </label>
            </div>

            {{-- Gift Reason input if checked --}}
            <div x-show="isGift" x-cloak class="p-2 rounded-xl bg-purple-500/10 border border-purple-500/20 text-xs space-y-1">
                <span class="font-bold text-purple-700 dark:text-purple-300 block">Complimentary Gift (Zero Payment)</span>
                <input type="text"
                       x-model="giftReason"
                       placeholder="Enter gift authorization note..."
                       class="w-full text-xs px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-purple-300 dark:border-purple-700 focus:outline-none">
            </div>

            {{-- Summary Breakdown --}}
            <div class="space-y-1.5 pt-1 text-xs">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                    <span>Subtotal</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="formatMoney(cartSubtotal) + ' RWF'"></span>
                </div>
                <div class="flex items-center justify-between text-base font-extrabold text-slate-900 dark:text-white pt-1 border-t border-slate-200/80 dark:border-slate-800">
                    <span>Total Amount</span>
                    <div class="text-right">
                        <span class="text-xl font-black text-indigo-600 dark:text-indigo-400" x-text="formatMoney(cartTotal)"></span>
                        <span class="text-xs font-semibold text-slate-400">RWF</span>
                    </div>
                </div>
            </div>

            {{-- Big Checkout Button --}}
            <button type="button"
                    @click="openPaymentModal()"
                    :disabled="cart.length === 0"
                    class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-[0.99] text-white font-bold text-sm shadow-lg shadow-emerald-500/25 flex items-center justify-between transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100">
                <span class="flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                    <span>PROCEED TO PAYMENT</span>
                </span>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs bg-white/20 px-2 py-0.5 rounded-md font-mono">Space / F9</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </div>
            </button>
        </div>
    </aside>

    {{-- =========================================================================
     | MODAL 1: PAYMENT & CHECKOUT MODAL
     |======================================================================== --}}
    <div x-show="showPaymentModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div @click.outside="closePaymentModal()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-700 dark:text-slate-300">
                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-900 dark:text-white leading-tight">Payment & Checkout</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Select payment method and confirm tendered amount</p>
                    </div>
                </div>
                <button type="button"
                        @click="closePaymentModal()"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Close (Esc)">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto pos-scroll">
                
                {{-- Total Due Banner --}}
                <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 block">Total Due</span>
                        <div class="flex items-baseline gap-1 mt-0.5">
                            <span class="text-3xl font-black text-slate-900 dark:text-white" x-text="formatMoney(cartTotal)"></span>
                            <span class="text-sm font-bold text-slate-500">RWF</span>
                        </div>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        <span x-text="cartItemsCount + ' items in cart'"></span>
                        <div class="font-semibold text-slate-700 dark:text-slate-300" x-text="customerDisplayName"></div>
                    </div>
                </div>

                {{-- Gift Sale Notification --}}
                <div x-show="isGift" class="p-3.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-700 dark:text-purple-300 text-xs">
                    <strong class="block text-sm mb-1">🎁 Boss Complimentary Gift</strong>
                    Zero cash collected. This sale will be recorded as completed with payment channel <code class="font-bold">boss_gift</code>.
                </div>

                {{-- Supplier Debt Offset Banner --}}
                <div x-show="custMode === 'supplier' && selectedSupplier" class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-xs space-y-1.5" x-cloak>
                    <div class="flex items-center justify-between font-bold">
                        <span class="flex items-center gap-1.5 text-sm">
                            <i data-lucide="handshake" class="w-4 h-4 text-amber-600"></i>
                            <span>Supplier Debt Offset (Goods Exchange)</span>
                        </span>
                        <span class="text-[10px] bg-amber-500/20 text-amber-800 dark:text-amber-200 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                            FIFO Loan Reduction
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                        Exchanging store items against money owed to <strong class="text-amber-900 dark:text-white" x-text="selectedSupplier?.name"></strong>. This sale reduces the oldest store debt first without cash leaving or entering the drawer.
                    </p>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-amber-500/20 font-semibold">
                        <span>Store Loan Balance: <span class="font-bold text-amber-700 dark:text-amber-300" x-text="formatMoney(selectedSupplierDebt) + ' RWF'"></span></span>
                        <template x-if="cartTotal > selectedSupplierDebt">
                            <button type="button" @click="setupSupplierSplit()" class="underline text-indigo-600 dark:text-indigo-400 font-bold hover:text-indigo-700">
                                Split Balance with Cash / MoMo &rarr;
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="!isGift" class="space-y-4">
                    {{-- Payment Mode: Single Channel vs Split --}}
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Payment Method</label>
                        <button type="button"
                                @click="isSplitPayment = !isSplitPayment"
                                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                            <i data-lucide="split" class="w-3.5 h-3.5"></i>
                            <span x-text="isSplitPayment ? 'Switch to Single Payment' : 'Split between methods'"></span>
                        </button>
                    </div>

                    {{-- Single Payment Channels Selector --}}
                    <div x-show="!isSplitPayment" class="space-y-3">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <template x-if="custMode === 'supplier'">
                                <button type="button"
                                        @click="selectedPaymentChannel = 'supplier_offset'"
                                        class="p-3 rounded-xl border text-xs font-bold flex flex-col items-center justify-center gap-1.5 transition-all"
                                        :class="selectedPaymentChannel === 'supplier_offset'
                                            ? 'bg-amber-600 text-white border-amber-600 shadow-md shadow-amber-500/20 scale-[1.02]'
                                            : 'bg-amber-500/10 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 hover:border-amber-500'">
                                    <i data-lucide="handshake" class="w-5 h-5"></i>
                                    <span>Supplier Offset</span>
                                </button>
                            </template>
                            <template x-for="ch in paymentChannels" :key="ch.id">
                                <button type="button"
                                        @click="selectedPaymentChannel = ch.slug"
                                        class="p-3 rounded-xl border text-xs font-bold flex flex-col items-center justify-center gap-1.5 transition-all"
                                        :class="selectedPaymentChannel === ch.slug
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20 scale-[1.02]'
                                            : 'bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-indigo-400'">
                                    <i :data-lucide="getChannelIcon(ch.slug)" class="w-5 h-5"></i>
                                    <span x-text="ch.name"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Amount Tendered & Change Section --}}
                        <div class="space-y-2 pt-2">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Amount Tendered (RWF)</label>
                            <div class="relative">
                                <input type="number"
                                       x-model.number="amountTendered"
                                       x-ref="tenderedInput"
                                       step="100"
                                       min="0"
                                       class="w-full text-xl font-bold py-2.5 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 font-bold text-sm text-slate-400">RWF</span>
                            </div>

                            {{-- Fast Cash Suggestion Buttons --}}
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <button type="button"
                                        @click="amountTendered = cartTotal"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-white hover:bg-slate-300">
                                    Exact (<span x-text="formatMoney(cartTotal)"></span>)
                                </button>
                                <template x-for="amt in quickCashAmounts" :key="amt">
                                    <button type="button"
                                            @click="amountTendered = amt"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200"
                                            x-text="formatMoney(amt)">
                                    </button>
                                </template>
                            </div>

                            {{-- Change / Return Visual --}}
                            <div class="p-3.5 rounded-xl flex items-center justify-between border"
                                 :class="changeReturn >= 0
                                     ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300'
                                     : 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300'">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider block"
                                          x-text="changeReturn >= 0 ? 'Change to Return' : 'Remaining / Underpaid'">
                                    </span>
                                    <span class="text-[11px] opacity-80" x-text="changeReturn >= 0 ? 'Give to customer' : 'Sale will be marked Pending'"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-black" x-text="formatMoney(Math.abs(changeReturn))"></span>
                                    <span class="text-xs font-bold">RWF</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Split Payments Mode --}}
                    <div x-show="isSplitPayment" class="space-y-3" x-cloak>
                        <template x-for="(pRow, pIdx) in splitRows" :key="pIdx">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 flex items-center gap-2">
                                <select x-model="pRow.method" class="text-xs py-2 px-2.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                                    <option value="supplier_offset">Supplier Debt Offset</option>
                                    <template x-for="ch in paymentChannels" :key="ch.id">
                                        <option :value="ch.slug" x-text="ch.name"></option>
                                    </template>
                                </select>
                                <input type="number"
                                       x-model.number="pRow.amount"
                                       placeholder="Amount"
                                       class="flex-1 text-xs font-bold py-2 px-2.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                                <input type="text"
                                       x-model="pRow.reference"
                                       placeholder="Ref / Phone (opt)"
                                       class="w-32 text-xs py-2 px-2.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                                <button type="button" @click="removeSplitRow(pIdx)" class="text-rose-500 hover:text-rose-700 p-1">
                                    <i data-lucide="trash" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </template>

                        <button type="button"
                                @click="addSplitRow()"
                                class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Add Another Payment Method</span>
                        </button>
                    </div>

                    {{-- Optional Note or Reference --}}
                    <div>
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Transaction Notes (Optional)</label>
                        <input type="text"
                               x-model="saleNotes"
                               placeholder="e.g. Table 4, MoMo transaction ID, etc."
                               class="w-full text-xs py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- Modal Footer: Submit Button --}}
            <div class="p-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <button type="button"
                        @click="closePaymentModal()"
                        class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Back (Esc)
                </button>

                <button type="button"
                        @click="submitCheckout()"
                        :disabled="submittingCheckout"
                        class="flex-1 py-3 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2 transition disabled:opacity-50">
                    <template x-if="submittingCheckout">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    </template>
                    <template x-if="!submittingCheckout">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </template>
                    <span x-text="submittingCheckout ? 'Processing Sale...' : 'CONFIRM & COMPLETE (Enter)'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================================
     | MODAL 2: SALE COMPLETED & RECEIPT PRINT MODAL
     |======================================================================== --}}
    <div x-show="showReceiptModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-4">
        
        <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[92vh]">
            
            {{-- Modal Header --}}
            <div class="px-5 py-3.5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white leading-tight">Sale Completed Successfully</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Receipt <span class="font-bold text-slate-700 dark:text-slate-200" x-text="completedSale.sale_number"></span>
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="startNewSale()"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Close (Esc)">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            {{-- Change to Return Banner if any --}}
            <div x-show="completedSale.change > 0" class="mx-4 mt-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 flex items-center justify-between text-xs shrink-0">
                <div>
                    <span class="font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wide text-[10px] block">Change to return</span>
                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Give to customer</span>
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-amber-900 dark:text-amber-200" x-text="formatMoney(completedSale.change)"></span>
                    <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400">RWF</span>
                </div>
            </div>

            {{-- Receipt Live Preview Frame --}}
            <div class="p-3 sm:p-4 flex-1 flex flex-col items-center justify-center bg-slate-100/70 dark:bg-slate-950/60 overflow-hidden">
                <div class="w-full max-w-[340px] bg-white rounded-xl shadow-lg border border-slate-200/90 overflow-hidden">
                    <iframe x-ref="receiptPreviewFrame"
                            :src="completedSale.receipt_url"
                            class="w-full h-[370px] border-0 bg-white">
                    </iframe>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-3.5 sm:p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0">
                <button type="button"
                        @click="startNewSale()"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold text-xs transition flex items-center gap-1.5">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>New Sale (Space)</span>
                </button>

                <div class="flex items-center gap-2">
                    <a :href="completedSale.receipt_url"
                       target="_blank"
                       class="px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold flex items-center gap-1.5 transition"
                       title="Open receipt in dedicated tab">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Open Tab</span>
                    </a>

                    <button type="button"
                            @click="printReceipt()"
                            class="py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-500/25 flex items-center gap-2 transition">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Print Receipt (Ctrl+P)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
     | MODAL 3: QUICK ADD NEW CUSTOMER
     |======================================================================== --}}
    <div x-show="showCustomerModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div @click.outside="showCustomerModal = false"
             class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4">
            
            <div class="flex items-center justify-between">
                <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-4 h-4 text-indigo-500"></i>
                    <span>Register Customer</span>
                </h4>
                <button type="button" @click="showCustomerModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-2.5 text-xs">
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Customer Name *</label>
                    <input type="text" x-model="newCustomer.name" placeholder="Full name" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                </div>
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Phone Number</label>
                    <input type="text" x-model="newCustomer.phone" placeholder="e.g. 078..." class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                </div>
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Email (Optional)</label>
                    <input type="email" x-model="newCustomer.email" placeholder="email@domain.com" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" @click="showCustomerModal = false" class="w-1/3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold">
                    Cancel
                </button>
                <button type="button" @click="saveNewCustomerDraft()" class="w-2/3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-500">
                    Set Customer
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================================
     | MODAL 4: KEYBOARD SHORTCUTS CHEAT SHEET
     |======================================================================== --}}
    <div x-show="showShortcutsModal"
         @open-shortcuts-modal.window="showShortcutsModal = true"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div @click.outside="showShortcutsModal = false"
             class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4">
            
            <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
                <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="keyboard" class="w-4 h-4 text-indigo-500"></i>
                    <span>POS Keyboard Shortcuts</span>
                </h4>
                <button type="button" @click="showShortcutsModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-600 dark:text-slate-400">Focus Product Search</span>
                    <span class="kbd-badge">/ or F2</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-600 dark:text-slate-400">Open Payment Modal</span>
                    <span class="kbd-badge">Space or F9</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-600 dark:text-slate-400">Clear Cart</span>
                    <span class="kbd-badge">F4</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-600 dark:text-slate-400">Hold / Suspend Order</span>
                    <span class="kbd-badge">F8</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-600 dark:text-slate-400">Close Open Modal</span>
                    <span class="kbd-badge">Escape</span>
                </div>
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-slate-600 dark:text-slate-400">Toggle Fullscreen</span>
                    <span class="kbd-badge">F11</span>
                </div>
            </div>

            <button type="button" @click="showShortcutsModal = false" class="w-full py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                Got it
            </button>
        </div>
    </div>

    {{-- Hidden Print Iframe for seamless thermal receipt printing --}}
    <iframe x-ref="printIframe" class="hidden"></iframe>
</div>
@endsection

@push('scripts')
<script>
function posApp(config) {
    return {
        products: config.initialProducts || [],
        categories: config.categories || [],
        customers: config.customers || [],
        suppliers: config.suppliers || [],
        paymentChannels: config.paymentChannels || [],
        checkoutUrl: config.checkoutUrl,
        csrfToken: config.csrfToken,

        // State
        searchQuery: '',
        selectedCategory: null,
        inStockOnly: false,
        viewMode: 'grid', // 'grid' or 'compact'

        // Cart state
        cart: [],
        heldOrders: [],
        custMode: 'walkin', // 'walkin' | 'existing' | 'new' | 'supplier'
        selectedCustomerId: null,
        selectedSupplierId: null,
        newCustomer: { name: '', phone: '', email: '', address: '' },
        isGift: false,
        giftReason: '',
        saleNotes: '',

        // Payment state
        showPaymentModal: false,
        selectedPaymentChannel: 'cash',
        amountTendered: 0,
        isSplitPayment: false,
        splitRows: [],
        submittingCheckout: false,

        // Receipt state
        showReceiptModal: false,
        completedSale: { sale_number: '', change: 0, receipt_url: '' },

        // Extra Modals
        showCustomerModal: false,
        showShortcutsModal: false,

        init() {
            // Load saved held carts from localStorage
            try {
                const saved = localStorage.getItem('pos_held_orders');
                if (saved) this.heldOrders = JSON.parse(saved);
            } catch (e) {}

            // Auto-focus search input
            this.$nextTick(() => {
                if (this.$refs.searchInput) this.$refs.searchInput.focus();
                if (window.lucide) window.lucide.createIcons();
            });

            // Set default payment channel
            if (this.paymentChannels.length > 0) {
                const cash = this.paymentChannels.find(p => p.slug === 'cash');
                this.selectedPaymentChannel = cash ? cash.slug : this.paymentChannels[0].slug;
            }
        },

        // Supplier Helpers & Getters
        get suppliersWithDebt() {
            return (this.suppliers || []).filter(s => (s.total_owed || 0) > 0);
        },
        get selectedSupplier() {
            if (!this.selectedSupplierId) return null;
            return (this.suppliers || []).find(s => s.id == this.selectedSupplierId) || null;
        },
        get selectedSupplierDebt() {
            return this.selectedSupplier ? Number(this.selectedSupplier.total_owed || 0) : 0;
        },
        get selectedSupplierLoansCount() {
            return this.selectedSupplier ? (this.selectedSupplier.loans_count || 0) : 0;
        },

        onSupplierSelected() {
            if (this.custMode === 'supplier') {
                this.selectedPaymentChannel = 'supplier_offset';
            }
        },

        setupSupplierSplit() {
            this.isSplitPayment = true;
            const offsetAmt = Math.min(this.cartTotal, this.selectedSupplierDebt);
            const remaining = Math.max(0, this.cartTotal - offsetAmt);
            this.splitRows = [
                { method: 'supplier_offset', amount: offsetAmt, reference: 'Store Debt Offset' },
                { method: 'cash', amount: remaining, reference: 'Cash Balance' }
            ];
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        // Filtered products list
        get filteredProducts() {
            let list = this.products;

            if (this.selectedCategory !== null) {
                list = list.filter(p => p.category_id === this.selectedCategory);
            }

            if (this.inStockOnly) {
                list = list.filter(p => p.stock > 0);
            }

            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase().trim();
                list = list.filter(p => {
                    return p.name.toLowerCase().includes(q) ||
                           (p.id && String(p.id) === q);
                });
            }

            return list;
        },

        countCategoryProducts(catId) {
            return this.products.filter(p => p.category_id === catId).length;
        },

        toggleInStockOnly() {
            this.inStockOnly = !this.inStockOnly;
        },

        // Cart Computations
        get cartItemsCount() {
            return this.cart.reduce((sum, item) => sum + item.quantity, 0);
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        },

        get cartTotal() {
            return this.isGift ? 0 : this.cartSubtotal;
        },

        getItemCartQty(productId) {
            const found = this.cart.find(i => i.product_id === productId);
            return found ? found.quantity : 0;
        },

        get customerDisplayName() {
            if (this.custMode === 'walkin') return 'Walk-in Customer';
            if (this.custMode === 'new') return this.newCustomer.name || 'New Customer';
            if (this.custMode === 'supplier' && this.selectedSupplier) {
                return this.selectedSupplier.name + ' (Supplier Offset)';
            }
            if (this.custMode === 'existing' && this.selectedCustomerId) {
                const c = this.customers.find(item => item.id == this.selectedCustomerId);
                return c ? c.name : 'Selected Customer';
            }
            return 'Customer';
        },

        get quickCashAmounts() {
            const tot = this.cartTotal;
            if (tot <= 0) return [1000, 2000, 5000, 10000];
            const roundUpToNearest = (val, round) => Math.ceil(val / round) * round;

            const suggestions = new Set();
            if (tot <= 5000) suggestions.add(roundUpToNearest(tot, 1000));
            if (tot <= 10000) suggestions.add(roundUpToNearest(tot, 2000));
            suggestions.add(roundUpToNearest(tot, 5000));
            suggestions.add(roundUpToNearest(tot, 10000));
            suggestions.add(roundUpToNearest(tot, 20000));
            suggestions.delete(tot);

            return Array.from(suggestions).filter(a => a > tot).slice(0, 4);
        },

        get changeReturn() {
            if (this.isGift) return 0;
            return this.amountTendered - this.cartTotal;
        },

        // Cart Actions
        addToCart(product) {
            if (product.stock <= 0) {
                this.toast('warning', 'Out of Stock', `'${product.name}' is currently unavailable.`);
                return;
            }

            const existing = this.cart.find(i => i.product_id === product.id);
            if (existing) {
                if (existing.quantity >= product.stock) {
                    this.toast('warning', 'Stock Limit Reached', `Only ${product.stock} units available.`);
                    return;
                }
                existing.quantity += 1;
            } else {
                this.cart.push({
                    product_id: product.id,
                    name: product.name,
                    unit_price: product.price,
                    cost_price: product.cost_price,
                    stock: product.stock,
                    quantity: 1
                });
            }

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        incrementQty(index) {
            const item = this.cart[index];
            if (item.quantity < item.stock) {
                item.quantity += 1;
            }
        },

        decrementQty(index) {
            const item = this.cart[index];
            if (item.quantity > 1) {
                item.quantity -= 1;
            } else {
                this.removeFromCart(index);
            }
        },

        validateItemQty(index) {
            const item = this.cart[index];
            if (isNaN(item.quantity) || item.quantity < 1) {
                item.quantity = 1;
            } else if (item.quantity > item.stock) {
                item.quantity = item.stock;
                this.toast('warning', 'Stock limit reached', `Maximum ${item.stock} available.`);
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        clearCart() {
            if (this.cart.length === 0) return;
            this.cart = [];
            this.isGift = false;
            this.giftReason = '';
            this.saleNotes = '';
            this.toast('info', 'Cart Cleared', 'All items removed.');
        },

        handleSearchEnter() {
            // If search matches exactly 1 product, add it and clear search
            if (this.filteredProducts.length === 1) {
                this.addToCart(this.filteredProducts[0]);
                this.searchQuery = '';
            }
        },

        // Order Hold / Suspend
        holdCurrentOrder() {
            if (this.cart.length === 0) return;

            const order = {
                id: Date.now(),
                name: this.customerDisplayName,
                items: JSON.parse(JSON.stringify(this.cart)),
                total: this.cartTotal,
                custMode: this.custMode,
                selectedCustomerId: this.selectedCustomerId,
                selectedSupplierId: this.selectedSupplierId,
                newCustomer: { ...this.newCustomer },
                isGift: this.isGift,
                giftReason: this.giftReason,
                notes: this.saleNotes,
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            };

            this.heldOrders.push(order);
            localStorage.setItem('pos_held_orders', JSON.stringify(this.heldOrders));
            this.clearCart();
            this.toast('info', 'Order Held', 'Cart saved to Held Orders list.');
        },

        restoreOrder(id) {
            const idx = this.heldOrders.findIndex(o => o.id === id);
            if (idx === -1) return;

            const order = this.heldOrders[idx];
            this.cart = order.items;
            this.custMode = order.custMode;
            this.selectedCustomerId = order.selectedCustomerId;
            this.selectedSupplierId = order.selectedSupplierId || null;
            this.newCustomer = order.newCustomer || { name: '', phone: '', email: '', address: '' };
            this.isGift = !!order.isGift;
            this.giftReason = order.giftReason || '';
            this.saleNotes = order.notes || '';

            this.heldOrders.splice(idx, 1);
            localStorage.setItem('pos_held_orders', JSON.stringify(this.heldOrders));
            this.toast('success', 'Order Restored', 'Resumed cart from hold.');

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        discardHeldOrder(id) {
            this.heldOrders = this.heldOrders.filter(o => o.id !== id);
            localStorage.setItem('pos_held_orders', JSON.stringify(this.heldOrders));
        },

        // Payment Handling
        openPaymentModal() {
            if (this.cart.length === 0) return;
            this.amountTendered = this.cartTotal;
            this.showPaymentModal = true;
            this.isSplitPayment = false;

            if (this.custMode === 'supplier') {
                this.selectedPaymentChannel = 'supplier_offset';
            }

            // Prepare default split rows
            this.splitRows = [
                { method: this.custMode === 'supplier' ? 'supplier_offset' : 'cash', amount: this.cartTotal, reference: '' }
            ];

            this.$nextTick(() => {
                if (this.$refs.tenderedInput) {
                    this.$refs.tenderedInput.focus();
                    this.$refs.tenderedInput.select();
                }
                if (window.lucide) window.lucide.createIcons();
            });
        },

        closePaymentModal() {
            this.showPaymentModal = false;
        },

        addSplitRow() {
            this.splitRows.push({
                method: this.custMode === 'supplier' ? 'supplier_offset' : (this.paymentChannels[0]?.slug || 'cash'),
                amount: 0,
                reference: ''
            });
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        removeSplitRow(index) {
            if (this.splitRows.length > 1) {
                this.splitRows.splice(index, 1);
            }
        },

        getChannelIcon(slug) {
            switch (slug) {
                case 'cash': return 'banknote';
                case 'momo':
                case 'mobile_money': return 'smartphone';
                case 'bank':
                case 'card': return 'credit-card';
                case 'boss_gift': return 'gift';
                case 'supplier_offset':
                case 'supplier-debt-offset':
                case 'goods_exchange': return 'handshake';
                default: return 'wallet';
            }
        },

        // Checkout Submission
        async submitCheckout() {
            if (this.cart.length === 0 || this.submittingCheckout) return;

            // Customer validations
            if (this.custMode === 'existing' && !this.selectedCustomerId) {
                this.toast('error', 'Customer Required', 'Please choose an existing customer.');
                return;
            }
            if (this.custMode === 'new' && !this.newCustomer.name) {
                this.toast('error', 'Customer Name Required', 'Please enter the new customer name.');
                return;
            }
            if (this.custMode === 'supplier' && !this.selectedSupplierId) {
                this.toast('error', 'Supplier Required', 'Please choose a supplier for debt offset.');
                return;
            }

            this.submittingCheckout = true;

            const payload = {
                cust_mode: this.custMode,
                customer_id: this.selectedCustomerId,
                supplier_id: this.custMode === 'supplier' ? this.selectedSupplierId : null,
                customer_name: this.newCustomer.name,
                customer_phone: this.newCustomer.phone,
                customer_email: this.newCustomer.email,
                sale_date: new Date().toISOString().slice(0, 10),
                is_gift: this.isGift,
                gift_reason: this.giftReason,
                notes: this.saleNotes,
                products: this.cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    unit_price: item.unit_price
                }))
            };

            if (this.isSplitPayment) {
                payload.payments = this.splitRows.map(r => ({
                    method: r.method,
                    amount: r.amount,
                    reference: r.reference
                }));
            } else {
                payload.payment_channel = this.selectedPaymentChannel;
                payload.amount_paid = this.cartTotal;
                payload.amount_tendered = this.amountTendered;
            }

            try {
                const res = await fetch(this.checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Server error occurred during checkout.');
                }

                // Deduct stock locally immediately for instant feedback
                this.cart.forEach(cItem => {
                    const prod = this.products.find(p => p.id === cItem.product_id);
                    if (prod) {
                        prod.stock = Math.max(0, prod.stock - cItem.quantity);
                    }
                });

                // Completed sale setup
                this.completedSale = {
                    sale_id: data.sale_id,
                    sale_number: data.sale_number,
                    change: data.change || 0,
                    receipt_url: data.receipt_url
                };

                this.showPaymentModal = false;
                this.showReceiptModal = true;
                this.toast('success', 'Sale Completed!', data.message);

                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });

            } catch (err) {
                console.error('POS Checkout Error:', err);
                this.toast('error', 'Checkout Failed', err.message);
            } finally {
                this.submittingCheckout = false;
            }
        },

        // Receipt Print & Reset
        printReceipt() {
            if (!this.completedSale.receipt_url) return;

            // 1. First attempt: print the embedded preview frame directly
            const frame = this.$refs.receiptPreviewFrame;
            if (frame && frame.contentWindow) {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                    return;
                } catch (e) {
                    console.warn('Direct frame print failed, falling back to popup window', e);
                }
            }

            // 2. Fallback: Open popup window with autoprint
            const printUrl = this.completedSale.receipt_url + '?autoprint=1';
            const win = window.open(printUrl, '_blank', 'width=450,height=750,scrollbars=yes');
            if (win) {
                win.focus();
            } else {
                window.open(printUrl, '_blank');
            }
        },

        startNewSale() {
            this.showReceiptModal = false;
            this.clearCart();
            this.custMode = 'walkin';
            this.selectedCustomerId = null;
            this.selectedSupplierId = null;
            this.newCustomer = { name: '', phone: '', email: '', address: '' };

            this.$nextTick(() => {
                if (this.$refs.searchInput) this.$refs.searchInput.focus();
            });
        },

        // Customer Modal
        openNewCustomerModal() {
            this.showCustomerModal = true;
        },

        saveNewCustomerDraft() {
            if (!this.newCustomer.name.trim()) {
                this.toast('warning', 'Name Missing', 'Please enter customer name.');
                return;
            }
            this.custMode = 'new';
            this.showCustomerModal = false;
            this.toast('info', 'Customer Staged', `Drafting sale for ${this.newCustomer.name}`);
        },

        // Global Shortcuts Listener
        handleGlobalKeydown(e) {
            // Escape: Close any open modal
            if (e.key === 'Escape') {
                if (this.showPaymentModal) this.closePaymentModal();
                if (this.showReceiptModal) this.startNewSale();
                if (this.showCustomerModal) this.showCustomerModal = false;
                if (this.showShortcutsModal) this.showShortcutsModal = false;
                return;
            }

            // Don't intercept shortcut keys if user is typing in an input (except function keys)
            const isTyping = ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName);

            // F1: Help / Shortcuts modal
            if (e.key === 'F1') {
                e.preventDefault();
                this.showShortcutsModal = !this.showShortcutsModal;
                return;
            }

            // F2 or '/' (when not typing): Focus search
            if (e.key === 'F2' || (!isTyping && e.key === '/')) {
                e.preventDefault();
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                    this.$refs.searchInput.select();
                }
                return;
            }

            // F4: Clear cart
            if (e.key === 'F4') {
                e.preventDefault();
                this.clearCart();
                return;
            }

            // F8: Hold order
            if (e.key === 'F8') {
                e.preventDefault();
                this.holdCurrentOrder();
                return;
            }

            // F9 or Space (when not typing): Open payment modal
            if (e.key === 'F9' || (!isTyping && e.key === ' ' && !this.showPaymentModal && !this.showReceiptModal)) {
                e.preventDefault();
                if (this.cart.length > 0) this.openPaymentModal();
                return;
            }

            // In Payment Modal: Enter triggers checkout
            if (this.showPaymentModal && e.key === 'Enter') {
                e.preventDefault();
                this.submitCheckout();
                return;
            }

            // In Receipt Modal: Ctrl+P prints, Space starts new sale
            if (this.showReceiptModal) {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    this.startNewSale();
                }
            }
        },

        // Helpers
        formatMoney(amount) {
            return Number(amount || 0).toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        },

        toast(type, title, message) {
            window.dispatchEvent(new CustomEvent('pos-toast', {
                detail: { type, title, message }
            }));
        }
    };
}
</script>
@endpush
