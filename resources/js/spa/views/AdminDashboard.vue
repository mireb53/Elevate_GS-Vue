<template>
  <!-- Error Banner -->
  <div v-if="showAdminBanner" class="alert alert-warning d-flex justify-content-between align-items-start mb-3">
    <div style="max-width:75%; white-space:pre-wrap;">{{ adminError }}</div>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" @click="initAdmin">Retry</button>
      <button class="btn btn-sm btn-outline-primary" @click="runAuthInspect">Auth Inspect</button>
      <button class="btn btn-sm btn-danger" @click="trySeedAdmin">Seed Admin (dev)</button>
    </div>
  </div>

  <!-- Welcome Header -->
  <div class="dashboard-header">
    <small>Welcome to</small>
    <h1>Elevate<span>GS</span></h1>
    <p>Administrator Dashboard</p>
  </div>
  
  <!-- Dashboard Content -->
  <div class="dashboard-body">

        <!-- Stats Cards Grid -->
        <div class="row g-3 mb-3">
          <div class="col-md-3 col-6">
            <div class="card shadow-sm border-start border-success border-4 h-100">
              <div class="card-body">
                <h6 class="card-title text-muted mb-2">Students</h6>
                <p class="fs-3 fw-bold text-success mb-0" v-text="stats.students"></p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="card shadow-sm border-start border-primary border-4 h-100">
              <div class="card-body">
                <h6 class="card-title text-muted mb-2">Teachers</h6>
                <p class="fs-3 fw-bold text-primary mb-0" v-text="stats.teachers"></p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="card shadow-sm border-start border-warning border-4 h-100">
              <div class="card-body">
                <h6 class="card-title text-muted mb-2">Admins</h6>
                <p class="fs-3 fw-bold text-warning mb-0" v-text="stats.admins"></p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="card shadow-sm border-start border-info border-4 h-100">
              <div class="card-body">
                <h6 class="card-title text-muted mb-2">Active Courses</h6>
                <p class="fs-3 fw-bold text-info mb-0">{{ stats.activeCourses === '--' ? '--' : stats.activeCourses }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Secondary Stats -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="card shadow-sm border-start border-primary border-3 h-100">
              <div class="card-body py-3">
                <h6 class="card-title text-muted mb-2">Active Instructors</h6>
                <p class="fs-4 fw-bold text-primary mb-0" v-text="stats.activeInstructors"></p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card shadow-sm border-start border-success border-3 h-100">
              <div class="card-body py-3">
                <h6 class="card-title text-muted mb-2">Active Students</h6>
                <p class="fs-4 fw-bold text-success mb-0" v-text="stats.activeStudents"></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Course Status Distribution -->
        <div class="card shadow-sm mb-4" id="activeDistCard">
              <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="card-title text-muted mb-0">Course Status Distribution</h6>
                  <small class="text-muted" id="activeSummary">{{ distributionSummary }}</small>
                </div>
                <div class="progress" style="height:10px;">
                  <div class="progress-bar bg-success" :style="{ width: distActive + '%' }" id="barActive"></div>
                  <div class="progress-bar bg-warning text-dark" :style="{ width: distPending + '%' }" id="barPending"></div>
                  <div class="progress-bar bg-secondary" :style="{ width: distArchived + '%' }" id="barArchived"></div>
                </div>
                <div class="d-flex justify-content-between small mt-2 text-muted flex-wrap gap-2">
                  <span><span class="badge bg-success me-1">&nbsp;</span><span id="lblActive">Active: {{ displayActive }}</span></span>
                  <span><span class="badge bg-warning text-dark me-1">&nbsp;</span><span id="lblPending">Pending: {{ displayPending }}</span></span>
                  <span><span class="badge bg-secondary me-1">&nbsp;</span><span id="lblArchived">Archived: {{ displayArchived }}</span></span>
                </div>
              </div>
            </div>

        <!-- Course Monitoring -->
        <div class="card course-monitoring shadow-sm mt-4" id="courseMonitor">
          <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <h5 class="mb-0">📚 Course Monitoring</h5>
              <div class="btn-group btn-group-sm" role="group" aria-label="Status filter" id="courseStatusTabs">
                <button type="button" class="btn btn-outline-secondary active" @click="setCourseStatusAll">All <span class="badge bg-secondary ms-1">{{ cmCounts.all }}</span></button>
                <button type="button" class="btn btn-outline-success" @click="setCourseStatusActive">Active <span class="badge bg-success ms-1">{{ cmCounts.active }}</span></button>
                <button type="button" class="btn btn-outline-warning" @click="setCourseStatusPending">Pending <span class="badge bg-warning text-dark ms-1">{{ cmCounts.pending }}</span></button>
                <button type="button" class="btn btn-outline-secondary" @click="setCourseStatusArchived">Archived <span class="badge bg-secondary ms-1">{{ cmCounts.archived }}</span></button>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input id="cmSearch" v-model="cmQuery" class="form-control form-control-sm" placeholder="Search courses..." style="width:200px;" />
                <button id="cmRefresh" class="btn btn-sm btn-outline-secondary" title="Refresh" @click="loadCourseMonitor"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="cmTable">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Instructor</th>
                  <th>Students</th>
                  <th>Created</th>
                  <th>Status</th>
                  <th style="width:140px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="course in cmList" :key="course.id">
                  <td>{{ course.id }}</td>
                  <td>{{ course.class_name }}</td>
                  <td>{{ course.instructor_name }}</td>
                  <td>{{ course.student_count }}</td>
                  <td>{{ formatDate(course.created_at) }}</td>
                  <td><span class="badge" :class="courseStatusClass(course.status)">{{ course.status }}</span></td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <button class="btn btn-outline-success"
                        :class="{ 'opacity-50': !(course.status && course.status.toLowerCase() === 'pending') }"
                        :disabled="!(course.status && course.status.toLowerCase() === 'pending')"
                        @click="tryApprove(course)">Approve</button>
                      <button v-if="!(course.status && course.status.toLowerCase() === 'archived')" class="btn btn-outline-secondary" @click="openArchive(course)">Archive</button>
                      <button v-else class="btn btn-outline-secondary" @click="openUnarchive(course)">Unarchive</button>
                        <button class="btn btn-outline-danger" @click="confirmDelete(course)">Delete</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="cmLoading"><td colspan="7" class="text-muted">Loading...</td></tr>
                <tr v-else-if="!cmList.length"><td colspan="7" class="text-muted">No courses found.</td></tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center p-2 border-top bg-light" id="cmPager">
            <small class="text-muted" id="cmMeta">&nbsp;</small>
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-secondary" id="cmPrev" @click="prevPage">Prev</button>
              <button class="btn btn-outline-secondary" id="cmNext" @click="nextPage">Next</button>
            </div>
          </div>
        </div>
        
        <!-- Loading overlay & toasts (legacy styles) -->
        <div class="gs-loading-overlay" :class="{ show: loading }">
          <div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div>
        </div>
        <div class="gs-toast-container">
          <div v-for="(t,idx) in toasts" :key="idx" :class="['gs-toast', t.type]">
            <div class="flex-grow-1" v-html="t.message"></div>
            <button class="close" @click="removeToast(idx)">&times;</button>
          </div>
        </div>

        <!-- Approve / Archive modals (lightweight) -->
        <div class="modal fade" tabindex="-1" ref="approveModalEl" id="approveCourseModal">
          <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Approve Course</h5><button type="button" class="btn-close" @click="hideApproveModal"></button></div>
              <div class="modal-body">Are you sure you want to approve <strong id="approveCourseName">{{ pendingActionName }}</strong>?</div>
              <div class="modal-footer"><button class="btn btn-sm btn-secondary" @click="hideApproveModal">Cancel</button><button class="btn btn-sm btn-success" @click="confirmApprove">Approve</button></div>
            </div>
          </div>
        </div>

        <div class="modal fade" tabindex="-1" ref="archiveModalEl" id="archiveCourseModal">
          <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Archive Course</h5><button type="button" class="btn-close" @click="hideArchiveModal"></button></div>
          <div class="modal-body">{{ pendingActionType === 'unarchive' ? 'Unarchive' : 'Archive' }} <strong id="archiveCourseName">{{ pendingActionName }}</strong>?</div>
            <div class="modal-footer"><button class="btn btn-sm btn-secondary" @click="hideArchiveModal">Cancel</button><button class="btn btn-sm btn-danger" @click="confirmArchive">{{ pendingActionType === 'unarchive' ? 'Unarchive' : 'Archive' }}</button></div>
            </div>
          </div>
        </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, computed } from 'vue'
import '../assets/admindash.css'
import { useRouter } from 'vue-router'
import { API_BASE } from '../services/apiBase'

const router = useRouter()
const adminName = ref('Admin')
const stats = ref({ students: '--', teachers: '--', admins: '--', activeCourses: '--', activeInstructors: '--', activeStudents: '--', coursesActive: '--', coursesPending: '--', coursesArchived: '--', distActive:0, distPending:0, distArchived:0, summary: '' })

const cmList = ref([])
const cmCounts = ref({ all:0, active:0, pending:0, archived:0 })
const cmQuery = ref('')
const cmLoading = ref(false)
let cmPage = 1
const sidebarOpen = ref(false)
const loading = ref(false)
const toasts = ref([])
const pendingActionId = ref(null)
const pendingActionName = ref('')
const pendingActionType = ref('')
const adminError = ref('')
const showAdminBanner = ref(false)
let approveModalInstance = null
let archiveModalInstance = null
const approveModalEl = ref(null)
const archiveModalEl = ref(null)
const _sampleApplied = { value: false }

const SAMPLE_STATS = {
  students: 1,
  teachers: 1,
  admins: 1,
  activeCourses: '--',
  activeInstructors: 1,
  activeStudents: 1,
  coursesActive: '--',
  coursesPending: '--',
  coursesArchived: '--',
  distActive: 0,
  distPending: 0,
  distArchived: 0
}

const SAMPLE_COURSES = [
  { id:45, name: 'EDUAS 205 – Instructional Supervision and Curriculum Development – 3', teacher: 'lenard gaa', studentCount:0, createdAt: '2025-10-16', status: 'Active' },
  { id:44, name: 'EDUMT 205 – Seminar in Mathematics Education – 3', teacher: 'lenard gaa', studentCount:0, createdAt: '2025-10-15', status: 'Active' },
  { id:43, name: 'EDUFI 203 – Barayti at Baryasyon ng Filipino – 3', teacher: 'lenard gaa', studentCount:0, createdAt: '2025-10-14', status: 'Active' },
  { id:42, name: 'EDUAS 202 – Educational Planning and Development – 3', teacher: 'lenard gaa', studentCount:1, createdAt: '2025-10-12', status: 'Active' }
]

// derived distribution from cmCounts (prefer cmCounts when available)
const cmTotal = computed(() => {
  const a = Number(cmCounts.value.active) || 0
  const p = Number(cmCounts.value.pending) || 0
  const r = Number(cmCounts.value.archived) || 0
  return a + p + r
})
const distActive = computed(() => cmTotal.value > 0 ? Math.round((Number(cmCounts.value.active || 0) / cmTotal.value) * 100) : stats.value.distActive || 0)
const distPending = computed(() => cmTotal.value > 0 ? Math.round((Number(cmCounts.value.pending || 0) / cmTotal.value) * 100) : stats.value.distPending || 0)
const distArchived = computed(() => {
  if (cmTotal.value > 0) {
    const a = distActive.value
    const p = distPending.value
    return Math.max(0, 100 - a - p)
  }
  return stats.value.distArchived || 0
})
const distributionSummary = computed(() => {
  if (cmTotal.value > 0) return `${distActive.value}% active (${cmCounts.value.active}/${cmTotal.value})`
  return stats.value.summary || ''
})
const displayActive = computed(() => cmTotal.value > 0 ? cmCounts.value.active : (stats.value.coursesActive === '--' ? 0 : stats.value.coursesActive))
const displayPending = computed(() => cmTotal.value > 0 ? cmCounts.value.pending : (stats.value.coursesPending === '--' ? 0 : stats.value.coursesPending))
const displayArchived = computed(() => cmTotal.value > 0 ? cmCounts.value.archived : (stats.value.coursesArchived === '--' ? 0 : stats.value.coursesArchived))

function formatDate(d){ try { return new Date(d).toLocaleDateString() } catch(e){ return '' } }
function courseStatusClass(s){ if(s==='Active') return 'bg-success text-white'; if(s==='Pending') return 'bg-warning text-dark'; if(s==='Archived') return 'bg-secondary text-white'; return 'bg-secondary' }

// API helper (attaches x-user-id fallback and includes credentials)
function api(path, opts){
  const BACKEND = API_BASE
  const userId = localStorage.getItem('loggedInUserId')
  const baseOpts = Object.assign({ credentials: 'include' }, opts || {})
  baseOpts.headers = Object.assign({}, baseOpts.headers || {})
  if (userId && !baseOpts.headers['x-user-id']) baseOpts.headers['x-user-id'] = userId
  // append userId as query fallback when not present
  const fullPath = (!path.includes('userId=') && userId) ? (path + (path.includes('?') ? '&' : '?') + 'userId=' + encodeURIComponent(userId)) : path
  return fetch(BACKEND + fullPath, baseOpts).then(async res => {
    if (res.status === 401) return null
    if (!res.ok) throw new Error('HTTP ' + res.status)
    return res.json()
  }).catch(e => { console.warn('api error', path, e); return null })
}

async function loadStats(){
  try{
    const d = await api('/api/admin/stats')
    if(d){
      // backend returns classesActive / classesPending / classesArchived
      const mapped = Object.assign({}, stats.value, d);
      // normalize keys so template uses coursesActive/Pending/Archived
      if(typeof d.classesActive !== 'undefined') mapped.coursesActive = d.classesActive;
      if(typeof d.classesPending !== 'undefined') mapped.coursesPending = d.classesPending;
      if(typeof d.classesArchived !== 'undefined') mapped.coursesArchived = d.classesArchived;
      // compute distribution percentages (avoid divide-by-zero)
      const a = Number(mapped.coursesActive) || 0;
      const p = Number(mapped.coursesPending) || 0;
      const r = Number(mapped.coursesArchived) || 0;
      const total = a + p + r;
      if(total > 0){
        mapped.distActive = Math.round((a / total) * 100);
        mapped.distPending = Math.round((p / total) * 100);
        mapped.distArchived = Math.max(0, 100 - mapped.distActive - mapped.distPending);
        mapped.summary = `${mapped.distActive}% active (${a}/${total})`;
      } else {
        mapped.distActive = 0; mapped.distPending = 0; mapped.distArchived = 0; mapped.summary = '';
      }
      stats.value = mapped;
    }
  }catch(e){ console.warn('loadStats failed', e) }
}

// Admin self-check and init
async function initAdmin(){
  adminError.value = ''
  const chk = await api('/api/admin/self')
  if(!chk){
    console.warn('Admin self-check failed')
    // Attempt dev-only seed fallback on localhost to reduce immediate kickouts
    const host = window.location.hostname || ''
    if(host.includes('localhost') || host === '127.0.0.1'){
      const seed = await api('/api/admin/seed-admin', { method: 'POST' })
      if(seed && seed.id){
        // store seeded id and retry
        localStorage.setItem('loggedInUserId', String(seed.id))
        localStorage.setItem('loggedInUserRole', 'admin')
        localStorage.setItem('loggedInUserName', 'Admin')
        const chk2 = await api('/api/admin/self')
        if(chk2 && chk2.ok){
          await loadStats(); await loadNotifications(); await loadCourseMonitor(currentStatus.value||'all', cmPage, cmQuery.value); return
        }
      }
    }
    adminError.value = 'Admin self-check failed. You may not be authenticated as admin.'
    showAdminBanner.value = true
    return
  }
  if(!chk.ok){
    // Not actually admin — show banner and don't immediately wipe storage in dev
    adminError.value = 'You are not an admin. Please login as admin.'
    showAdminBanner.value = true
    return
  }
  // ready: load stats and notifications
  await loadStats()
  await loadNotifications()
  // initial course load
  await loadCourseMonitor(currentStatus.value || 'all', cmPage, cmQuery.value)
  ensureSampleContent()
}

function ensureSampleContent(){
  // only auto-fill on localhost/dev and only once
  try{
    const host = (window.location.hostname || '')
    if(!host.includes('localhost') && host !== '127.0.0.1') return
  }catch(e){ return }
  if(_sampleApplied.value) return
  // if stats are empty/placeholder or cmList is empty, fill with sample data
  const needStats = !stats.value || stats.value.students === '--' || stats.value.students === undefined
  const needCourses = !Array.isArray(cmList.value) || cmList.value.length === 0
  if(needStats) stats.value = Object.assign({}, stats.value, SAMPLE_STATS)
  if(needCourses){
    cmList.value = SAMPLE_COURSES.map(r => ({
      id: r.id,
      class_name: r.name,
      instructor_name: r.teacher,
      student_count: r.studentCount,
      created_at: r.createdAt,
      status: r.status
    }))
    cmCounts.value = { all: cmList.value.length, active: cmList.value.length, pending: 0, archived: 0 }
  }
  _sampleApplied.value = true
}

async function trySeedAdmin(){
  const seed = await api('/api/admin/seed-admin', { method: 'POST' })
  if(seed && seed.id){
    localStorage.setItem('loggedInUserId', String(seed.id))
    localStorage.setItem('loggedInUserRole', 'admin')
    localStorage.setItem('loggedInUserName', 'Admin')
    showAdminBanner.value = false
    adminError.value = ''
    await initAdmin()
  } else {
    adminError.value = 'Seed admin failed — check backend logs.'
  }
}

async function runAuthInspect(){
  try{
    const userId = localStorage.getItem('loggedInUserId')
  const res = await fetch(API_BASE + '/api/debug/auth-inspect', { credentials:'include', headers: userId ? { 'x-user-id': userId } : {} })
    const body = await res.json().catch(()=>null)
    adminError.value = JSON.stringify(body || { status: res.status }, null, 2)
  }catch(e){ adminError.value = 'auth-inspect failed: '+ String(e) }
}

async function loadNotifications(){
  const notifs = await api('/api/admin/notifications')
  if(!notifs) return
  const btn = document.getElementById('notifBtn')
  const count = notifs.pendingCoursesCount || 0
  // store badge state in DOM or reactive (simple approach)
  let badge = btn && btn.querySelector('.notification-badge')
  if(count > 0){
    if(!badge){ badge = document.createElement('span'); badge.className = 'notification-badge'; btn.appendChild(badge) }
    badge.textContent = count > 9 ? '9+' : String(count)
    badge.style.display = 'inline-block'
  } else if(badge) {
    badge.style.display = 'none'
  }
  // set popover content
  if(btn){
    const content = count > 0 ? (`<div class="notification-popover"><div class="notification-header"><strong>${count} Pending Course Request${count>1?'s':''}</strong></div><div class="notification-list">${(notifs.pendingCourses||[]).map(c=>`<div class="notification-item"><div class="notification-title">${(c.name||'')}</div><div class="notification-meta">${(c.createdBy||'')} • ${new Date(c.createdAt||'').toLocaleString()}</div></div>`).join('')}</div><div class="notification-footer"><a href="/admin/courses" class="btn btn-sm btn-primary w-100">View All Courses</a></div></div>`) : 'No new notifications.'
    btn.setAttribute('data-bs-content', content)
    btn.setAttribute('data-bs-html', 'true')
    // initialize popover
    await nextTick()
    try{ const existing = window.bootstrap && window.bootstrap.Popover && window.bootstrap.Popover.getInstance(btn); if(existing) existing.dispose(); if(window.bootstrap && window.bootstrap.Popover) new window.bootstrap.Popover(btn, { trigger: 'click', placement: 'bottom', html: true, sanitize: false }) }catch(e){ /* ignore */ }
  }
}

async function loadCourseMonitor(status='all', page=1, q=''){
  cmLoading.value = true
  try{
    cmPage = page
    const path = `/api/admin/courses?status=${status}&page=${page}` + (q ? '&search='+encodeURIComponent(q) : '')
    const d = await api(path)
    if(!d){ cmList.value = []; return }
    const rows = Array.isArray(d.data) ? d.data : []
    cmList.value = rows.map(r => ({
      id: r.id,
      class_name: r.name || r.class_name || '',
      instructor_name: r.teacher || r.teacher_display || r.instructor_name || '',
      student_count: (r.studentCount != null) ? r.studentCount : (r.student_count || 0),
      created_at: r.createdAt || r.created_at || '',
      status: (typeof r.status === 'string') ? (r.status.charAt(0).toUpperCase() + r.status.slice(1)) : (r.status || '')
    }))
    if(d.meta){ cmCounts.value.all = d.meta.total || cmCounts.value.all; if(typeof d.meta.active !== 'undefined') cmCounts.value.active = d.meta.active; if(typeof d.meta.pending !== 'undefined') cmCounts.value.pending = d.meta.pending; if(typeof d.meta.archived !== 'undefined') cmCounts.value.archived = d.meta.archived }
  }catch(e){ console.warn('loadCourseMonitor failed', e); cmList.value = [] }
  finally{ cmLoading.value = false }
}

const currentStatus = ref('all')
function setCourseStatus(s){ currentStatus.value = s; loadCourseMonitor(s, 1, cmQuery.value) }
function setCourseStatusAll(){ setCourseStatus('all') }
function setCourseStatusActive(){ setCourseStatus('active') }
function setCourseStatusPending(){ setCourseStatus('pending') }
function setCourseStatusArchived(){ setCourseStatus('archived') }
function prevPage(){ if(cmPage>1){ cmPage--; loadCourseMonitor(currentStatus.value || 'all', cmPage, cmQuery.value) } }
function nextPage(){ cmPage++; loadCourseMonitor(currentStatus.value || 'all', cmPage, cmQuery.value) }

function openApprove(course){
  pendingActionId.value = course.id
  pendingActionName.value = course.class_name
  showApproveModal()
}
function openArchive(course){
  pendingActionId.value = course.id
  pendingActionName.value = course.class_name
  pendingActionType.value = 'archive'
  showArchiveModal()
}
function openUnarchive(course){
  pendingActionId.value = course.id
  pendingActionName.value = course.class_name
  pendingActionType.value = 'unarchive'
  showArchiveModal()
}

function showToast(message, type=''){ toasts.value.push({ message, type }); setTimeout(()=>{ if(toasts.value.length) toasts.value.shift(); }, 4000) }
function removeToast(i){ toasts.value.splice(i,1) }
function setLoading(v){ loading.value = !!v }

function showApproveModal(){
  if(window.bootstrap && approveModalInstance==null && refsAvailable()){
    approveModalInstance = new window.bootstrap.Modal(approveModalEl.value)
  }
  if(approveModalInstance) approveModalInstance.show();
}
function hideApproveModal(){ if(approveModalInstance) approveModalInstance.hide(); }
function showArchiveModal(){ if(window.bootstrap && archiveModalInstance==null && refsAvailable()){ archiveModalInstance = new window.bootstrap.Modal(archiveModalEl.value) } if(archiveModalInstance) archiveModalInstance.show(); }
function hideArchiveModal(){ if(archiveModalInstance) archiveModalInstance.hide(); }

function refsAvailable(){ return !!(approveModalEl && approveModalEl.value) && !!(archiveModalEl && archiveModalEl.value) }

async function confirmApprove(){ await mutateCourse('approve'); }
async function confirmArchive(){ const kind = pendingActionType.value === 'unarchive' ? 'unarchive' : 'archive'; await mutateCourse(kind); }

async function mutateCourse(kind, id){
  const targetId = id || pendingActionId.value
  if(!targetId) return;
  setLoading(true)
  try{
    const path = `/api/admin/courses/${targetId}`
    if(kind === 'delete'){
      const res = await api(path, { method: 'DELETE' })
      if(res) showToast('Course deleted','success'); else showToast('Action failed','error')
    } else {
      let newStatus = (kind === 'approve') ? 'active' : 'archived'
      if(kind === 'unarchive') newStatus = 'active'
      const body = { status: newStatus }
      const res = await api(path, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
      if(res) showToast(kind==='approve'? 'Course approved': (kind==='unarchive' ? 'Course unarchived' : 'Course archived'), 'success'); else showToast('Action failed','error')
    }
  }catch(e){ showToast('Action failed','error') }
  finally{ setLoading(false); hideApproveModal(); hideArchiveModal(); pendingActionId.value = null; pendingActionName.value=''; loadStats(); loadCourseMonitor('all', cmPage, cmQuery.value); }
}

function confirmDelete(course){
  if(!course) return
  if(confirm(`Delete course "${course.class_name}"? This cannot be undone.`)){
    mutateCourse('delete', course.id)
  }
}

function tryApprove(course){
  if(!course) return
  if(course.status && course.status.toLowerCase() === 'pending'){
    openApprove(course)
  } else {
    // noop - clicking disabled approve should not do anything
  }
}

function logout(){ localStorage.removeItem('loggedInUserId'); localStorage.removeItem('loggedInUserRole'); router.push('/login') }
function onLogout(e){ e && e.preventDefault(); logout() }

function toggleSidebar(){ sidebarOpen.value = !sidebarOpen.value }
function closeSidebar(){ sidebarOpen.value = false }

// Debounce search
let _qTimer = null
watch(cmQuery, (v)=>{ clearTimeout(_qTimer); _qTimer = setTimeout(()=> loadCourseMonitor(currentStatus.value||'all', 1, cmQuery.value), 300) })

onMounted(async ()=>{
  const name = localStorage.getItem('loggedInUserName') || 'Admin'
  adminName.value = name
  // initialize bootstrap popovers if present (notif popover handled in loadNotifications)
  try{ if(window.bootstrap && window.bootstrap.Popover){ document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el=> new window.bootstrap.Popover(el)) } }catch(e){}
  await initAdmin()
})
</script>

<style scoped>
.topbar{display:flex;justify-content:space-between;align-items:center;padding:0.5rem 1rem;background:#fff;border-bottom:1px solid #e9ecef}
.brand-mark{font-weight:700;background:linear-gradient(135deg,var(--gs-red),#f87171);color:#fff;padding:6px;border-radius:4px}
.layout{display:flex}
.sidebar{width:200px}
.admin-main{flex:1}
.welcome-container h1{font-size:1.6rem}
.badge{font-size:0.75rem}
</style>

<style scoped>
/* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* Dashboard Header */
.dashboard-header {
  background: white;
  padding: 2rem 0;
  margin: -1.5rem -2rem 1.5rem -2rem;
  border-bottom: 1px solid #e9ecef;
  text-align: center;
}

.dashboard-header small {
  display: block;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6c757d;
  margin-bottom: 0.5rem;
}

.dashboard-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

.dashboard-header h1 span {
  color: #ab1818;
}

.dashboard-header p {
  color: #6c757d;
  margin: 0.25rem 0 0 0;
}

/* Dashboard Body */
.dashboard-body {
  padding: 0;
  margin: 0;
}

/* Stat Cards */
.card {
  border: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

.card-body {
  padding: 1.25rem;
}

.card-title {
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Progress Bar Improvements */
.progress {
  border-radius: 6px;
  overflow: hidden;
}

.progress-bar {
  transition: width 0.6s ease;
}

/* Course Monitoring */
.course-monitoring {
  border: none;
}

.course-monitoring .card-header {
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
  padding: 1rem 1.25rem;
}

.course-monitoring .btn-group-sm .btn {
  font-size: 0.875rem;
  padding: 0.375rem 0.75rem;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
  .welcome-section {
    padding: 1.5rem;
  }
  
  .welcome-section h1 {
    font-size: 1.5rem;
  }
  
  .card-body {
    padding: 1rem;
  }
}
</style>
