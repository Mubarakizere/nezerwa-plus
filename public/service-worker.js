const CACHE_NAME = 'stock-manager-cache-v3';
const OFFLINE_URL = '/offline.html';
const urlsToCache = [
  '/',
  OFFLINE_URL,
  '/manifest.json',
  '/favicon.ico',
  '/build/assets/app.css',
  '/build/assets/app.js',
];

// ✅ Install — cache the app shell
self.addEventListener('install', (event) => {
  console.log('Installing Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
      .then(() => self.skipWaiting())
  );
});

// ✅ Activate — clean old caches
self.addEventListener('activate', (event) => {
  console.log('Activating Service Worker...');
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.map((key) => {
        if (key !== CACHE_NAME) return caches.delete(key);
      }))
    )
  );
  self.clients.claim();
});

// ✅ Fetch — use cache-first, then update network
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        return response;
      })
      .catch(() => caches.match(event.request).then((res) =>
        res || caches.match(OFFLINE_URL)
      ))
  );
});

// ✅ Background Sync — triggered when back online
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-sales') {
    event.waitUntil(syncOfflineSales());
  }
});

// 🧩 Helper function: sync offline sales to server
async function syncOfflineSales() {
  console.log('🔄 Syncing offline sales...');
  const db = await openDB('StockManagerDB', 1);
  const tx = db.transaction('offline_sales', 'readonly');
  const store = tx.objectStore('offline_sales');
  const allReq = store.getAll();

  return new Promise((resolve) => {
    allReq.onsuccess = async () => {
      const sales = allReq.result || [];
      if (!sales.length) {
        console.log(' No offline sales to sync');
        resolve();
        return;
      }

      try {
        await fetch('/api/sync/offline-sales', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ sales }),
        });

        const tx2 = db.transaction('offline_sales', 'readwrite');
        tx2.objectStore('offline_sales').clear();
        console.log(' Offline sales synced successfully');
      } catch (err) {
        console.error('❌ Sync failed, will retry later', err);
      }
      resolve();
    };
  });
}

//  Helper: IndexedDB connection
function openDB(name, version) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(name, version);
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('offline_sales')) {
        db.createObjectStore('offline_sales', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}
