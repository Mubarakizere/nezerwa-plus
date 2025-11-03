{{-- Global Toasts (bottom-right, color-coded, animated) --}}
<div
    x-data="{ toasts: [] }"
    x-init="
        const messages = [
            @if (session('success')) { type: 'success', text: '{{ session('success') }}' }, @endif
            @if (session('error'))   { type: 'error',   text: '{{ session('error') }}' },   @endif
            @if (session('warning')) { type: 'warning', text: '{{ session('warning') }}' }, @endif
            @if (session('info'))    { type: 'info',    text: '{{ session('info') }}' },    @endif
        ];
        messages.forEach((m, i) => setTimeout(() => toasts.push(m), i * 200));
    "
    class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-3 items-end w-full max-w-sm"
>
    <template x-for="(toast, index) in toasts" :key="index">
        <div
            x-show="true"
            x-transition:enter="transform ease-out duration-300"
            x-transition:enter-start="translate-x-20 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transform ease-in duration-300"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-20 opacity-0"
            @click="toasts.splice(index, 1)"
            x-init="setTimeout(() => toasts.splice(index, 1), 4500)"
            class="flex items-start gap-3 w-full rounded-lg shadow-lg px-4 py-3 cursor-pointer
                   text-sm font-medium text-white hover:shadow-xl transition
                   border-l-4"
            :class="{
                'bg-green-600 dark:bg-green-700 border-green-400': toast.type === 'success',
                'bg-red-600 dark:bg-red-700 border-red-400': toast.type === 'error',
                'bg-yellow-500 dark:bg-yellow-600 border-yellow-300 text-gray-900 dark:text-gray-100': toast.type === 'warning',
                'bg-blue-600 dark:bg-blue-700 border-blue-400': toast.type === 'info',
            }"
        >
            {{-- Icon --}}
            <template x-if="toast.type === 'success'">
                <i data-lucide='check-circle' class="w-5 h-5 text-white mt-0.5"></i>
            </template>
            <template x-if="toast.type === 'error'">
                <i data-lucide='x-circle' class="w-5 h-5 text-white mt-0.5"></i>
            </template>
            <template x-if="toast.type === 'warning'">
                <i data-lucide='alert-triangle' class="w-5 h-5 text-white mt-0.5"></i>
            </template>
            <template x-if="toast.type === 'info'">
                <i data-lucide='info' class="w-5 h-5 text-white mt-0.5"></i>
            </template>

            {{-- Message --}}
            <p x-text="toast.text" class="flex-1"></p>

            {{-- Close --}}
            <button class="text-white/80 hover:text-white" @click.stop="toasts.splice(index, 1)">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </template>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
