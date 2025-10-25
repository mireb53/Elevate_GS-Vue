/**
 * Cache Service for ElevateGS PWA
 * 
 * Handles offline data caching using IndexedDB and localStorage
 * Stores user data, courses, grades, and classwork for offline access
 */

const DB_NAME = 'elevategs-offline';
const DB_VERSION = 2;

// Store names
const STORES = {
  USER_DATA: 'user_data',
  COURSES: 'courses',
  GRADES: 'grades',
  CLASSWORK: 'classwork',
  PEOPLE: 'people',
  SUBMISSIONS: 'submissions',
  PENDING_CHANGES: 'pending_changes'
};

class CacheService {
  constructor() {
    this.db = null;
    this.initPromise = this.initDB();
  }

  /**
   * Initialize IndexedDB
   * @private
   */
  async initDB() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onerror = () => {
        console.error('[CacheService] IndexedDB error:', request.error);
        reject(request.error);
      };

      request.onsuccess = () => {
        this.db = request.result;
        console.log('[CacheService] ✓ IndexedDB initialized');
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // Create object stores
        if (!db.objectStoreNames.contains(STORES.USER_DATA)) {
          db.createObjectStore(STORES.USER_DATA, { keyPath: 'id' });
        }

        if (!db.objectStoreNames.contains(STORES.COURSES)) {
          const courseStore = db.createObjectStore(STORES.COURSES, { keyPath: 'id' });
          courseStore.createIndex('userId', 'userId', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORES.GRADES)) {
          const gradeStore = db.createObjectStore(STORES.GRADES, { keyPath: 'id', autoIncrement: true });
          gradeStore.createIndex('courseId', 'courseId', { unique: false });
          gradeStore.createIndex('userId', 'userId', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORES.CLASSWORK)) {
          const classworkStore = db.createObjectStore(STORES.CLASSWORK, { keyPath: 'id' });
          classworkStore.createIndex('courseId', 'courseId', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORES.PEOPLE)) {
          const peopleStore = db.createObjectStore(STORES.PEOPLE, { keyPath: 'id' });
          peopleStore.createIndex('courseId', 'courseId', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORES.SUBMISSIONS)) {
          const submissionStore = db.createObjectStore(STORES.SUBMISSIONS, { keyPath: 'id', autoIncrement: true });
          submissionStore.createIndex('classworkId', 'classworkId', { unique: false });
          submissionStore.createIndex('studentId', 'studentId', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORES.PENDING_CHANGES)) {
          const pendingStore = db.createObjectStore(STORES.PENDING_CHANGES, { keyPath: 'id', autoIncrement: true });
          pendingStore.createIndex('type', 'type', { unique: false });
          pendingStore.createIndex('timestamp', 'timestamp', { unique: false });
        }

        console.log('[CacheService] ✓ Database upgraded to version', DB_VERSION);
      };
    });
  }

  /**
   * Ensure DB is initialized
   * @private
   */
  async ensureDB() {
    if (!this.db) {
      await this.initPromise;
    }
    return this.db;
  }

  /**
   * Generic method to save data
   * @private
   */
  async saveToStore(storeName, data) {
    const db = await this.ensureDB();
    
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.put(data);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  /**
   * Generic method to get data
   * @private
   */
  async getFromStore(storeName, key) {
    const db = await this.ensureDB();
    
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.get(key);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  /**
   * Generic method to get all data from a store
   * @private
   */
  async getAllFromStore(storeName) {
    const db = await this.ensureDB();
    
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.getAll();

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  /**
   * Get data by index
   * @private
   */
  async getByIndex(storeName, indexName, value) {
    const db = await this.ensureDB();
    
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const index = store.index(indexName);
      const request = index.getAll(value);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);
    });
  }

  /**
   * Clear a store
   * @private
   */
  async clearStore(storeName) {
    const db = await this.ensureDB();
    
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.clear();

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve();
    });
  }

  // ============ USER DATA ============

  /**
   * Cache user data
   * @param {Object} userData - User information
   */
  async cacheUserData(userData) {
    try {
      await this.saveToStore(STORES.USER_DATA, {
        id: userData.id || userData.email,
        ...userData,
        cachedAt: Date.now()
      });
      console.log('[CacheService] ✓ User data cached');
    } catch (error) {
      console.error('[CacheService] Failed to cache user data:', error);
    }
  }

  /**
   * Get cached user data
   * @returns {Promise<Object|null>}
   */
  async getCachedUserData() {
    try {
      const userId = localStorage.getItem('loggedInUserId');
      const userEmail = localStorage.getItem('loggedInUserEmail');
      const key = userId || userEmail;
      
      if (!key) return null;
      
      return await this.getFromStore(STORES.USER_DATA, key);
    } catch (error) {
      console.error('[CacheService] Failed to get user data:', error);
      return null;
    }
  }

  /**
   * Clear user cache
   */
  async clearUserCache() {
    try {
      await this.clearStore(STORES.USER_DATA);
      console.log('[CacheService] ✓ User cache cleared');
    } catch (error) {
      console.error('[CacheService] Failed to clear user cache:', error);
    }
  }

  // ============ COURSES ============

  /**
   * Cache courses list
   * @param {Array} courses - Array of course objects
   * @param {string} userId - User ID
   */
  async cacheCourses(courses, userId) {
    try {
      for (const course of courses) {
        await this.saveToStore(STORES.COURSES, {
          ...course,
          userId: userId,
          cachedAt: Date.now()
        });
      }
      console.log(`[CacheService] ✓ Cached ${courses.length} courses`);
    } catch (error) {
      console.error('[CacheService] Failed to cache courses:', error);
    }
  }

  /**
   * Get cached courses
   * @param {string} userId - User ID
   * @returns {Promise<Array>}
   */
  async getCachedCourses(userId) {
    try {
      return await this.getByIndex(STORES.COURSES, 'userId', userId);
    } catch (error) {
      console.error('[CacheService] Failed to get cached courses:', error);
      return [];
    }
  }

  // ============ GRADES ============

  /**
   * Cache grades for a course
   * @param {string} courseId - Course ID
   * @param {Array} grades - Array of grade objects
   */
  async cacheGrades(courseId, grades) {
    try {
      for (const grade of grades) {
        await this.saveToStore(STORES.GRADES, {
          ...grade,
          courseId: courseId,
          cachedAt: Date.now()
        });
      }
      console.log(`[CacheService] ✓ Cached ${grades.length} grades for course ${courseId}`);
    } catch (error) {
      console.error('[CacheService] Failed to cache grades:', error);
    }
  }

  /**
   * Get cached grades for a course
   * @param {string} courseId - Course ID
   * @returns {Promise<Array>}
   */
  async getCachedGrades(courseId) {
    try {
      return await this.getByIndex(STORES.GRADES, 'courseId', courseId);
    } catch (error) {
      console.error('[CacheService] Failed to get cached grades:', error);
      return [];
    }
  }

  // ============ CLASSWORK ============

  /**
   * Cache classwork for a course
   * @param {string} courseId - Course ID
   * @param {Array} classwork - Array of classwork objects
   */
  async cacheClasswork(courseId, classwork) {
    try {
      for (const work of classwork) {
        await this.saveToStore(STORES.CLASSWORK, {
          ...work,
          courseId: courseId,
          cachedAt: Date.now()
        });
      }
      console.log(`[CacheService] ✓ Cached ${classwork.length} classwork for course ${courseId}`);
    } catch (error) {
      console.error('[CacheService] Failed to cache classwork:', error);
    }
  }

  /**
   * Get cached classwork for a course
   * @param {string} courseId - Course ID
   * @returns {Promise<Array>}
   */
  async getCachedClasswork(courseId) {
    try {
      return await this.getByIndex(STORES.CLASSWORK, 'courseId', courseId);
    } catch (error) {
      console.error('[CacheService] Failed to get cached classwork:', error);
      return [];
    }
  }

  // ============ PENDING CHANGES ============

  /**
   * Add pending change to sync later
   * @param {Object} change - Change object with type, action, data
   */
  async addPendingChange(change) {
    try {
      await this.saveToStore(STORES.PENDING_CHANGES, {
        ...change,
        timestamp: Date.now(),
        synced: false
      });
      console.log('[CacheService] ✓ Pending change added:', change.type);
    } catch (error) {
      console.error('[CacheService] Failed to add pending change:', error);
    }
  }

  /**
   * Get all pending changes
   * @returns {Promise<Array>}
   */
  async getPendingChanges() {
    try {
      const changes = await this.getAllFromStore(STORES.PENDING_CHANGES);
      return changes.filter(c => !c.synced);
    } catch (error) {
      console.error('[CacheService] Failed to get pending changes:', error);
      return [];
    }
  }

  /**
   * Mark change as synced
   * @param {number} changeId - Change ID
   */
  async markChangeSynced(changeId) {
    try {
      const change = await this.getFromStore(STORES.PENDING_CHANGES, changeId);
      if (change) {
        change.synced = true;
        change.syncedAt = Date.now();
        await this.saveToStore(STORES.PENDING_CHANGES, change);
      }
    } catch (error) {
      console.error('[CacheService] Failed to mark change as synced:', error);
    }
  }

  /**
   * Clear all pending changes
   */
  async clearPendingChanges() {
    try {
      await this.clearStore(STORES.PENDING_CHANGES);
      console.log('[CacheService] ✓ Pending changes cleared');
    } catch (error) {
      console.error('[CacheService] Failed to clear pending changes:', error);
    }
  }

  /**
   * Get cache statistics
   * @returns {Promise<Object>}
   */
  async getCacheStats() {
    try {
      const [courses, grades, classwork, pending] = await Promise.all([
        this.getAllFromStore(STORES.COURSES),
        this.getAllFromStore(STORES.GRADES),
        this.getAllFromStore(STORES.CLASSWORK),
        this.getPendingChanges()
      ]);

      return {
        courses: courses.length,
        grades: grades.length,
        classwork: classwork.length,
        pendingChanges: pending.length
      };
    } catch (error) {
      console.error('[CacheService] Failed to get cache stats:', error);
      return {
        courses: 0,
        grades: 0,
        classwork: 0,
        pendingChanges: 0
      };
    }
  }

  /**
   * Clear all caches
   */
  async clearAllCaches() {
    try {
      await Promise.all([
        this.clearStore(STORES.USER_DATA),
        this.clearStore(STORES.COURSES),
        this.clearStore(STORES.GRADES),
        this.clearStore(STORES.CLASSWORK),
        this.clearStore(STORES.PEOPLE),
        this.clearStore(STORES.SUBMISSIONS),
        this.clearStore(STORES.PENDING_CHANGES)
      ]);
      console.log('[CacheService] ✓ All caches cleared');
    } catch (error) {
      console.error('[CacheService] Failed to clear all caches:', error);
    }
  }
}

// Export singleton instance
export const cacheService = new CacheService();

// Make available globally for debugging
if (typeof window !== 'undefined') {
  window.cacheService = cacheService;
}
