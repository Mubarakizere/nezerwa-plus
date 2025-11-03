// ===============================================
// ✅ Laravel + Vite main entry for JS
// ===============================================

import './bootstrap';
import Alpine from 'alpinejs';

// Optional: make Alpine globally available
window.Alpine = Alpine;

// ===============================================
// ✅ Register global Alpine stores BEFORE start()
// ===============================================
document.addEventListener('alpine:init', () => {
    Alpine.store('deleteConfirm', {
        open(url) {
            this.url = url;
            this.visible = true;
        },
        close() {
            this.visible = false;
            this.url = null;
        },
        confirm() {
            if (this.url) {
                document.getElementById('deleteForm').action = this.url;
                document.getElementById('deleteForm').submit();
            }
            this.close();
        },
        visible: false,
        url: null,
    });
});

// ✅ Start Alpine AFTER registering stores
Alpine.start();

// ===============================================
// ✅ Chart.js setup (used in dashboard)
// ===============================================
import Chart from 'chart.js/auto';
window.Chart = Chart;

// ===============================================
// ✅ Service Worker registration (for PWA support)
// ===============================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/service-worker.js')
            .then(() => console.log('✅ Service Worker registered successfully'))
            .catch(err => console.error('❌ Service Worker registration failed:', err));
    });
}

// ===============================================
// ✅ Optional: load any static assets via Vite
// ===============================================
import.meta.glob([
    '../images/**',
]);

console.log('✅ app.js loaded and running');
