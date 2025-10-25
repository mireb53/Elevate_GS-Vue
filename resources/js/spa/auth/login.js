/**
 * Authentication and Login Handler for ElevateGS PWA
 * 
 * Features:
 * - JWT token-based authentication
 * - Offline login support with cached tokens
 * - Secure token storage in localStorage
 * - Auto-login when offline
 */

import { tokenHandler } from '../services/tokenHandler.js';
import { cacheService } from '../services/cacheService.js';

class AuthService {
  constructor() {
    this.API_BASE = window.BACKEND_API_BASE_URL || 'http://localhost:3000';
  }

  /**
   * Login with Google OAuth + PIN
   * @param {Object} googleUser - Google user profile
   * @param {string} pin - Optional PIN for verification
   * @returns {Promise<Object>} User data and token
   */
  async loginWithGoogle(googleUser, pin = null) {
    const profile = googleUser.getBasicProfile();
    const loginData = {
      google_id: profile.getId(),
      full_name: profile.getName(),
      email: profile.getEmail(),
      picture: profile.getImageUrl(),
      pin: pin
    };

    try {
      // Attempt online login
      const response = await fetch(`${this.API_BASE}/api/auth/google-login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(loginData)
      });

      const data = await response.json();

      if (data.status === 'success' && data.token) {
        // Save token and user data
        await this.saveAuthData(data);
        
        // Cache user data for offline access
        await cacheService.cacheUserData(data.user);
        
        return {
          success: true,
          user: data.user,
          requiresPIN: false
        };
      } else if (data.status === 'pin_required') {
        return {
          success: false,
          requiresPIN: true,
          demo_pin: data.demo_pin
        };
      } else {
        throw new Error(data.message || 'Login failed');
      }
    } catch (error) {
      console.error('Online login failed:', error);
      
      // Try offline login if we have cached credentials
      return await this.attemptOfflineLogin(loginData.email);
    }
  }

  /**
   * Traditional email/password login
   * @param {string} email 
   * @param {string} password 
   * @returns {Promise<Object>}
   */
  async loginWithCredentials(email, password) {
    try {
      const response = await fetch(`${this.API_BASE}/api/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();

      if (data.status === 'success' && data.token) {
        await this.saveAuthData(data);
        await cacheService.cacheUserData(data.user);
        
        return {
          success: true,
          user: data.user
        };
      } else {
        throw new Error(data.message || 'Invalid credentials');
      }
    } catch (error) {
      console.error('Online login failed:', error);
      
      // Try offline login
      return await this.attemptOfflineLogin(email);
    }
  }

  /**
   * Attempt to login using cached data when offline
   * @param {string} email 
   * @returns {Promise<Object>}
   */
  async attemptOfflineLogin(email) {
    console.log('[Auth] Attempting offline login...');
    
    const cachedUser = await cacheService.getCachedUserData();
    
    if (!cachedUser || cachedUser.email !== email) {
      throw new Error('No cached credentials found. Please connect to the internet to login.');
    }

    const token = tokenHandler.getToken();
    
    if (!token) {
      throw new Error('No saved token. Please connect to the internet to login.');
    }

    // Validate token structure (but we can't verify signature offline)
    if (!tokenHandler.isTokenValid(token)) {
      throw new Error('Cached token expired. Please connect to the internet to login.');
    }

    console.log('[Auth] ✓ Offline login successful');
    
    // Restore auth state
    this.restoreAuthState(cachedUser);
    
    return {
      success: true,
      user: cachedUser,
      offline: true
    };
  }

  /**
   * Auto-login on app start if valid token exists
   * @returns {Promise<Object|null>}
   */
  async autoLogin() {
    const token = tokenHandler.getToken();
    
    if (!token) {
      return null;
    }

    // Check if token is expired
    if (!tokenHandler.isTokenValid(token)) {
      console.log('[Auth] Token expired, clearing...');
      this.logout();
      return null;
    }

    const cachedUser = await cacheService.getCachedUserData();
    
    if (!cachedUser) {
      console.log('[Auth] No cached user data');
      return null;
    }

    console.log('[Auth] ✓ Auto-login successful');
    
    // Restore auth state
    this.restoreAuthState(cachedUser);
    
    // Try to refresh token in background if online
    if (navigator.onLine) {
      this.refreshTokenInBackground().catch(err => {
        console.warn('[Auth] Background token refresh failed:', err);
      });
    }

    return {
      success: true,
      user: cachedUser,
      auto: true
    };
  }

  /**
   * Refresh token in background
   * @private
   */
  async refreshTokenInBackground() {
    const token = tokenHandler.getToken();
    
    if (!token) return;

    try {
      const response = await fetch(`${this.API_BASE}/api/auth/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      });

      const data = await response.json();

      if (data.status === 'success' && data.token) {
        tokenHandler.saveToken(data.token);
        console.log('[Auth] ✓ Token refreshed');
      }
    } catch (error) {
      console.warn('[Auth] Token refresh failed:', error);
    }
  }

  /**
   * Save authentication data
   * @private
   * @param {Object} data - Response data containing token and user
   */
  async saveAuthData(data) {
    // Save JWT token
    tokenHandler.saveToken(data.token);
    
    // Save user info (backward compatible with existing code)
    localStorage.setItem('loggedInUserRole', data.user.role || 'student');
    localStorage.setItem('loggedInUserId', String(data.user.id));
    localStorage.setItem('loggedInUserName', data.user.full_name || data.user.name || '');
    localStorage.setItem('loggedInUserEmail', data.user.email || '');
    
    // Session storage (for compatibility)
    sessionStorage.setItem('userName', data.user.full_name || data.user.name || '');
    
    console.log('[Auth] ✓ Auth data saved');
  }

  /**
   * Restore auth state from cached user
   * @private
   */
  restoreAuthState(user) {
    localStorage.setItem('loggedInUserRole', user.role || 'student');
    localStorage.setItem('loggedInUserId', String(user.id));
    localStorage.setItem('loggedInUserName', user.full_name || user.name || '');
    localStorage.setItem('loggedInUserEmail', user.email || '');
    sessionStorage.setItem('userName', user.full_name || user.name || '');
  }

  /**
   * Logout user and clear all data
   */
  async logout() {
    // Clear token
    tokenHandler.clearToken();
    
    // Clear localStorage
    localStorage.removeItem('loggedInUserRole');
    localStorage.removeItem('loggedInUserId');
    localStorage.removeItem('loggedInUserName');
    localStorage.removeItem('loggedInUserEmail');
    
    // Clear sessionStorage
    sessionStorage.removeItem('userName');
    
    // Clear cached data
    await cacheService.clearUserCache();
    
    console.log('[Auth] ✓ Logged out');
  }

  /**
   * Check if user is currently logged in
   * @returns {boolean}
   */
  isLoggedIn() {
    const token = tokenHandler.getToken();
    return token && tokenHandler.isTokenValid(token);
  }

  /**
   * Get current user from localStorage
   * @returns {Object|null}
   */
  getCurrentUser() {
    const userId = localStorage.getItem('loggedInUserId');
    const userName = localStorage.getItem('loggedInUserName');
    const userRole = localStorage.getItem('loggedInUserRole');
    const userEmail = localStorage.getItem('loggedInUserEmail');
    
    if (!userId || userId === 'null') {
      return null;
    }
    
    return {
      id: userId,
      name: userName,
      role: userRole,
      email: userEmail
    };
  }

  /**
   * Get auth token for API requests
   * @returns {string|null}
   */
  getAuthToken() {
    return tokenHandler.getToken();
  }

  /**
   * Get auth headers for fetch requests
   * @returns {Object}
   */
  getAuthHeaders() {
    const token = this.getAuthToken();
    
    if (!token) {
      return {};
    }
    
    return {
      'Authorization': `Bearer ${token}`
    };
  }
}

// Export singleton instance
export const authService = new AuthService();

// Make available globally for legacy code
if (typeof window !== 'undefined') {
  window.authService = authService;
}
