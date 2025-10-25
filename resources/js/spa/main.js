import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

import 'bootstrap/dist/css/bootstrap.min.css';
// Ensure Bootstrap JS is available and exposed globally for components that call window.bootstrap
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';
// Expose on window for programmatic modal/show calls used across the app
// (e.g., AppLayout openCreateClassModal/openJoinClassModal, modal components)
// This mirrors the legacy project which relied on global bootstrap.
// eslint-disable-next-line no-undef
if (typeof window !== 'undefined') {
  // Attach only once to avoid reassign during HMR
  window.bootstrap = window.bootstrap || bootstrap;
}
import 'bootstrap-icons/font/bootstrap-icons.css';
import './assets/calendaradmin.css';
import './assets/classreecord.css';
import './assets/responsive.css';
import './assets/mobile-fixes.css'; // Mobile layout fixes - MUST load last
import { registerLegacyClassworkShim } from './compat/legacy-classwork-shim';
import { registerLegacyEventListeners } from './compat/legacy-event-listeners';

// Import offline and auth services
import { authService } from './auth/login.js';
import { offlineManager } from './services/offlineManager.js';
import { pushService } from './services/pushService.js';
import { realtimeNotificationManager } from './services/realtimeNotificationManager.js';

createApp(App)
  .use(router)
  .mount('#app');

// Register small compatibility shims
try { registerLegacyClassworkShim(router); } catch(e){ /* ignore */ }
try { registerLegacyEventListeners(router); } catch(e) { /* ignore */ }

// Register service worker for PWA offline support
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      // Register main service worker
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/'
      });
      
      console.log('[SW] Service worker registered successfully:', registration.scope);
      
      // Check for updates every 60 seconds
      setInterval(() => {
        registration.update();
      }, 60000);

      // Register Firebase Messaging service worker (if push enabled)
      if (await pushService.checkSupport?.() !== false) {
        try {
          await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
            scope: '/firebase-cloud-messaging-push-scope'
          });
          console.log('[FCM-SW] Firebase Messaging service worker registered');
        } catch (fcmError) {
          console.warn('[FCM-SW] Firebase Messaging SW registration failed:', fcmError);
        }
      }
      
      // Listen for service worker messages
      navigator.serviceWorker.addEventListener('message', (event) => {
        const { type, data } = event.data || {};
        
        switch (type) {
          case 'SW_ACTIVATED':
            console.log('[SW] Service worker activated:', data);
            break;
          case 'NOTIFICATION_CLICKED':
            // Handle notification click - navigate to URL
            if (data?.url) {
              router.push(data.url).catch(() => {});
            }
            break;
          case 'GRADE_SYNCED':
            console.log('[SW] Grade synced:', data);
            break;
          default:
            console.log('[SW] Message received:', type, data);
        }
      });
      
      // PWA Install Prompt
      let deferredPrompt;
      window.addEventListener('beforeinstallprompt', (e) => {
        console.log('[PWA] Install prompt event fired');
        e.preventDefault();
        deferredPrompt = e;
        
        // Dispatch custom event for components to show install button
        window.dispatchEvent(new CustomEvent('pwa-install-available', {
          detail: { prompt: deferredPrompt }
        }));
      });
      
      // Track install success
      window.addEventListener('appinstalled', (e) => {
        console.log('[PWA] App installed successfully');
        window.dispatchEvent(new CustomEvent('pwa-installed'));
      });
      
    } catch (error) {
      console.error('[SW] Service worker registration failed:', error);
    }
  });
}

// Auto-connect to real-time notifications if already logged in
window.addEventListener('load', async () => {
  // Check if user is already logged in
  const userId = localStorage.getItem('loggedInUserId');
  const authToken = localStorage.getItem('elevategs_auth_token');
  
  if (userId && authToken) {
    console.log('[App] User already logged in, connecting to real-time notifications...');
    
    // Connect to real-time notifications immediately
    realtimeNotificationManager.connect();
    
    // Setup notification listener
    realtimeNotificationManager.addListener((notification) => {
      console.log('[App] Real-time notification:', notification);
      
      // Emit event for Vue components to listen
      window.dispatchEvent(new CustomEvent('realtime-notification', {
        detail: notification
      }));
      
      // If it's a classwork notification, refresh the page data
      if (notification.type === 'classwork_created') {
        window.dispatchEvent(new CustomEvent('classwork-created', {
          detail: notification
        }));
      }
    });
  }
  
  // Try auto-login
  try {
    const result = await authService.autoLogin();
    
    if (result?.success) {
      console.log('[App] ✓ Auto-login successful');
      
      // If we didn't connect earlier (shouldn't happen, but just in case)
      if (!userId || !authToken) {
        realtimeNotificationManager.connect();
      }
      
      // Don't redirect if already logged in
      const currentPath = window.location.pathname;
      if (currentPath === '/login' || currentPath === '/') {
        const userRole = result.user.role || 'student';
        const redirectPath = userRole === 'teacher' ? '/teacher/dashboard' : '/dashboard';
        router.push(redirectPath).catch(() => {});
      }
    }
  } catch (error) {
    console.log('[App] Auto-login not available:', error.message);
  }
});

// Initialize offline manager
console.log('[App] Offline manager status:', offlineManager.getStatus() ? 'Online' : 'Offline');

// Global bridge: allow legacy scripts to request SPA navigation via window events.
window.addEventListener('spa:navigate', (e) => {
  try {
    const to = e?.detail?.to;
    if (!to) return;
    // If vue-router is available, navigate programmatically
    if (router && typeof router.push === 'function') {
      router.push(to).catch(()=>{});
      // mark event as handled so legacy code can fallback if needed
      if (e.stopImmediatePropagation) e.stopImmediatePropagation();
    }
  } catch (err) { /* ignore */ }
});

// Make services globally available
window.authService = authService;
window.offlineManager = offlineManager;
window.pushService = pushService;
window.realtimeNotificationManager = realtimeNotificationManager;
