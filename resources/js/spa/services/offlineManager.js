/**
 * Offline Manager for ElevateGS PWA
 * 
 * Monitors online/offline status and manages offline features
 */

import { cacheService } from './cacheService.js';
import { authService } from '../auth/login.js';

class OfflineManager {
  constructor() {
    this.isOnline = navigator.onLine;
    this.listeners = new Set();
    this.pendingSyncs = 0;
    
    this.init();
  }

  /**
   * Initialize offline manager
   * @private
   */
  init() {
    // Listen for online/offline events
    window.addEventListener('online', () => this.handleOnline());
    window.addEventListener('offline', () => this.handleOffline());
    
    // Check initial status
    if (!this.isOnline) {
      this.handleOffline();
    }
    
    console.log('[OfflineManager] ✓ Initialized');
  }

  /**
   * Handle going online
   * @private
   */
  async handleOnline() {
    console.log('[OfflineManager] 🟢 Online');
    this.isOnline = true;
    
    // Notify listeners
    this.notifyListeners('online');
    
    // Hide offline banner
    this.hideOfflineBanner();
    
    // Sync pending changes
    await this.syncPendingChanges();
    
    // Emit custom event
    window.dispatchEvent(new CustomEvent('app-online'));
  }

  /**
   * Handle going offline
   * @private
   */
  handleOffline() {
    console.log('[OfflineManager] 🔴 Offline');
    this.isOnline = false;
    
    // Notify listeners
    this.notifyListeners('offline');
    
    // Show offline banner
    this.showOfflineBanner();
    
    // Emit custom event
    window.dispatchEvent(new CustomEvent('app-offline'));
  }

  /**
   * Show offline mode banner
   * @private
   */
  showOfflineBanner() {
    // Remove existing banner
    this.hideOfflineBanner();
    
    const banner = document.createElement('div');
    banner.id = 'offline-banner';
    banner.className = 'offline-banner';
    banner.innerHTML = `
      <div class="offline-banner-content">
        <i class="bi bi-wifi-off"></i>
        <span>Offline Mode Active - Changes will sync when you reconnect</span>
        <button class="offline-banner-close" onclick="this.parentElement.parentElement.remove()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    `;
    
    // Add styles
    const style = document.createElement('style');
    style.textContent = `
      .offline-banner {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 16px;
        z-index: 9999;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        animation: slideDown 0.3s ease-out;
      }
      
      .offline-banner-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        max-width: 1200px;
        margin: 0 auto;
        font-size: 14px;
        font-weight: 500;
      }
      
      .offline-banner i.bi-wifi-off {
        font-size: 18px;
        animation: pulse 2s ease-in-out infinite;
      }
      
      .offline-banner-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
        transition: background 0.2s;
      }
      
      .offline-banner-close:hover {
        background: rgba(255,255,255,0.3);
      }
      
      @keyframes slideDown {
        from {
          transform: translateY(-100%);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
      
      @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
      }
    `;
    
    if (!document.getElementById('offline-banner-styles')) {
      style.id = 'offline-banner-styles';
      document.head.appendChild(style);
    }
    
    document.body.insertBefore(banner, document.body.firstChild);
  }

  /**
   * Hide offline mode banner
   * @private
   */
  hideOfflineBanner() {
    const banner = document.getElementById('offline-banner');
    if (banner) {
      banner.style.animation = 'slideDown 0.3s ease-out reverse';
      setTimeout(() => banner.remove(), 300);
    }
  }

  /**
   * Sync pending changes when back online
   * @private
   */
  async syncPendingChanges() {
    try {
      const pendingChanges = await cacheService.getPendingChanges();
      
      if (pendingChanges.length === 0) {
        console.log('[OfflineManager] No pending changes to sync');
        return;
      }
      
      console.log(`[OfflineManager] Syncing ${pendingChanges.length} pending changes...`);
      this.pendingSyncs = pendingChanges.length;
      
      let syncedCount = 0;
      
      for (const change of pendingChanges) {
        try {
          await this.syncChange(change);
          await cacheService.markChangeSynced(change.id);
          syncedCount++;
        } catch (error) {
          console.error('[OfflineManager] Failed to sync change:', change, error);
        }
      }
      
      this.pendingSyncs = 0;
      
      if (syncedCount > 0) {
        this.showSyncNotification(syncedCount);
      }
      
      console.log(`[OfflineManager] ✓ Synced ${syncedCount}/${pendingChanges.length} changes`);
    } catch (error) {
      console.error('[OfflineManager] Sync failed:', error);
    }
  }

  /**
   * Sync a single change
   * @private
   */
  async syncChange(change) {
    const { type, action, data } = change;
    const API_BASE = window.BACKEND_API_BASE_URL || 'http://localhost:3000';
    
    // Get auth token
    const token = authService.getAuthToken();
    const headers = {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {})
    };
    
    let url = '';
    let method = 'POST';
    
    // Map change type to API endpoint
    switch (type) {
      case 'grade_update':
        url = `${API_BASE}/api/classes/${data.classId}/gradebook/cell`;
        method = 'PUT';
        break;
      case 'submission':
        url = `${API_BASE}/api/classwork/${data.classworkId}/submit`;
        method = 'POST';
        break;
      case 'comment':
        url = `${API_BASE}/api/classwork/${data.classworkId}/comments`;
        method = 'POST';
        break;
      default:
        console.warn('[OfflineManager] Unknown change type:', type);
        return;
    }
    
    const response = await fetch(url, {
      method,
      headers,
      body: JSON.stringify(data)
    });
    
    if (!response.ok) {
      throw new Error(`Sync failed: ${response.status} ${response.statusText}`);
    }
    
    return response.json();
  }

  /**
   * Show sync success notification
   * @private
   */
  showSyncNotification(count) {
    const notification = document.createElement('div');
    notification.className = 'sync-notification';
    notification.innerHTML = `
      <i class="bi bi-check-circle-fill"></i>
      <span>${count} change${count > 1 ? 's' : ''} synced successfully</span>
    `;
    
    const style = document.createElement('style');
    style.textContent = `
      .sync-notification {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 9998;
        animation: slideIn 0.3s ease-out;
        font-size: 14px;
        font-weight: 500;
      }
      
      .sync-notification i {
        font-size: 20px;
      }
      
      @keyframes slideIn {
        from {
          transform: translateX(120%);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }
    `;
    
    if (!document.getElementById('sync-notification-styles')) {
      style.id = 'sync-notification-styles';
      document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.style.animation = 'slideIn 0.3s ease-out reverse';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  /**
   * Add status change listener
   * @param {Function} callback - Called with 'online' or 'offline'
   */
  addListener(callback) {
    this.listeners.add(callback);
  }

  /**
   * Remove status change listener
   * @param {Function} callback
   */
  removeListener(callback) {
    this.listeners.delete(callback);
  }

  /**
   * Notify all listeners
   * @private
   */
  notifyListeners(status) {
    this.listeners.forEach(callback => {
      try {
        callback(status);
      } catch (error) {
        console.error('[OfflineManager] Listener error:', error);
      }
    });
  }

  /**
   * Get current online status
   * @returns {boolean}
   */
  getStatus() {
    return this.isOnline;
  }

  /**
   * Get pending sync count
   * @returns {number}
   */
  getPendingSyncCount() {
    return this.pendingSyncs;
  }

  /**
   * Manually trigger sync
   */
  async manualSync() {
    if (!this.isOnline) {
      console.warn('[OfflineManager] Cannot sync while offline');
      return;
    }
    
    await this.syncPendingChanges();
  }
}

// Export singleton instance
export const offlineManager = new OfflineManager();

// Make available globally
if (typeof window !== 'undefined') {
  window.offlineManager = offlineManager;
}
