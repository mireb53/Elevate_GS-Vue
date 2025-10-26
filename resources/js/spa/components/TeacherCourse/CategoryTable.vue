<template>
  <div class="card p-3 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="mb-0">{{ title }} <span class="text-muted">({{ groupWeight }}%)</span></h6>
      <div class="d-flex align-items-center gap-2">
        <small class="text-muted">Columns total: <strong>{{ totalPercent }}%</strong> / {{ groupWeight }}%</small>
        <button class="btn btn-sm btn-outline-secondary" @click="addColumn">Add Column</button>
      </div>
    </div>
    <div v-if="totalPercent>groupWeight" class="alert alert-warning py-1 mb-2">Columns percentage exceed {{ groupWeight }}% for this table. Adjust column percentages.</div>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle grade-table">
        <thead>
          <tr>
            <th class="sticky-col">Student</th>
            <th v-for="(col, cIdx) in local.columns" :key="col.key" class="text-center">
              <div class="d-flex flex-column align-items-center gap-1">
                <div class="d-flex gap-2 align-items-center">
                  <input v-model="col.name" class="form-control form-control-sm text-center" style="min-width:140px" />
                  <button class="btn btn-sm btn-outline-danger" @click="removeColumn(cIdx)" :disabled="isFixedWrittenWorks(col)"><i class="bi bi-x"></i></button>
                </div>
                <div class="input-group input-group-sm" style="width:120px">
                  <input v-model.number="col.percent" type="number" min="0" :max="groupWeight" class="form-control text-center" />
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </th>
            <th class="text-center">Total</th>
          </tr>
          <tr>
            <th class="sticky-col"></th>
            <th v-for="col in local.columns" :key="col.key" class="text-center">
              <div class="d-flex flex-wrap gap-2 justify-content-center">
                <div v-for="(sub, i) in col.subcolumns" :key="sub.key" class="d-flex align-items-center gap-1">
                  <input v-model="sub.name" class="form-control form-control-sm" style="min-width:120px" />
                  <div class="input-group input-group-sm" style="width:100px">
                    <span class="input-group-text">/</span>
                    <input v-model.number="sub.max" type="number" min="1" class="form-control text-center" />
                  </div>
                  <button class="btn btn-sm btn-outline-danger" @click="removeSub(col, i)" :disabled="isAutoWrittenWorksSub(col, sub)"><i class="bi bi-dash"></i></button>
                </div>
                <button class="btn btn-sm btn-outline-secondary" @click="addSub(col)"><i class="bi bi-plus"></i> Sub</button>
              </div>
            </th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="stu in students" :key="stu.id">
            <td class="sticky-col">{{ stu.name }}</td>
            <td v-for="col in local.columns" :key="col.key">
              <div class="d-flex flex-wrap gap-2">
                <div v-for="sub in col.subcolumns" :key="sub.key" class="d-flex align-items-center gap-1">
                  <input :value="getScore(sub, stu.id)" @input="setScore(sub, stu.id, $event.target.value)" type="number" min="0" :max="sub.max" class="form-control form-control-sm text-end" style="width:90px" />
                </div>
              </div>
            </td>
            <td class="text-center"><strong>{{ totalForStudentLocal(stu.id) }}%</strong></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="text-end">
      <button class="btn btn-sm btn-primary" @click="emitUpdate">Apply Changes</button>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'

const props = defineProps({
  title: String,
  group: Object,
  students: Array,
  autoWrittenWorks: { type: Array, default: () => [] }
})
const emit = defineEmits(['update:group'])

const local = reactive(structuredClone(props.group || { name:'Table', weight: 0, columns: [] }))

const groupWeight = computed(() => Number(props.group?.weight || 0))

function isFixedWrittenWorks(col) { return !!(local.fixedWrittenWorks && col.key === 'written_works') }
function isAutoWrittenWorksSub(col, sub) {
  if (!(local.fixedWrittenWorks && col.key === 'written_works')) return false
  const k = String(sub.key)
  return /^ww_/.test(k) || /^\d+$/.test(k)
}

function mergeWrittenWorks() {
  if (!(props.group && props.group.fixedWrittenWorks)) return
  let ww = local.columns.find(c => c.key === 'written_works')
  if (!ww) {
    ww = { key: 'written_works', name: 'Written Works', percent: 100, subcolumns: [] }
    local.columns.unshift(ww)
  }
  const autoList = props.autoWrittenWorks || []
  const existingByKey = new Map((ww.subcolumns || []).map(s => [String(s.key), s]))
  const merged = autoList.map((w, idx) => {
    const key = String(w.id ?? ('ww_' + idx))
    const prev = existingByKey.get(key)
    if (prev) return { key, name: prev.name || w.title || w.name || 'Task', max: prev.max ?? 100, __scores: prev.__scores || {} }
    return { key, name: w.title || w.name || 'Task', max: 100, __scores: {} }
  })
  for (const [k, sub] of existingByKey.entries()) if (!merged.find(m => String(m.key) === k)) merged.push(sub)
  ww.subcolumns = merged
}

mergeWrittenWorks()
watch(() => props.group, (g) => { if (!g) return; Object.assign(local, structuredClone(g)); mergeWrittenWorks() }, { deep: true })
watch(() => props.autoWrittenWorks, () => { mergeWrittenWorks() }, { deep: true })

const totalPercent = computed(() => local.columns.reduce((s, c) => s + (Number(c.percent) || 0), 0))

function addColumn() { local.columns.push({ key: 'col_' + Math.random().toString(36).slice(2,7), name: 'New Column', percent: 10, subcolumns: [] }) }
function removeColumn(idx) { local.columns.splice(idx,1) }
function addSub(col) { col.subcolumns.push({ key: 'sub_' + Math.random().toString(36).slice(2,7), name: 'Item', max: 100 }) }
function removeSub(col, i) { col.subcolumns.splice(i,1) }

function emitUpdate() { emit('update:group', JSON.parse(JSON.stringify(local))) }

function totalForStudentLocal(studentId) {
  let total = 0, pctTotal = 0
  for (const col of local.columns) {
    let got=0, max=0
    for (const sub of (col.subcolumns || [])) {
      const s = Number((sub.__scores && sub.__scores[studentId]) || 0)
      const m = Number(sub.max || 0)
      if (m>0) { got += Math.min(s,m); max += m }
    }
    const perc = max>0 ? (got/max)*100 : 0
    total += perc * ((Number(col.percent)||0)/100)
    pctTotal += Number(col.percent)||0
  }
  const norm = pctTotal>0 ? (100/pctTotal) : 0
  return (total * norm).toFixed(2)
}

function getScore(sub, studentId) { return (sub.__scores && (sub.__scores[studentId] ?? 0)) || 0 }
function setScore(sub, studentId, val) {
  if (!sub.__scores) sub.__scores = {}
  const num = Number(val)
  sub.__scores[studentId] = isFinite(num) ? num : 0
}
</script>

<style scoped>
.grade-table { table-layout: auto; }
.grade-table .sticky-col { position: sticky; left: 0; background: #fff; z-index: 1; }
</style>
