import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ProfileView from '../views/ProfileView.vue';
import Dashboard from '../views/Dashboard.vue';
import AppLayout from '../components/AppLayout.vue';
import CalendarView from '../views/CalendarView.vue';
import AdminDashboard from '../views/AdminDashboard.vue';
import AdminLayout from '../layouts/AdminLayout.vue';
import OfflineFilesView from '../views/OfflineFilesView.vue';
import ClassRecordView from '../views/ClassRecordView.vue';
import LandingPage from '../views/LandingPage.vue';

const routes = [
  {
    path: '/',
    name: 'landing',
    component: LandingPage
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView
  },
  {
    path: '/',
    component: AppLayout,
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: Dashboard
      },
      {
        path: 'profile',
        name: 'profile',
        component: ProfileView
      },
      {
        path: 'teacher/dashboard',
        name: 'teacher-dashboard',
        component: Dashboard,
        meta: { requiresTeacher: true }
      },
      {
        path: 'admin',
        component: AdminLayout,
        meta: { requiresAdmin: true },
        children: [
          { path: 'dashboard', name: 'admin-dashboard', component: AdminDashboard },
          { path: 'calendar', name: 'admin-calendar', component: () => import('../views/AdminCalendar.vue') },
          { path: 'class-records', name: 'admin-class-records', component: () => import('../views/ClassRecordView.vue') },
          { path: 'courses', name: 'admin-courses', component: () => import('../views/AdminCourses.vue').catch(()=>import('../views/AdminDashboard.vue')) },
          { path: 'academic-years', name: 'admin-academic-years', component: () => import('../views/AdminAcademicYears.vue').catch(()=>import('../views/AdminDashboard.vue')) },
          { path: 'users', name: 'admin-users', component: () => import('../views/AdminUsers.vue').catch(()=>import('../views/AdminDashboard.vue')) },
          { path: 'auditlogs', name: 'admin-auditlogs', component: () => import('../views/AdminAuditLogs.vue').catch(()=>import('../views/AdminDashboard.vue')) },
          { path: 'reports', name: 'admin-reports', component: () => import('../views/AdminReports.vue').catch(()=>import('../views/AdminDashboard.vue')) }
        ]
      },
      {
        path: 'calendar',
        name: 'calendar',
        component: CalendarView
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: () => import('../views/NotificationsView.vue')
      },
      {
        path: 'offline-files',
        name: 'offline-files',
        component: OfflineFilesView
      },
      {
        path: 'offline-push-demo',
        name: 'offline-push-demo',
        component: () => import('../views/OfflinePushDemo.vue')
      },
      {
        path: 'class-records',
        name: 'class-records',
        component: ClassRecordView
      },
      // Course-related routes
      {
        path: 'teacher/courses/:courseId',
        name: 'TeacherCourse',
        component: () => import('../views/TeacherCourse.vue'),
        meta: { requiresTeacher: true }
      },
      {
        path: 'courses/:id/midterm-sheet',
        name: 'midterm-sheet',
        component: () => import('../views/MidtermSheetView.vue'),
        meta: { requiresTeacher: true }
      },
      {
        path: 'courses/:id/category-grading',
        name: 'category-grading',
        component: () => import('../views/CategoryGradingView.vue'),
        meta: { requiresTeacher: true },
        props: true
      },
      {
        path: 'courses/:courseId',
        name: 'StudentCourse',
        component: () => import('../views/StudentCourseView.vue'),
        meta: { requiresStudent: true }
      },
      {
        path: 'courses/:courseId/classwork',
        name: 'classwork',
        component: () => import('../views/StudentClassworkView.vue'),
        meta: { requiresStudent: true }
      },
      {
        path: 'courses/:courseId/grades',
        name: 'grades',
        component: () => import('../views/GradesView.vue')
      },
      {
        path: 'courses/:courseId/people',
        name: 'people',
        component: () => import('../views/PeopleView.vue')
      }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
});

// Navigation Guards
router.beforeEach((to, from, next) => {
  const publicPages = ['/', '/login', '/register'];
  const isPublic = publicPages.includes(to.path);
  // Check for session cookie (primary) or localStorage (fallback)
  const hasSession = document.cookie.includes('connect.sid') || document.cookie.includes('session'); // Adjust cookie name based on backend
  const rawId = localStorage.getItem('loggedInUserId');
  const loggedIn = hasSession || (rawId && rawId !== 'null' && rawId !== 'undefined');
  const userRole = (localStorage.getItem('loggedInUserRole') || 'student');

  // If trying to access a public page while logged in
  if (loggedIn && isPublic) {
    return next(userRole === 'teacher' ? '/teacher/dashboard' : '/dashboard');
  }

  // If page requires auth and user isn't logged in -> redirect to landing page
  if (!isPublic && !loggedIn) {
    return next('/');
  }

  // Handle teacher routes
  if (to.meta.requiresTeacher && userRole !== 'teacher') {
    return next('/dashboard');
  }

  // Handle admin routes
  if (to.meta.requiresAdmin && userRole !== 'admin') {
    return next('/dashboard');
  }

  // Handle student routes
  if (to.meta.requiresStudent && userRole !== 'student') {
    return next('/teacher/dashboard');
  }

  // Allow navigation
  next();
});

export default router;
