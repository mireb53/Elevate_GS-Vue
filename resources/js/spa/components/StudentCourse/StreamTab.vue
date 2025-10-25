<template>
  <div class="student-stream-tab">
    <div class="stream-container">
      <!-- Upcoming Sidebar -->
      <aside class="upcoming-sidebar">
        <div class="upcoming-card">
          <div class="upcoming-header">
            <i class="bi bi-calendar-check"></i>
            <h3>Upcoming</h3>
          </div>
          <div class="upcoming-body">
            <div v-if="upcoming.length" class="upcoming-list">
              <div 
                v-for="it in upcoming" 
                :key="it.id"
                class="upcoming-item"
                @click="openDetail(it)"
              >
                <div class="upcoming-content">
                  <div class="upcoming-title" v-html="it.titleHtml"></div>
                  <div class="upcoming-due">
                    <i class="bi bi-clock"></i>
                    <span>{{ formatDueDate(it.due_at || it.dueAt) }}</span>
                  </div>
                </div>
                <i class="bi bi-chevron-right upcoming-arrow"></i>
              </div>
            </div>
            <div v-else class="empty-upcoming">
              <i class="bi bi-check-circle"></i>
              <p>All caught up!</p>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Stream -->
      <main class="stream-main">
        <div class="stream-header">
          <div class="stream-title">
            <i class="bi bi-newspaper"></i>
            <h2>Class Stream</h2>
          </div>
          <div class="stream-subtitle">Latest updates and materials</div>
        </div>

        <div v-if="latest.length" class="stream-feed">
          <div 
            v-for="mat in latest" 
            :key="mat.id"
            class="stream-card"
            :class="`stream-${getTypeClass(mat.type)}`"
            @click="openDetail(mat)"
          >
            <div class="stream-card-header">
              <div class="type-indicator">
                <i :class="getTypeIcon(mat.type)"></i>
                <span>{{ getTypeLabel(mat.type) }}</span>
              </div>
              <div class="stream-date">
                <i class="bi bi-calendar3"></i>
                <span>{{ formatPostedDate(mat.created_at || mat.createdAt) }}</span>
              </div>
            </div>
            <div class="stream-card-body">
              <h4 class="stream-item-title">{{ mat.title }}</h4>
              <p v-if="mat.description" class="stream-item-description">
                {{ truncateDescription(mat.description) }}
              </p>
            </div>
            <div class="stream-card-footer">
              <button class="btn-view-stream" @click.stop="openDetail(mat)">
                <span>View Details</span>
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-stream">
          <div class="empty-stream-icon">
            <i class="bi bi-inbox"></i>
          </div>
          <h3 class="empty-stream-title">No Materials Yet</h3>
          <p class="empty-stream-description">
            Check back later for new materials and announcements from your instructor.
          </p>
        </div>
      </main>
    </div>

    <!-- Detail Modal -->
    <div v-if="selected" class="detail-modal-overlay" @click="closeDetail">
      <div class="detail-modal" @click.stop>
        <div class="detail-modal-header">
          <button class="btn-back" @click="closeDetail">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Stream</span>
          </button>
          <div class="detail-type-badge" :class="`type-${getTypeClass(selected.type)}`">
            <i :class="getTypeIcon(selected.type)"></i>
            <span>{{ getTypeLabel(selected.type) }}</span>
          </div>
        </div>
        
        <div class="detail-modal-body">
          <h2 class="detail-title">{{ selected.title }}</h2>
          
          <div class="detail-meta">
            <div class="meta-item">
              <i class="bi bi-calendar-plus"></i>
              <span>Posted {{ formatPostedDate(selected.created_at || selected.createdAt) }}</span>
            </div>
            <div v-if="selected.dueDisplay" class="meta-item due">
              <i class="bi bi-alarm"></i>
              <span>Due {{ formatDueDate(selected.due_at || selected.dueAt) }}</span>
            </div>
            <div v-if="selected.submitted" class="meta-item submitted">
              <i class="bi bi-check-circle-fill"></i>
              <span>Submitted</span>
            </div>
          </div>

          <div class="detail-description" v-html="selected.description"></div>

          <!-- Rubric Section -->
          <div class="mt-4" v-if="selected.id">
            <RubricViewer :classwork-id="selected.id" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref, watch, onMounted, onUnmounted } from 'vue'
import RubricViewer from '@/components/RubricViewer.vue'
import { API_BASE } from '@/services/apiBase'

const props = defineProps({ classId: [String, Number] })
const currentId = ref(props.classId || '')
watch(() => props.classId, (v) => { if (v) currentId.value = v })

const courseInfo = ref(null)
const classwork = ref([])
const upcoming = ref([])
const latest = ref([])
const selected = ref(null)

function safeText(s){ return String(s||'') }

function formatDate(d){ try { return d ? new Date(d).toLocaleString() : ''; } catch(e){ return '' } }

function prepareItems(items){
  return items.map(it => ({
    ...it,
    title: it.title || it.name || 'Untitled',
    description: it.description || '',
    createdDisplay: formatDate(it.created_at || it.createdAt || it.createdAt),
    dueDisplay: formatDate(it.due_at || it.dueAt || it.due),
    titleHtml: (it.submitted ? `<span class=\"text-decoration-line-through\">${escapeHtml(it.title||it.name||'Untitled')}</span>` : `<strong>${escapeHtml(it.title||it.name||'Untitled')}</strong>`) + (it.submitted ? ' <span class="badge bg-success ms-2">Submitted</span>' : '')
  }))
}

function escapeHtml(unsafe){ return String(unsafe||'').replace(/[&<"']/g, function(m){return {'&':'&amp;','<':'&lt;','"':'&quot;','\'':"&#39;"}[m] }); }

async function loadForClass(id){
  courseInfo.value = null; classwork.value = []; upcoming.value = []; latest.value = []; selected.value = null;
  if(!id) return;
  try{
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
    const base = API_BASE || ''
    const [cRes, cwRes] = await Promise.all([
      fetch(`${base}/api/classes/${id}`, { headers }),
      fetch(`${base}/api/classes/${id}/classwork`, { headers })
    ])
    if(cRes.ok) courseInfo.value = await cRes.json()
    if(cwRes.ok) classwork.value = await cwRes.json()
    const prepared = Array.isArray(classwork.value) ? prepareItems(classwork.value) : []
    // upcoming: items with due date in future, sorted asc
    upcoming.value = prepared.filter(it=> it.dueDisplay && new Date(it.dueDisplay) > new Date()).sort((a,b)=> new Date(a.dueDisplay)-new Date(b.dueDisplay))
    latest.value = prepared.slice(0,5)
  }catch(e){ console.error('stream load failed', e) }
}

function openDetail(item){ selected.value = item }
function closeDetail(){ selected.value = null }

// Helper functions for type handling
function getTypeClass(type) {
  const typeStr = String(type || '').toLowerCase();
  if (typeStr.includes('assignment')) return 'assignment';
  if (typeStr.includes('activity')) return 'activity';
  if (typeStr.includes('quiz')) return 'quiz';
  if (typeStr.includes('exam')) return 'exam';
  if (typeStr.includes('lesson')) return 'lesson';
  if (typeStr.includes('material')) return 'material';
  return 'material';
}

function getTypeLabel(type) {
  const typeStr = String(type || '').toLowerCase();
  if (typeStr.includes('assignment')) return 'Assignment';
  if (typeStr.includes('activity')) return 'Activity';
  if (typeStr.includes('quiz')) return 'Quiz';
  if (typeStr.includes('exam')) return 'Exam';
  if (typeStr.includes('lesson')) return 'Lesson';
  if (typeStr.includes('material')) return 'Material';
  return 'Material';
}

function getTypeIcon(type) {
  const typeStr = String(type || '').toLowerCase();
  if (typeStr.includes('assignment')) return 'bi bi-file-earmark-text';
  if (typeStr.includes('activity')) return 'bi bi-lightning-charge';
  if (typeStr.includes('quiz')) return 'bi bi-patch-question';
  if (typeStr.includes('exam')) return 'bi bi-mortarboard';
  if (typeStr.includes('lesson')) return 'bi bi-journal-bookmark';
  if (typeStr.includes('material')) return 'bi bi-file-earmark';
  return 'bi bi-file-earmark';
}

function formatPostedDate(date) {
  if (!date) return '';
  try {
    const d = new Date(date);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  } catch (e) {
    return '';
  }
}

function formatDueDate(date) {
  if (!date) return '';
  try {
    const d = new Date(date);
    const now = new Date();
    const diffMs = d - now;
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMs < 0) return 'Past due';
    if (diffDays === 0) return 'Due today';
    if (diffDays === 1) return 'Due tomorrow';
    if (diffDays < 7) return `Due in ${diffDays} days`;
    
    return `Due ${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
  } catch (e) {
    return '';
  }
}

function truncateDescription(text) {
  if (!text) return '';
  const stripped = text.replace(/<[^>]*>/g, '');
  if (stripped.length <= 120) return stripped;
  return stripped.substring(0, 120) + '...';
}

// Listen for classwork changes to auto-refresh
function onClassworkChanged() {
  if (currentId.value) {
    console.log('StudentStreamTab: Refreshing due to classwork event');
    loadForClass(currentId.value);
  }
}

if (typeof window !== 'undefined') {
  window.addEventListener('classwork:created', onClassworkChanged);
  window.addEventListener('classwork:updated', onClassworkChanged);
  window.addEventListener('classwork:deleted', onClassworkChanged);
}

onMounted(()=>{ if(currentId.value) loadForClass(currentId.value) })

watch(() => currentId.value, (v, o)=>{ if(v && v !== o) loadForClass(v) })

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('classwork:created', onClassworkChanged);
    window.removeEventListener('classwork:updated', onClassworkChanged);
    window.removeEventListener('classwork:deleted', onClassworkChanged);
  }
});
</script>

<style scoped>
/* ========================================
   STUDENT STREAM TAB - MODERN DESIGN
   ======================================== */

.student-stream-tab {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem;
}

/* Stream Container Layout */
.stream-container {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 2rem;
  align-items: start;
}

/* ========================================
   UPCOMING SIDEBAR
   ======================================== */

.upcoming-sidebar {
  position: sticky;
  top: 90px;
}

.upcoming-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.upcoming-header {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.upcoming-header i {
  font-size: 1.5rem;
}

.upcoming-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
}

.upcoming-body {
  padding: 1rem;
}

.upcoming-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.upcoming-item {
  padding: 1rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  border-radius: 12px;
  border: 2px solid transparent;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.upcoming-item:hover {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-color: #667eea;
  transform: translateX(4px);
}

.upcoming-content {
  flex: 1;
  min-width: 0;
}

.upcoming-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.upcoming-title strong {
  color: #667eea;
}

.upcoming-due {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.upcoming-due i {
  color: #94a3b8;
}

.upcoming-arrow {
  color: #cbd5e1;
  font-size: 1.25rem;
  transition: transform 0.2s ease;
}

.upcoming-item:hover .upcoming-arrow {
  transform: translateX(4px);
  color: #667eea;
}

.empty-upcoming {
  text-align: center;
  padding: 2rem 1rem;
  color: #64748b;
}

.empty-upcoming i {
  font-size: 3rem;
  color: #10b981;
  margin-bottom: 0.75rem;
  display: block;
}

.empty-upcoming p {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 500;
}

/* ========================================
   STREAM MAIN
   ======================================== */

.stream-main {
  min-height: 500px;
}

.stream-header {
  margin-bottom: 2rem;
}

.stream-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.stream-title i {
  font-size: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stream-title h2 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
}

.stream-subtitle {
  font-size: 1.05rem;
  color: #64748b;
  margin-left: 2.75rem;
}

/* Stream Feed */
.stream-feed {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stream-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  border: 2px solid transparent;
  transition: all 0.3s ease;
  cursor: pointer;
}

.stream-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stream-card:focus {
  outline: none;
  border-color: #667eea;
}

/* Type-specific colors */
.stream-assignment {
  border-left: 6px solid #3b82f6;
}

.stream-activity {
  border-left: 6px solid #f59e0b;
}

.stream-quiz {
  border-left: 6px solid #a855f7;
}

.stream-exam {
  border-left: 6px solid #ef4444;
}

.stream-lesson {
  border-left: 6px solid #06b6d4;
}

.stream-material {
  border-left: 6px solid #10b981;
}

/* Stream Card Header */
.stream-card-header {
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.type-indicator {
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

.stream-assignment .type-indicator {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.stream-activity .type-indicator {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.stream-quiz .type-indicator {
  background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
  color: white;
}

.stream-exam .type-indicator {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.stream-lesson .type-indicator {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
  color: white;
}

.stream-material .type-indicator {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.stream-date {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.stream-date i {
  color: #94a3b8;
}

/* Stream Card Body */
.stream-card-body {
  padding: 1.5rem;
}

.stream-item-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 1rem 0;
  line-height: 1.4;
}

.stream-item-description {
  font-size: 1rem;
  color: #64748b;
  margin: 0;
  line-height: 1.6;
}

/* Stream Card Footer */
.stream-card-footer {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e2e8f0;
}

.btn-view-stream {
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

.btn-view-stream:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-view-stream:active {
  transform: translateY(0);
}

/* Empty Stream State */
.empty-stream {
  text-align: center;
  padding: 5rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.empty-stream-icon {
  font-size: 5rem;
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.empty-stream-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: 0.75rem;
}

.empty-stream-description {
  font-size: 1.05rem;
  color: #64748b;
  margin: 0;
  max-width: 500px;
  margin-left: auto;
  margin-right: auto;
}

/* ========================================
   DETAIL MODAL
   ======================================== */

.detail-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
  overflow-y: auto;
}

.detail-modal {
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.detail-modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-back:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.detail-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-type-badge.type-assignment {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.detail-type-badge.type-activity {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.detail-type-badge.type-quiz {
  background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
  color: white;
}

.detail-type-badge.type-exam {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.detail-type-badge.type-lesson {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
  color: white;
}

.detail-type-badge.type-material {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.detail-modal-body {
  padding: 2rem;
}

.detail-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 1.5rem 0;
  line-height: 1.3;
}

.detail-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
  color: #64748b;
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  border-radius: 10px;
}

.meta-item i {
  color: #94a3b8;
  font-size: 1rem;
}

.meta-item.due {
  background: #fef3c7;
  color: #92400e;
}

.meta-item.due i {
  color: #f59e0b;
}

.meta-item.submitted {
  background: #d1fae5;
  color: #065f46;
}

.meta-item.submitted i {
  color: #10b981;
}

.detail-description {
  font-size: 1.05rem;
  color: #475569;
  line-height: 1.8;
}

.detail-description p {
  margin-bottom: 1rem;
}

/* ========================================
   MOBILE RESPONSIVE
   ======================================== */

@media (max-width: 991.98px) {
  .stream-container {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .upcoming-sidebar {
    position: static;
    order: 2;
  }

  .stream-main {
    order: 1;
  }
}

@media (max-width: 767.98px) {
  .student-stream-tab {
    padding: 1rem;
  }

  .stream-header {
    margin-bottom: 1.5rem;
  }

  .stream-title h2 {
    font-size: 1.75rem;
  }

  .stream-subtitle {
    font-size: 0.95rem;
  }

  .stream-card-header {
    padding: 1rem;
  }

  .stream-card-body {
    padding: 1rem;
  }

  .stream-item-title {
    font-size: 1.25rem;
  }

  .detail-modal {
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .detail-modal-body {
    padding: 1.5rem;
  }

  .detail-title {
    font-size: 1.5rem;
  }

  .detail-modal-header {
    padding: 1rem 1.5rem;
  }
}

@media (max-width: 479.98px) {
  .stream-card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .upcoming-header h3 {
    font-size: 1.1rem;
  }

  .empty-stream {
    padding: 3rem 1rem;
  }

  .empty-stream-icon {
    font-size: 3.5rem;
  }

  .empty-stream-title {
    font-size: 1.5rem;
  }
}
</style>