<template>
  <div class="people-tab-container">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="mt-3 text-muted fw-medium">Loading people...</div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger d-flex align-items-center" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
      <div>{{ error }}</div>
    </div>

    <!-- Content -->
    <div v-else class="people-content">
      <!-- Instructors Section -->
      <section class="people-section instructors-section">
        <div class="section-header">
          <div class="section-title-group">
            <h2 class="section-title">
              <i class="bi bi-person-badge me-2"></i>
              Instructors
            </h2>
            <span class="count-badge count-badge-primary">{{ instructors.length }}</span>
          </div>
        </div>

        <!-- No Instructors -->
        <div v-if="!instructors.length" class="empty-section">
          <i class="bi bi-person-x empty-icon"></i>
          <p class="empty-text">No instructors assigned to this course.</p>
        </div>

        <!-- Instructors Grid -->
        <div v-else class="people-grid">
          <div 
            v-for="ins in instructors" 
            :key="ins.id || ins.email"
            class="person-card instructor-card"
            @click="openProfile(ins)" 
            @keydown.enter.prevent="openProfile(ins)" 
            tabindex="0" 
            role="button"
          >
            <div class="person-card-header">
              <div class="avatar-wrapper avatar-instructor">
                <img 
                  :src="personAvatar(ins)" 
                  :alt="personDisplayName(ins)" 
                  class="person-avatar"
                  @error="(e)=>avatarFallback(e, ins, 64)"
                >
              </div>
              <div class="person-info">
                <h3 class="person-name">{{ personDisplayName(ins) }}</h3>
                <p class="person-email">{{ personDisplayEmail(ins) }}</p>
                <span :class="['role-badge', getRoleBadgeClass(ins.role_in_class)]">
                  <i :class="getRoleIcon(ins.role_in_class)" class="me-1"></i>
                  {{ getRoleLabel(ins.role_in_class) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Students Section -->
      <section class="people-section students-section">
        <div class="section-header">
          <div class="section-title-group">
            <h2 class="section-title">
              <i class="bi bi-people me-2"></i>
              Students
            </h2>
            <span class="count-badge count-badge-secondary">{{ students.length }}</span>
          </div>
        </div>

        <!-- No Students -->
        <div v-if="!students.length" class="empty-section">
          <i class="bi bi-people empty-icon"></i>
          <p class="empty-text">No students enrolled in this course yet.</p>
        </div>

        <!-- Students Grid -->
        <div v-else class="people-grid">
          <div 
            v-for="s in students" 
            :key="s.id || s.email"
            class="person-card student-card"
            @click="openProfile(s)" 
            @keydown.enter.prevent="openProfile(s)" 
            tabindex="0" 
            role="button"
          >
            <div class="person-card-header">
              <div class="avatar-wrapper avatar-student">
                <img 
                  :src="personAvatar(s)" 
                  :alt="personDisplayName(s)" 
                  class="person-avatar"
                  @error="(e)=>avatarFallback(e, s, 64)"
                >
              </div>
              <div class="person-info">
                <h3 class="person-name">{{ personDisplayName(s) }}</h3>
                <p class="person-email">{{ personDisplayEmail(s) }}</p>
              </div>
            </div>

            <!-- Progress Section -->
            <div class="progress-section">
              <div class="progress-header">
                <span class="progress-label">
                  <i class="bi bi-clipboard-check me-1"></i>
                  Course Progress
                </span>
                <span class="progress-value">
                  {{ (progressMap[s.id] && progressMap[s.id].submitted) || 0 }}/{{ progressTotal }}
                </span>
              </div>
              <div class="progress-bar-wrapper">
                <div 
                  :class="['progress-bar-fill', getProgressColorClass(getPct(s.id))]"
                  :style="{ width: getPct(s.id) + '%' }"
                  role="progressbar"
                  :aria-valuenow="getPct(s.id)"
                  aria-valuemin="0"
                  aria-valuemax="100"
                >
                  <span class="progress-percentage">{{ Math.round(getPct(s.id)) }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Empty State (no people at all) -->
      <div v-if="!instructors.length && !students.length" class="global-empty-state">
        <i class="bi bi-people-fill empty-icon-large"></i>
        <h3 class="empty-title">No People Found</h3>
        <p class="empty-description">This course doesn't have any instructors or students yet.</p>
      </div>
    </div>

    <!-- Profile Modal -->
    <teleport to="body">
      <transition name="modal-fade">
        <div v-if="selectedPerson" class="profile-modal-backdrop" @click.self="closeProfile">
          <div class="profile-modal">
            <!-- Modal Header -->
            <div class="modal-header-custom">
              <button class="modal-close-btn" @click="closeProfile" aria-label="Close">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <!-- Modal Content -->
            <div class="modal-content-custom">
              <!-- Avatar Section -->
              <div class="modal-avatar-section">
                <div :class="['modal-avatar-wrapper', isInstructor(selectedPerson) ? 'avatar-instructor' : 'avatar-student']">
                  <img 
                    :src="personAvatar(selectedPerson)" 
                    alt="Profile" 
                    class="modal-avatar"
                    @error="(e)=>avatarFallback(e, selectedPerson, 96)"
                  />
                </div>
                <h2 class="modal-person-name">{{ personDisplayName(selectedPerson) }}</h2>
                <p class="modal-person-email">{{ personDisplayEmail(selectedPerson) }}</p>
                <span 
                  v-if="selectedPerson.role_in_class" 
                  :class="['role-badge', 'role-badge-large', getRoleBadgeClass(selectedPerson.role_in_class)]"
                >
                  <i :class="getRoleIcon(selectedPerson.role_in_class)" class="me-1"></i>
                  {{ getRoleLabel(selectedPerson.role_in_class) }}
                </span>
              </div>

              <!-- Info Section -->
              <div class="modal-info-section">
                <div v-if="!isInstructor(selectedPerson) && progressMap[selectedPerson.id]" class="modal-progress-card">
                  <div class="progress-card-header">
                    <i class="bi bi-graph-up me-2"></i>
                    <span>Course Progress</span>
                  </div>
                  <div class="progress-stats">
                    <div class="stat-item">
                      <span class="stat-label">Completed</span>
                      <span class="stat-value">{{ (progressMap[selectedPerson.id] && progressMap[selectedPerson.id].submitted) || 0 }}</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                      <span class="stat-label">Total</span>
                      <span class="stat-value">{{ progressTotal }}</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                      <span class="stat-label">Percentage</span>
                      <span :class="['stat-value', getProgressColorClass(getPct(selectedPerson.id))]">
                        {{ Math.round(getPct(selectedPerson.id)) }}%
                      </span>
                    </div>
                  </div>
                  <div class="progress-bar-wrapper mt-3">
                    <div 
                      :class="['progress-bar-fill', getProgressColorClass(getPct(selectedPerson.id))]"
                      :style="{ width: getPct(selectedPerson.id) + '%' }"
                    ></div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="modal-actions">
                <button class="btn btn-primary btn-lg w-100 mb-2" @click="messagePerson(selectedPerson)">
                  <i class="bi bi-envelope me-2"></i>
                  Send Message
                </button>
                <button class="btn btn-outline-secondary btn-lg w-100" @click="closeProfile">
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { API_BASE } from '../../services/apiBase'

const props = defineProps({ classId: [String, Number], course: Object });
const route = useRoute();
const instructors = ref([]);
const students = ref([]);
const loading = ref(true);
const error = ref('');
const selectedPerson = ref(null);
const progressMap = ref({});
const progressTotal = ref(0);

onMounted(async () => {
  console.log('PeopleTab: received classId', props.classId);
  const id = props.classId || route.query.classId || route.params.classId;
  if (!id) {
    error.value = 'No class ID provided.';
    loading.value = false;
    return;
  }

  // If parent already provided course object, use it to avoid extra request
  if (props.course && Object.keys(props.course).length) {
    console.log('PeopleTab: using parent course prop', props.course);
    const uniq = (arr) => {
      if (!Array.isArray(arr)) return [];
      const seen = new Set();
      return arr.filter(it => {
        const key = String(it.id ?? it.email ?? JSON.stringify(it));
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    };
    instructors.value = uniq(props.course.instructors || props.course.teachers || []);
    students.value = uniq(props.course.students || []);
    loading.value = false;
    return;
  }

  // Otherwise load using the legacy endpoints used in the template
  await loadPeople();
  loading.value = false;
});

// Expose a loadPeople function similar to legacy behavior and attach a global listener
async function loadPeople() {
  loading.value = true;
  error.value = '';
  const id = props.classId || route.query.classId || route.params.classId;
  if (!id) {
    error.value = 'No class ID provided.';
    loading.value = false;
    return;
  }
  try {
  // people endpoint returns { instructors: [], students: [] }
  const res = await fetch(`${API_BASE}/api/classes/${encodeURIComponent(id)}/people`);
      if (res.ok) {
      const data = await res.json();
      // Deduplicate instructors and students by id/email to avoid duplicates shown in the UI
      const uniq = (arr) => {
        if (!Array.isArray(arr)) return [];
        const seen = new Set();
        return arr.filter(it => {
          const key = String(it.id ?? it.email ?? JSON.stringify(it));
          if (seen.has(key)) return false;
          seen.add(key);
          return true;
        });
      };
      instructors.value = uniq(data.instructors || []);
      students.value = uniq(data.students || []);
    } else {
      error.value = `Failed to fetch people (status ${res.status})`;
    }

    // Try to fetch progress data for students (legacy template requests /progress)
    try {
      const pRes = await fetch(`${API_BASE}/api/classes/${encodeURIComponent(id)}/progress`, { headers: { 'x-user-id': localStorage.getItem('loggedInUserId')||'' } });
      if (pRes.ok) {
        const pData = await pRes.json();
        progressTotal.value = pData.total || 0;
        if (pData.rows && Array.isArray(pData.rows)) {
          progressMap.value = Object.fromEntries(pData.rows.map(r => [String(r.id), r]));
        } else {
          progressMap.value = {};
        }
      }
    } catch(_){
      progressMap.value = {};
    }
  } catch (e) {
    console.error('PeopleTab.loadPeople error', e);
    error.value = 'Error fetching people.';
  }
  loading.value = false;
}

// Listen for the global 'teacher-tab-shown' event so the People list loads when the tab becomes visible
function onTeacherTabShown(evt) {
  try {
    if (evt && evt.detail && evt.detail.tab === 'people') {
      loadPeople();
    }
  } catch (e) { /* ignore */ }
}

// Listen for student join events to refresh people list
function onStudentJoined(evt) {
  try {
    const id = props.classId || route.query.classId || route.params.classId;
    if (id) {
      console.log('PeopleTab: Refreshing due to student-joined event');
      loadPeople();
    }
  } catch (e) { /* ignore */ }
}

if (typeof window !== 'undefined') {
  window.addEventListener('teacher-tab-shown', onTeacherTabShown);
  window.addEventListener('student-joined', onStudentJoined);
  window.addEventListener('people-updated', onStudentJoined); // Generic people update event
}

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('teacher-tab-shown', onTeacherTabShown);
    window.removeEventListener('student-joined', onStudentJoined);
    window.removeEventListener('people-updated', onStudentJoined);
  }
});

function openProfile(person) {
  selectedPerson.value = person;
}
function closeProfile() {
  selectedPerson.value = null;
}
function messagePerson(person) {
  // Minimal action: open default mail client if email exists
  if (person && person.email) {
    window.location.href = `mailto:${encodeURIComponent(person.email)}`;
  } else {
    console.log('No email to message for', person);
  }
}

function getPct(id) {
  try {
    const p = progressMap.value && progressMap.value[String(id)];
    if (!p) return 0;
    const pct = typeof p.percent === 'number' ? Math.max(0, Math.min(100, p.percent)) : 0;
    return pct;
  } catch (e) { return 0; }
}

function personDisplayName(p) {
  if (!p) return 'Unknown';
  if (p.full_name) return p.full_name;
  const f = (p.first_name || '').trim();
  const l = (p.last_name || '').trim();
  if (f || l) return (f + ' ' + l).trim();
  return p.email || 'Unknown';
}

function personDisplayEmail(p) {
  if (!p) return 'No email';
  return p.email || p.email_address || 'No email';
}

function personDisplayRole(p) {
  if (!p) return '—';
  return p.role_in_class || p.role || p.position || '—';
}

function personAvatar(p) {
  if (!p) return '';
  // Priority: google_picture (Gmail) → profile_picture (uploaded) → empty (fallback to initials)
  return p.google_picture || p.profile_picture || p.avatar || '';
}

function svgInitialsDataUrl(name, size = 48) {
  const initials = (name || '').split(' ').map(s => s.charAt(0)).filter(Boolean).slice(0,2).join('').toUpperCase() || 'U';
  
  // Generate unique color based on name
  const colors = [
    ['#667eea', '#764ba2'], // Purple
    ['#f093fb', '#f5576c'], // Pink
    ['#4facfe', '#00f2fe'], // Blue
    ['#43e97b', '#38f9d7'], // Green
    ['#fa709a', '#fee140'], // Orange-Pink
    ['#30cfd0', '#330867'], // Teal-Purple
    ['#a8edea', '#fed6e3'], // Mint-Pink
    ['#ff9a9e', '#fecfef'], // Rose
    ['#ffecd2', '#fcb69f'], // Peach
    ['#ff6e7f', '#bfe9ff'], // Red-Blue
    ['#fbc2eb', '#a6c1ee'], // Lavender
    ['#fdcbf1', '#e6dee9'], // Light Purple
  ];
  
  // Simple hash function to pick a consistent color
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  const colorIndex = Math.abs(hash) % colors.length;
  const [color1, color2] = colors[colorIndex];
  
  const bg = encodeURIComponent(`linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`);
  const fg = 'white';
  const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='${size}' height='${size}'><defs><linearGradient id='g' x1='0%' y1='0%' x2='100%' y2='100%'><stop offset='0%' style='stop-color:${color1}'/><stop offset='100%' style='stop-color:${color2}'/></linearGradient></defs><rect width='100%' height='100%' fill='url(%23g)' rx='${size/2}' ry='${size/2}'/><text x='50%' y='50%' dy='.35em' text-anchor='middle' fill='${fg}' font-family='Arial, sans-serif' font-weight='600' font-size='${Math.floor(size/2)}'>${initials}</text></svg>`;
  return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
}

function avatarFallback(e, person, size=48) {
  try {
    const name = personDisplayName(person);
    e.target.src = svgInitialsDataUrl(name, size);
  } catch(_) {
    e.target.src = svgInitialsDataUrl('', size);
  }
}

// Helper: Get role badge class
function getRoleBadgeClass(role) {
  const r = (role || '').toLowerCase();
  if (r === 'owner') return 'role-badge-owner';
  if (r === 'ta' || r === 'assistant') return 'role-badge-ta';
  if (r === 'instructor' || r === 'teacher') return 'role-badge-instructor';
  return 'role-badge-default';
}

// Helper: Get role icon
function getRoleIcon(role) {
  const r = (role || '').toLowerCase();
  if (r === 'owner') return 'bi bi-star-fill';
  if (r === 'ta' || r === 'assistant') return 'bi bi-mortarboard-fill';
  if (r === 'instructor' || r === 'teacher') return 'bi bi-person-check-fill';
  return 'bi bi-person-fill';
}

// Helper: Get role label
function getRoleLabel(role) {
  if (!role) return 'Member';
  return role.toUpperCase();
}

// Helper: Get progress color class
function getProgressColorClass(percentage) {
  if (percentage >= 75) return 'progress-success';
  if (percentage >= 50) return 'progress-warning';
  if (percentage >= 25) return 'progress-info';
  return 'progress-danger';
}

// Helper: Check if person is instructor
function isInstructor(person) {
  if (!person) return false;
  const role = (person.role_in_class || '').toLowerCase();
  return role === 'owner' || role === 'ta' || role === 'instructor' || role === 'teacher' || role === 'assistant';
}
</script>

<style scoped>
/* ========================================
   PEOPLE TAB - MODERN DESIGN
   ======================================== */

.people-tab-container {
  max-width: 1400px;
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

/* Content */
.people-content {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

/* Section */
.people-section {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.section-header {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e2e8f0;
}

.section-title-group {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.section-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
  display: flex;
  align-items: center;
}

.section-title i {
  color: #667eea;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  height: 32px;
  padding: 0 0.75rem;
  border-radius: 16px;
  font-size: 0.9rem;
  font-weight: 700;
}

.count-badge-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.count-badge-secondary {
  background: #e2e8f0;
  color: #475569;
}

/* Empty Section */
.empty-section {
  text-align: center;
  padding: 3rem 2rem;
}

.empty-icon {
  font-size: 3rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}

.empty-text {
  color: #64748b;
  margin: 0;
}

/* People Grid */
.people-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

/* Person Card */
.person-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

.person-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.25s ease;
}

.person-card:hover::before {
  transform: scaleX(1);
}

.person-card:hover {
  border-color: #667eea;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
  transform: translateY(-4px);
}

.person-card:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
}

/* Instructor Card Variant */
.instructor-card {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
}

/* Card Header */
.person-card-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

/* Avatar */
.avatar-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  padding: 3px;
  flex-shrink: 0;
  position: relative;
}

.avatar-instructor {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.avatar-student {
  background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
}

.person-avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  background: white;
  border: 2px solid white;
}

/* Person Info */
.person-info {
  flex: 1;
  min-width: 0;
}

.person-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.person-email {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0 0 0.75rem 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Role Badge */
.role-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-badge-owner {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #78350f;
}

.role-badge-ta {
  background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
  color: white;
}

.role-badge-instructor {
  background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
  color: white;
}

.role-badge-default {
  background: #e2e8f0;
  color: #475569;
}

.role-badge-large {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}

/* Progress Section */
.progress-section {
  padding-top: 1rem;
  border-top: 2px solid #f1f5f9;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.progress-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  display: flex;
  align-items: center;
}

.progress-label i {
  color: #667eea;
}

.progress-value {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1a202c;
}

/* Progress Bar */
.progress-bar-wrapper {
  height: 12px;
  background: #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 6px;
  transition: width 0.3s ease;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 4px;
}

.progress-percentage {
  font-size: 0.7rem;
  font-weight: 700;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.progress-success {
  background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
}

.progress-warning {
  background: linear-gradient(90deg, #facc15 0%, #eab308 100%);
}

.progress-info {
  background: linear-gradient(90deg, #38bdf8 0%, #0ea5e9 100%);
}

.progress-danger {
  background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

/* Global Empty State */
.global-empty-state {
  text-align: center;
  padding: 5rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.empty-icon-large {
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

/* ========================================
   PROFILE MODAL
   ======================================== */

.profile-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1060;
  padding: 1rem;
}

.profile-modal {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  position: relative;
}

/* Modal Header */
.modal-header-custom {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: flex-end;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.modal-close-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

/* Modal Content */
.modal-content-custom {
  padding: 2rem;
}

/* Modal Avatar Section */
.modal-avatar-section {
  text-align: center;
  margin-top: -60px;
  margin-bottom: 2rem;
}

.modal-avatar-wrapper {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  padding: 4px;
  display: inline-flex;
  margin-bottom: 1rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.modal-avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  background: white;
  border: 3px solid white;
}

.modal-person-name {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
}

.modal-person-email {
  font-size: 1rem;
  color: #64748b;
  margin: 0 0 1rem 0;
}

/* Modal Info Section */
.modal-info-section {
  margin-bottom: 2rem;
}

.modal-progress-card {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
}

.progress-card-header {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.progress-card-header i {
  color: #667eea;
}

.progress-stats {
  display: flex;
  align-items: center;
  justify-content: space-around;
  margin-bottom: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: #e2e8f0;
}

/* Modal Actions */
.modal-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Modal Transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-active .profile-modal,
.modal-fade-leave-active .profile-modal {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-from .profile-modal {
  transform: scale(0.9) translateY(20px);
}

.modal-fade-leave-to .profile-modal {
  transform: scale(0.95) translateY(-10px);
}

/* ========================================
   MOBILE RESPONSIVE
   ======================================== */

@media (max-width: 767.98px) {
  .people-section {
    padding: 1.5rem 1rem;
  }

  .section-title {
    font-size: 1.35rem;
  }

  .people-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .person-card {
    padding: 1.25rem;
  }

  .person-card-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .person-info {
    width: 100%;
  }

  .person-name,
  .person-email {
    white-space: normal;
  }

  .modal-content-custom {
    padding: 1.5rem;
  }

  .modal-person-name {
    font-size: 1.5rem;
  }

  .progress-stats {
    flex-direction: column;
    gap: 1rem;
  }

  .stat-divider {
    width: 100%;
    height: 1px;
  }

  .stat-item {
    width: 100%;
    flex-direction: row;
    justify-content: space-between;
  }
}

@media (max-width: 479.98px) {
  .section-title {
    font-size: 1.2rem;
  }

  .avatar-wrapper {
    width: 56px;
    height: 56px;
  }

  .modal-avatar-section {
    margin-top: -50px;
  }

  .modal-avatar-wrapper {
    width: 80px;
    height: 80px;
  }
}
</style>
