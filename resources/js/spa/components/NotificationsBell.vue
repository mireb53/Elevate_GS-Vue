<template>
  <div class="dropdown">
    <button id="notifBtn" class="btn btn-light topbar-btn" type="button" data-bs-toggle="popover" data-bs-placement="bottom" title="Notifications">
      <i class="bi bi-bell"></i>
    </button>
  </div>
</template>
<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  // initialize popover if Bootstrap JS is available
  try {
    const el = document.getElementById('notifBtn')
    if (window.bootstrap && window.bootstrap.Popover && el) {
      new window.bootstrap.Popover(el, { trigger: 'click', placement: 'bottom', html: true, sanitize: false, content: 'No new notifications.' })
    }
  } catch (e) { /* ignore */ }
})
</script>
<style scoped>
</style>
