// Retired service worker.
// The old one kept CSS/JS forever, so visitors never saw site updates.
// This version deletes those caches and removes itself; the site no longer registers a service worker.
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.map((key) => caches.delete(key)));
        await self.registration.unregister();
    })());
});
