<template>
  <div v-if="notifications.length > 0" class="notification-container">
    <TransitionGroup name="notification">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="['notification-toast', notification.type]"
        @click="removeNotification(notification.id)"
      >
        <div class="notification-icon">
          <i v-if="notification.type === 'classwork'" class="bi bi-file-earmark-text"></i>
          <i v-else-if="notification.type === 'grade'" class="bi bi-star"></i>
          <i v-else-if="notification.type === 'announcement'" class="bi bi-megaphone"></i>
          <i v-else class="bi bi-bell"></i>
        </div>
        <div class="notification-content">
          <div class="notification-title">{{ notification.title }}</div>
          <div class="notification-message">{{ notification.message }}</div>
          <div class="notification-time">{{ formatTime(notification.timestamp) }}</div>
        </div>
        <button class="notification-close" @click.stop="removeNotification(notification.id)">
          <i class="bi bi-x"></i>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const notifications = ref([])
let notificationId = 0

const addNotification = (data) => {
  const id = notificationId++
  
  const notification = {
    id,
    type: getNotificationType(data.type),
    title: data.title || 'Notification',
    message: data.message || '',
    timestamp: Date.now()
  }
  
  console.log('[NotificationToast] Adding notification:', notification)
  notifications.value.push(notification)
  
  // NOTE: Browser notifications are handled by realtimeNotificationManager
  // We only show the toast notification here
  console.log('[NotificationToast] Toast notification added (browser notification handled by SSE manager)')
  
  // Auto-remove after 5 seconds
  setTimeout(() => {
    removeNotification(id)
  }, 5000)
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
  }
}

const getNotificationType = (type) => {
  if (type === 'classwork_created') return 'classwork'
  if (type === 'grade_posted') return 'grade'
  if (type === 'announcement') return 'announcement'
  return 'info'
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString('en-US', { 
    hour: 'numeric', 
    minute: '2-digit',
    hour12: true 
  })
}

const handleRealtimeNotification = (event) => {
  const data = event.detail
  console.log('[NotificationToast] 🔔 Received real-time notification event:', data)
  addNotification(data)
}

onMounted(() => {
  // Listen for real-time notifications
  window.addEventListener('realtime-notification', handleRealtimeNotification)
  window.addEventListener('classwork-created', handleRealtimeNotification)
  
  // Request notification permission immediately
  if ('Notification' in window && Notification.permission === 'default') {
    console.log('[NotificationToast] Requesting browser notification permission...')
    Notification.requestPermission().then(permission => {
      console.log('[NotificationToast] Permission:', permission)
      if (permission === 'granted') {
        // Show welcome notification
        new Notification('ElevateGS Notifications Enabled', {
          body: 'You will now receive real-time notifications!',
          icon: '/icons/icon-192x192.png',
          tag: 'welcome'
        })
      }
    })
  }
  
  // Expose method globally for manual notifications
  window.showNotificationToast = (title, message, type = 'info') => {
    addNotification({ title, message, type })
  }
})

onUnmounted(() => {
  window.removeEventListener('realtime-notification', handleRealtimeNotification)
  window.removeEventListener('classwork-created', handleRealtimeNotification)
})
</script>

<style scoped>
.notification-container {
  position: fixed;
  top: 80px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 400px;
  pointer-events: none;
}

.notification-toast {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  padding: 16px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  pointer-events: all;
  transition: all 0.3s ease;
  border-left: 4px solid #667eea;
}

.notification-toast:hover {
  transform: translateX(-5px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.notification-toast.classwork {
  border-left-color: #667eea;
}

.notification-toast.grade {
  border-left-color: #f59e0b;
}

.notification-toast.announcement {
  border-left-color: #10b981;
}

.notification-toast.info {
  border-left-color: #6366f1;
}

.notification-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 18px;
}

.notification-toast.grade .notification-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.notification-toast.announcement .notification-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 4px;
  font-size: 15px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.notification-message {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.4;
  margin-bottom: 4px;
}

.notification-time {
  font-size: 12px;
  color: #9ca3af;
}

.notification-close {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  color: #9ca3af;
  cursor: pointer;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  font-size: 18px;
}

.notification-close:hover {
  background: #f3f4f6;
  color: #1f2937;
}

/* Transition animations */
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100px);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100px);
}

.notification-move {
  transition: transform 0.3s ease;
}

/* Mobile responsive */
@media (max-width: 640px) {
  .notification-container {
    top: 70px;
    right: 10px;
    left: 10px;
    max-width: none;
  }
  
  .notification-toast {
    padding: 12px;
  }
  
  .notification-icon {
    width: 36px;
    height: 36px;
    font-size: 16px;
  }
  
  .notification-title {
    font-size: 14px;
  }
  
  .notification-message {
    font-size: 13px;
  }
}
</style>
