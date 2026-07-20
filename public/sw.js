const CACHE_NAME = 'siasek-cache-v2';
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
    // Hanya tangani permintaan GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Untuk permintaan navigasi (halaman HTML), gunakan jaringan dulu, lalu fallback ke offline
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match('/offline').then(response => {
                    // Jika halaman offline tidak ada di cache, kembalikan response default agar tidak ERR_FAILED
                    return response || new Response('Anda sedang offline dan halaman offline tidak tersedia.', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: new Headers({ 'Content-Type': 'text/plain' })
                    });
                });
            })
        );
    } else {
        // Untuk aset lainnya (CSS, JS, gambar), gunakan cache dulu, lalu fallback ke jaringan
        event.respondWith(
            caches.match(event.request).then(response => {
                return response || fetch(event.request);
            })
        );
    }
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
