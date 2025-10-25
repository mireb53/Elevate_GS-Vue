<template>
  <div class="gradebook-toolbar d-flex gap-2 align-items-center">
    <button class="btn btn-sm btn-outline-secondary" @click="$emit('refresh')">Refresh</button>
    <slot />
  </div>
</template>
<script setup>
// Simple toolbar stub for gradebook actions
</script>
<style scoped>
.gradebook-toolbar { padding: 0.25rem; }
</style>