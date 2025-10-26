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
          >
            <div class="person-card-header">
              <div class="avatar-wrapper avatar-instructor">
                <img 
                  v-if="ins.avatarUrl"
                  :src="ins.avatarUrl" 
                  :alt="insDisplayName(ins)" 
                  class="person-avatar"
                  @error="(e)=>avatarFallback(e, ins, 64)"
                >
                <div v-else class="person-avatar initials-fallback" :style="{ background: getInitialsColor(insDisplayName(ins)) }">
                  {{ getInitials(insDisplayName(ins)) }}
                </div>
              </div>
              <div class="person-info">
                <h3 class="person-name">{{ insDisplayName(ins) }}</h3>
                <p class="person-email">{{ ins.email || '' }}</p>
                <span v-if="showRoleBadge(ins.role_in_class)" :class="['role-badge', getRoleBadgeClass(ins.role_in_class)]">
                  <i :class="getRoleIcon(ins.role_in_class)" class="me-1"></i>
                  {{ getRoleLabel(ins.role_in_class) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Classmates Section -->
      <section class="people-section students-section">
        <div class="section-header">
          <div class="section-title-group">
            <h2 class="section-title">
              <i class="bi bi-people me-2"></i>
              Classmates
            </h2>
            <span class="count-badge count-badge-secondary">{{ students.length }}</span>
          </div>
        </div>

        <!-- No Classmates -->
        <div v-if="!students.length" class="empty-section">
          <i class="bi bi-people empty-icon"></i>
          <p class="empty-text">You are the only student in this class.</p>
        </div>

        <!-- Classmates Grid -->
        <div v-else class="people-grid">
          <div 
            v-for="stu in students" 
            :key="stu.id || stu.email"
            class="person-card student-card"
          >
            <div class="person-card-header">
              <div class="avatar-wrapper avatar-student">
                <img 
                  v-if="stu.avatarUrl"
                  :src="stu.avatarUrl" 
                  :alt="studentDisplayName(stu)" 
                  class="person-avatar"
                  @error="(e)=>avatarFallback(e, stu, 64)"
                >
                <div v-else class="person-avatar initials-fallback" :style="{ background: getInitialsColor(studentDisplayName(stu)) }">
                  {{ getInitials(studentDisplayName(stu)) }}
                </div>
              </div>
              <div class="person-info">
                <h3 class="person-name">{{ studentDisplayName(stu) }}</h3>
                <p class="person-email">{{ stu.email || '' }}</p>
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
  </div>
</template>

<script setup>
import { defineProps, ref, watch, onMounted, onUnmounted } from 'vue'
import { API_BASE } from '@/services/apiBase'

const props = defineProps({ classId: [String, Number] })
const currentId = ref(props.classId || '')
watch(() => props.classId, (v) => { if (v) currentId.value = v })

const loading = ref(false)
const instructors = ref([])
const students = ref([])
const error = ref('')

function insDisplayName(ins){
  const fn = ins.first_name || ins.firstName || ''
  const ln = ins.last_name || ins.lastName || ''
  return (fn + ' ' + ln).trim() || ins.email || 'Instructor'
}

function studentDisplayName(stu){
  const fn = stu.first_name || stu.firstName || ''
  const ln = stu.last_name || stu.lastName || ''
  return (fn + ' ' + ln).trim() || stu.email || 'Student'
}

async function loadPeopleForClass(id){
  loading.value = true
  instructors.value = []
  students.value = []
  error.value = ''
  if(!id){ loading.value=false; return }
  try{
  const BACKEND = API_BASE || ''
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
    const res = await fetch(`${BACKEND}/api/classes/${id}/people`, { headers, credentials: 'include' })
    if(!res.ok) throw new Error('Failed to load people')
    const data = await res.json()
  instructors.value = (data.instructors || []).filter(i => (i.role_in_class || '').toLowerCase() === 'owner')
    students.value = data.students || []
    // debug: log instructors shape to verify the patched component is loaded
    try{ console.log('[StudentPeopleTab] instructors loaded', instructors.value.map(i=>({ id:i.id, email:i.email, role: i.role_in_class || null }))) }catch(e){}
    // normalize avatars if any
    instructors.value.forEach(i=>{ i.avatarUrl = resolveAvatarUrl(i) })
    students.value.forEach(s=>{ s.avatarUrl = resolveAvatarUrl(s) })
  }catch(e){
    console.error('loadPeopleForClass', e)
    error.value = 'Error loading people.'
  }
  loading.value = false
}

function resolveAvatarUrl(person){
  if(!person) return null
  // Priority: google_picture (Gmail) → profile_picture (uploaded) → avatar → null (fallback to icon)
  if(person.google_picture && person.google_picture.startsWith('http')) return person.google_picture
  if(person.profile_picture && person.profile_picture.startsWith('http')) return person.profile_picture
  if(person.avatar && person.avatar.startsWith('http')) return person.avatar
  if(person.avatarUrl && person.avatarUrl.startsWith('http')) return person.avatarUrl
  // fallback to backend hosted profile images (relative paths)
  const BACKEND = API_BASE || ''
  if(person.google_picture) return person.google_picture // already a full URL
  if(person.profile_picture) return BACKEND.replace(/\/$/,'') + '/' + person.profile_picture.replace(/^\//,'')
  return null
}

function getInitials(name) {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(n => n.charAt(0))
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function getInitialsColor(name) {
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
  
  let hash = 0;
  for (let i = 0; i < (name || '').length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  const colorIndex = Math.abs(hash) % colors.length;
  const [color1, color2] = colors[colorIndex];
  return `linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`;
}

function avatarFallback(e, person, size=64) {
  try {
    const name = person.first_name || person.email || 'U';
    e.target.outerHTML = `<div class="person-avatar initials-fallback" style="background: ${getInitialsColor(name)}; width: ${size}px; height: ${size}px; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: white; font-weight: 700; font-size: ${size/3}px;">${getInitials(name)}</div>`;
  } catch(_) {
    e.target.style.display = 'none';
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
  if (!role) return '';
  return String(role).toUpperCase();
}

function showRoleBadge(role) {
  return !!(role && String(role).trim());
}

onMounted(()=>{ if(currentId.value) loadPeopleForClass(currentId.value) })
watch(()=> currentId.value, (v,o)=>{ if(v && v!==o) loadPeopleForClass(v) })

// Listen for people updates to auto-refresh
function onPeopleChanged() {
  if (currentId.value) {
    console.log('StudentPeopleTab: Refreshing due to people-updated event');
    loadPeopleForClass(currentId.value);
  }
}

if (typeof window !== 'undefined') {
  window.addEventListener('people-updated', onPeopleChanged);
  window.addEventListener('student-joined', onPeopleChanged);
}

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('people-updated', onPeopleChanged);
    window.removeEventListener('student-joined', onPeopleChanged);
  }
});
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

/* Instructor Card Variant */
.instructor-card {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
}

/* Card Header */
.person-card-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
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

.initials-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1.5rem;
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
}

@media (max-width: 479.98px) {
  .section-title {
    font-size: 1.2rem;
  }

  .avatar-wrapper {
    width: 56px;
    height: 56px;
  }
}
</style>
