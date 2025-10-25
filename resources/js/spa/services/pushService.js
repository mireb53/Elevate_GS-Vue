/**
 * Push Notification Service for ElevateGS PWA
 * 
 * Features:
 * - Firebase Cloud Messaging integration
 * - Push notification permission management
 * - FCM token handling
 * - Foreground and background notification handling
 * 
 * Note: Requires HTTPS for production (works on localhost for testing)
 */

import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getMessaging, getToken, onMessage } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging.js';

// Firebase configuration (Demo project for testing - replace with your own)
const firebaseConfig = {
  apiKey: "AIzaSyDemoKey123456789",
  authDomain: "elevategs-demo.firebaseapp.com",
  projectId: "elevategs-demo",
  storageBucket: "elevategs-demo.appspot.com",
  messagingSenderId: "123456789012",
  appId: "1:123456789012:web:abcdef123456"
};

// VAPID key for web push (Demo key - replace with your own)
const VAPID_KEY = "BDemo-VapidKey-1234567890-abcdefghijklmnopqrstuvwxyz-ABCDEFGHIJKLMNOPQRSTUVWXYZ_1234567890";

class PushService {
  constructor() {
    this.app = null;
    this.messaging = null;
    this.currentToken = null;
    this.isSupported = this.checkSupport();
    this.API_BASE = window.BACKEND_API_BASE_URL || 'http://localhost:3000';
  }

  /**
   * Check if push notifications are supported
   * @private
   */
  checkSupport() {
    const hasServiceWorker = 'serviceWorker' in navigator;
    const hasNotification = 'Notification' in window;
    const hasPushManager = 'PushManager' in window;
    
    if (!hasServiceWorker) {
      console.warn('[PushService] Service Worker not supported');
    }
    if (!hasNotification) {
      console.warn('[PushService] Notifications not supported');
    }
    if (!hasPushManager) {
      console.warn('[PushService] Push Manager not supported');
    }
    
    return hasServiceWorker && hasNotification && hasPushManager;
  }

  /**
   * Initialize Firebase and FCM
   */
  async init() {
    if (!this.isSupported) {
      console.warn('[PushService] Push notifications not supported on this browser');
      return false;
    }

    try {
      // Initialize Firebase
      this.app = initializeApp(firebaseConfig);
      this.messaging = getMessaging(this.app);
      
      console.log('[PushService] ✓ Firebase initialized');
      
      // Setup foreground message handler
      this.setupMessageHandler();
      
      return true;
    } catch (error) {
      console.error('[PushService] Firebase initialization failed:', error);
      return false;
    }
  }

  /**
   * Request notification permission
   * @returns {Promise<string>} Permission state: 'granted', 'denied', or 'default'
   */
  async requestPermission() {
    if (!this.isSupported) {
      return 'unsupported';
    }

    try {
      const permission = await Notification.requestPermission();
      
      console.log('[PushService] Notification permission:', permission);
      
      if (permission === 'granted') {
        console.log('[PushService] ✓ Notification permission granted');
        
        // Get FCM token after permission granted
        await this.getFCMToken();
      } else if (permission === 'denied') {
        console.warn('[PushService] Notification permission denied');
      }
      
      return permission;
    } catch (error) {
      console.error('[PushService] Error requesting permission:', error);
      return 'error';
    }
  }

  /**
   * Get current notification permission status
   * @returns {string}
   */
  getPermissionStatus() {
    if (!this.isSupported) {
      return 'unsupported';
    }
    
    return Notification.permission;
  }

  /**
   * Check if notifications are enabled
   * @returns {boolean}
   */
  isEnabled() {
    return this.isSupported && Notification.permission === 'granted';
  }

  /**
   * Get FCM token
   * @returns {Promise<string|null>}
   */
  async getFCMToken() {
    if (!this.isSupported) {
      console.warn('[PushService] FCM not supported');
      return null;
    }

    if (!this.messaging) {
      await this.init();
    }

    try {
      // Wait for service worker to be ready
      const registration = await navigator.serviceWorker.ready;
      
      // Get FCM token
      const token = await getToken(this.messaging, {
        vapidKey: VAPID_KEY,
        serviceWorkerRegistration: registration
      });

      if (token) {
        this.currentToken = token;
        
        // Save token to localStorage
        localStorage.setItem('fcm_token', token);
        
        console.log('[PushService] ✓ FCM Token:', token);
        console.log('[PushService] Token saved to localStorage');
        
        // Send token to backend (when ready)
        await this.sendTokenToBackend(token);
        
        return token;
      } else {
        console.warn('[PushService] No FCM token available');
        return null;
      }
    } catch (error) {
      console.error('[PushService] Error getting FCM token:', error);
      return null;
    }
  }

  /**
   * Send FCM token to backend
   * @private
   * @param {string} token - FCM token
   */
  async sendTokenToBackend(token) {
    try {
      // Use the realtime notification manager to save token
      if (window.realtimeNotificationManager) {
        const saved = await window.realtimeNotificationManager.saveFCMToken(token);
        if (saved) {
          console.log('[PushService] ✓ FCM token saved to backend');
          return;
        }
      }
      
      // Fallback: direct API call
      const authToken = localStorage.getItem('elevategs_auth_token');
      if (!authToken) {
        console.log('[PushService] No auth token, skipping token sync');
        return;
      }

      const deviceInfo = {
        userAgent: navigator.userAgent,
        platform: navigator.platform,
        language: navigator.language
      };

      const response = await fetch(`${this.API_BASE}/api/notifications/fcm-token`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify({
          token,
          deviceInfo
        })
      });

      if (response.ok) {
        console.log('[PushService] ✓ FCM token saved to backend');
      } else {
        console.error('[PushService] Failed to save FCM token:', response.statusText);
      }
    } catch (error) {
      console.error('[PushService] Error saving FCM token to backend:', error);
    }
  }

  /**
   * Get device info for token registration
   * @private
   */
  getDeviceInfo() {
    return {
      userAgent: navigator.userAgent,
      platform: navigator.platform,
      language: navigator.language,
      timestamp: new Date().toISOString()
    };
  }

  /**
   * Setup foreground message handler
   * @private
   */
  setupMessageHandler() {
    if (!this.messaging) {
      return;
    }

    onMessage(this.messaging, (payload) => {
      console.log('[PushService] Foreground message received:', payload);
      
      const { notification, data } = payload;
      
      if (notification) {
        // Show notification using Notification API
        this.showNotification(notification.title, {
          body: notification.body,
          icon: notification.icon || '/icons/icon-192x192.png',
          badge: '/icons/icon-72x72.png',
          data: data,
          tag: data?.tag || 'elevategs-notification',
          requireInteraction: false
        });
        
        // Emit custom event for app to handle
        window.dispatchEvent(new CustomEvent('push-notification', {
          detail: { notification, data }
        }));
      }
    });
  }

  /**
   * Show notification
   * @param {string} title - Notification title
   * @param {Object} options - Notification options
   */
  async showNotification(title, options = {}) {
    if (!this.isEnabled()) {
      console.warn('[PushService] Cannot show notification: not enabled');
      return;
    }

    try {
      const registration = await navigator.serviceWorker.ready;
      
      await registration.showNotification(title, {
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        ...options
      });
      
      console.log('[PushService] ✓ Notification shown:', title);
    } catch (error) {
      console.error('[PushService] Error showing notification:', error);
    }
  }

  /**
   * Show local notification (test purposes)
   * @param {string} title 
   * @param {string} message 
   */
  async showTestNotification(title = 'ElevateGS Test', message = 'Push notifications are working!') {
    await this.showNotification(title, {
      body: message,
      icon: '/icons/icon-192x192.png',
      badge: '/icons/icon-72x72.png',
      tag: 'test-notification',
      requireInteraction: false,
      actions: [
        { action: 'open', title: 'Open App' },
        { action: 'close', title: 'Dismiss' }
      ]
    });
  }

  /**
   * Delete FCM token
   */
  async deleteToken() {
    if (!this.messaging || !this.currentToken) {
      console.log('[PushService] No token to delete');
      return;
    }

    try {
      // Note: Firebase Messaging v10+ doesn't have deleteToken
      // Token will expire naturally or can be invalidated on backend
      
      localStorage.removeItem('fcm_token');
      this.currentToken = null;
      
      console.log('[PushService] ✓ Token deleted from localStorage');
    } catch (error) {
      console.error('[PushService] Error deleting token:', error);
    }
  }

  /**
   * Get stored FCM token
   * @returns {string|null}
   */
  getStoredToken() {
    return localStorage.getItem('fcm_token');
  }

  /**
   * Subscribe to topic (requires backend support)
   * @param {string} topic - Topic name
   */
  async subscribeToTopic(topic) {
    const token = this.currentToken || this.getStoredToken();
    
    if (!token) {
      console.warn('[PushService] No token available for topic subscription');
      return false;
    }

    try {
      const response = await fetch(`${this.API_BASE}/api/notifications/subscribe-topic`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('elevategs_auth_token')}`
        },
        body: JSON.stringify({
          token: token,
          topic: topic
        })
      });

      if (response.ok) {
        console.log(`[PushService] ✓ Subscribed to topic: ${topic}`);
        return true;
      } else {
        console.warn(`[PushService] Failed to subscribe to topic: ${topic}`);
        return false;
      }
    } catch (error) {
      console.error('[PushService] Error subscribing to topic:', error);
      return false;
    }
  }

  /**
   * Get notification statistics
   * @returns {Object}
   */
  getStats() {
    return {
      supported: this.isSupported,
      permission: this.getPermissionStatus(),
      enabled: this.isEnabled(),
      hasToken: !!this.getStoredToken(),
      token: this.getStoredToken() ? '***' + this.getStoredToken().slice(-10) : null
    };
  }
}

// Export singleton instance
export const pushService = new PushService();

// Make available globally
if (typeof window !== 'undefined') {
  window.pushService = pushService;
}

// Auto-initialize if user is logged in
if (typeof window !== 'undefined') {
  window.addEventListener('load', async () => {
    const userId = localStorage.getItem('loggedInUserId');
    
    if (userId && pushService.getPermissionStatus() === 'granted') {
      // NOTE: Firebase FCM is disabled due to invalid demo credentials
      // The app uses SSE (Server-Sent Events) for real-time notifications instead
      // Browser notifications still work via Notification API
      console.log('[PushService] User logged in, using SSE for real-time notifications');
      console.log('[PushService] To enable Firebase FCM, update firebaseConfig in pushService.js');
      
      // Uncomment below when you have valid Firebase credentials:
      // await pushService.init();
      // if (!pushService.getStoredToken()) {
      //   await pushService.getFCMToken();
      // }
    }
  });
}
