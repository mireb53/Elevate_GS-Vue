<template>
  <div class="student-grades-tab">
    <div v-if="loading" class="p-4 text-center">Loading grades...</div>
    <div v-else>
      <div v-if="error" class="text-danger mb-3">{{ error }}</div>
      <div v-else>
        <!-- Show locked card first when final is not visible -->
        <div v-if="finalLocked" class="card border-info shadow-sm mb-3">
          <div class="card-body text-center py-5">
            <h5 class="card-title mb-2">Grade Locked</h5>
            <p class="card-text mb-3">{{ selfMessage }}</p>
            <div>
              <button class="btn btn-primary" @click="requestFinal" :disabled="self && self.finalRequested">
                {{ (self && self.finalRequested) ? 'Request sent' : 'Request to View Grade' }}
              </button>
            </div>
            <div class="small text-muted mt-3">Once approved, reload this page to view the full grade breakdown.</div>
          </div>
        </div>

        <div v-else>
          <div v-if="!items.length" class="text-center text-muted py-4">No graded work yet.</div>

          <div v-else>
            <!-- Overall -->
            <div v-if="role !== 'student'" class="card mb-3">
              <div class="card-body p-3">
                <h6 class="mb-1">Overall Grade</h6>
                <div class="d-flex flex-wrap gap-4 align-items-center">
                  <div><span class="display-6">{{ overallPercent }}</span></div>
                  <div class="text-muted small">Earned {{ overall.earned || 0 }}/{{ overall.possible || '?' }} pts</div>
                </div>
              </div>
            </div>

            <div v-if="self && role !== 'student'" class="card mb-3">
              <div class="card-header py-2"><strong>Term Grades</strong></div>
              <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                  <div class="col-12 col-md-3">
                    <div class="text-muted small">Midterm</div>
                    <div class="fs-5">{{ self.midtermDisplay }}</div>
                  </div>
                  <div class="col-12 col-md-3">
                    <div class="text-muted small">Tentative Final</div>
                    <div class="fs-5">{{ self.tentativeFinalDisplay }}</div>
                  </div>
                  <div class="col-12 col-md-3">
                    <div class="text-muted small">Final</div>
                    <div class="fs-5" v-html="self.finalDisplay"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Categories -->
            <div v-if="role !== 'student'" class="card mb-3">
              <div class="card-header py-2"><strong>Category Breakdown</strong></div>
              <div class="card-body p-0">
                <div class="table-responsive mb-0">
                  <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Category</th><th>Points</th><th>Percent</th></tr></thead>
                    <tbody>
                      <tr v-for="(c, label) in categories" :key="label">
                        <td>{{ label }} <small v-if="c.weight" class="text-muted">(w {{ c.weight }})</small></td>
                        <td>{{ c.earned || 0 }}/{{ c.possible || '?' }} pts</td>
                        <td>{{ c.percent != null ? c.percent.toFixed(2) + '%' : '—' }}</td>
                      </tr>
                      <tr v-if="!Object.keys(categories).length"><td colspan="3" class="text-center text-muted">No categories yet</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Controls -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
              <div>
                <h5 class="mb-1"><i class="bi bi-clipboard-data me-2"></i>My Gradebook</h5>
                <p class="text-muted small mb-0">Organized by assignment type</p>
              </div>
              <div class="d-flex gap-2 align-items-center">
                <div class="btn-group btn-group-sm" role="group" aria-label="Filter">
                  <button class="btn btn-outline-primary" :class="{ active: filter==='all' }" @click="setFilter('all')">All</button>
                  <button class="btn btn-outline-success" :class="{ active: filter==='graded' }" @click="setFilter('graded')">Graded</button>
                  <button class="btn btn-outline-danger" :class="{ active: filter==='missing' }" @click="setFilter('missing')">Missing</button>
                  <button class="btn btn-outline-secondary" :class="{ active: filter==='submitted' }" @click="setFilter('submitted')">Submitted</button>
                </div>
                <button v-if="role !== 'student'" class="btn btn-sm btn-outline-success" @click="exportCsv(filteredItems)"><i class="bi bi-download me-1"></i>Export CSV</button>
              </div>
            </div>

            <!-- Grouped items -->
            <div v-for="(group, type) in groupedByType" :key="type" class="card mb-3 border-start border-4 border-primary">
              <div class="card-header d-flex justify-content-between align-items-center py-2" style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);">
                <h6 class="mb-0"><i :class="['bi', typeIcon(type), 'me-2', 'text-primary']"></i>{{ type }} <span class="badge bg-primary ms-2">{{ group.length }}</span></h6>
                <div class="text-end"><div class="fw-bold text-primary">{{ typePercent(type) }}%</div><div class="small text-muted">{{ typeEarned(type) }}/{{ typePossible(type) }} pts</div></div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Assignment</th><th class="text-center" style="width:100px">Score</th><th class="text-center" style="width:100px">Percent</th><th class="text-center" style="width:120px">Status</th></tr></thead>
                    <tbody>
                      <tr v-for="it in group" :key="it.id">
                        <td>
                          <div v-html="it.title"></div>
                          <div v-if="it.dueAt" class="text-muted small"><i class="bi bi-calendar-event me-1"></i>Due: {{ formatDate(it.dueAt) }}</div>
                          <div v-if="it.submittedAt" class="text-muted small"><i class="bi bi-check-circle me-1"></i>Submitted: {{ formatDate(it.submittedAt) }}</div>
                        </td>
                        <td class="text-center">{{ scoreStr(it) }}</td>
                        <td class="text-center">{{ percentStr(it) }}</td>
                        <td class="text-center"><span class="badge" :class="statusBadgeClass(it.status)">{{ it.status }}</span><span v-if="it.late" class="badge bg-danger-subtle text-danger border ms-1">Late</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref, watch, computed, onMounted, onBeforeUnmount } from 'vue'
import { API_BASE } from '@/services/apiBase'
const props = defineProps({ classId: [String, Number] })
const currentId = ref(props.classId || '')
watch(() => props.classId, (v) => { if (v) currentId.value = v })

const loading = ref(false)
const error = ref('')
const items = ref([])
const categories = ref({})
const overall = ref({})
const self = ref(null)
const finalLocked = ref(false)
const filter = ref('all')
// determine role so we can hide certain sections for students
const role = (typeof localStorage !== 'undefined' && localStorage.getItem('loggedInUserRole')) ? localStorage.getItem('loggedInUserRole') : 'student'

const groupedByType = computed(()=>{
  const map = {}
  const list = (filteredItems.value && Array.isArray(filteredItems.value)) ? filteredItems.value : []
  list.forEach(it => { const t = it.type || 'Other'; if(!map[t]) map[t]=[]; map[t].push(it) })
  // preserve ordering by type per legacy: Quiz, Performance Task, Exam, then alphabetical
  const keys = Object.keys(map).sort((a,b)=>{
    const order = ['Quiz','Performance Task','Exam']
    const ai = order.indexOf(a)
    const bi = order.indexOf(b)
    if(ai!==-1 && bi!==-1) return ai-bi
    if(ai!==-1) return -1
    if(bi!==-1) return 1
    return a.localeCompare(b)
  })
  const out = {}
  keys.forEach(k=> out[k]=map[k])
  return out
})

const filteredItems = computed(()=>{
  if(filter.value==='all') return items.value.slice()
  if(filter.value==='graded') return items.value.filter(i=>i.status==='Graded')
  if(filter.value==='missing') return items.value.filter(i=>i.status==='Missing')
  if(filter.value==='submitted') return items.value.filter(i=>i.status==='Submitted')
  return items.value.slice()
})

const overallPercent = computed(()=>{
  if(overall.value.weightingApplied && overall.value.weightedPercent != null) return `${overall.value.weightedPercent.toFixed(2)}% (weighted)`
  if(overall.value.percentRaw != null) return `${overall.value.percentRaw.toFixed(2)}%`
  return 'N/A'
})

function formatDate(d){ try { return new Date(d).toLocaleDateString() } catch(e){ return '' } }
function scoreStr(it){ return (it.score!=null && it.max!=null) ? `${it.score}/${it.max}` : (it.score!=null? `${it.score}` : '—') }
function percentStr(it){ return it.percent!=null ? `${it.percent.toFixed(2)}%` : (it.score!=null && it.max!=null ? `${((it.score/it.max)*100).toFixed(2)}%` : '—') }
function statusBadgeClass(status){ if(status==='Graded') return 'bg-success'; if(status==='Submitted') return 'bg-secondary'; if(status==='Missing') return 'bg-danger'; return 'bg-warning text-dark' }
function typeIcon(type){ if(type==='Quiz') return 'bi-question-circle'; if(type==='Performance Task') return 'bi-clipboard-check'; if(type==='Exam') return 'bi-file-earmark-text'; return 'bi-journal-text' }

function typeEarned(type){ const g = groupedByType.value[type] || []; return g.reduce((s,it)=> s + (it.score||0), 0) }
function typePossible(type){ const g = groupedByType.value[type] || []; return g.reduce((s,it)=> s + (it.max||0), 0) }
function typePercent(type){ const p = typePossible(type); if(p>0) return ((typeEarned(type)/p)*100).toFixed(2); return '—' }

function setFilter(f){ filter.value = f }

function exportCsv(list){
  const header = ['Title','Type','Score','Max','Percent','Status','Late','Due At','Submitted At']
  const lines = [header].concat(list.map(it => [
    it.title,
    it.type,
    it.score ?? '',
    it.max ?? '',
    it.percent ?? '',
    it.status ?? '',
    it.late ? 'Yes' : 'No',
    it.dueAt ? new Date(it.dueAt).toISOString() : '',
    it.submittedAt ? new Date(it.submittedAt).toISOString() : ''
  ]))
  const csv = lines.map(r => r.map(v => String(v).includes(',') ? '"'+String(v).replace(/"/g,'""')+'"' : String(v)).join(',')).join('\n')
  const blob = new Blob([csv], { type:'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a'); a.href = url; a.download = 'grades.csv'; a.click(); URL.revokeObjectURL(url)
}

let pollHandle = null
async function requestFinal(){
  if(!currentId.value) return
  try{
  const BACKEND = API_BASE || ''
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
    const res = await fetch(`${BACKEND}/api/classes/${currentId.value}/final/request`, { method:'POST', headers, credentials:'include' })
    if(!res.ok) throw new Error('Request failed')
    await loadGradesForClass(currentId.value)
    // start polling for approval
    startPollingForApproval()
  }catch(e){ console.error('requestFinal', e) }
}

const selfLocked = computed(()=> self.value && !self.value.finalVisible )
const canRequestFinal = computed(()=> self.value && !self.value.finalVisible && !self.value.finalRequested )
const selfMessage = computed(()=>{
  if(!self.value) return ''
  if(self.value.finalRequested) return 'Your teacher is reviewing your request. You will gain access once it is approved.'
  return 'You need to request permission from your teacher before your course grade becomes visible.'
})

const selfDisplays = computed(()=>{
  if(!self.value) return null
  return {
    midtermDisplay: self.value.midterm != null ? `${Number(self.value.midterm).toFixed(2)}%` : '—',
    tentativeFinalDisplay: self.value.tentativeFinal != null ? `${Number(self.value.tentativeFinal).toFixed(2)}%` : '—',
    finalDisplay: self.value.finalVisible ? (self.value.final != null ? `${Number(self.value.final).toFixed(2)}%` : '—') : '<span class="text-muted">Hidden</span>'
  }
})

onMounted(()=>{ if(currentId.value) loadGradesForClass(currentId.value) })
watch(()=> currentId.value, (v,o)=>{ if(v && v!==o) loadGradesForClass(v) })

function startPollingForApproval(){
  stopPollingForApproval()
  // poll every 6 seconds (legacy UI polled occasionally). Stop after 10 minutes to avoid runaway.
  let attempts = 0
  pollHandle = setInterval(async ()=>{
    attempts++
    if(!currentId.value) return
    try{
  const BACKEND = API_BASE || ''
      const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
      const headers = userId ? { 'x-user-id': userId } : {}
      const res = await fetch(`${BACKEND}/api/classes/${currentId.value}/grades/self`, { headers, credentials:'include' })
      if(res.ok){ const s = await res.json(); self.value = s; if(s.finalVisible){ // approved
          stopPollingForApproval()
          await loadGradesForClass(currentId.value)
        }
      }
    }catch(e){ console.warn('polling approval failed', e) }
    if(attempts > 100) { // ~10 minutes
      stopPollingForApproval()
    }
  }, 6000)
}

function stopPollingForApproval(){ if(pollHandle){ clearInterval(pollHandle); pollHandle = null } }

onBeforeUnmount(()=>{ stopPollingForApproval() })

async function loadGradesForClass(id){
  loading.value = true
  error.value = ''
  items.value = []
  categories.value = {}
  overall.value = {}
  self.value = null
  if(!id){ loading.value=false; return }
  try{
  const BACKEND = API_BASE || ''
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
    const res = await fetch(`${BACKEND}/api/classes/${id}/grades/summary`, { headers, credentials:'include' })
    if(!res.ok){ if(res.status===404){ items.value = []; loading.value=false; return } throw new Error('Failed to load grade summary') }
    const summary = await res.json()
      items.value = summary.items || []
      categories.value = summary.categories || {}
      overall.value = summary.overall || {}

      // get self
      try{
  const selfRes = await fetch(`${BACKEND}/api/classes/${id}/grades/self`, { headers, credentials:'include' })
        if(selfRes.ok) self.value = await selfRes.json()
        // If final grade is not visible yet, hide the summary/items per legacy behavior
        if(self.value && !self.value.finalVisible){
          finalLocked.value = true
          // clear out fetched summary so UI doesn't display it
          items.value = []
          categories.value = {}
          overall.value = {}
          loading.value = false
          // If finalRequested already true, start polling
          if(self.value.finalRequested) startPollingForApproval()
          return
        } else {
          finalLocked.value = false
        }
      }catch(e){ console.warn('self fetch failed', e) }

  }catch(e){ console.error('loadGradesForClass', e); error.value = 'Failed to load grades.' }
  loading.value = false
}

// expose computed display fields from selfDisplays
const selfMidterm = computed(()=> selfDisplays.value?.midtermDisplay)
const selfTentativeFinal = computed(()=> selfDisplays.value?.tentativeFinalDisplay)
const selfFinal = computed(()=> selfDisplays.value?.finalDisplay)

// map computed names used earlier
Object.defineProperty(self, 'midtermDisplay', { get: ()=> selfMidterm.value })
Object.defineProperty(self, 'tentativeFinalDisplay', { get: ()=> selfTentativeFinal.value })
Object.defineProperty(self, 'finalDisplay', { get: ()=> selfFinal.value })

</script>

<style scoped>
.student-grades-tab { padding: 1rem; }
.badge { font-size: 0.75rem; }
</style>