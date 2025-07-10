const CACHE_NAME = 'siasek-cache-v1';
const urlsToCache = [
    '/',
    '/offline',
    // Tambahkan aset penting lainnya di sini (CSS, JS, gambar utama)
    // Contoh: '/css/app.css', '/js/app.js'
];

// Instalasi Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Menggunakan Cache
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Jika ada di cache, kembalikan dari cache
                if (response) {
                    return response;
                }
                
                // Jika tidak, coba ambil dari jaringan
                return fetch(event.request).catch(() => {
                    // Jika jaringan gagal, tampilkan halaman offline
                    return caches.match('/offline');
                });
            })
    );
});

// Aktivasi & Membersihkan Cache Lama
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
