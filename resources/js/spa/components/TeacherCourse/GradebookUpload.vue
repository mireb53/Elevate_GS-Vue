<template>
  <div>
    <label class="btn btn-outline-success mb-0 ms-1">
      Upload Edited Excel
      <input type="file" accept=".xlsx" style="display:none" @change="onFile" />
    </label>
  </div>
</template>

<script setup>
const emit = defineEmits(['file-selected'])
function onFile(e) {
  const files = e.target.files;
  if (!files || !files.length) return;
  const file = files[0];
  emit('file-selected', { file });
}
</script>

<style scoped>
</style>
