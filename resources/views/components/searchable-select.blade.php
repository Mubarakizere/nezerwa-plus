@props([
    'name',
    'options' => [],
    'optionsJs' => null, // Name of the JS variable containing options
    'value' => '',
    'label' => 'name',
    'track' => 'id',
    'placeholder' => 'Select option',
    'required' => false,
    'emit' => null,
])

<div x-data="{
        open: false,
        search: '',
        selected: @js($value),
        options: [],
        
        init() {
            if (@js($optionsJs)) {
                this.options = {{ $optionsJs }};
            } else {
                this.options = @js($options);
            }
        },
        
        get filteredOptions() {
            if (this.search === '') {
                return this.options;
            }
            return this.options.filter(option => {
                return String(option[this.labelKey]).toLowerCase().includes(this.search.toLowerCase());
            });
        },
        
        get selectedLabel() {
            const found = this.options.find(o => o[this.trackKey] == this.selected);
            return found ? found[this.labelKey] : this.placeholder;
        },

        labelKey: @js($label),
        trackKey: @js($track),
        placeholder: @js($placeholder),

        select(option) {
            this.selected = option[this.trackKey];
            this.open = false;
            this.search = '';
            
            // Dispatch native event for x-model
            this.$dispatch('input', this.selected);
            
            // Dispatch custom event if requested
            if (@js($emit)) {
                this.$dispatch(@js($emit), option);
            }
        },
        
        clear() {
            this.selected = '';
            this.open = false;
            this.$dispatch('input', '');
            if (@js($emit)) {
                this.$dispatch(@js($emit) + '-cleared');
            }
        }
    }"
    class="relative w-full"
    @click.outside="open = false"
>
    {{-- Hidden Input for Form Submission --}}
    <input type="hidden" :name="name" :value="selected">

    {{-- Trigger Button --}}
    <button type="button" 
            @click="open = !open; $nextTick(() => $refs.searchInput.focus())"
            class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg py-2 px-3 text-left shadow-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm flex items-center justify-between"
    >
        <span class="block truncate" :class="{'text-gray-500': !selected, 'text-gray-900 dark:text-gray-100': selected}" x-text="selectedLabel"></span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </span>
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="open" 
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
         style="min-width: 200px;" 
    >
        {{-- Search Input --}}
        <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 px-2 py-1.5 border-b border-gray-100 dark:border-gray-700">
            <input type="text" 
                   x-ref="searchInput"
                   x-model="search"
                   class="w-full border-0 border-b border-gray-200 dark:border-gray-700 bg-transparent focus:ring-0 text-sm p-1 text-gray-900 dark:text-gray-100 placeholder-gray-400"
                   placeholder="Search..."
            >
        </div>

        {{-- Options List --}}
        <ul class="max-h-48 overflow-y-auto">
            <template x-for="option in filteredOptions" :key="option[trackKey]">
                <li class="text-gray-900 dark:text-gray-100 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                    @click="select(option)"
                >
                    <span class="block truncate" :class="{'font-semibold': selected === option[trackKey], 'font-normal': selected !== option[trackKey]}" x-text="option[labelKey]"></span>
                    
                    <span x-show="selected === option[trackKey]" class="text-indigo-600 absolute inset-y-0 right-0 flex items-center pr-4">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            </template>
            <li x-show="filteredOptions.length === 0" class="text-gray-500 dark:text-gray-400 cursor-default select-none relative py-2 pl-3 pr-9 text-sm">
                No results found.
            </li>
        </ul>
    </div>
</div>
