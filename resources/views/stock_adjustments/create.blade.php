<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Adjust Stock: ') }} {{ $product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    <div class="mb-6">
                        <p class="text-gray-600">
                            Use this form to correct the stock level after a physical survey or audit. 
                            The system will automatically create a stock movement (IN or OUT) to match the actual stock you enter.
                        </p>
                    </div>

                    <form action="{{ route('products.adjust.store', $product) }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Current Stock (Read-only) -->
                            <div>
                                <x-input-label for="current_stock" :value="__('Current System Stock')" />
                                <x-text-input id="current_stock" class="block mt-1 w-full bg-gray-100" type="text" value="{{ $product->currentStock() }}" disabled />
                            </div>

                            <!-- Actual Stock -->
                            <div>
                                <x-input-label for="actual_stock" :value="__('Actual Physical Stock')" />
                                <x-text-input id="actual_stock" class="block mt-1 w-full" type="number" step="0.01" name="actual_stock" :value="old('actual_stock')" required autofocus />
                                <x-input-error :messages="$errors->get('actual_stock')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-4">
                            <x-input-label for="notes" :value="__('Reason / Notes')" />
                            <textarea id="notes" name="notes" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="e.g. Annual Survey, Damaged goods found...">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('products.show', $product) }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <x-primary-button>
                                {{ __('Update Stock') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
