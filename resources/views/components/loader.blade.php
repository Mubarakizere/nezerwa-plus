{{-- Clean Full-Screen Site Loader --}}
{{-- Minimalist, theme-aware, non-AI full-screen preloader that dismisses immediately on page ready --}}
<div id="site-loader"
     class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-white dark:bg-[#0f172a]"
     style="transition: opacity 0.25s ease, visibility 0.25s ease;"
     aria-live="polite"
     aria-busy="true">
    <div class="flex flex-col items-center justify-center gap-3">
        {{-- Clean modern circular spinner --}}
        <div class="relative w-10 h-10">
            <div class="w-10 h-10 rounded-full border-2 border-indigo-100 dark:border-slate-800"></div>
            <div class="w-10 h-10 rounded-full border-2 border-indigo-600 dark:border-indigo-400 border-t-transparent animate-spin absolute inset-0"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var dismissed = false;

    function hideLoader() {
        if (dismissed) return;
        dismissed = true;

        var loader = document.getElementById('site-loader');
        if (!loader) return;

        loader.style.opacity = '0';
        loader.style.visibility = 'hidden';
        loader.setAttribute('aria-busy', 'false');

        setTimeout(function () {
            if (loader && loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
        }, 260);
    }

    // Dismiss immediately once page resources are ready
    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
    }

    // Safety fallback so users are never stuck if a 3rd-party script/font hangs
    setTimeout(hideLoader, 2000);

    // Support browser back/forward cache
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hideLoader();
    });
})();
</script>
