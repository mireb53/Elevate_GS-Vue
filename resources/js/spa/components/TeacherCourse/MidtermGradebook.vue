<template>
  <div class="midterm-gradebook">
    <!-- Midterm tables group: Weights, Tables, Summary -->
    <!-- Groups Manager (Midterm Category Weights) -->
    <div class="card p-3 mb-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="mb-0">Midterm Category Weights</h6>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-primary" :disabled="groupWeightSum>=100" @click="addGroup">
            <i class="bi bi-plus-lg me-1"></i>Add Table
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:40%">Table Name</th>
              <th style="width:30%" class="text-center">Percent</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(g, idx) in groups" :key="g.id">
              <td>
                <input v-model="g.name" class="form-control form-control-sm" :readonly="!!g.fixedWrittenWorks" />
              </td>
              <td>
                <div class="input-group input-group-sm mx-auto" style="width:140px">
                  <input :value="g.weight" @input="onEditWeight(idx, $event.target.value)" type="number" min="0" max="100" class="form-control text-center" />
                  <span class="input-group-text">%</span>
                </div>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" @click="removeGroup(idx)" :disabled="g.fixedWrittenWorks || isDefaultGroup(g)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="alert p-1 px-2 mb-0" :class="groupWeightSum===100 ? 'alert-success' : (groupWeightSum>100?'alert-danger':'alert-warning')">
          Total: <strong>{{ groupWeightSum }}%</strong>
        </div>
        <small v-if="groupWeightSum===100" class="text-success">All set. Totals are balanced.</small>
        <small v-else-if="groupWeightSum>100" class="text-danger">Total grading percentage exceeds 100%. Please adjust your table weights.</small>
        <small v-else class="text-warning">You can still allocate {{ 100 - groupWeightSum }}% (Add Table to use it).</small>
        <small v-if="groupWeightSum>=100" class="ms-auto text-muted">
          <i class="bi bi-info-circle me-1"></i> Add Table disabled at 100%
        </small>
      </div>
    </div>

    <!-- Tables rendered dynamically (between weights and computation summary) -->
    <CategoryTable
      v-for="g in groups"
      :key="g.id"
      :title="g.name"
      :group="g"
      :students="students"
      :autoWrittenWorks="g.fixedWrittenWorks ? writtenWorks : []"
      @update:group="val => updateGroup(val)"
    />

    <!-- Summary -->
    <div class="card p-3 mb-4">
      <h6 class="mb-3">Computation Summary</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Student</th>
              <th v-for="g in groups" :key="'h_'+g.id" class="text-center">{{ g.name }} ({{ g.weight }}%)</th>
              <th class="text-center">Midterm Grade (100%)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="stu in students" :key="stu.id">
              <td>{{ stu.name }}</td>
              <td v-for="g in groups" :key="'b_'+g.id" class="text-center">{{ fmt(groupContribution(stu.id, g)) }}</td>
              <td class="text-center"><strong>{{ fmt(midtermWeightedTotal(stu.id)) }}</strong></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="groupWeightSum!==100" class="alert alert-warning mt-2">
        Midterm category weights must total 100% for correct weighted computation.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { API_BASE } from '../../services/apiBase'
import CategoryTable from './CategoryTable.vue'

const props = defineProps({ classId: { type: [String, Number], required: true } })

// Default groups and rules
const groups = ref([
  { id: 'asynch', name: 'Asynchronous', weight: 35, columns: [], fixedWrittenWorks: true },
  { id: 'synch', name: 'Synchronous', weight: 35, columns: [] },
  { id: 'major', name: 'Major Exam', weight: 30, columns: [ { key:'exam_1', name:'Exam 1', percent: 100, subcolumns:[{ key:'exam1', name:'Score', max:100 }] } ] }
])

const students = ref([])
const writtenWorks = ref([])

const groupWeightSum = computed(() => groups.value.reduce((s,g)=> s + (Number(g.weight)||0), 0))

function fmt(n){ const v = Number(n); return isFinite(v) ? v.toFixed(2) : '-' }

function isDefaultGroup(g){ return g.id === 'asynch' || g.id === 'synch' || g.id === 'major' }

function addGroup(){
  if (groupWeightSum.value >= 100) return
  const remaining = 100 - groupWeightSum.value
  const weight = Math.min(remaining, 10)
  groups.value.push({ id: 'grp_' + Math.random().toString(36).slice(2,7), name: 'New Table', weight, columns: [] })
}

function removeGroup(idx){
  const g = groups.value[idx]
  if (!g || g.fixedWrittenWorks || isDefaultGroup(g)) return
  groups.value.splice(idx,1)
}

function onEditWeight(index, val){
  const g = groups.value[index]
  if (!g) return
  const num = Math.max(0, Math.min(100, Number(val)))
  const others = groupWeightSum.value - (Number(g.weight)||0)
  const maxAllowed = Math.max(0, 100 - others)
  g.weight = Math.min(num, maxAllowed)
}

function updateGroup(val){
  if (!val || !val.id) return
  const i = groups.value.findIndex(x => x.id === val.id)
  if (i >= 0) groups.value[i] = val
}

// Computation helpers
function groupContribution(studentId, g){
  if (!g) return 0
  let colSum = 0
  let colPercentTotal = 0
  for (const col of (g.columns || [])) {
    const subs = col.subcolumns || []
    let got = 0, max = 0
    for (const sub of subs) {
      const s = Number(sub.__scores?.[studentId] || 0)
      const m = Number(sub.max || 0)
      if (m > 0) { got += Math.min(s, m); max += m }
    }
    const perc = max > 0 ? (got / max) * 100 : 0
    colSum += perc * ((Number(col.percent)||0) / 100)
    colPercentTotal += Number(col.percent)||0
  }
  const norm = colPercentTotal > 0 ? (100 / colPercentTotal) : 0
  const groupPercent = colSum * norm
  return Number((groupPercent * (Number(g.weight)||0) / 100).toFixed(2))
}

function midtermWeightedTotal(studentId){
  let sum = 0
  for (const g of groups.value) sum += groupContribution(studentId, g)
  return Number(sum.toFixed(2))
}

onMounted(async () => {
  // Load students list
  try {
    const res = await fetch(`${API_BASE}/api/classes/${props.classId}/people`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } })
    if (res.ok) {
      const payload = await res.json()
      const list = Array.isArray(payload.students) ? payload.students : []
      students.value = list.map(s => ({ id: s.id || s.student_id || s.user_id, name: `${s.first_name||''} ${s.last_name||''}`.trim() }))
    }
  } catch {}
  // Load classwork for Written Works subcolumns
  try {
    const res = await fetch(`${API_BASE}/api/classes/${props.classId}/classwork`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } })
    if (res.ok) {
      const rows = await res.json()
      writtenWorks.value = rows.filter(r => /quiz|assignment|activity/i.test(String(r.type||''))).map(r => ({ id: r.id, title: r.title }))
    }
  } catch {}
})
</script>

<style scoped>
.grade-table { table-layout: auto; }
.grade-table .sticky-col { position: sticky; left: 0; background: #fff; z-index: 1; }
</style>
