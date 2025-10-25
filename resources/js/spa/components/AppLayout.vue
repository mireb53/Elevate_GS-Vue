<template>
  <div class="app-layout">
  <CreateClassModal />
  <JoinClassModal />
    <ClassModals />
  <div class="topbar">
      <div class="topbar-left">
        <button id="toggleSidebar" class="btn btn-gs-outline hamburger-btn" @click="toggleSidebar" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <a href="#" class="navbar-brand" @click.prevent="navigate('/dashboard')">
          <div class="logo-square">EGS</div>
          <span class="brand-text">Elevate<span class="brand-highlight">GS</span></span>
        </a>
        <span class="welcome-text">
          <span :class="roleWelcomeClass">
            <i :class="roleIcon"></i>
            Welcome {{ userRole }}
          </span>, 
          <strong>{{ userName }}</strong>
        </span>
      </div>
      <div class="topbar-right">
        <!-- Add/Join Dropdown -->
        <div class="dropdown">
          <button class="btn btn-gs-outline topbar-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-plus-circle"></i>
            <span class="btn-text">Add / Join</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li v-if="userRole === 'teacher'">
              <a class="dropdown-item" href="#" @click.prevent="openCreateClassModal">
                <i class="bi bi-plus-square me-2"></i>
                <span>Create a Class</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" @click.prevent="openJoinClassModal">
                <i class="bi bi-person-add me-2"></i>
                <span>Join a Class</span>
              </a>
            </li>
          </ul>
        </div>
        <!-- Notification Button -->
        <NotificationsBell />
        <!-- Profile Dropdown -->
        <div class="dropdown">
          <button class="btn btn-light topbar-btn profile-btn" @click="toggleProfileDropdown">
            <template v-if="profilePicture">
              <img :src="profilePicture" alt="Profile" class="user-avatar" @error="handleAvatarError">
            </template>
            <template v-else-if="initials">
              <span class="initials-avatar" :style="{ background: initialsColor }">{{ initials }}</span>
            </template>
            <template v-else>
              <i class="bi bi-person-circle"></i>
            </template>
          </button>
          <ul class="dropdown-menu dropdown-menu-start" :class="{ show: showProfileDropdown }" style="right: 0; left: auto;">
            <li><router-link class="dropdown-item" to="/profile"><i class="bi bi-person me-2"></i>Profile</router-link></li>
            <li><router-link class="dropdown-item" to="/offline-files"><i class="bi bi-file-earmark me-2"></i>Offline files</router-link></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" @click.prevent="logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
    <!-- Provide modal containers migrated from legacy fragments -->
  <ClassModals />
  <div v-if="!isAdminRoute" id="sidebarOverlay" :class="['overlay', { show: sidebarVisible }]" @click="closeSidebar"></div>
    <div class="layout">
      <nav id="sidebar" v-if="!isAdminRoute" :class="['sidebar', { show: sidebarVisible }]">
        <div class="sidebar-content">
          <ul class="nav flex-column">
            <li class="nav-item">
              <router-link to="/dashboard" class="nav-link">
                <i class="bi bi-speedometer2"></i>
                <span class="link-text">Dashboard</span>
              </router-link>
            </li>

            <li class="nav-item">
              <router-link :to="userRole === 'admin' ? '/admin/calendar' : '/calendar'" class="nav-link">
                <i class="bi bi-calendar3"></i>
                <span class="link-text">Calendar</span>
              </router-link>
            </li>

            <li class="nav-item" v-if="userRole === 'teacher'">
              <router-link to="/class-records" class="nav-link">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span class="link-text">Class Records</span>
              </router-link>
            </li>
            <li class="nav-item" v-else>
              <div class="nav-link disabled" title="Class Records (teachers only)">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span class="link-text">Class Records</span>
              </div>
            </li>

            <li class="nav-item" id="adminNavItem" v-if="userRole === 'admin'">
              <router-link to="/admin/dashboard" class="nav-link">
                <i class="bi bi-shield-lock"></i>
                <span class="link-text">Admin</span>
              </router-link>
            </li>

            <div class="section-label">Courses</div>
            <!-- Created Courses (Teachers/Admins Only) -->
            <li class="nav-item" id="myCoursesNavItem">
              <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" :href="`#myCoursesCollapse`" aria-expanded="false" :class="{ 'disabled': userRole !== 'teacher' }">
                <i class="bi bi-journal-text me-2"></i>
                <span class="link-text grow">My Courses</span>
                <i class="bi bi-caret-down-fill caret shrink-0"></i>
              </a>
              <div class="collapse" :id="`myCoursesCollapse`" v-show="sidebarReady">
                <ul class="nav flex-column ms-2 mt-1" id="sidebarMyCoursesList">
                  <li v-if="userRole !== 'teacher'" class="nav-item">
                    <span class="nav-link text-muted" style="font-size: 0.85rem;">Teacher only</span>
                  </li>
                  <li v-else-if="displayedCreatedCourses.length === 0" class="nav-item">
                    <span class="nav-link text-muted">No courses created.</span>
                  </li>
                  <li v-else v-for="course in displayedCreatedCourses" :key="course.class_id" class="nav-item">
                    <template v-if="(course.status || '').toLowerCase() === 'active'">
                      <router-link
                        :to="{ name: 'TeacherCourse', params: { courseId: course.class_id } }"
                        class="nav-link course-link"
                        :title="course.class_name"
                        style="background: #f3f4f6; color: #111827; border-radius: 6px; margin-bottom: 2px;"
                      >
                        {{ course.class_name }}
                      </router-link>
                    </template>
                    <template v-else>
                      <span class="nav-link course-link text-muted" :title="course.class_name + ' (Pending approval)'" style="background: #fffbe6; color: #ab1818; border-radius: 6px; margin-bottom: 2px;">
                        {{ course.class_name }}
                        <span class="badge bg-warning text-dark ms-2">Pending</span>
                      </span>
                    </template>
                  </li>
                </ul>
              </div>
            </li>

            <!-- Joined Courses -->
            <li class="nav-item">
              <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" :href="`#joinedCoursesCollapse`" aria-expanded="false">
                <i class="bi bi-journal-bookmark-fill me-2"></i>
                <span class="link-text grow">Joined Courses</span>
                <i class="bi bi-caret-down-fill caret shrink-0"></i>
              </a>
              <div class="collapse" :id="`joinedCoursesCollapse`" v-show="sidebarReady">
                <ul class="nav flex-column ms-2 mt-1" id="sidebarJoinedCoursesList">
                  <li v-if="displayedJoinedCourses.length === 0" class="nav-item">
                    <span class="nav-link text-muted">No joined courses.</span>
                  </li>
                  <li v-else v-for="course in displayedJoinedCourses" :key="course.class_id" class="nav-item">
                    <template v-if="(course.status || '').toLowerCase() === 'active'">
                      <router-link
                        :to="{ name: 'StudentCourse', params: { courseId: course.class_id } }"
                        class="nav-link course-link"
                        :title="course.class_name"
                        style="background: #e6f7ff; color: #111827; border-radius: 6px; margin-bottom: 2px;"
                      >
                        {{ course.class_name }}
                      </router-link>
                    </template>
                    <template v-else>
                      <span class="nav-link course-link text-muted" :title="course.class_name + ' (Not active)'" style="background: #f8f9fa; color: #6c757d; border-radius: 6px; margin-bottom: 2px;">
                        {{ course.class_name }}
                        <span class="badge bg-secondary ms-2">Inactive</span>
                      </span>
                    </template>
                  </li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </nav>
  <main class="main-content" :class="{ 'no-sidebar': isAdminRoute }">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import CreateClassModal from './CreateClassModal.vue'
import JoinClassModal from './JoinClassModal.vue'
import { ref, computed, onMounted, nextTick, watch, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Sidebar from './Sidebar.vue'
import ClassModals from './ClassModals.vue'
import NotificationsBell from './NotificationsBell.vue'
import { API_BASE } from '../services/apiBase'

const sidebarVisible = ref(false)
const showProfileDropdown = ref(false)

// Add/Join Dropdown modal handlers
function openCreateClassModal() {
  const modal = document.getElementById('createClassModal')
  if (modal && window.bootstrap) {
    const bsModal = window.bootstrap.Modal.getOrCreateInstance(modal)
    bsModal.show()
  }
}
function openJoinClassModal() {
  const modal = document.getElementById('joinClassModal')
  if (modal && window.bootstrap) {
    const bsModal = window.bootstrap.Modal.getOrCreateInstance(modal)
    bsModal.show()
  }
}

function toggleProfileDropdown() {
  showProfileDropdown.value = !showProfileDropdown.value
}

// Close profile dropdown when clicking outside (notifications dropdown manages itself)
document.addEventListener('click', (e) => {
  if (!e.target.closest('.dropdown')) {
    showProfileDropdown.value = false
  }
})

const router = useRouter()
const route = useRoute()
const BACKEND_API_BASE_URL = API_BASE

// User state (read from localStorage so changes from login are reflected immediately)
const userName = computed(() => localStorage.getItem('loggedInUserName') || 'Guest')
const userRole = computed(() => localStorage.getItem('loggedInUserRole') || 'student')
  const userId = computed(() => localStorage.getItem('loggedInUserId'))
const profilePicture = ref('')
const createdCourses = ref([])
const joinedCourses = ref([])
const sidebarReady = ref(false)
const displayedCreatedCourses = computed(() => {
  // Teachers see all created; keep pending visible but styled disabled
  if (userRole.value !== 'teacher') return []
  // Only show courses that are not archived
  return (Array.isArray(createdCourses.value) ? createdCourses.value : []).filter(c => (c.status || '').toLowerCase() !== 'archived')
})
const displayedJoinedCourses = computed(() => {
  // Only show joined classes whose class status is active and not archived
  const all = Array.isArray(joinedCourses.value) ? joinedCourses.value : []
  return all.filter(c => {
    const status = (c.status || '').toLowerCase()
    return status === 'active'
  })
})

// Computed properties
const initials = computed(() => {
  const [firstName = '', lastName = ''] = userName.value.split(' ')
  return `${firstName?.[0] || ''}${lastName?.[0] || ''}`.toUpperCase()
})

const initialsColor = computed(() => {
  // Generate unique color based on user name
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
  
  const name = userName.value || 'User';
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  const colorIndex = Math.abs(hash) % colors.length;
  const [color1, color2] = colors[colorIndex];
  return `linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`;
})

const roleIcon = computed(() => {
  return userRole.value === 'student' 
    ? 'bi bi-mortarboard-fill text-primary'
    : 'bi bi-person-workspace text-success'
})

const roleWelcomeClass = computed(() => {
  return userRole.value === 'student' ? 'text-primary' : 'text-success'
})

const isAdminRoute = computed(() => {
  try { return (route && route.path) ? route.path.startsWith('/admin') : false }
  catch(e){ return false }
})

// Methods
function toggleSidebar() {
  try {
    // If we're on an admin route, prefer the admin layout's toggle (legacy exposed on window)
    if (isAdminRoute.value && typeof window.toggleAdminSidebar === 'function') {
      window.toggleAdminSidebar()
      if (import.meta.env && import.meta.env.DEV) console.debug('Admin toggle invoked via topbar hamburger')
      return
    }
  } catch (e) {}
  sidebarVisible.value = !sidebarVisible.value
  if (import.meta.env && import.meta.env.DEV) console.debug('AppLayout toggleSidebar, sidebarVisible=', sidebarVisible.value)
}

function closeSidebar() {
  sidebarVisible.value = false
}

function handleAvatarError(e) {
  e.target.outerHTML = `<span class="initials-avatar">${initials.value}</span>`
}

function logout() {
  localStorage.clear()
  sessionStorage.clear()
  // On explicit user-initiated logout, redirect to the landing page
  router.push('/')
}

async function loadUserProfile() {
  if (!userId.value) return
  try {
    const res = await fetch(`${BACKEND_API_BASE_URL}/api/users/${userId.value}`)
    if (res.ok) {
      const data = await res.json()
      // Priority: google_picture (Gmail profile) → profile_picture (uploaded) → empty (show initials)
      profilePicture.value = data.google_picture || data.profile_picture || ''
    }
  } catch (err) {
    console.error('Error loading profile:', err)
  }
}

async function loadCourses() {
  if (!userId.value) return
  try {
    // Load created courses (for teachers)
    if (userRole.value === 'teacher') {
      const createdRes = await fetch(`${BACKEND_API_BASE_URL}/api/classes?userId=${userId.value}`)
      if (createdRes.ok) {
        createdCourses.value = await createdRes.json()
        console.debug('[Sidebar] loaded createdCourses', createdCourses.value.length, createdCourses.value)
      }
    }

    // Load joined courses (for all users)
    const joinedRes = await fetch(`${BACKEND_API_BASE_URL}/api/joined-classes?userId=${userId.value}`)
    if (joinedRes.ok) {
      joinedCourses.value = await joinedRes.json()
      console.debug('[Sidebar] loaded joinedCourses', joinedCourses.value.length, joinedCourses.value)
    }
  } catch (err) {
    console.error('Error loading courses:', err)
  }
}

// Navigation helper
async function navigate(path) {
  try {
    console.log('Navigating to:', path)
    await router.push({
      path,
      replace: false
    })
    closeSidebar() // Close sidebar after navigation on mobile
  } catch (err) {
    if (err.name === 'NavigationDuplicated') {
      // Ignore duplicate navigation errors
      return
    }
    console.error('Navigation error:', err)
  }
}

// Lifecycle hooks
onMounted(async () => {
  if (!userId.value) {
    router.push('/')
    return
  }

  // Load profile and courses, then update sidebar
  await loadUserProfile()
  await loadCourses()

  // Initialize Bootstrap collapse for course sections and auto-expand if they have content
  await nextTick(() => {
    if (window.bootstrap && window.bootstrap.Collapse) {
      // Initialize collapse elements
      const myCoursesCollapse = document.getElementById('myCoursesCollapse')
      const joinedCoursesCollapse = document.getElementById('joinedCoursesCollapse')

      if (myCoursesCollapse) {
        const myInst = new window.bootstrap.Collapse(myCoursesCollapse, { toggle: false })
        // Auto-expand if there are created courses
        if (displayedCreatedCourses.value.length > 0) {
          myInst.show()
        }
      }
      if (joinedCoursesCollapse) {
        const joinedInst = new window.bootstrap.Collapse(joinedCoursesCollapse, { toggle: false })
        // Auto-expand if there are joined courses
        if (displayedJoinedCourses.value.length > 0) {
          joinedInst.show()
        }
      }
    }
    // Mark sidebar ready so UI can render without flashing
    sidebarReady.value = true
  })
})

// keep body class in sync so other components can adapt (e.g. suppress tab focus glow)
watch(sidebarVisible, (val) => {
  try {
    if (val) document.body.classList.add('sidebar-open')
    else document.body.classList.remove('sidebar-open')
  } catch (e) {}
})

onUnmounted(() => {
  try { document.body.classList.remove('sidebar-open') } catch(e){}
})

// Refresh sidebar course lists when classes are created/joined
if (typeof window !== 'undefined') {
  const handler = () => { loadCourses() }
  window.addEventListener('gs:classes-changed', handler)
  window.addEventListener('courses:updated', handler)
  onUnmounted(() => {
    try {
      window.removeEventListener('gs:classes-changed', handler)
      window.removeEventListener('courses:updated', handler)
    } catch(e){}
  })
}
</script>

<style scoped>
/* ========================================
   APP LAYOUT - CLEAN & SIMPLE
   ======================================== */

.app-layout {
  min-height: 100vh;
  background-color: #f8f9fa;
}

:root {
  --topbar-height: 70px;
}

.layout {
  display: flex;
  flex-wrap: nowrap;
}

/* ========================================
   TOPBAR
   ======================================== */

.topbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: var(--topbar-height);
  background: white;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  z-index: 1100;
  gap: 1rem;
}

.topbar-left,
.topbar-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.topbar-left {
  flex: 1;
  min-width: 0;
}

.topbar-right {
  flex-shrink: 0;
}

/* Hamburger - hidden on desktop */
.hamburger-btn {
  display: none;
  padding: 0.5rem;
  min-width: 40px;
  height: 40px;
  border: none !important;
  box-shadow: none !important;
  outline: none !important;
  -webkit-tap-highlight-color: transparent !important;
}

.hamburger-btn:focus,
.hamburger-btn:active,
.hamburger-btn:hover {
  border: none !important;
  box-shadow: none !important;
  outline: none !important;
  -webkit-tap-highlight-color: transparent !important;
}

.hamburger-btn i {
  font-size: 1.5rem;
}

/* Logo */
.logo-square {
  background: #ab1818;
  color: white;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-weight: 700;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: inherit;
  flex-shrink: 0;
}

.brand-text {
  font-weight: bold;
  font-size: 1.3rem;
  white-space: nowrap;
}

.brand-highlight {
  color: #ab1818;
}

.welcome-text {
  color: #6c757d;
  font-size: 1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Topbar buttons */
.topbar-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  min-height: 44px;
  white-space: nowrap;
}

.topbar-btn i {
  font-size: 1.2rem;
}

.profile-btn {
  padding: 0.5rem !important;
  min-width: 44px;
}

/* User avatars */
.user-avatar,
.initials-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  flex-shrink: 0;
}

.user-avatar {
  object-fit: cover;
}

.initials-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  /* Background color now set dynamically via :style binding */
  color: white;
  font-size: 1rem;
  font-weight: 600;
}

/* Dropdowns */
.dropdown {
  position: relative;
}

.dropdown-menu {
  min-width: 200px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border: 1px solid #ddd;
  margin-top: 0.5rem;
}

.dropdown-menu.show {
  display: block;
}

/* ========================================
   SIDEBAR
   ======================================== */

.sidebar {
  position: fixed;
  top: var(--topbar-height);
  left: 0;
  width: 220px;
  height: calc(100vh - var(--topbar-height));
  background: white;
  border-right: 1px solid #e5e7eb;
  overflow-y: auto;
  z-index: 1050;
  transition: transform 0.3s ease;
}

.sidebar-content {
  padding: 1rem;
}

.section-label {
  color: #6b7280;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 12px 16px 6px;
}

.nav-link {
  color: #374151;
  border-radius: 8px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: background 0.15s ease;
}

.nav-link:hover {
  background: #f3f4f6;
  color: #111827;
  text-decoration: none;
}

.nav-link.active {
  background: #e5e7eb;
  color: #111827;
}

.nav-link .bi {
  font-size: 1rem;
}

.caret {
  transition: transform 0.15s ease;
  margin-left: auto;
}

.nav-link[aria-expanded="true"] .caret {
  transform: rotate(180deg);
}

.course-link {
  font-size: 13px;
  padding: 8px 10px;
}

.nav-link.disabled {
  pointer-events: none;
  opacity: 0.5;
  cursor: not-allowed;
}

/* ========================================
   MAIN CONTENT
   ======================================== */

.main-content {
  flex: 1;
  margin-left: 220px;
  margin-top: 0;
  padding-top: var(--topbar-height);
  padding-left: 0;
  padding-right: 0;
  padding-bottom: 0;
  min-height: 100vh;
  background: #f8f9fa;
}

.main-content.no-sidebar {
  margin-left: 0 !important;
  padding-top: 0 !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
  background: transparent !important;
}

/* Overlay */
.overlay {
  display: none;
  position: fixed;
  top: var(--topbar-height);
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1045;
}

.overlay.show {
  display: block;
}

/* Page transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ========================================
   MOBILE (<768px)
   ======================================== */

@media (max-width: 767.98px) {
  /* Show hamburger */
  .hamburger-btn {
    display: flex !important;
  }
  
  /* Hide welcome text */
  .welcome-text {
    display: none !important;
  }
  
  /* Smaller logo */
  .logo-square {
    width: 36px;
    height: 36px;
  }
  
  /* Hide button text, show icon only */
  .btn-text {
    display: none !important;
  }
  
  .topbar-btn {
    padding: 0.5rem !important;
    min-width: 40px !important;
    gap: 0 !important;
  }
  
  .topbar-btn i {
    margin: 0 !important;
  }
  
  /* Sidebar slides from left */
  .sidebar {
    transform: translateX(-100%);
    width: 75%;
    max-width: 280px;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
  }
  
  .sidebar.show {
    transform: translateX(0);
  }
  
  /* Main content full width */
  .main-content {
    margin-left: 0 !important;
    padding: 1rem;
  }
  
  .main-content.no-sidebar {
    padding-top: 0 !important;
  }
  
  /* Show overlay when sidebar open */
  .overlay {
    display: none;
  }
  
  .overlay.show {
    display: block;
  }
  
  /* Fix dropdown positioning */
  .topbar .dropdown {
    position: static;
  }
  
  .topbar .dropdown-menu {
    position: fixed !important;
    top: 60px !important;
    right: 0.5rem !important;
    left: auto !important;
    z-index: 1110 !important;
  }
}

/* ========================================
   SMALL MOBILE (<480px)
   ======================================== */

@media (max-width: 479.98px) {
  .topbar {
    padding: 0 0.5rem;
  }
  
  /* Hide brand text */
  .brand-text {
    display: none !important;
  }
  
  /* Smaller logo */
  .logo-square {
    width: 32px;
    height: 32px;
    font-size: 0.9rem;
  }
  
  /* Compact buttons */
  .topbar-btn {
    padding: 0.4rem !important;
    min-width: 36px !important;
  }
  
  .user-avatar,
  .initials-avatar {
    width: 30px;
    height: 30px;
  }
  
  /* Wider sidebar */
  .sidebar {
    width: 85%;
  }
}

/* ========================================
   DESKTOP (≥768px)
   ======================================== */

@media (min-width: 768px) {
  .overlay {
    display: none !important;
  }
  
  .sidebar {
    transform: translateX(0) !important;
  }
}

/* Sidebar course link fixes: ensure visibility even if global styles change */
.sidebar .nav-link.course-link {
  display: block !important;
  padding: 8px 10px !important;
  border-radius: 6px !important;
  margin-bottom: 4px !important;
  color: #111827 !important;
  background: transparent !important;
}
.sidebar .nav-link.course-link .badge {
  vertical-align: middle;
}

/* Force nav-link color inside sidebar */
.sidebar .nav-link {
  color: #374151 !important;
}
</style>
