// service-worker.js — minimal PWA offline cache for the generic main.php
// layout (registered by register-sw.js). Not wired into the dashboard/auth
// pages, which don't reference /manifest.json.
// 1. install  — pre-caches the core boilerplate assets.
// 2. activate — deletes any cache left over from a previous CACHE_NAME.
// 3. fetch    — cache-first, falls back to the network on a miss.
const CACHE_NAME = 'mvc-app-cache-v1';
const urlsToCache = [
  '/css/style.css',
  '/js/main.js',
  '/manifest.json',
];

// Install: pre-cache core assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(urlsToCache))
  );
  self.skipWaiting();
});

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      )
    )
  );
  self.clients.claim();
});

// Fetch: serve from cache first, fall back to network
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((cached) => {
      return cached || fetch(event.request);
    })
  );
});