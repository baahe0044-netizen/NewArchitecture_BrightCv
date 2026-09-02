/**
 * BrightCV service worker.
 *
 * Scope comes from this file's own location, so the app keeps working when it
 * is installed from a subdirectory.
 *
 * What is cached, and what deliberately is not:
 *
 * - Static assets (CSS, JS, icons) are cached and served cache-first. They
 *   already carry a ?v= build stamp, so a changed file is a different URL.
 * - The offline page is precached so a navigation without a network still
 *   lands somewhere useful.
 * - HTML pages and every /api/ response are NEVER cached. They contain the
 *   signed-in person's own CV content, and a cache on a shared device would
 *   outlive their session. Offline users get the offline page instead, and the
 *   builder's existing local draft recovery protects unsaved edits.
 */

const VERSION = 'brightcv-v1';
const ASSET_CACHE = VERSION + '-assets';
const SHELL_CACHE = VERSION + '-shell';
const SCOPE = new URL(self.registration.scope);
const OFFLINE_URL = new URL('offline.html', SCOPE).href;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE)
      .then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((key) => key.startsWith('brightcv-') && !key.startsWith(VERSION))
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

// The page asks for a clean slate on sign-out, so nothing survives for the
// next person to use the device.
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'clear-caches') {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key)))));
  }
});

const isAsset = (url) => url.pathname.includes('/assets/');

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;
  if (!url.pathname.startsWith(SCOPE.pathname)) return;

  // Never touch the API: those responses are per-user and often per-request.
  if (url.pathname.includes('/api/')) return;

  if (isAsset(url)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
          if (response && response.ok) {
            const copy = response.clone();
            caches.open(ASSET_CACHE).then((cache) => cache.put(request, copy));
          }
          return response;
        });
      })
    );
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(OFFLINE_URL).then(
        (cached) => cached || new Response(
          '<h1>Offline</h1><p>Reconnect to continue working on your CV.</p>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' }, status: 503 }
        )
      ))
    );
  }
});
