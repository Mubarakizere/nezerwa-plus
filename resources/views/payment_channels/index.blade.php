<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Channels') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ deleteChannelId: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between mb-4">
                        <h3 class="text-lg font-medium">Manage Payment Channels</h3>
                        <a href="{{ route('payment-channels.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Add New Channel
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($channels as $channel)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $channel->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $channel->slug }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($channel->is_active)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('payment-channels.edit', $channel) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                            <button @click="deleteChannelId = {{ $channel->id }}" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $channels->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="deleteChannelId" x-cloak
             class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-sm w-full">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Confirm Deletion
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Are you sure you want to delete this payment channel? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteChannelId = null" class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">Cancel</button>
                    <form :action="`{{ url('payment-channels') }}/${deleteChannelId}`" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
