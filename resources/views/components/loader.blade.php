<div
    x-data="{
        loading: true,
        progress: 0,
        init() {
            let step = 0;
            const tick = setInterval(() => {
                step += Math.random() * 18 + 4;
                if (step >= 100) {
                    this.progress = 100;
                    clearInterval(tick);
                    setTimeout(() => this.loading = false, 400);
                } else {
                    this.progress = Math.round(step);
                }
            }, 200);
        }
    }"
    x-show="loading"
    x-transition:leave="transition ease-in duration-600"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-105"
    class="fixed inset-0 z-[9999] flex items-center justify-center"
    style="background: linear-gradient(135deg, #312e81 0%, #4338ca 40%, #6366f1 100%);"
>
    <div class="flex flex-col items-center">

        {{-- Wine Glass Icon --}}
        <div class="mb-6" style="opacity: 0.9;">
            <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2h8l-1.5 8c-.3 1.8-1.8 3-3.5 3h-1c-1.7 0-3.2-1.2-3.5-3L8 2z"/>
                <path d="M12 13v6"/>
                <path d="M8 22h8"/>
                <path d="M7.5 7h9"/>
            </svg>
        </div>

        {{-- Brand Name --}}
        <h1 class="text-white text-3xl font-bold tracking-widest mb-1" style="letter-spacing: 0.25em; font-family: 'Inter', sans-serif;">
            KINGWINE
        </h1>
        <p class="text-indigo-200 text-xs tracking-wider mb-8" style="letter-spacing: 0.15em;">
            LIQUOR &amp; SPIRITS
        </p>

        {{-- Progress Bar --}}
        <div class="w-48 h-1 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.15);">
            <div
                class="h-full rounded-full transition-all duration-200 ease-out"
                :style="'width: ' + progress + '%; background: linear-gradient(90deg, #c7d2fe, #ffffff);'"
            ></div>
        </div>

        {{-- Percentage --}}
        <p class="text-indigo-200 text-xs mt-3 tabular-nums" x-text="progress + '%'" style="font-family: 'Inter', monospace;"></p>

    </div>
</div>
