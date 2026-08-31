self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(self.clients.claim());
});

// Deliberately no fetch/cache handler.
// Private Backoffice responses must always come from the authenticated server.
