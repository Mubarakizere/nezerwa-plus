<div
    x-data="{
        open: false,
        targetForm: null,
        title: 'Confirm Deletion',
        message: 'Are you sure you want to delete this item?',
        confirmText: 'Delete',
        confirmColor: 'btn-danger'
    }"
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    @open-delete-modal.window="
        open = true;
        targetForm = $event.detail.formId;
        title = $event.detail.title || 'Confirm Deletion';
        message = $event.detail.message || 'Are you sure you want to delete this item?';
        confirmText = $event.detail.confirmText || 'Delete';
        confirmColor = $event.detail.confirmColor || 'btn-danger';
    "
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
>
    <div
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-full max-w-md border border-gray-200 dark:border-gray-700"
    >
        <div class="text-center sm:text-left">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2" x-text="title"></h2>
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-6 leading-relaxed" x-text="message"></p>
        </div>

        <div class="flex justify-end gap-3 items-center">
            <button type="button"
                    class="btn btn-outline px-4 py-2"
                    @click="open = false">
                Cancel
            </button>
            <button type="button"
                    :class="'btn ' + confirmColor"
                    class="px-4 py-2"
                    @click="if(targetForm) document.getElementById(targetForm).submit(); open = false;">
                <span x-text="confirmText"></span>
            </button>
        </div>
    </div>
</div>
