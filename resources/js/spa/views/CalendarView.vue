<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Calendar</h3>
      <button class="btn btn-outline-primary btn-sm" @click="refresh">
        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
      </button>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <template v-if="loading">
          <div class="d-flex align-items-center gap-2 text-muted small">
            <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
            <span>Loading…</span>
          </div>
        </template>
        <template v-else-if="events.length === 0">
          <div class="text-muted">No events yet.</div>
        </template>
        <template v-else>
          <div class="list-group">
            <div v-for="ev in events" :key="ev.id" class="list-group-item list-group-item-action">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">{{ ev.title || 'Untitled' }}</div>
                  <div class="text-muted small">{{ formatDate(ev.start) }} • {{ ev.type || 'event' }}</div>
                </div>
                <div class="text-muted small">{{ formatTime(ev.start) }}</div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { API_BASE } from '../services/apiBase'

const loading = ref(false)
const events = ref([])

function formatDate(date) {
  const d = new Date(date)
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}
function formatTime(date) {
  const d = new Date(date)
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

async function refresh() {
  try {
    loading.value = true
  const res = await fetch(`${API_BASE}/api/calendar`, { credentials: 'include' })
    const data = await res.json().catch(() => ({ events: [] }))
    events.value = data.events || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(refresh)
</script>
