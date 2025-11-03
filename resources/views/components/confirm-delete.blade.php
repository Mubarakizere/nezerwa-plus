<div
    x-data="{ open: false, action: null }"
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
>
    <div
        @click.outside="open = false"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 w-full max-w-md border border-gray-200 dark:border-gray-700"
    >
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
            Confirm Deletion
        </h2>
        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">
            Are you sure you want to delete this item? This action cannot be undone.
        </p>

        <div class="flex justify-end gap-3">
            <button type="button"
                    class="btn btn-outline"
                    @click="open = false">
                Cancel
            </button>
            <button type="button"
                    class="btn btn-danger"
                    @click="action?.submit(); open = false;">
                Delete
            </button>
        </div>
    </div>
</div>
