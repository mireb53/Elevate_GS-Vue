<template>
  <div id="joinClassModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Join a Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="message" :class="['alert', messageClass]" role="alert">{{ message }}</div>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-upc"></i></span>
            <input v-model.trim="classCode" type="text" class="form-control" placeholder="Enter class code" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" :disabled="joining" @click="joinClass">
            <span v-if="joining" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            Join
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { API_BASE } from '../services/apiBase'

const classCode = ref('')
const joining = ref(false)
const message = ref('')
const messageType = ref('info')
const messageClass = computed(() => ({
  info: 'alert-info', success: 'alert-success', danger: 'alert-danger', warning: 'alert-warning'
}[messageType.value] || 'alert-info'))

function showMessage(text, type='info') {
  message.value = text
  messageType.value = type
  if (text) setTimeout(() => { if (message.value === text) message.value = '' }, 5000)
}

async function joinClass() {
  const userId = localStorage.getItem('loggedInUserId')
  if (!userId) { showMessage('Please login first.', 'warning'); return }
  if (!classCode.value) { showMessage('Please enter a class code.', 'warning'); return }
  joining.value = true
  try {
    // Legacy API path uses /api/join-class and sends user via x-user-id header
    const res = await fetch(`${API_BASE}/api/join-class`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'x-user-id': String(userId) },
      body: JSON.stringify({ classCode: classCode.value })
    })
    const data = await res.json().catch(()=>({}))
    if (!res.ok) {
      showMessage(data.message || 'Failed to join class', 'danger')
      return
    }
    showMessage('Joined class successfully', 'success')
    classCode.value = ''
    // Close modal
    try {
      const el = document.getElementById('joinClassModal')
      const modal = window.bootstrap && window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(el) || new window.bootstrap.Modal(el) : null
      if (modal) modal.hide()
    } catch(_){}
  // Notify listeners to refresh (support legacy and new events)
  window.dispatchEvent(new CustomEvent('gs:classes-changed', { detail: { type: 'joined', classId: data.classId } }))
  window.dispatchEvent(new CustomEvent('courses:updated', { detail: { action: 'joined', classId: data.classId } }))
  } catch (e) {
    console.error(e)
    showMessage('Network error joining class', 'danger')
  } finally {
    joining.value = false
  }
}
</script>
<style scoped>
</style>
