// Minimal service worker for GradSmart
// This provides a stable /sw.js to satisfy the SPA registration.

self.addEventListener('install', (event) => {
  // Activate immediately
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  // Take control of all clients immediately
  event.waitUntil(self.clients.claim());
});

// Optional: basic fetch handler - allow requests to proceed normally
self.addEventListener('fetch', (event) => {
  // We don't intercept fetches by default, but this listener keeps the SW alive for messages.
});

self.addEventListener('message', (event) => {
  // Handle messages from the page if needed
  const data = event.data || {};
  if (data && data.type === 'PING') {
    event.source && event.source.postMessage({ type: 'PONG' });
  }
});

console.log('[SW] Minimal service worker active');
