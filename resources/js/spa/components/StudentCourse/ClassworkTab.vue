<template>
  <div class="student-classwork-tab">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="mt-3 text-muted fw-medium">Loading classwork...</div>
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Empty State -->
      <div v-if="items.length === 0" class="empty-state">
        <i class="bi bi-journal-text empty-icon"></i>
        <h3 class="empty-title">No Classwork Yet</h3>
        <p class="empty-description">Your teacher hasn't posted any classwork. Check back later!</p>
      </div>

      <!-- Classwork Grid -->
      <div v-else class="classwork-grid">
        <div 
          v-for="item in items" 
          :key="item.id" 
          :class="['classwork-card', `classwork-${getTypeClass(item.type)}`, { 'has-submission': item.submission_id }]"
          @click="openDetail(item)"
          role="button"
          tabindex="0"
        >
          <!-- Card Header -->
          <div class="card-header">
            <div class="type-badge" :class="`type-${getTypeClass(item.type)}`">
              <i :class="getTypeIcon(item.type)"></i>
              <span>{{ getTypeLabel(item.type) }}</span>
            </div>
            <div v-if="item.submission_id" class="status-badge status-submitted">
              <i class="bi bi-check-circle-fill me-1"></i>
              Submitted
            </div>
            <div v-else-if="isPastDue(item.due_at)" class="status-badge status-missing">
              <i class="bi bi-exclamation-circle-fill me-1"></i>
              Missing
            </div>
            <div v-else class="status-badge status-assigned">
              <i class="bi bi-clock me-1"></i>
              Assigned
            </div>
          </div>

          <!-- Card Body -->
          <div class="card-content">
            <h3 class="card-title">{{ item.title }}</h3>
            <p v-if="item.description" class="card-description">{{ truncateText(item.description, 100) }}</p>
            
            <!-- Meta Info -->
            <div class="card-meta">
              <div class="meta-item">
                <i class="bi bi-calendar3 me-1"></i>
                <span>Posted {{ item.postedDate }}</span>
              </div>
              <div v-if="item.due_at" class="meta-item">
                <i class="bi bi-alarm me-1"></i>
                <span>Due {{ formatDueDate(item.due_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Card Footer -->
          <div class="card-footer">
            <button 
              type="button" 
              class="btn-view-details" 
              @click.stop="openDetail(item)"
            >
              View Details
              <i class="bi bi-arrow-right ms-2"></i>
            </button>
          </div>

          <!-- Submission Indicator -->
          <div v-if="item.submission_id" class="submission-indicator">
            <i class="bi bi-check2-circle"></i>
          </div>
        </div>
      </div>

      <!-- Classwork detail modal -->
      <ClassworkDetailModal ref="detailModal" />
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref, watch, onMounted, onUnmounted, computed } from 'vue'
import ClassworkDetailModal from '@/components/StudentCourse/ClassworkDetailModal.vue'
import { API_BASE } from '@/services/apiBase'
const props = defineProps({ classId: [String, Number], openClassworkId: [String, Number] })
const currentId = ref(props.classId || '')
// react to external requests to open a specific classwork item
const requestedOpenId = ref(props.openClassworkId || null)
watch(() => props.openClassworkId, (v) => { requestedOpenId.value = v })
watch(() => props.classId, (v) => { if (v) currentId.value = v })

const loading = ref(false)
const items = ref([])
const detail = ref(null)
const attachments = ref([])
const submission = ref(null)
const submissionSectionHtml = ref('')
const detailModal = ref(null)

function postedDateDisplay(d){ try { return d ? new Date(d).toLocaleDateString() : 'N/A' } catch(e){ return 'N/A' } }

// Helper functions for display
function getTypeClass(type) {
  const t = String(type || '').toLowerCase();
  if (t === 'assignment') return 'assignment';
  if (t === 'activity') return 'activity';
  if (t === 'quiz') return 'quiz';
  if (t === 'exam') return 'exam';
  if (t === 'lesson') return 'lesson';
  if (t === 'material') return 'material';
  return 'assignment';
}

function getTypeLabel(type) {
  const t = String(type || '').toLowerCase();
  if (t === 'assignment') return 'Assignment';
  if (t === 'activity') return 'Activity';
  if (t === 'quiz') return 'Quiz';
  if (t === 'exam') return 'Exam';
  if (t === 'lesson') return 'Lesson';
  if (t === 'material') return 'Material';
  return 'Assignment';
}

function getTypeIcon(type) {
  const t = String(type || '').toLowerCase();
  if (t === 'assignment') return 'bi bi-file-earmark-text';
  if (t === 'activity') return 'bi bi-lightning-charge';
  if (t === 'quiz') return 'bi bi-patch-question';
  if (t === 'exam') return 'bi bi-file-earmark-check';
  if (t === 'lesson') return 'bi bi-journal-bookmark';
  if (t === 'material') return 'bi bi-book';
  return 'bi bi-file-earmark-text';
}

function formatDueDate(dueAt) {
  if (!dueAt) return 'No due date';
  try {
    const date = new Date(dueAt);
    const now = new Date();
    const diff = date - now;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days < 0) return 'Past due';
    if (days === 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    if (days < 7) return `In ${days} days`;
    return date.toLocaleDateString();
  } catch(e) {
    return 'Invalid date';
  }
}

function isPastDue(dueAt) {
  if (!dueAt) return false;
  try {
    return new Date(dueAt) < new Date();
  } catch(e) {
    return false;
  }
}

function truncateText(text, maxLength) {
  if (!text) return '';
  const stripped = text.replace(/<[^>]*>/g, ''); // Remove HTML tags
  if (stripped.length <= maxLength) return stripped;
  return stripped.substring(0, maxLength) + '...';
}

async function loadForClass(id){
  loading.value = true
  items.value = []
  detail.value = null
  attachments.value = []
  submission.value = null
  submissionSectionHtml.value = ''
  if(!id){ loading.value=false; return }
  try{
  const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
  const headers = userId ? { 'x-user-id': userId } : {}
  const base = API_BASE || ''
  const res = await fetch(`${base}/api/classes/${id}/classwork`, { headers })
    if(res.ok){ const data = await res.json(); items.value = Array.isArray(data) ? data.map(it=>({ ...it, postedDate: postedDateDisplay(it.created_at || it.creation_time || it.createdAt) })) : [] }
  }catch(e){ console.error('Failed to load classwork list', e) }
  loading.value = false
}

async function openDetail(item){
  const id = item && item.id ? item.id : item
  if(!id) return
  // delegate to modal component which handles loading and display
  try{
    if(detailModal.value && detailModal.value.showFor){
      await detailModal.value.showFor(id)
    } else if(detailModal.value && detailModal.value.showFor){
      // fallback in case getCurrentInstance proxy resolves differently
      detailModal.value.showFor(id)
    }
  }catch(e){ console.error('Failed to open detail modal', e) }
}

function closeDetail(){ detail.value=null; attachments.value=[]; submission.value=null; submissionSectionHtml.value='' }

function normalizeAttachments(cw){
  const BACKEND = API_BASE || ''
  let arr = []
  if(Array.isArray(cw.materialFiles)) arr = cw.materialFiles
  else if(Array.isArray(cw.materials)) arr = cw.materials
  else if(cw.materialFile) arr = [cw.materialFile]
  return arr.map(f=>({ name: f.originalName||f.fileName||f.name||f.storedName||'file', url: (f.url && f.url.startsWith('http'))? f.url : (BACKEND.replace(/\/$/,'') + (f.url||('/uploads/' + (f.storedName||f.fileName||'')))) }))
}

function buildQuestionOptions(question, questionIndex){
  // return simple HTML similar to legacy for display (disabled inputs)
  const cfg = question.config || {}
  if(question.type === 'multiple' && Array.isArray(cfg.choices)){
    return `<div class="list-group">${cfg.choices.map((c, idx)=>`<div class="list-group-item"><input class="form-check-input me-2" type="radio" disabled> ${c}</div>`).join('')}</div><small class="text-muted mt-2 d-block"><i class="bi bi-info-circle"></i> Submit your work to answer this question</small>`
  }
  if(question.type === 'truefalse'){
    return `<div class="list-group"><div class="list-group-item">True</div><div class="list-group-item">False</div></div>`
  }
  return '<p class="text-muted">Question preview not available</p>'
}

function buildSubmissionSectionHtml(cw, sub){
  const t = String((cw.type||'')).toLowerCase()
  if(t === 'lesson' || t === 'attendance'){
    return `<div class="card"><div class="card-body"><h5 class="card-title">This item doesn't require a submission</h5><p class="text-muted small mb-0">No submission needed</p></div></div>`
  }
  if(sub){
  return `<div class="card"><div class="card-body"><h5 class="card-title">Your Work</h5><p class="text-success">Submitted on ${new Date(sub.submission_time).toLocaleString()}</p>${(Array.isArray(sub.files)&&sub.files.length)? sub.files.map(f=>{ const url = f.url||('/uploads/'+(f.storedName||'')); const name = f.originalName||f.storedName||'file'; const safeUrl = String(url).replace(/'/g, "%27"); const safeName = String(name).replace(/'/g, "%27"); return `<a class="btn btn-sm btn-outline-secondary me-1 mb-1" href="#" onclick="window.openFilePreview('${safeUrl}','${safeName}'); return false;">${name}</a>` }).join('') : '<span class="text-muted small">No files attached.</span>'}<p class="mt-2">Grade: ${sub.grade !== null && sub.grade !== undefined ? sub.grade : 'Not graded yet'}</p><button class="btn btn-danger mt-2" id="unsubmitBtn">Unsubmit</button></div></div>`
  }
  return `<div class="card"><div class="card-body"><h5 class="card-title">Your Work</h5><form id="submissionForm"><div class="mb-3"><label class="form-label">Attach File</label><input class="form-control" type="file" id="submissionFile" required></div><div class="mb-3"><label class="form-label">Private Comment (Optional)</label><textarea class="form-control" id="submissionComment" rows="3"></textarea></div><button type="submit" class="btn btn-primary w-100">Mark as Done</button></form></div></div>`
}

// handle form submit/unsubmit via delegated listeners (watch for detailVisible changes)
// submission handling is delegated to the modal component now

async function handleSubmission(classworkId){
  const BACKEND = API_BASE || ''
  try{
    const fileInput = document.getElementById('submissionFile')
    const comment = document.getElementById('submissionComment')?.value || ''
    if(!fileInput || !fileInput.files[0]){ alert('Please select a file to submit.'); return }
    const fd = new FormData(); fd.append('attachments', fileInput.files[0]); fd.append('comment', comment)
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
    const res = await fetch(`${BACKEND}/api/classwork/${classworkId}/submit`, { method:'POST', body: fd, headers })
    if(!res.ok){ const err = await res.json().catch(()=>({})); throw new Error(err.message || 'Submission failed') }
    alert('Work submitted successfully!')
    await openDetail(classworkId)
  }catch(e){ console.error('Submission error', e); alert('Error: '+e.message) }
}

async function unsubmitWork(classworkId, submissionId){
  const BACKEND = window.BACKEND_API_BASE_URL || 'http://localhost:3000'
  try{
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
    const res = await fetch(`${BACKEND}/api/submissions/${submissionId}`, { method:'DELETE', headers })
    if(!res.ok){ const err = await res.json().catch(()=>({})); throw new Error(err.message || 'Failed to unsubmit') }
    alert('Submission removed. You can submit again.')
    await openDetail(classworkId)
  }catch(e){ console.error('Unsubmit error', e); alert('Error: '+e.message) }
}

onMounted(()=>{ if(currentId.value) loadForClass(currentId.value) })
watch(()=> currentId.value, (v,o)=>{ if(v && v!==o) loadForClass(v) })

// Listen for classwork changes to auto-refresh
function onClassworkChanged() {
  if (currentId.value) {
    console.log('StudentClassworkTab: Refreshing due to classwork event');
    loadForClass(currentId.value);
  }
}

if (typeof window !== 'undefined') {
  window.addEventListener('classwork:created', onClassworkChanged);
  window.addEventListener('classwork:updated', onClassworkChanged);
  window.addEventListener('classwork:deleted', onClassworkChanged);
}

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('classwork:created', onClassworkChanged);
    window.removeEventListener('classwork:updated', onClassworkChanged);
    window.removeEventListener('classwork:deleted', onClassworkChanged);
  }
});

// When a requested open id appears, try to load details (after items have loaded)
watch(requestedOpenId, async (val) => {
  if (!val) return
  // ensure list loaded and classId present
  if (!currentId.value) return
  // Wait for items to be present
  const waitUntil = 5000
  const start = Date.now()
  while(items.value.length === 0 && (Date.now() - start) < waitUntil) {
    await new Promise(r => setTimeout(r, 100))
  }
  // open the detail (val might be a string)
  try { await openDetail(val) } catch(e) { console.error('Auto-open classwork failed', e) }
})

const detailDescription = computed(()=>{
  if(!detail.value) return '<p class="text-muted">No description.</p>'
  return detail.value.description || '<p class="text-muted">No description.</p>'
})
</script>

<style scoped>
/* ========================================
   STUDENT CLASSWORK TAB - MODERN DESIGN
   ======================================== */

.student-classwork-tab {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 2rem;
  min-height: 400px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 5rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.empty-icon {
  font-size: 5rem;
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.empty-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: 0.5rem;
}

.empty-description {
  font-size: 1.05rem;
  color: #64748b;
  margin: 0;
}

/* Classwork Grid */
.classwork-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

/* Classwork Card */
.classwork-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
  border: 2px solid transparent;
}

.classwork-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.classwork-card:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
}

/* Type-specific colors */
.classwork-assignment {
  border-top: 4px solid #3b82f6;
}

.classwork-activity {
  border-top: 4px solid #f59e0b;
}

.classwork-quiz {
  border-top: 4px solid #a855f7;
}

.classwork-exam {
  border-top: 4px solid #ef4444;
}

.classwork-lesson {
  border-top: 4px solid #06b6d4;
}

.classwork-material {
  border-top: 4px solid #10b981;
}

/* Card Header */
.card-header {
  padding: 1.25rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* Type Badge */
.type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.type-assignment {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.type-activity {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.type-quiz {
  background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
  color: white;
}

.type-exam {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.type-lesson {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
  color: white;
}

.type-material {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-submitted {
  background: #d1fae5;
  color: #065f46;
}

.status-assigned {
  background: #dbeafe;
  color: #1e40af;
}

.status-missing {
  background: #fee2e2;
  color: #991b1b;
}

/* Card Content */
.card-content {
  padding: 1.5rem;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.75rem 0;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-description {
  font-size: 0.95rem;
  color: #64748b;
  margin: 0 0 1rem 0;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Card Meta */
.card-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.meta-item {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: #64748b;
}

.meta-item i {
  color: #94a3b8;
}

/* Card Footer */
.card-footer {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e2e8f0;
}

.btn-view-details {
  width: 100%;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
  cursor: pointer;
}

.btn-view-details:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-view-details:active {
  transform: translateY(0);
}

/* Submission Indicator */
.submission-indicator {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.submission-indicator i {
  font-size: 1.5rem;
  color: white;
}

.classwork-card.has-submission {
  border-color: #10b981;
}

/* ========================================
   MOBILE RESPONSIVE
   ======================================== */

@media (max-width: 767.98px) {
  .student-classwork-tab {
    padding: 1rem;
  }

  .classwork-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .card-header {
    padding: 1rem;
  }

  .card-content {
    padding: 1rem;
  }

  .card-title {
    font-size: 1.1rem;
  }

  .type-badge {
    font-size: 0.75rem;
    padding: 0.4rem 0.75rem;
  }

  .status-badge {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
  }
}

@media (max-width: 479.98px) {
  .empty-icon {
    font-size: 3.5rem;
  }

  .empty-title {
    font-size: 1.5rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .submission-indicator {
    width: 40px;
    height: 40px;
  }

  .submission-indicator i {
    font-size: 1.2rem;
  }
}
</style>