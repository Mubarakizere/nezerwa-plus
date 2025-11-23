<div x-data="commandPalette()"
     @keydown.window.meta.k.prevent="open = true"
     @keydown.window.ctrl.k.prevent="open = true"
     @keydown.escape.window="open = false"
     class="relative z-[9999]"
     x-show="open"
     x-cloak>
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity" 
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20">
        <div class="mx-auto max-w-xl transform divide-y divide-gray-100 overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black ring-opacity-5 transition-all"
             @click.away="open = false"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <div class="relative">
                <i data-lucide="search" class="pointer-events-none absolute top-3.5 left-4 h-5 w-5 text-gray-400"></i>
                <input type="text" 
                       class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-0 sm:text-sm" 
                       placeholder="Search commands... (Cmd+K)"
                       x-model="query"
                       x-ref="searchInput"
                       @keydown.arrow-down.prevent="selectNext()"
                       @keydown.arrow-up.prevent="selectPrev()"
                       @keydown.enter.prevent="executeSelected()">
            </div>

            <ul class="max-h-96 scroll-py-3 overflow-y-auto p-3" x-show="filteredActions.length > 0">
                <template x-for="(action, index) in filteredActions" :key="action.id">
                    <li class="group flex cursor-default select-none rounded-xl p-3"
                        :class="{ 'bg-gray-100 dark:bg-gray-700': activeIndex === index }"
                        @mouseenter="activeIndex = index"
                        @click="executeAction(action)">
                        <div class="flex h-10 w-10 flex-none items-center justify-center rounded-lg"
                             :class="action.colorClass || 'bg-indigo-500'">
                            <i :data-lucide="action.icon" class="h-6 w-6 text-white"></i>
                        </div>
                        <div class="ml-4 flex-auto">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200" x-text="action.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="action.description"></p>
                        </div>
                    </li>
                </template>
            </ul>

            <div class="py-14 px-6 text-center text-sm sm:px-14" x-show="query !== '' && filteredActions.length === 0">
                <i data-lucide="alert-circle" class="mx-auto h-6 w-6 text-gray-400"></i>
                <p class="mt-4 font-semibold text-gray-900 dark:text-white">No results found</p>
                <p class="mt-2 text-gray-500">No components found for this search term. Please try again.</p>
            </div>
            
            <div class="flex flex-wrap items-center bg-gray-50 dark:bg-gray-700/50 py-2.5 px-4 text-xs text-gray-700 dark:text-gray-400">
                Type <kbd class="mx-1 flex h-5 w-5 items-center justify-center rounded border bg-white dark:bg-gray-800 font-semibold sm:mx-2 border-gray-400 dark:border-gray-600">?</kbd> for help.
            </div>
        </div>
    </div>
</div>

<script>
function commandPalette() {
    return {
        open: false,
        query: '',
        activeIndex: 0,
        actions: [
            { id: 'new-sale', name: 'New Sale', description: 'Create a new sales record', icon: 'plus-circle', url: '{{ route("sales.create") }}', colorClass: 'bg-green-500' },
            { id: 'dashboard', name: 'Dashboard', description: 'Go to main dashboard', icon: 'layout-dashboard', url: '{{ route("dashboard") }}', colorClass: 'bg-indigo-500' },
            { id: 'sales', name: 'All Sales', description: 'View sales history', icon: 'shopping-cart', url: '{{ route("sales.index") }}', colorClass: 'bg-blue-500' },
            { id: 'products', name: 'Products', description: 'Manage inventory', icon: 'package', url: '{{ route("products.index") }}', colorClass: 'bg-orange-500' },
            { id: 'customers', name: 'Customers', description: 'Manage customer database', icon: 'users', url: '{{ route("customers.index") }}', colorClass: 'bg-purple-500' },
            { id: 'loans', name: 'Loans', description: 'View loans and debts', icon: 'credit-card', url: '{{ route("loans.index") }}', colorClass: 'bg-red-500' },
            { id: 'toggle-theme', name: 'Toggle Dark Mode', description: 'Switch between light and dark theme', icon: 'moon', action: 'toggleTheme', colorClass: 'bg-gray-600' },
        ],
        
        get filteredActions() {
            if (this.query === '') return this.actions;
            return this.actions.filter(action => {
                return action.name.toLowerCase().includes(this.query.toLowerCase()) ||
                       action.description.toLowerCase().includes(this.query.toLowerCase());
            });
        },

        init() {
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => this.$refs.searchInput.focus());
                    this.query = '';
                    this.activeIndex = 0;
                }
            });
            this.$watch('query', () => {
                this.activeIndex = 0;
            });
        },

        selectNext() {
            if (this.activeIndex < this.filteredActions.length - 1) {
                this.activeIndex++;
            }
        },

        selectPrev() {
            if (this.activeIndex > 0) {
                this.activeIndex--;
            }
        },

        executeSelected() {
            if (this.filteredActions.length > 0) {
                this.executeAction(this.filteredActions[this.activeIndex]);
            }
        },

        executeAction(action) {
            if (action.url) {
                window.location.href = action.url;
            } else if (action.action === 'toggleTheme') {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.theme = isDark ? 'dark' : 'light';
                this.open = false;
            }
        }
    }
}
</script>
