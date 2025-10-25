
<template>
  <div class="stream-tab-container">
    <!-- Loading/Error State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-3 text-muted">Loading course stream...</div>
    </div>
    <div v-else-if="error" class="alert alert-danger shadow-sm">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ error }}
    </div>

    <!-- Stream Content -->
    <div v-else class="stream-content">
      <!-- Course Info Card (if not provided by parent) -->
      <div v-if="!props.course && courseInfo" class="course-banner-card mb-4">
        <div class="course-banner-background"></div>
        <div class="course-banner-content">
          <div class="d-flex align-items-start justify-content-between">
            <div class="course-info">
              <h3 class="course-name mb-2">{{ courseInfo.class_name }}</h3>
              <div class="course-meta">
                <span class="meta-item">
                  <i class="bi bi-bookmark-fill me-1"></i>
                  Section: <strong>{{ courseInfo.section || '—' }}</strong>
                </span>
                <span class="meta-item ms-3">
                  <i class="bi bi-key-fill me-1"></i>
                  Code: <strong class="text-monospace">{{ joinCode }}</strong>
                </span>
              </div>
            </div>
            <span class="status-badge" :class="statusBadgeClass">
              <i :class="statusIcon" class="me-1"></i>
              {{ statusText }}
            </span>
          </div>
          
          <!-- Status Message -->
          <div v-if="courseInfo.status === 'pending'" class="status-message warning mt-3">
            <i class="bi bi-hourglass-split me-2"></i>
            This class is pending admin approval. Students cannot join yet.
          </div>
          <div v-if="courseInfo.status === 'archived'" class="status-message secondary mt-3">
            <i class="bi bi-archive-fill me-2"></i>
            This class is archived. Joining disabled.
          </div>
        </div>
      </div>

      <!-- Materials & Activities Section -->
      <div class="materials-section">
        <div class="section-header mb-4">
          <div>
            <h4 class="section-title mb-1">
              <i class="bi bi-newspaper me-2"></i>
              Course Stream
            </h4>
            <p class="section-subtitle mb-0">Recent classwork, assignments, and materials</p>
          </div>
        </div>

        <!-- Materials List -->
        <div v-if="!selectedMaterial" class="materials-grid">
          <div v-if="materials.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="bi bi-inbox"></i>
            </div>
            <h5 class="empty-title">No materials yet</h5>
            <p class="empty-text">No assignments or materials have been posted to this course</p>
          </div>

          <div v-for="mat in materials" :key="mat.id" 
               class="material-card" 
               @click="showMaterialDetail(mat)"
               role="button"
               tabindex="0"
               @keydown.enter="showMaterialDetail(mat)">
            <div class="material-icon" :class="getMaterialIconClass(mat.type)">
              <i :class="getMaterialIcon(mat.type)"></i>
            </div>
            <div class="material-content">
              <h5 class="material-title">{{ mat.title }}</h5>
              <p class="material-type">
                <span class="type-badge" :class="getTypeBadgeClass(mat.type)">
                  {{ mat.type }}
                </span>
              </p>
              <div class="material-meta">
                <span class="meta-date">
                  <i class="bi bi-calendar-event me-1"></i>
                  {{ mat.date }}
                </span>
                <span v-if="mat.dueTime" class="meta-time">
                  <i class="bi bi-clock-fill me-1"></i>
                  {{ mat.dueTime }}
                </span>
              </div>
            </div>
            <div class="material-arrow">
              <i class="bi bi-chevron-right"></i>
            </div>
          </div>
        </div>

        <!-- Material Detail View -->
        <div v-if="selectedMaterial" class="material-detail-card">
          <button class="btn btn-outline-secondary btn-sm mb-4" @click="backToStream">
            <i class="bi bi-arrow-left me-2"></i>
            Back to Stream
          </button>
          
          <div class="detail-header">
            <div class="detail-icon" :class="getMaterialIconClass(selectedMaterial.type)">
              <i :class="getMaterialIcon(selectedMaterial.type)"></i>
            </div>
            <div class="detail-header-content">
              <span class="detail-type-badge" :class="getTypeBadgeClass(selectedMaterial.type)">
                {{ selectedMaterial.type }}
              </span>
              <h3 class="detail-title">{{ selectedMaterial.title }}</h3>
              <div class="detail-meta">
                <span class="meta-item">
                  <i class="bi bi-calendar-event me-1"></i>
                  {{ selectedMaterial.date }}
                </span>
                <span v-if="selectedMaterial.dueTime" class="meta-item ms-3">
                  <i class="bi bi-clock-fill me-1"></i>
                  {{ selectedMaterial.dueTime }}
                </span>
              </div>
            </div>
          </div>

          <div class="detail-content">
            <div v-if="!isEditing">
              <h5 class="mb-3">Description</h5>
              <p>{{ selectedMaterial.content || 'No description provided.' }}</p>
            </div>
            <div v-else class="edit-form">
              <h5 class="mb-3">Edit Material</h5>
              <div class="mb-3">
                <label class="form-label">Title</label>
                <input v-model="editForm.title" type="text" class="form-control" placeholder="Material title">
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea v-model="editForm.content" class="form-control" rows="4" placeholder="Material description"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Due Date (optional)</label>
                <input v-model="editForm.due_at" type="datetime-local" class="form-control">
              </div>
            </div>
          </div>

          <div class="detail-actions">
            <button v-if="!isEditing" class="btn btn-primary" @click="editMaterial">
              <i class="bi bi-pencil-fill me-2"></i>
              Edit
            </button>
            <button v-if="isEditing" class="btn btn-success" @click="saveEdit">
              <i class="bi bi-check-lg me-2"></i>
              Save Changes
            </button>
            <button v-if="isEditing" class="btn btn-outline-secondary" @click="cancelEdit">
              <i class="bi bi-x-lg me-2"></i>
              Cancel
            </button>
            <button v-if="!isEditing" class="btn btn-outline-danger" @click="deleteMaterial">
              <i class="bi bi-trash-fill me-2"></i>
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { API_BASE } from '../../services/apiBase'

const props = defineProps({ classId: [String, Number], course: Object });
const route = useRoute();
const courseInfo = ref(null);
const loading = ref(true);
const error = ref('');
const materials = ref([]);
const selectedMaterial = ref(null);
const isEditing = ref(false);
const editForm = ref({
  title: '',
  type: '',
  content: '',
  due_at: ''
});

const statusBadgeClass = computed(() => {
  if (!courseInfo.value) return '';
  if (courseInfo.value.status === 'pending') return 'badge-warning';
  if (courseInfo.value.status === 'archived') return 'badge-secondary';
  return 'badge-success';
});

const statusIcon = computed(() => {
  if (!courseInfo.value) return '';
  if (courseInfo.value.status === 'pending') return 'bi-hourglass-split';
  if (courseInfo.value.status === 'archived') return 'bi-archive-fill';
  return 'bi-check-circle-fill';
});

const statusText = computed(() => {
  if (!courseInfo.value) return '';
  if (courseInfo.value.status === 'pending') return 'Pending';
  if (courseInfo.value.status === 'archived') return 'Archived';
  return 'Active';
});

const joinCode = computed(() => {
  if (!courseInfo.value) return '—';
  if (courseInfo.value.status && courseInfo.value.status !== 'active') {
    return courseInfo.value.status === 'pending' ? 'Awaiting approval' : '—';
  }
  return courseInfo.value.class_code || '—';
});

function getMaterialIcon(type) {
  const icons = {
    'Assignment': 'bi-file-earmark-text-fill',
    'Quiz': 'bi-question-circle-fill',
    'Material': 'bi-book-fill',
    'Exam': 'bi-clipboard-check-fill'
  };
  return icons[type] || 'bi-file-earmark-fill';
}

function getMaterialIconClass(type) {
  const classes = {
    'Assignment': 'icon-assignment',
    'Quiz': 'icon-quiz',
    'Material': 'icon-material',
    'Exam': 'icon-exam'
  };
  return classes[type] || 'icon-default';
}

function getTypeBadgeClass(type) {
  const classes = {
    'Assignment': 'badge-assignment',
    'Quiz': 'badge-quiz',
    'Material': 'badge-material',
    'Exam': 'badge-exam'
  };
  return classes[type] || 'badge-default';
}

function showMaterialDetail(material) {
  selectedMaterial.value = material;
}

function backToStream() {
  selectedMaterial.value = null;
  isEditing.value = false;
}

async function editMaterial() {
  if (!selectedMaterial.value) return;
  
  // Populate edit form with current material data
  editForm.value = {
    title: selectedMaterial.value.title,
    content: selectedMaterial.value.content,
    due_at: selectedMaterial.value.date
  };
  
  isEditing.value = true;
}

async function saveEdit() {
  if (!selectedMaterial.value) return;
  
  const confirmed = confirm(`Save changes to "${editForm.value.title}"?`);
  if (!confirmed) return;
  
  try {
    const userId = localStorage.getItem('loggedInUserId') || localStorage.getItem('userId');
    
    const response = await fetch(`${API_BASE}/api/classwork/${selectedMaterial.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'x-user-id': userId || '1'
      },
      body: JSON.stringify({
        title: editForm.value.title,
        description: editForm.value.content,
        due_at: editForm.value.due_at
      })
    });
    
    if (response.ok) {
      alert('Material updated successfully!');
      
      // Update the material in the list
      const index = materials.value.findIndex(m => m.id === selectedMaterial.value.id);
      if (index !== -1) {
        materials.value[index] = {
          ...materials.value[index],
          title: editForm.value.title,
          content: editForm.value.content,
          date: editForm.value.due_at
        };
        selectedMaterial.value = materials.value[index];
      }
      
      // Notify other components that classwork was updated
      window.dispatchEvent(new CustomEvent('classwork:updated', { 
        detail: { classworkId: selectedMaterial.value.id } 
      }));
      
      isEditing.value = false;
    } else {
      const errorData = await response.json().catch(() => ({}));
      alert(`Failed to update material: ${errorData.message || 'Unknown error'}`);
    }
  } catch (err) {
    console.error('Error updating material:', err);
    alert('Error updating material. Please try again.');
  }
}

function cancelEdit() {
  isEditing.value = false;
}

async function deleteMaterial() {
  if (!selectedMaterial.value) return;
  
  const confirmed = confirm(`Are you sure you want to delete "${selectedMaterial.value.title}"?\n\nThis action cannot be undone.`);
  if (!confirmed) return;
  
  try {
    const userId = localStorage.getItem('loggedInUserId') || localStorage.getItem('userId');
    
    const response = await fetch(`${API_BASE}/api/classwork/${selectedMaterial.value.id}`, {
      method: 'DELETE',
      headers: {
        'x-user-id': userId || '1'
      }
    });
    
    if (response.ok) {
      // Store the classwork ID before clearing
      const deletedId = selectedMaterial.value.id;
      
      // Remove from the list immediately
      materials.value = materials.value.filter(m => m.id !== deletedId);
      
      // Clear the detail view to go back to stream
      selectedMaterial.value = null;
      
      // Show success message
      alert('Material deleted successfully!');
      
      // NOTE: Don't dispatch event here - we already updated the local list
      // and don't want to trigger a reload that clears materials temporarily
      
      // Only notify other views/components that might need to update
      // but mark that this component already handled the update
      window.dispatchEvent(new CustomEvent('classwork:deleted', { 
        detail: { 
          classworkId: deletedId,
          source: 'StreamTab' // Mark that we're the source
        } 
      }));
    } else {
      const errorData = await response.json().catch(() => ({}));
      alert(`Failed to delete material: ${errorData.message || 'Unknown error'}`);
    }
  } catch (err) {
    console.error('Error deleting material:', err);
    alert('Error deleting material. Please try again.');
  }
}

async function loadForClass(id) {
  loading.value = true;
  error.value = '';
  courseInfo.value = null;
  selectedMaterial.value = null;
  materials.value = [];

  if (!id) {
    error.value = 'No class ID provided.';
    loading.value = false;
    return;
  }

  // Load course info (from prop or fetch)
  if (props.course && Object.keys(props.course).length) {
    courseInfo.value = props.course;
  } else {
    try {
      // First attempt using API_BASE with encoded id
      const url = `${API_BASE}/api/classes/${encodeURIComponent(id)}`
      let res = await fetch(url)
      if (!res.ok) {
        // Try a relative URL as a fallback (helps in dev proxy scenarios)
        try {
          res = await fetch(`/api/classes/${encodeURIComponent(id)}`)
        } catch (_) {
          // ignore, will be handled below
        }
      }
      if (res && res.ok) {
        courseInfo.value = await res.json();
      } else {
        const status = res ? res.status : 'network-error'
        error.value = `Failed to fetch course info (${status})`;
        console.warn('StreamTab: course fetch failed', { id, urlTried: [url, `/api/classes/${encodeURIComponent(id)}`], status })
      }
    } catch (e) {
      console.error('StreamTab: error fetching course info', e)
      error.value = 'Error fetching course info.';
    }
  }

  // ALWAYS load materials/classwork regardless of whether course prop was provided
  try {
    // Fetch classwork/materials using the same endpoint as ClassworkView
    const apiUrl = `${API_BASE}/api/classes/${encodeURIComponent(id)}/classwork`;
    console.log('Fetching classwork from:', apiUrl);
    
    let cwRes = await fetch(apiUrl);
    if (!cwRes.ok) {
      // Fallback to relative path in case API_BASE is mis-resolved
      try { cwRes = await fetch(`/api/classes/${encodeURIComponent(id)}/classwork`) } catch (_) {}
    }
    if (cwRes.ok) {
      const cw = await cwRes.json();
      console.log('Fetched classwork:', cw); // Debug log
      
      materials.value = Array.isArray(cw) ? cw.map(it => ({
        id: it.id,
        title: it.title || 'Untitled',
        date: it.due_at ? new Date(it.due_at).toLocaleDateString() : (it.created_at ? new Date(it.created_at).toLocaleDateString() : ''),
        dueTime: it.due_at ? new Date(it.due_at).toLocaleTimeString() : '',
        type: it.type || 'Material',
        content: it.description || ''
      })) : [];
      
      console.log('Mapped materials:', materials.value);
    } else {
      console.warn('Failed to fetch classwork, status:', cwRes.status);
      const errorText = await cwRes.text();
      console.warn('Error response:', errorText);
    }
  } catch (err) {
    console.error('Error fetching classwork for stream tab:', err);
  }
  loading.value = false;
}

onMounted(async () => {
  // Prefer explicit prop, then query, then route param (courseId)
  const id = props.classId || route.query.classId || route.params.courseId;
  await loadForClass(id);
});

watch(() => props.classId, async (newId, oldId) => {
  if (!newId || newId === oldId) return;
  await loadForClass(newId);
});

watch(() => route.params.courseId, async (newId, oldId) => {
  if (!newId || newId === oldId) return;
  await loadForClass(newId);
});

// Listen for classwork creation/update/delete events to refresh stream
function onClassworkChanged(event) {
  // Ignore delete events from this component (we already updated locally)
  if (event.type === 'classwork:deleted' && event.detail?.source === 'StreamTab') {
    console.log('StreamTab: Ignoring delete event from self');
    return;
  }
  
  const id = props.classId || route.query.classId || route.params.classId;
  if (id) {
    console.log('StreamTab: Refreshing due to classwork event');
    loadForClass(id);
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

</script>

<style scoped>
/* ========================================
   STREAM TAB - MODERN DESIGN
   ======================================== */

.stream-tab-container {
  max-width: 1200px;
  margin: 0 auto;
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

/* Course Banner Card */
.course-banner-card {
  position: relative;
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.course-banner-background {
  height: 120px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

.course-banner-background::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 400px;
  height: 400px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
}

.course-banner-content {
  padding: 1.5rem;
  margin-top: -40px;
  position: relative;
  z-index: 1;
}

.course-info {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  flex: 1;
  margin-right: 1rem;
}

.course-name {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

.course-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  font-size: 0.95rem;
  color: #64748b;
  margin-top: 0.5rem;
}

.meta-item i {
  color: #667eea;
}

.text-monospace {
  font-family: 'Courier New', monospace;
  background: #f8fafc;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.9rem;
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  white-space: nowrap;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-secondary {
  background: #e2e8f0;
  color: #475569;
}

/* Status Messages */
.status-message {
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
}

.status-message.warning {
  background: #fef3c7;
  color: #92400e;
  border-left: 4px solid #f59e0b;
}

.status-message.secondary {
  background: #e2e8f0;
  color: #475569;
  border-left: 4px solid #64748b;
}

/* Materials Section */
.materials-section {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
  display: flex;
  align-items: center;
  margin: 0;
}

.section-title i {
  color: #667eea;
}

.section-subtitle {
  color: #64748b;
  font-size: 0.95rem;
}

/* Materials Grid */
.materials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

/* Material Card */
.material-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  min-height: 140px;
}

.material-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
  transform: translateY(-2px);
}

.material-card:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

/* Material Icon */
.material-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.icon-assignment {
  background: #dbeafe;
  color: #1e40af;
}

.icon-quiz {
  background: #fce7f3;
  color: #be123c;
}

.icon-material {
  background: #d1fae5;
  color: #065f46;
}

.icon-exam {
  background: #fef3c7;
  color: #92400e;
}

.icon-default {
  background: #e2e8f0;
  color: #475569;
}

/* Material Content */
.material-content {
  flex: 1;
  min-width: 0;
}

.material-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.material-type {
  margin: 0 0 0.5rem 0;
}

.type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-assignment {
  background: #dbeafe;
  color: #1e40af;
}

.badge-quiz {
  background: #fce7f3;
  color: #be123c;
}

.badge-material {
  background: #d1fae5;
  color: #065f46;
}

.badge-exam {
  background: #fef3c7;
  color: #92400e;
}

.badge-default {
  background: #e2e8f0;
  color: #475569;
}

.material-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.85rem;
  color: #64748b;
}

.meta-date,
.meta-time {
  display: flex;
  align-items: center;
}

.material-arrow {
  color: #94a3b8;
  font-size: 1.25rem;
  transition: transform 0.2s ease;
}

.material-card:hover .material-arrow {
  transform: translateX(4px);
  color: #667eea;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.5rem;
}

.empty-text {
  color: #64748b;
  margin-bottom: 1.5rem;
}

/* Material Detail Card */
.material-detail-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.detail-header {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.detail-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  flex-shrink: 0;
}

.detail-header-content {
  flex: 1;
}

.detail-type-badge {
  display: inline-block;
  padding: 0.35rem 1rem;
  border-radius: 16px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.75rem;
}

.detail-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 1rem;
}

.detail-meta {
  display: flex;
  gap: 1.5rem;
  font-size: 1rem;
  color: #64748b;
}

.detail-content {
  padding: 2rem 0;
  font-size: 1.05rem;
  line-height: 1.7;
  color: #475569;
}

.detail-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  padding-top: 2rem;
  border-top: 2px solid #e2e8f0;
}

/* ========================================
   MOBILE RESPONSIVE
   ======================================== */

@media (max-width: 767.98px) {
  .course-banner-background {
    height: 80px;
  }

  .course-banner-content {
    padding: 1rem;
    margin-top: -20px;
  }

  .course-info {
    padding: 1rem;
    margin-right: 0;
    margin-bottom: 1rem;
  }

  .course-name {
    font-size: 1.35rem;
  }

  .course-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .meta-item.ms-3 {
    margin-left: 0 !important;
  }

  .status-badge {
    width: 100%;
    justify-content: center;
  }

  .materials-section {
    padding: 1.5rem 1rem;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .section-header .btn {
    width: 100%;
  }

  .section-title {
    font-size: 1.25rem;
  }

  .materials-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .material-card {
    padding: 1rem;
  }

  .material-icon {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
  }

  .material-title {
    font-size: 1rem;
  }

  .material-meta {
    flex-direction: column;
    gap: 0.25rem;
  }

  .detail-header {
    flex-direction: column;
    gap: 1rem;
  }

  .detail-title {
    font-size: 1.5rem;
  }

  .detail-meta {
    flex-direction: column;
    gap: 0.5rem;
  }

  .detail-actions {
    flex-direction: column;
  }

  .detail-actions .btn {
    width: 100%;
  }
}

@media (max-width: 479.98px) {
  .empty-state {
    padding: 3rem 1rem;
  }

  .empty-icon {
    font-size: 3rem;
  }

  .empty-title {
    font-size: 1.25rem;
  }
}
</style>
