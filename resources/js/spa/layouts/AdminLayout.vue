<template>
  <!-- Overlay for mobile sidebar -->
  <div v-if="sidebarOpen" class="sidebar-overlay" @click="closeSidebar"></div>
  
  <!-- Sidebar Navigation -->
  <nav :class="['admin-sidebar', { open: sidebarOpen }]">
    <ul>
      <li><router-link to="/admin/dashboard"><i class="bi bi-speedometer2"></i><span>Dashboard</span></router-link></li>
      <li><router-link to="/admin/calendar"><i class="bi bi-calendar3"></i><span>Calendar</span></router-link></li>
      <li><router-link to="/admin/class-records"><i class="bi bi-file-earmark-spreadsheet"></i><span>Class Records</span></router-link></li>
      <li><router-link to="/admin/courses"><i class="bi bi-journal-bookmark"></i><span>Courses</span></router-link></li>
      <li><router-link to="/admin/academic-years"><i class="bi bi-calendar-range"></i><span>Academic Years</span></router-link></li>
      <li><router-link to="/admin/users"><i class="bi bi-people"></i><span>Users</span></router-link></li>
      <li><router-link to="/admin/auditlogs"><i class="bi bi-clipboard-data"></i><span>Audit Logs</span></router-link></li>
      <li><router-link to="/admin/reports"><i class="bi bi-bar-chart-line-fill"></i><span>Reports</span></router-link></li>
    </ul>
  </nav>

  <!-- Main Content Area -->
  <main class="admin-content">
    <router-view />
  </main>
</template>

<script setup>
import { ref } from 'vue'

const sidebarOpen = ref(false)
function closeSidebar(){ sidebarOpen.value = false }
function toggleSidebar(){ sidebarOpen.value = !sidebarOpen.value }

// expose to page scripts if needed (legacy pages might call window.toggleAdminSidebar)
window.toggleAdminSidebar = toggleSidebar
</script>

<style scoped>
/* Reset everything */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* Sidebar Navigation */
.admin-sidebar {
  position: fixed;
  top: 70px;
  left: 0;
  width: 200px;
  height: calc(100vh - 70px);
  background: white;
  border-right: 1px solid #e5e7eb;
  overflow-y: auto;
  z-index: 1105;
}

.admin-sidebar ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.admin-sidebar li {
  margin: 0;
  padding: 0;
}

.admin-sidebar a {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  color: #374151;
  text-decoration: none;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.2s ease;
}

.admin-sidebar a:hover {
  background: #f8f9fa;
  color: #ab1818;
}

.admin-sidebar a.router-link-active {
  background: #f8f9fa;
  color: #ab1818;
  border-left: 3px solid #ab1818;
  font-weight: 600;
}

.admin-sidebar i {
  font-size: 1.1rem;
  width: 20px;
  text-align: center;
}

/* Main Content */
.admin-content {
  margin-left: 200px;
  margin-top: 70px;
  min-height: calc(100vh - 70px);
  padding: 1.5rem 2rem;
  background: #f8f9fa;
}

/* Sidebar Overlay for Mobile */
.sidebar-overlay {
  display: none;
  position: fixed;
  top: 70px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1100;
}

/* Mobile Styles */
@media (max-width: 767.98px) {
  .admin-sidebar {
    transform: translateX(-100%);
    width: 260px;
    max-width: 80vw;
    transition: transform 0.3s ease;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
    z-index: 1110;
  }

  .admin-sidebar.open {
    transform: translateX(0);
  }

  .sidebar-overlay {
    display: block;
  }

  .admin-content {
    margin-left: 0;
    padding: 1rem;
  }
}

/* Very Small Screens */
@media (max-width: 420px) {
  .admin-content {
    padding: 0.75rem;
  }
}
</style>
