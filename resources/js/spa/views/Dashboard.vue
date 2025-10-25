<template>
  <div class="dashboard">
    <div class="row g-3">
      <!-- Welcome Section -->
      <div class="col-12 col-lg-6">
        <div class="welcome-container">
          <small id="welcomeSubtext">Welcome to</small>
          <h1 id="mainWelcomeTitle">Elevate<span>GS</span></h1>
          <p :class="roleGreetingClass">
            <i :class="roleIcon"></i>
            <span>Welcome back, {{ roleName }} <strong>{{ userName }}</strong>!</span>
          </p>
        </div>
      </div>

      <!-- Calendar Overview -->
      <div class="col-12 col-lg-6">
        <div class="calendar-overview h-100">
          <h5 class="mb-3">Your Calendar Overview</h5>
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3">
              <template v-if="loading">
                <div class="d-flex align-items-center gap-2 text-muted small">
                  <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                  <span>Loading upcoming events…</span>
                </div>
              </template>
              <template v-else-if="events.length === 0">
                <div class="text-muted small">No upcoming events. Once your teachers post items with due dates, they'll appear here.</div>
              </template>
              <template v-else>
                <div v-for="event in events" :key="event.id" class="d-flex align-items-start gap-3 mb-3">
                  <div class="text-center" style="min-width:60px;">
                    <div class="fw-semibold">{{ formatDate(event.startDate) }}</div>
                    <div class="text-muted small">{{ formatTime(event.startDate) }}</div>
                  </div>
                  <div class="grow">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <span :class="getEventBadgeClass(event.type)">{{ event.type || 'event' }}</span>
                      <span class="fw-semibold">{{ event.title || 'Untitled' }}</span>
                    </div>
                    <div v-if="event.className" class="small text-muted">{{ event.className }}</div>
                  </div>
                </div>
              </template>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
              <small class="text-muted">Events are synced from your joined courses.</small>
              <router-link to="/calendar" class="btn btn-sm btn-outline-primary">Open full calendar</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

  <!-- Courses tabs -->
    <div class="courses-container mt-4">
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="joined-tab" data-bs-toggle="tab" data-bs-target="#joined" type="button" role="tab">Joined Courses</button>
        </li>
        <li v-if="isTeacherOrAdmin" class="nav-item" role="presentation">
          <button class="nav-link" id="my-tab" data-bs-toggle="tab" data-bs-target="#mine" type="button" role="tab">My Courses</button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="joined" role="tabpanel" aria-labelledby="joined-tab">
          <div v-if="joinedCourses.length === 0" class="alert alert-info text-center" role="alert">No joined classes yet. Click <i class="bi bi-plus-circle"></i> to join one!</div>
          <div class="row g-3">
            <div v-for="course in joinedCourses" :key="course.class_id" class="col-12 col-sm-6 col-lg-4">
              <div :class="['card','course-card','h-100',{ 'pending-course': course.status === 'pending' }]" :style="course.status === 'pending' ? 'cursor:not-allowed; opacity:0.7;' : ''">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title course-name mb-0"><i class="bi bi-journal-text me-2"></i>{{ course.program || course.class_name }}</h5>
                    <span class="badge section-badge">{{ course.section }}</span>
                  </div>
                  <p v-if="course.course_name || course.subject_code" class="card-text small text-muted mb-2"><i class="bi bi-book me-1"></i>{{ course.course_name || course.subject_code }}</p>
                  <div v-if="course.status === 'pending'" class="alert alert-warning py-2 px-2 mb-2 small"><i class="bi bi-hourglass-split me-1"></i><strong>Pending Approval</strong><div class="small text-muted">This course is awaiting admin approval.</div></div>
                  <button v-if="!isMyCourse(course)" class="btn btn-sm btn-outline-danger leave-course-btn mt-2" @click.prevent="leaveCourse(course)"><i class="bi bi-box-arrow-right me-1"></i>Leave Course</button>
                  <p v-if="course.description" class="card-text small text-muted mb-0">{{ course.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="isTeacherOrAdmin" class="tab-pane fade" id="mine" role="tabpanel" aria-labelledby="my-tab">
          <div v-if="myCourses.length === 0" class="alert alert-info text-center" role="alert">No classes created yet. Click <i class="bi bi-plus-circle"></i> to create one!</div>
          <div class="row g-3">
            <div v-for="course in myCourses" :key="course.class_id" class="col-12 col-sm-6 col-lg-4">
              <div :class="['card','course-card','h-100',{ 'pending-course': course.status === 'pending' }]" :style="course.status === 'pending' ? 'cursor:not-allowed; opacity:0.7;' : 'cursor:pointer;'" @click="goToTeacherCourse(course)">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title course-name mb-0"><i class="bi bi-journal-text me-2"></i>{{ course.program || course.class_name }}</h5>
                    <span class="badge section-badge">{{ course.section }}</span>
                  </div>
                  <p v-if="course.course_name || course.subject_code" class="card-text small text-muted mb-2"><i class="bi bi-book me-1"></i>{{ course.course_name || course.subject_code }}</p>
                  <div v-if="course.status === 'pending'" class="alert alert-warning py-2 px-2 mb-2 small"><i class="bi bi-hourglass-split me-1"></i><strong>Pending Approval</strong><div class="small text-muted">This course is awaiting admin approval.</div></div>
                  <p v-if="course.class_code" class="card-text mb-1 d-flex align-items-center"><i class="bi bi-upc me-1"></i><span class="me-1">Class Code:</span><code class="me-2">{{ course.class_code }}</code><button type="button" class="btn btn-sm btn-outline-secondary copy-code-btn" @click="copyClassCode(course.class_code)" title="Copy code"><i class="bi bi-clipboard"></i></button></p>
                  <p v-if="course.description" class="card-text small text-muted mb-0">{{ course.description }}</p>
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
import { ref, computed, onMounted, watch } from 'vue'
import { API_BASE } from '../services/apiBase'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(true)
const events = ref([])
const myCourses = ref([])
const joinedCourses = ref([])

// User state from localStorage
const userName = ref(localStorage.getItem('loggedInUserName') || 'Guest')
const userRole = ref(localStorage.getItem('loggedInUserRole') || 'student')
const userId = ref(localStorage.getItem('loggedInUserId'))

function goToTeacherCourse(course) {
  if (course.status === 'pending') return;
  // Named route requires `courseId` param (route: teacher/courses/:courseId)
  router.push({
    name: 'TeacherCourse',
    params: { courseId: String(course.class_id) },
    // keep a query fallback for legacy consumers if needed
    query: { classId: course.class_id }
  });
}

// Computed properties
const isTeacherOrAdmin = computed(() => {
  return ['teacher', 'admin'].includes(userRole.value)
})

const roleName = computed(() => {
  return userRole.value.charAt(0).toUpperCase() + userRole.value.slice(1)
})

const roleIcon = computed(() => {
  return userRole.value === 'student' 
    ? 'bi bi-mortarboard-fill me-1' 
    : 'bi bi-person-workspace me-1'
})

const roleGreetingClass = computed(() => {
  const baseClasses = 'mt-2 mb-0'
  return userRole.value === 'student'
    ? `text-primary ${baseClasses}`
    : `text-success ${baseClasses}`
})

// Methods
function formatDate(date) {
  if (!(date instanceof Date)) {
    date = new Date(date)
  }
  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function formatTime(date) {
  if (!(date instanceof Date)) {
    date = new Date(date)
  }
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

function getEventBadgeClass(type) {
  const baseClass = 'badge text-uppercase'
  switch (type) {
    case 'due':
      return `${baseClass} bg-danger-subtle text-danger`
    case 'quiz':
      return `${baseClass} bg-warning-subtle text-warning-emphasis`
    default:
      return `${baseClass} bg-primary-subtle text-primary-emphasis`
  }
}

function isMyCourse(course) {
  return course.user_id === userId.value
}

async function copyClassCode(code) {
  try {
    await navigator.clipboard.writeText(code)
    // TODO: Add visual feedback
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

async function leaveCourse(course) {
  if (!confirm(`Are you sure you want to leave "${course.program || course.class_name}"?\nThis action cannot be undone.`)) {
    return
  }

  try {
  const response = await fetch(`${API_BASE}/api/joined-classes/${course.class_id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'x-user-id': userId.value
      }
    })
    
    const result = await response.json()
    
    if (response.ok) {
      // Remove course from list
      joinedCourses.value = joinedCourses.value.filter(c => c.class_id !== course.class_id)
      alert(`Successfully left "${course.program || course.class_name}"`)
    } else {
      alert(result.message || 'Failed to leave course')
    }
  } catch (error) {
    console.error('Error leaving course:', error)
    alert('An error occurred while leaving the course')
  }
}

async function loadCalendarEvents() {
  try {
    loading.value = true
    const headers = userId.value ? { 'x-user-id': userId.value } : {}
  const res = await fetch(`${API_BASE}/api/calendar`, { 
      headers 
    })
    
    if (!res.ok) throw new Error('Unable to load calendar')
    
    const data = await res.json()
    events.value = (data.events || [])
      .map(ev => ({
        ...ev,
        startDate: ev.start ? new Date(ev.start) : null
      }))
      .filter(ev => ev.startDate && !Number.isNaN(ev.startDate.getTime()))
      .sort((a, b) => a.startDate - b.startDate)
      .slice(0, 5)
  } catch (err) {
    console.error('Calendar error:', err)
  } finally {
    loading.value = false
  }
}

async function loadCourses() {
  if (!userId.value) return

  try {
    // Load joined courses
  const joinedRes = await fetch(`${API_BASE}/api/joined-classes?userId=${userId.value}`)
    if (joinedRes.ok) {
      joinedCourses.value = await joinedRes.json()
    }

    // Load my courses if teacher/admin
    if (isTeacherOrAdmin.value) {
  const myRes = await fetch(`${API_BASE}/api/classes?userId=${userId.value}`)
      if (myRes.ok) {
        myCourses.value = await myRes.json()
      }
    }
  } catch (err) {
    console.error('Error loading courses:', err)
  }
}

// Redirect to login if not logged in
function checkAuth() {
  if (!userId.value) {
    router.replace('/login')
    return false
  }
  return true
}

onMounted(async () => {
  if (!checkAuth()) return
  await Promise.all([
    loadCalendarEvents(),
    loadCourses()
  ])
})

// Watch for logout (reactive)
watch(userId, (val) => {
  if (!val) router.replace('/login')
})

// Listen for class changes from modals to refresh course lists
if (typeof window !== 'undefined') {
  const refresh = () => loadCourses()
  window.addEventListener('gs:classes-changed', refresh)
  window.addEventListener('courses:updated', refresh)
}
</script>

<style scoped>
.welcome-container {
  padding: 2rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

#welcomeSubtext {
  color: #6b7280;
  font-size: 0.9rem;
}

#mainWelcomeTitle {
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0.5rem 0;
}

#mainWelcomeTitle span {
  color: #ab1818;
}

.course-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  transition: transform 0.2s ease-in-out;
}

.course-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 8px rgba(0,4,8,0.1);
}

.course-card.pending-course {
  border: 2px dashed #ffc107;
  background-color: #fffbf0;
}

.course-card.pending-course:hover {
  transform: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.course-name {
  font-weight: bold;
  margin-bottom: 5px;
  font-size: 1.1em;
}

.section-badge {
  background-color: #007bff;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8em;
}

.leave-course-btn {
  width: 100%;
  font-size: 0.875rem;
}

.leave-course-btn:hover {
  background-color: #dc3545;
  color: white;
  border-color: #dc3545;
}

/* Animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.welcome-container {
  animation: fadeIn 0.5s ease-in;
}
/* Add / Join Dropdown custom style */
.btn-gs-outline {
  border: 1px solid #ab1818;
  color: #ab1818;
  background: #fff;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: background 0.15s, color 0.15s;
}
.btn-gs-outline:hover {
  background: #ab1818;
  color: #fff;
}

/* Mobile tweaks for Dashboard */
@media (max-width: 575.98px) {
  .welcome-container {
    padding: 1rem;
  }
  #mainWelcomeTitle { font-size: 1.6rem; }
  #welcomeSubtext { font-size: 0.85rem }
  .calendar-overview .card .card-body { padding: 0.75rem; }
  .calendar-overview .card-footer { flex-direction: column; gap: 0.5rem; align-items: stretch; }
  .calendar-overview .card-footer .btn { width: 100%; }
  .courses-container h5 { font-size: 1rem; }
  .course-card { padding: 10px; }
  .course-card .course-name { font-size: 1rem }
  .section-badge { padding: 3px 6px; font-size: 0.75rem }
}
</style>
