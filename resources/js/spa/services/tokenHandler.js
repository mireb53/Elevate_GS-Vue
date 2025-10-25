/**
 * JWT Token Handler for ElevateGS PWA
 * 
 * Handles JWT token storage, validation, and expiration checking
 */

const TOKEN_KEY = 'elevategs_auth_token';
const TOKEN_REFRESH_THRESHOLD = 5 * 60 * 1000; // Refresh if expires in < 5 minutes

class TokenHandler {
  /**
   * Save JWT token to localStorage
   * @param {string} token - JWT token
   */
  saveToken(token) {
    if (!token) {
      console.warn('[TokenHandler] Attempted to save empty token');
      return;
    }
    
    try {
      localStorage.setItem(TOKEN_KEY, token);
      
      // Extract and cache expiration time
      const payload = this.decodeToken(token);
      if (payload && payload.exp) {
        const expiresAt = payload.exp * 1000; // Convert to milliseconds
        localStorage.setItem(`${TOKEN_KEY}_expires`, expiresAt.toString());
      }
      
      console.log('[TokenHandler] ✓ Token saved');
    } catch (error) {
      console.error('[TokenHandler] Failed to save token:', error);
    }
  }

  /**
   * Get JWT token from localStorage
   * @returns {string|null}
   */
  getToken() {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch (error) {
      console.error('[TokenHandler] Failed to get token:', error);
      return null;
    }
  }

  /**
   * Clear JWT token from localStorage
   */
  clearToken() {
    try {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(`${TOKEN_KEY}_expires`);
      console.log('[TokenHandler] ✓ Token cleared');
    } catch (error) {
      console.error('[TokenHandler] Failed to clear token:', error);
    }
  }

  /**
   * Check if token exists
   * @returns {boolean}
   */
  hasToken() {
    return !!this.getToken();
  }

  /**
   * Decode JWT token (without verification)
   * @param {string} token - JWT token
   * @returns {Object|null} Decoded payload
   */
  decodeToken(token) {
    if (!token) return null;
    
    try {
      // JWT structure: header.payload.signature
      const parts = token.split('.');
      
      if (parts.length !== 3) {
        console.warn('[TokenHandler] Invalid JWT format');
        return null;
      }
      
      // Decode base64url payload
      const payload = parts[1];
      const decoded = this.base64UrlDecode(payload);
      
      return JSON.parse(decoded);
    } catch (error) {
      console.error('[TokenHandler] Failed to decode token:', error);
      return null;
    }
  }

  /**
   * Base64URL decode
   * @private
   */
  base64UrlDecode(str) {
    // Convert base64url to base64
    let base64 = str.replace(/-/g, '+').replace(/_/g, '/');
    
    // Pad with = to make length multiple of 4
    while (base64.length % 4) {
      base64 += '=';
    }
    
    // Decode base64
    const decoded = atob(base64);
    
    // Convert to UTF-8
    return decodeURIComponent(
      decoded.split('').map(c => {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
      }).join('')
    );
  }

  /**
   * Check if token is valid (not expired)
   * @param {string} token - JWT token (optional, uses stored token if not provided)
   * @returns {boolean}
   */
  isTokenValid(token = null) {
    const targetToken = token || this.getToken();
    
    if (!targetToken) {
      return false;
    }
    
    const payload = this.decodeToken(targetToken);
    
    if (!payload || !payload.exp) {
      console.warn('[TokenHandler] Token has no expiration');
      return false;
    }
    
    const expiresAt = payload.exp * 1000; // Convert to milliseconds
    const now = Date.now();
    
    const isValid = now < expiresAt;
    
    if (!isValid) {
      console.warn('[TokenHandler] Token expired');
    }
    
    return isValid;
  }

  /**
   * Check if token needs refresh
   * @returns {boolean}
   */
  needsRefresh() {
    const token = this.getToken();
    
    if (!token) {
      return false;
    }
    
    const payload = this.decodeToken(token);
    
    if (!payload || !payload.exp) {
      return false;
    }
    
    const expiresAt = payload.exp * 1000;
    const now = Date.now();
    const timeUntilExpiry = expiresAt - now;
    
    // Refresh if expiring in less than threshold
    return timeUntilExpiry > 0 && timeUntilExpiry < TOKEN_REFRESH_THRESHOLD;
  }

  /**
   * Get token expiration time
   * @returns {Date|null}
   */
  getTokenExpiration() {
    const token = this.getToken();
    
    if (!token) {
      return null;
    }
    
    const payload = this.decodeToken(token);
    
    if (!payload || !payload.exp) {
      return null;
    }
    
    return new Date(payload.exp * 1000);
  }

  /**
   * Get time until token expires
   * @returns {number} Milliseconds until expiration (negative if expired)
   */
  getTimeUntilExpiration() {
    const expiration = this.getTokenExpiration();
    
    if (!expiration) {
      return -1;
    }
    
    return expiration.getTime() - Date.now();
  }

  /**
   * Get user info from token
   * @returns {Object|null}
   */
  getUserFromToken() {
    const token = this.getToken();
    
    if (!token) {
      return null;
    }
    
    const payload = this.decodeToken(token);
    
    if (!payload) {
      return null;
    }
    
    // Extract common JWT claims
    return {
      id: payload.sub || payload.userId || payload.id,
      email: payload.email,
      role: payload.role,
      name: payload.name || payload.full_name,
      ...payload
    };
  }

  /**
   * Create auth header for API requests
   * @returns {Object}
   */
  getAuthHeader() {
    const token = this.getToken();
    
    if (!token) {
      return {};
    }
    
    return {
      'Authorization': `Bearer ${token}`
    };
  }

  /**
   * Format token expiration as human-readable string
   * @returns {string}
   */
  getExpirationString() {
    const expiration = this.getTokenExpiration();
    
    if (!expiration) {
      return 'Unknown';
    }
    
    const timeUntil = this.getTimeUntilExpiration();
    
    if (timeUntil < 0) {
      return 'Expired';
    }
    
    const hours = Math.floor(timeUntil / (1000 * 60 * 60));
    const minutes = Math.floor((timeUntil % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours > 0) {
      return `Expires in ${hours}h ${minutes}m`;
    } else {
      return `Expires in ${minutes}m`;
    }
  }
}

// Export singleton instance
export const tokenHandler = new TokenHandler();

// Make available globally for debugging
if (typeof window !== 'undefined') {
  window.tokenHandler = tokenHandler;
}
