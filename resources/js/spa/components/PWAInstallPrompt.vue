<template>
  <div v-if="showPrompt" class="pwa-install-prompt">
    <div class="install-content">
      <div class="install-icon">
        <i class="bi bi-download"></i>
      </div>
      <div class="install-text">
        <h4>Install ElevateGS</h4>
        <p>Get the full app experience with offline access and notifications</p>
      </div>
      <div class="install-actions">
        <button @click="install" class="btn-install">
          <i class="bi bi-check-circle"></i>
          Install
        </button>
        <button @click="dismiss" class="btn-dismiss">
          <i class="bi bi-x"></i>
          Later
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const showPrompt = ref(false)
const deferredPrompt = ref(null)

let installListener, installedListener

onMounted(() => {
  // Listen for install prompt availability
  installListener = (e) => {
    deferredPrompt.value = e.detail.prompt
    showPrompt.value = true
  }

  // Listen for successful installation
  installedListener = () => {
    showPrompt.value = false
  }

  window.addEventListener('pwa-install-available', installListener)
  window.addEventListener('pwa-installed', installedListener)
})

onUnmounted(() => {
  window.removeEventListener('pwa-install-available', installListener)
  window.removeEventListener('pwa-installed', installedListener)
})

const install = async () => {
  if (deferredPrompt.value) {
    deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice
    console.log('[PWA] User install choice:', outcome)
    deferredPrompt.value = null
    showPrompt.value = false
  }
}

const dismiss = () => {
  showPrompt.value = false
  // Store dismissal in localStorage to not show again for a while
  localStorage.setItem('pwa-install-dismissed', Date.now().toString())
}
</script>

<style scoped>
.pwa-install-prompt {
  position: fixed;
  bottom: 20px;
  left: 20px;
  right: 20px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(128, 0, 0, 0.1);
  z-index: 10000;
  animation: slideUp 0.3s ease-out;
}

.install-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.install-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #800000, #a00000);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.install-text {
  flex: 1;
}

.install-text h4 {
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a1a1a;
}

.install-text p {
  margin: 0;
  font-size: 0.9rem;
  color: #666;
  line-height: 1.4;
}

.install-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.btn-install, .btn-dismiss {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.9rem;
}

.btn-install {
  background: #800000;
  color: white;
}

.btn-install:hover {
  background: #600000;
  transform: translateY(-1px);
}

.btn-dismiss {
  background: #f8f9fa;
  color: #666;
  border: 1px solid #e9ecef;
}

.btn-dismiss:hover {
  background: #e9ecef;
}

@keyframes slideUp {
  from {
    transform: translateY(100px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Mobile responsive */
@media (max-width: 480px) {
  .pwa-install-prompt {
    left: 10px;
    right: 10px;
    bottom: 10px;
  }

  .install-content {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }

  .install-actions {
    width: 100%;
    justify-content: center;
  }

  .btn-install, .btn-dismiss {
    flex: 1;
    justify-content: center;
  }
}
</style>
