<template>
  <div class="classwork-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Classwork</h2>
      <div class="d-flex gap-2 align-items-center">
        <select v-model="filter" class="form-select form-select-sm" style="width:160px">
          <option value="All">All</option>
          <option value="Lesson">Lessons</option>
          <option value="Activity">Activities</option>
          <option value="Assignment">Assignments</option>
          <option value="Quiz">Quizzes</option>
        </select>

        <div class="btn-group">
          <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-plus-lg"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" @click.prevent="openCreateModal('Lesson')">Create Lesson</a></li>
            <li><a class="dropdown-item" href="#" @click.prevent="openCreateModal('Activity')">Create Activity</a></li>
            <li><a class="dropdown-item" href="#" @click.prevent="openCreateModal('Assignment')">Create Assignment</a></li>
            <li><a class="dropdown-item" href="#" @click.prevent="openCreateModal('Quiz')">Create Quiz</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div id="classwork-content">
      <div v-if="loading" class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        {{ error }}
      </div>

      <div v-else-if="filteredClassworks.length === 0" class="text-center text-muted">
        <p>No {{ filter === 'All' ? '' : filter.toLowerCase() }} items posted yet.</p>
      </div>

      <div v-else>
        <div v-for="item in filteredClassworks" :key="item.id" class="card p-3 mb-3 shadow-sm" style="cursor:pointer" @click="showClassworkDetail(item)">
          <div :style="{height:'5px',background: itemColor(item.type), margin:'-16px -16px 16px -16px', borderRadius:'3px 3px 0 0'}"></div>
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
              <h6 class="mb-1">{{ item.title }}</h6>
              <small class="text-muted">{{ item.type }}{{ item.due ? ' • Due ' + item.due : '' }}</small>
              <p class="small mt-2 mb-2" v-html="item.description"></p>
              <div class="d-flex flex-wrap gap-2 small" :id="`stats-${item.id}`">
                <span class="badge text-bg-light">Loading stats...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Classwork Modal (component) -->
    <ClassworkCreateModal ref="createModalRef" :type="selectedType" @submit="onCreateModalSubmit" />

    <!-- Submissions Modal -->
    <div class="modal fade" id="submissionsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Submissions<span id="submissionsModalTitle"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <button id="showNotSubmittedBtn" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person-slash me-1"></i>Not Submitted
              </button>
            </div>
            <table id="submissionsTable" class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Submitted</th>
                  <th>Score</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed, watch } from 'vue'
import { useRoute } from 'vue-router'

// Accept courseId from parent when embedded in Teacher tab
const props = defineProps({ courseId: [String, Number] })
import ClassworkCreateModal from '../components/ClassworkCreateModal.vue'

import { API_BASE } from '../services/apiBase'

// State
const classworks = ref([])
const loading = ref(false)
const error = ref(null)
const selectedType = ref('')
// component modal ref
const route = useRoute()
const filter = ref('All')

// Derived list based on filter
const filteredClassworks = computed(() => {
  if (!Array.isArray(classworks.value)) return []
  if (filter.value === 'All') return classworks.value
  return classworks.value.filter(it => it.type === filter.value)
})

const form = ref({
  title: '',
  description: '',
  type: '',
  dueDate: '',
  courseId: props.courseId || route.params.courseId || route.query.courseId || 1 // Get from prop, route, or default
})

// attachments state for create modal
const attachments = ref([]) // { file, name, progress, uploadedMeta }


// Additional create modal state for quiz/rubric
const quizPayload = ref({ questions: [] })
const rubricPayload = ref([])
const showRubricBuilder = ref(false)

// Bootstrap Modal component ref
const createModalRef = ref(null)

function formatDate(dateStr) {
  if (!dateStr) return ''
  try { return new Date(dateStr).toLocaleDateString() } catch { return dateStr }
}

function itemColor(type){
  return { Lesson: '#1a73e8', Assignment: '#ab1818', Quiz: '#9c27b0', Activity: '#fbc02d', Attendance: '#2e7d32' }[type] || '#ccc'
}

async function loadClasswork() {
  loading.value = true
  error.value = null
  const courseId = form.value.courseId
  try {
    const res = await fetch(`${API_BASE}/api/classes/${courseId}/classwork`)
    if (!res.ok) throw new Error('Failed to load classwork')
    const rows = await res.json()
    classworks.value = rows.map(r => ({
      id: r.id,
      title: r.title,
      type: r.type,
      description: r.description || '',
      due: r.due_at ? new Date(r.due_at).toLocaleString() : '',
      rubric: r.rubric_json ? safeParse(r.rubric_json) : null,
      extra: r.extra_json ? safeParse(r.extra_json) : null,
      material: r.material || (r.extra_json && safeParse(r.extra_json)?.material) || '',
      materialFiles: (() => { try { const ex = r.extra_json ? safeParse(r.extra_json) : null; return ex && Array.isArray(ex.materialFiles) ? ex.materialFiles : []; } catch { return []; } })(),
      materialFile: (() => { try { const ex = r.extra_json ? safeParse(r.extra_json) : null; return ex && ex.materialFile ? ex.materialFile : null; } catch { return null; } })()
    }))

    // fetch stats for each item (async)
    classworks.value.forEach(i => fetchSubmissionsStats(i.id).then(stats => renderStatsBadge(i.id, stats)).catch(()=>renderStatsBadge(i.id,null)))
  } catch (err) {
    console.error('Error loading classwork:', err)
    error.value = 'Could not load classwork. Please try again later.'
  } finally {
    loading.value = false
  }
}

function safeParse(v){ try { return typeof v === 'string' ? JSON.parse(v) : v } catch { return null } }

function openCreateModal(type) {
  selectedType.value = type
  form.value = { ...form.value, type, title: '', description: '', dueDate: '' }
  // reset builders
  quizPayload.value = { questions: [] }
  rubricPayload.value = []
  showRubricBuilder.value = false
  // show component modal
  nextTick(()=> createModalRef.value?.show && createModalRef.value.show())
}

function closeModal() { createModalRef.value?.hide && createModalRef.value.hide() }

async function handleSubmit() {
  loading.value = true
  error.value = null
  try {
    // If attachments present, send a multipart request to the upload endpoint which expects form fields + files
    let res
    if (attachments.value && attachments.value.length) {
      const uploadForm = new FormData()
      // append scalar form fields
      Object.entries(form.value).forEach(([key, value]) => { if (typeof value !== 'undefined' && value !== null) uploadForm.append(key, value) })
      // include quiz/rubric
      if (form.value.type === 'Quiz' && quizPayload.value && Array.isArray(quizPayload.value.questions) && quizPayload.value.questions.length) {
        uploadForm.append('quiz', JSON.stringify(quizPayload.value))
      }
      // Include rubric if it has criteria
      if (Array.isArray(rubricPayload.value) && rubricPayload.value.length > 0) {
        console.log('📋 Sending rubric with classwork (multipart):', rubricPayload.value)
        uploadForm.append('rubric', JSON.stringify(rubricPayload.value))
      }
      // append files under 'attachments' as expected by multer
      attachments.value.forEach(a => uploadForm.append('attachments', a.file, a.name))

      res = await fetch(`${API_BASE}/api/classes/${form.value.courseId}/classwork/upload`, {
        method: 'POST',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' },
        body: uploadForm
      })
      if (!res.ok) {
        let body = null
        try { body = await res.json() } catch(e) { body = null }
        const msg = body && body.message ? body.message : (`HTTP ${res.status} ${res.statusText}`)
        throw new Error(msg)
      }
    } else {
      // No attachments: use the JSON endpoint
      const formJson = {}
      Object.entries(form.value).forEach(([k,v]) => { formJson[k] = v })
      if (formJson.type === 'Quiz' && quizPayload.value && Array.isArray(quizPayload.value.questions) && quizPayload.value.questions.length) formJson.quiz = quizPayload.value
      // Include rubric if it has criteria
      if (Array.isArray(rubricPayload.value) && rubricPayload.value.length > 0) {
        console.log('📋 Sending rubric with classwork (JSON):', rubricPayload.value)
        formJson.rubric = rubricPayload.value
      }
      res = await fetch(`${API_BASE}/api/classes/${form.value.courseId}/classwork`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'x-user-id': localStorage.getItem('loggedInUserId') || '' },
        body: JSON.stringify(formJson)
      })
      if (!res.ok) {
        let body = null
        try { body = await res.json() } catch(e) { body = null }
        const msg = body && body.message ? body.message : (`HTTP ${res.status} ${res.statusText}`)
        throw new Error(msg)
      }
    }

  // Parse response to get created classwork ID
  const createdClasswork = await res.json()
  
  // 🔗 Auto-assign to Asynchronous category (35%) for Quiz/Assignment/Activity
  if (['Quiz', 'Assignment', 'Activity'].includes(form.value.type)) {
    try {
      const categoryResponse = await fetch(`${API_BASE}/api/classwork/${createdClasswork.id}/link-category`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'x-user-id': localStorage.getItem('loggedInUserId') || '' 
        },
        body: JSON.stringify({
          category_id: 1 // Asynchronous category (35%)
        })
      })
      
      if (categoryResponse.ok) {
        console.log('✅ Auto-categorized as Asynchronous (35%):', form.value.title)
        
        // Sync scores to grading sheet
        await fetch(`${API_BASE}/api/classwork/${createdClasswork.id}/sync-scores`, {
          method: 'POST',
          headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
        })
      }
    } catch (linkErr) {
      console.error('⚠️ Auto-categorization failed (non-critical):', linkErr)
    }
  }

  await loadClasswork()
  // notify other parts of the app (legacy listeners and other views) that a classwork was created
  try { window.dispatchEvent(new CustomEvent('classwork:created', { detail: { courseId: form.value.courseId } })) } catch(e) { /* ignore */ }
  // reset attachments on success
  attachments.value = []
  closeModal()
  // extra safety: reset create builders and form so modal shows fresh next time
  quizPayload.value = { questions: [] }
  rubricPayload.value = []
  showRubricBuilder.value = false
  form.value.title = ''
  form.value.description = ''
  form.value.dueDate = ''
  } catch (err) {
    console.error('Error creating classwork:', err)
    // If the error has message, surface it to the UI for debugging (trimmed)
    const msg = err && err.message ? String(err.message).slice(0,300) : 'Could not create classwork. Please try again.'
    error.value = msg
  } finally { loading.value = false }
}

function onFilesSelected(e){
  const files = Array.from(e.target.files || [])
  files.forEach(f => attachments.value.push({ file: f, name: f.name, progress: 0, uploadedMeta: null }))
  // clear the input value so selecting same file again works
  e.target.value = ''
}

async function onCreateModalSubmit(payload){
  // payload: { form, files, quiz (optional), rubric (optional) }
  console.log('📝 onCreateModalSubmit received payload:', payload)
  
  // merge incoming form into our form and delegate to existing handleSubmit flow
  form.value = { ...form.value, ...payload.form }
  attachments.value = payload.files.map(f => ({ file: f.file, name: f.name, progress: 0, uploadedMeta: null }))
  
  // If quiz is provided, set it
  if (payload.quiz && payload.quiz.questions && Array.isArray(payload.quiz.questions) && payload.quiz.questions.length > 0) {
    console.log('📝 Setting quizPayload from modal:', payload.quiz)
    quizPayload.value = payload.quiz
  } else {
    console.log('⚠️ No quiz in payload or quiz is empty')
  }
  
  // If rubric is provided, set it
  if (payload.rubric && Array.isArray(payload.rubric) && payload.rubric.length > 0) {
    console.log('📋 Setting rubricPayload from modal:', payload.rubric)
    rubricPayload.value = payload.rubric
    showRubricBuilder.value = true
  } else {
    console.log('⚠️ No rubric in payload or rubric is empty')
  }
  
  await handleSubmit()
}

function removeAttachment(idx){
  attachments.value.splice(idx,1)
}

function uploadAttachments(courseId, files){
  // upload each file with XHR to /api/classes/:id/classwork/upload
  return Promise.all(files.map((a, idx) => new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest()
    const fd = new FormData()
    fd.append('attachments', a.file)
  xhr.open('POST', `${API_BASE}/api/classes/${courseId}/classwork/upload`)
  // include user id header for simple auth when session is not present
  try { xhr.setRequestHeader('x-user-id', localStorage.getItem('loggedInUserId')||'') } catch(e) {}
    xhr.upload.onprogress = (ev) => { if (ev.lengthComputable) { a.progress = Math.round((ev.loaded/ev.total)*100) } }
    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        try { const json = JSON.parse(xhr.responseText); a.uploadedMeta = json; resolve(json) } catch(e){ resolve(null) }
      } else { resolve(null) }
    }
    xhr.onerror = () => resolve(null)
    xhr.send(fd)
  })))
}

// Stats and submissions handling (simplified port)
async function fetchSubmissionsStats(classworkId){
  try {
    const res = await fetch(`${API_BASE}/api/classwork/${classworkId}/submissions`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } })
    if (!res.ok) throw new Error('failed')
    const list = await res.json()
    const graded = list.filter(s => s.grade && (typeof s.grade.score !== 'undefined')).length
    return { total: list.length, graded, list }
  } catch(e){ return null }
}

function renderStatsBadge(classworkId, stats){
  // Update DOM area if still present (backwards-compatible)
  const wrap = document.getElementById(`stats-${classworkId}`)
  if (!wrap) return
  if (!stats) { wrap.innerHTML = '<span class="badge text-bg-danger">Stats error</span>'; return }
  wrap.innerHTML = `\n    <span class="badge text-bg-primary"><i class="bi bi-upload me-1"></i>${stats.total} submitted</span>\n    <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>${stats.graded} graded</span>\n    <button class="btn btn-sm btn-outline-secondary ms-1" data-open-submissions="${classworkId}">View Submissions</button>`
  wrap.querySelector('[data-open-submissions]')?.addEventListener('click', e => { e.stopPropagation(); openSubmissionsModal(classworks.value.find(it=>it.id==classworkId)) })
}

// Open detail when clicking a classwork card
function showClassworkDetail(item){
  openSubmissionsModal(item)
}

function showInlineFeedback(msg, isErr){
  let el = document.getElementById('inlineFeedbackToast')
  if (!el){ el = document.createElement('div'); el.id='inlineFeedbackToast'; el.style.position='fixed'; el.style.bottom='20px'; el.style.right='20px'; el.style.zIndex=1080; document.body.appendChild(el) }
  el.innerHTML = `<div class="alert ${isErr?'alert-danger':'alert-success'} py-2 px-3 shadow-sm mb-0">${escapeHtml(msg)}</div>`
  setTimeout(()=>{ if (el) el.innerHTML=''; }, 2000)
}

function escapeHtml(str){ return (str||'').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[s]||s) }

// Submissions modal wiring (kept DOM based for now)
function openSubmissionsModal(item){
  if (!item) return
  const modalEl = document.getElementById('submissionsModal')
  if (!modalEl || !window.bootstrap) { alert('Modal component missing'); return }
  document.getElementById('submissionsModalTitle').textContent = ' - ' + item.title
  const table = document.getElementById('submissionsTable')
  const tbody = table.querySelector('tbody')
  table.setAttribute('data-classwork-id', item.id)
  tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Loading...</td></tr>'
  const modalInst = new bootstrap.Modal(modalEl)
  modalInst.show()
  refreshModalSubmissions(item.id, true)
  setupNotSubmittedButton(item.id)
}

function setupNotSubmittedButton(classworkId){
  const btn = document.getElementById('showNotSubmittedBtn')
  if (!btn) return
  btn.onclick = async () => {
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'
    try {
      const [subsRes, peopleRes] = await Promise.all([
        fetch(`${API_BASE}/api/classwork/${classworkId}/submissions`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } }),
        fetch(`${API_BASE}/api/classes/${form.value.courseId}/people`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } })
      ])
      if (!subsRes.ok || !peopleRes.ok) throw new Error('Failed')
      const subs = await subsRes.json(); const people = await peopleRes.json()
      const submittedIds = new Set(subs.map(s=>s.userId))
      const notSubmitted = (people.students||[]).filter(s => !submittedIds.has(s.id))
      const tbody = document.querySelector('#submissionsTable tbody')
      if (!notSubmitted.length){ showInlineFeedback('Everyone submitted') } else {
        const rows = notSubmitted.map(s => `<tr class="table-warning"><td class="small">${escapeHtml(s.first_name + ' ' + s.last_name)}</td><td colspan="5" class="small text-muted">No submission</td></tr>`).join('')
        tbody.insertAdjacentHTML('beforeend', rows)
        showInlineFeedback('Listed not-submitted')
      }
    } catch(e){ showInlineFeedback('Failed', true) }
    finally { btn.disabled = false; btn.innerHTML = '<i class="bi bi-person-slash me-1"></i>Not Submitted' }
  }
}

function refreshModalSubmissions(classworkId, firstLoad){
  const tbody = document.querySelector('#submissionsTable tbody')
  if (!tbody) return
  tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Loading submissions...</td></tr>'

  // Load submissions and people to map names
  Promise.all([
    fetch(`${API_BASE}/api/classwork/${classworkId}/submissions`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } }),
    fetch(`${API_BASE}/api/classes/${form.value.courseId}/people`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } })
  ])
    .then(async ([subsRes, pplRes]) => {
      if (!subsRes.ok) throw new Error('Failed to load submissions')
      if (!pplRes.ok) throw new Error('Failed to load class people')
      const subs = await subsRes.json()
      const people = await pplRes.json()
      const students = (people.students || []).reduce((acc, s) => { acc[s.id] = s; return acc }, {})

      if (!Array.isArray(subs) || subs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">No submissions yet.</td></tr>'
        return
      }

      const rowsHtml = subs.map(s => {
        const stu = students[s.userId] || {}
        const name = [stu.first_name, stu.last_name].filter(Boolean).join(' ') || `Student #${s.userId || ''}`
        const submittedAt = s.submittedAt || s.submitted_at || s.created_at || ''
        const score = (s.grade && (typeof s.grade.score !== 'undefined')) ? s.grade.score : ''
        const safeSubmittedAt = submittedAt ? new Date(submittedAt).toLocaleString() : '—'
        return `
          <tr data-submission-id="${String(s.id || '')}" data-user-id="${String(s.userId || '')}">
            <td class="small">${escapeHtml(name)}</td>
            <td class="small">${escapeHtml(safeSubmittedAt)}</td>
            <td class="small">${typeof score === 'number' || typeof score === 'string' ? escapeHtml(String(score)) : ''}</td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-primary" data-action="grade">Save grade</button>
            </td>
          </tr>
        `
      }).join('')

      tbody.innerHTML = rowsHtml

      // Wire buttons
      tbody.querySelectorAll('button[data-action="grade"]').forEach(btn => {
        btn.addEventListener('click', async (ev) => {
          ev.stopPropagation()
          const tr = ev.currentTarget.closest('tr')
          const submissionId = tr?.getAttribute('data-submission-id')
          const userId = tr?.getAttribute('data-user-id')
          const newScore = prompt('Enter score:')
          if (newScore === null) return
          try {
            await saveGrade(classworkId, submissionId, userId, newScore)
            showInlineFeedback('Grade saved')
            // Update cell
            const cell = tr.querySelector('td:nth-child(3)')
            if (cell) cell.textContent = String(newScore)
          } catch (e) {
            showInlineFeedback('Failed to save grade', true)
          }
        })
      })
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-danger">Failed to load submissions</td></tr>'
    })
}


async function saveGrade(classworkId, submissionId, userId, score){
  // Backend endpoint unknown; try a best-guess route, else no-op
  const userHeader = { 'x-user-id': localStorage.getItem('loggedInUserId')||'' }
  const payload = { userId, score: Number(score) }
  const urls = [
    `${API_BASE}/api/classwork/${classworkId}/submissions/${submissionId}/grade`,
    `${API_BASE}/api/classwork/${classworkId}/grade`
  ]
  let lastErr
  for (const url of urls){
    try {
      const res = await fetch(url, { method:'POST', headers: { 'Content-Type':'application/json', ...userHeader }, body: JSON.stringify(payload) })
      if (res.ok) return true
      lastErr = new Error(`HTTP ${res.status}`)
    } catch(e){ lastErr = e }
  }
  // If all attempts fail, throw to caller (UI will show feedback)
  throw lastErr || new Error('Failed to save')
}

// Initial load and event wiring
onMounted(() => {
  loadClasswork()
  // Refresh when classwork is created/updated/deleted anywhere
  if (typeof window !== 'undefined'){
    window.addEventListener('classwork:created', loadClasswork)
    window.addEventListener('classwork:updated', loadClasswork)
    window.addEventListener('classwork:deleted', loadClasswork)
  }
})

watch(() => [props.courseId, route.params.courseId, route.query.courseId].join(':'), (nv, ov) => {
  if (nv !== ov){
    form.value.courseId = props.courseId || route.params.courseId || route.query.courseId || form.value.courseId
    loadClasswork()
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined'){
    window.removeEventListener('classwork:created', loadClasswork)
    window.removeEventListener('classwork:updated', loadClasswork)
    window.removeEventListener('classwork:deleted', loadClasswork)
  }
})

</script>
