/**
 * Real-time Notification Manager
 * Handles Server-Sent Events (SSE) connection for live notifications
 */

class RealtimeNotificationManager {
  constructor() {
    this.eventSource = null;
    this.listeners = [];
    this.isConnected = false;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 2000; // Start with 2 seconds
    this.connectTime = null; // Track when connection was established
    this.API_BASE = window.BACKEND_API_BASE_URL || 'http://localhost:3000';
  }

  /**
   * Connect to SSE stream
   */
  connect() {
    if (this.eventSource) {
      console.log('[RealtimeNotifications] Already connected');
      return;
    }

    const token = localStorage.getItem('elevategs_auth_token');
    const userId = localStorage.getItem('loggedInUserId');
    
    if (!userId) {
      console.warn('[RealtimeNotifications] No user ID found, cannot connect');
      return;
    }

    console.log('[RealtimeNotifications] Connecting to SSE stream for user:', userId);

    // Create EventSource with user ID in URL (since EventSource doesn't support custom headers)
    const url = `${this.API_BASE}/api/notifications/stream?userId=${userId}`;
    this.eventSource = new EventSource(url);
    this.connectTime = new Date();

    // Handle connection open
    this.eventSource.onopen = () => {
      console.log('[RealtimeNotifications] ✓ Connected to notification stream');
      this.isConnected = true;
      this.reconnectAttempts = 0;
      this.reconnectDelay = 2000;
      
      // Notify listeners of connection
      this.notifyListeners({
        type: 'connection',
        status: 'connected'
      });
    };

    // Handle incoming messages
    this.eventSource.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        console.log('[RealtimeNotifications] Received:', data);

        // Notify all listeners
        this.notifyListeners(data);

        // Show browser notification if it's a classwork notification
        if (data.type === 'classwork_created') {
          this.showBrowserNotification(data);
        }

      } catch (error) {
        console.error('[RealtimeNotifications] Error parsing message:', error);
      }
    };

    // Handle errors
    this.eventSource.onerror = (error) => {
      console.error('[RealtimeNotifications] Connection error:', error);
      this.isConnected = false;

      // Close the connection
      if (this.eventSource) {
        this.eventSource.close();
        this.eventSource = null;
      }

      // Notify listeners of disconnection
      this.notifyListeners({
        type: 'connection',
        status: 'disconnected'
      });

      // Attempt reconnection
      this.attemptReconnect();
    };
  }

  /**
   * Disconnect from SSE stream
   */
  disconnect() {
    if (this.eventSource) {
      console.log('[RealtimeNotifications] Disconnecting...');
      this.eventSource.close();
      this.eventSource = null;
      this.isConnected = false;
      
      this.notifyListeners({
        type: 'connection',
        status: 'disconnected'
      });
    }
  }

  /**
   * Attempt to reconnect with exponential backoff
   */
  attemptReconnect() {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('[RealtimeNotifications] Max reconnection attempts reached');
      return;
    }

    this.reconnectAttempts++;
    const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);

    console.log(`[RealtimeNotifications] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);

    setTimeout(() => {
      this.connect();
    }, delay);
  }

  /**
   * Add listener for notifications
   * @param {Function} callback 
   * @returns {Function} Unsubscribe function
   */
  addListener(callback) {
    this.listeners.push(callback);
    
    // Return unsubscribe function
    return () => {
      const index = this.listeners.indexOf(callback);
      if (index > -1) {
        this.listeners.splice(index, 1);
      }
    };
  }

  /**
   * Notify all listeners
   * @param {Object} data 
   */
  notifyListeners(data) {
    this.listeners.forEach(callback => {
      try {
        callback(data);
      } catch (error) {
        console.error('[RealtimeNotifications] Listener error:', error);
      }
    });
  }

  /**
   * Show browser notification
   * @param {Object} data 
   */
  async showBrowserNotification(data) {
    console.log('[RealtimeNotifications] showBrowserNotification called with:', data);
    
    // Check if notifications are supported and permission granted
    if (!('Notification' in window)) {
      console.warn('[RealtimeNotifications] Notification API not supported');
      return;
    }

    console.log('[RealtimeNotifications] Notification permission:', Notification.permission);

    if (Notification.permission === 'granted') {
      console.log('[RealtimeNotifications] Creating browser notification...');
      try {
        const notification = new Notification(data.title || 'New Notification', {
          body: data.message,
          icon: '/icons/icon-192x192.png',
          badge: '/icons/icon-96x96.png',
          tag: `classwork-${data.classwork_id}`,
          data: data,
          requireInteraction: false
        });

        notification.onclick = () => {
          console.log('[RealtimeNotifications] Notification clicked');
          // Navigate to classwork page
          window.focus();
          if (data.class_id && data.classwork_id) {
            const url = `/class/${data.class_id}/classwork/${data.classwork_id}`;
            if (window.router) {
              window.router.push(url).catch(() => {});
            } else {
              window.location.href = url;
            }
          }
          notification.close();
        };
        
        notification.onshow = () => {
          console.log('[RealtimeNotifications] ✅ Browser notification DISPLAYED!');
        };
        
        notification.onerror = (error) => {
          console.error('[RealtimeNotifications] Browser notification error:', error);
        };
        
        console.log('[RealtimeNotifications] ✅ Browser notification created successfully');
      } catch (error) {
        console.error('[RealtimeNotifications] Error creating notification:', error);
      }
    } else if (Notification.permission === 'default') {
      console.log('[RealtimeNotifications] Requesting notification permission...');
      // Request permission
      const permission = await Notification.requestPermission();
      console.log('[RealtimeNotifications] Permission result:', permission);
      if (permission === 'granted') {
        this.showBrowserNotification(data);
      }
    } else {
      console.warn('[RealtimeNotifications] Notification permission denied');
    }
  }

  /**
   * Save FCM token to backend
   * @param {string} token 
   */
  async saveFCMToken(token) {
    try {
      const authToken = localStorage.getItem('elevategs_auth_token');
      if (!authToken) {
        console.warn('[RealtimeNotifications] No auth token, cannot save FCM token');
        return false;
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
        console.log('[RealtimeNotifications] ✓ FCM token saved');
        return true;
      } else {
        console.error('[RealtimeNotifications] Failed to save FCM token:', await response.text());
        return false;
      }
    } catch (error) {
      console.error('[RealtimeNotifications] Error saving FCM token:', error);
      return false;
    }
  }

  /**
   * Get connection status
   * @returns {boolean}
   */
  isConnectionActive() {
    return this.isConnected;
  }

  /**
   * Get stats
   * @returns {Object}
   */
  getStats() {
    return {
      connected: this.isConnected,
      listeners: this.listeners.length,
      reconnectAttempts: this.reconnectAttempts
    };
  }
}

// Export singleton instance
export const realtimeNotificationManager = new RealtimeNotificationManager();
export default realtimeNotificationManager;
