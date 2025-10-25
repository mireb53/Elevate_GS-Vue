
<template>
  <router-view />
  <FilePreviewModal />
  <NotificationToast />
  <!-- <PWAInstallPrompt /> -->
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import FilePreviewModal from './components/FilePreviewModal.vue'
import NotificationToast from './components/NotificationToast.vue'
import PWAInstallPrompt from './components/PWAInstallPrompt.vue'

const router = useRouter()
let connectionSetup = false

// Function to setup SSE connection
const setupConnection = () => {
  if (connectionSetup) {
    console.log('[App.vue] Connection already setup, skipping')
    return
  }
  
  const userId = localStorage.getItem('loggedInUserId')
  const authToken = localStorage.getItem('sse_auth_token')  // Use SSE-specific token
  
  // Don't try to connect if not logged in
  if (!userId || userId === 'null' || userId === 'undefined') {
    console.log('[App.vue] Not logged in - skipping SSE connection')
    return
  }
  
  if (!authToken) {
    console.log('[App.vue] No auth token - skipping SSE connection')
    return
  }
  
  if (!window.realtimeNotificationManager) {
    console.warn('[App.vue] realtimeNotificationManager not ready yet, retrying in 200ms...')
    setTimeout(setupConnection, 200)
    return
  }
  
  console.log('[App.vue] ✓ Setting up real-time notifications for user:', userId)
  
  try {
    window.realtimeNotificationManager.connect()
    console.log('[App.vue] ✓ SSE connection initiated')
    connectionSetup = true
  } catch (error) {
    console.error('[App.vue] ❌ Error connecting:', error)
    return
  }
  
  // Setup notification listener for all components
  window.realtimeNotificationManager.addListener((notification) => {
    console.log('[App.vue] 🔔 Real-time notification received:', notification)
    
    // Emit events for components to listen
    window.dispatchEvent(new CustomEvent('realtime-notification', {
      detail: notification
    }))
    
    if (notification.type === 'classwork_created') {
      window.dispatchEvent(new CustomEvent('classwork-created', {
        detail: notification
      }))
    }
  })
  
  console.log('[App.vue] ✓ Notification listener setup complete')
}

// Auto-connect to real-time notifications if user is logged in
onMounted(() => {
  console.log('[App.vue] onMounted - checking login status...')
  setupConnection()
})

// Also try to connect after navigation (in case user just logged in)
watch(() => router.currentRoute.value.path, (newPath) => {
  console.log('[App.vue] Route changed to:', newPath)
  // Try to setup connection on each route change (will skip if already setup)
  setTimeout(setupConnection, 100)
})

onUnmounted(() => {
  // Cleanup connection when app unmounts
  if (window.realtimeNotificationManager) {
    console.log('[App.vue] Disconnecting SSE...')
    window.realtimeNotificationManager.disconnect()
    connectionSetup = false
  }
})
</script>

<style>
/* You can put global styles here, but for now it's fine if empty */
body {
  margin: 0;
  font-family: sans-serif; /* Example global font */
}
</style>
