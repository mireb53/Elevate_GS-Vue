// Minimal firebase messaging service worker stub
// If your project doesn't use FCM right now this is a safe noop file to avoid registration errors.

self.addEventListener('push', function(event) {
  // Optionally handle push messages
  console.log('[FCM-SW] push received', event);
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(clients.openWindow('/'));
});

console.log('[FCM-SW] Firebase messaging stub loaded');
