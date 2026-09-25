// Service worker RuangKelas.
// Sengaja minimal: halaman (HTML) tidak pernah disimpan di cache karena berisi
// data pribadi siswa dan harus selalu terbaru; yang disimpan hanya aset build
// (nama berkasnya ber-hash, jadi aman disimpan lama) dan halaman offline.
const VERSION = 'ruangkelas-v1';
const OFFLINE_URL = new URL('offline.html', self.registration.scope).href;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.add(OFFLINE_URL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));

        return;
    }

    const url = new URL(request.url);

    if (url.origin === self.location.origin && url.pathname.includes('/build/assets/')) {
        event.respondWith(
            caches.open(VERSION).then((cache) =>
                cache.match(request).then((cached) => cached || fetch(request).then((response) => {
                    if (response.ok) {
                        cache.put(request, response.clone());
                    }

                    return response;
                })),
            ),
        );
    }
});
