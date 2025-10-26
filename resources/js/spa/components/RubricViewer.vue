<template>
  <div class="rubric-viewer" v-if="!hidden">
    <div v-if="loading" class="text-muted small">Loading rubric...</div>
    <div v-else-if="error" class="alert alert-warning py-2 px-3 mb-2">{{ error }}</div>
    <div v-else-if="!hasRubric" class="text-muted small">No rubric provided.</div>
    <div v-else class="card shadow-sm mb-3">
      <div class="card-header py-2"><strong>Rubric</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive mb-0">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>Criterion</th>
                <th style="width: 100px" class="text-center">Points</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in rows" :key="idx">
                <td>{{ row.criterion || row.criterion_name || row.name || row.title || `Criterion ${idx+1}` }}</td>
                <td class="text-center">{{ row.points != null ? row.points : row.max_points != null ? row.max_points : row.max != null ? row.max : row.weight != null ? row.weight : '—' }}</td>
                <td>{{ row.description || row.desc || '' }}</td>
              </tr>
              <tr v-if="rows.length === 0">
                <td colspan="3" class="text-center text-muted">Rubric format not recognized</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { API_BASE } from '@/services/apiBase'

const props = defineProps({ classworkId: [String, Number], hideIfEmpty: { type: Boolean, default: true } })
const loading = ref(false)
const error = ref('')
const rubric = ref(null)

const hasRubric = computed(() => {
  if (!rubric.value) return false
  if (Array.isArray(rubric.value) && rubric.value.length) return true
  if (rubric.value && typeof rubric.value === 'object' && Object.keys(rubric.value).length) return true
  return false
})

const rows = computed(() => {
  const r = rubric.value
  if (!r) return []
  if (Array.isArray(r)) return r
  if (Array.isArray(r?.criteria)) return r.criteria
  if (Array.isArray(r?.rows)) return r.rows
  return []
})

const hidden = computed(() => props.hideIfEmpty && !loading.value && !hasRubric.value)

async function loadRubric(id){
  error.value = ''
  rubric.value = null
  if(!id) return
  loading.value = true
  try{
    const res = await fetch(`${API_BASE}/api/classwork/${encodeURIComponent(id)}/rubric`)
    if(!res.ok){ error.value = 'Failed to load rubric.'; return }
    const data = await res.json()
    rubric.value = data?.rubric ?? null
  }catch(e){ error.value = 'Failed to load rubric.' }
  loading.value = false
}

onMounted(()=>{ if(props.classworkId) loadRubric(props.classworkId) })
watch(() => props.classworkId, (v,o)=>{ if(v && v!==o) loadRubric(v) })
</script>

<style scoped>
.table td, .table th { vertical-align: middle }
</style>
