/*
 * SmartLabel staff PWA service worker. Makes the dashboard installable and shows
 * a friendly offline page. It never caches dashboard pages or data: payments and
 * stock must always be live, and a shared phone must not keep a copy of them.
 */
const CACHE = 'smartlabel-shell-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE).then(cache => cache.add(OFFLINE_URL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  // Only page navigations, and never the customer QR pages (/p/...).
  if (request.mode !== 'navigate' || new URL(request.url).pathname.startsWith('/p/')) return;

  event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
});
