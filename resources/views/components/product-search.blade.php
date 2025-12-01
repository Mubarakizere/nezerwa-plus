@props(['name' => 'product_id', 'value' => '', 'placeholder' => 'Search product...', 'required' => false])

<div x-data="productAutocomplete({
    name: '{{ $name }}',
    value: '{{ $value }}',
    url: '{{ route('products.search.json') }}'
})"
class="relative w-full"
@click.outside="close()">

    {{-- Hidden Input for Form Submission --}}
    <input type="hidden" :name="name" x-model="value">

    {{-- Search Input --}}
    <div class="relative">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <input
            type="text"
            x-model="query"
            @input.debounce.300ms="search()"
            @focus="open = true"
            @keydown.escape="close()"
            @keydown.arrow-down.prevent="highlightNext()"
            @keydown.arrow-up.prevent="highlightPrev()"
            @keydown.enter.prevent="selectHighlighted()"
            placeholder="{{ $placeholder }}"
            :required="required && !value"
            class="form-input w-full pl-9 text-sm"
            autocomplete="off"
        >
        {{-- Clear Button --}}
        <button type="button" x-show="query" @click="clear()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <i data-lucide="x" class="w-3 h-3"></i>
        </button>
    </div>

    {{-- Dropdown --}}
    <div x-show="open && (results.length > 0 || loading || noResults)"
         x-transition
         class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">

        {{-- Loading --}}
        <div x-show="loading" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Searching...
        </div>

        {{-- No Results --}}
        <div x-show="!loading && noResults" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
            No products found.
        </div>

        {{-- Results --}}
        <ul x-show="!loading && results.length > 0">
            <template x-for="(item, index) in results" :key="item.id">
                <li
                    @click="select(item)"
                    @mouseenter="highlightedIndex = index"
                    :class="{ 'bg-indigo-50 dark:bg-indigo-900/30': index === highlightedIndex }"
                    class="px-4 py-2 cursor-pointer border-b border-gray-100 dark:border-gray-700/50 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium text-gray-800 dark:text-gray-200 text-sm" x-text="item.name"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="item.sku || 'No SKU'"></div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium text-indigo-600 dark:text-indigo-400 text-sm" x-text="'RWF ' + Number(item.price).toLocaleString()"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="item.stock + ' in stock'"></div>
                        </div>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productAutocomplete', (config) => ({
                name: config.name,
                value: config.value,
                url: config.url,
                query: '',
                results: [],
                open: false,
                loading: false,
                noResults: false,
                highlightedIndex: -1,

                init() {
                    // If there's an initial value, we might want to fetch the name.
                    // For now, we assume the parent handles initial population or we leave it empty.
                    // Ideally, we'd pass the initial name as a prop too.
                },

                async search() {
                    if (!this.query) {
                        this.results = [];
                        this.open = false;
                        return;
                    }

                    this.loading = true;
                    this.open = true;
                    this.noResults = false;

                    try {
                        const res = await fetch(`${this.url}?q=${encodeURIComponent(this.query)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) throw new Error('Failed');
                        this.results = await res.json();
                        this.noResults = this.results.length === 0;
                        this.highlightedIndex = -1;
                    } catch (e) {
                        console.error(e);
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },

                select(item) {
                    this.value = item.id;
                    this.query = item.name; // Set input to name
                    this.open = false;
                    
                    // Dispatch event for parent to listen (e.g. to update price)
                    this.$dispatch('product-selected', item);
                },

                selectHighlighted() {
                    if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
                        this.select(this.results[this.highlightedIndex]);
                    }
                },

                highlightNext() {
                    if (this.highlightedIndex < this.results.length - 1) {
                        this.highlightedIndex++;
                    }
                },

                highlightPrev() {
                    if (this.highlightedIndex > 0) {
                        this.highlightedIndex--;
                    }
                },

                close() {
                    this.open = false;
                    // If cleared, reset value
                    if (!this.query) {
                        this.value = '';
                        this.$dispatch('product-cleared');
                    }
                },

                clear() {
                    this.query = '';
                    this.value = '';
                    this.results = [];
                    this.open = false;
                    this.$dispatch('product-cleared');
                }
            }));
        });
    </script>
@endonce
