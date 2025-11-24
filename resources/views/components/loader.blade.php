<div 
    x-data="{ loading: true }" 
    x-init="window.onload = () => { setTimeout(() => loading = false, 800); }"
    x-show="loading"
    x-transition:leave="transition ease-in duration-500"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/90 backdrop-blur-sm"
    style="display: none;"
>
    <div class="flex flex-col items-center gap-4">
        {{-- Cute Bouncing Dots Animation --}}
        <div class="flex space-x-3">
            <div class="w-4 h-4 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
            <div class="w-4 h-4 bg-purple-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
            <div class="w-4 h-4 bg-pink-500 rounded-full animate-bounce"></div>
        </div>
        <p class="text-sm font-medium text-gray-500 animate-pulse">Loading...</p>
    </div>
</div>
