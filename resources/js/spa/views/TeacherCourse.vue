<template>
  <div class="teacher-course-view">
    <!-- Tabs -->
    <div class="course-nav-wrapper mb-3">
      <nav class="course-nav d-flex gap-4 border-bottom pb-2">
        <a v-for="tab in tabs" :key="tab.key" href="#" class="course-link" :class="{ active: activeTab === tab.key }" @click.prevent="switchTab(tab.key)">{{ tab.label }}</a>
      </nav>
    </div>

    <div class="mb-3">
      <div v-if="loading" class="alert alert-info">Loading course...</div>
      <div v-else-if="fetchError" class="alert alert-danger">{{ fetchError }}</div>
      <div v-else-if="!course">
        <div class="text-muted">Course not found.</div>
      </div>
      <div v-else class="mb-3">
        <div class="card p-3 shadow-sm rounded bg-white course-header-card">
          <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center gap-2">
              <h4 class="mb-0 course-title">{{ course.class_name }}</h4>
              <small class="text-muted">(#{{ classId }})</small>
              <span class="ms-2 badge" :class="statusBadgeClass">{{ prettyStatus }}</span>
            </div>
            <div class="text-end mt-2 mt-sm-0">
              <div class="text-muted small">
                Section: <strong>{{ capitalizedSection }}</strong>
                <span v-if="course.units" class="ms-3">Units: <strong>{{ course.units }}</strong></span>
              </div>
              <div class="text-monospace"><strong>{{ course.class_code || '—' }}</strong></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-show="activeTab === 'stream'">
      <StreamTab :classId="classIdValue" :course="course" />
    </div>
    <div v-show="activeTab === 'classwork'">
      <ClassworkTab :classId="classIdValue" :course="course" />
    </div>
    <div v-show="activeTab === 'people'">
      <PeopleTab :classId="classIdValue" :course="course" />
    </div>
    <div v-show="activeTab === 'gradebook'">
      <GradebookTab :classId="classIdValue" />
    </div>
    <div v-show="activeTab === 'classRecords'">
      <ClassRecordsTab :classId="classIdValue" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import StreamTab from '../components/TeacherCourse/StreamTab.vue'
import ClassworkTab from '../components/TeacherCourse/ClassworkTab.vue'
import PeopleTab from '../components/TeacherCourse/PeopleTab.vue'
import GradebookTab from '../components/TeacherCourse/GradebookTab.vue'
import ClassRecordsTab from '../components/TeacherCourse/ClassRecordsTab.vue'
import { API_BASE } from '../services/apiBase'

const route = useRoute()
const router = useRouter()

const classId = computed(() => route.params.courseId || route.query.classId)
const classIdValue = computed(() => (classId.value == null ? '' : String(classId.value)))
const course = ref(null)
const loading = ref(false)
const fetchError = ref('')
const activeTab = ref('stream')
const tabs = [
  { key: 'stream', label: 'Stream' },
  { key: 'classwork', label: 'Classwork' },
  { key: 'people', label: 'People' },
  { key: 'gradebook', label: 'Gradebook' },
  { key: 'classRecords', label: 'Class Record' }
]

const statusBadgeClass = computed(() => {
  if (!course.value) return 'bg-secondary'
  if (course.value.status === 'pending') return 'bg-warning text-dark'
  if (course.value.status === 'archived') return 'bg-secondary'
  return 'bg-success'
})

const prettyStatus = computed(() => {
  if (!course.value || !course.value.status) return ''
  return String(course.value.status).charAt(0).toUpperCase() + String(course.value.status).slice(1)
})

const capitalizedSection = computed(() => {
  if (!course.value || !course.value.section) return '—'
  const s = String(course.value.section).trim()
  return s.length === 1 ? s.toUpperCase() : s
})

function switchTab(tabKey) { activeTab.value = tabKey }

async function fetchCourse(id) {
  fetchError.value = ''
  if (!id) { fetchError.value = 'No course id provided'; course.value = null; return }
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/api/classes/${encodeURIComponent(id)}`)
    if (!res.ok) { fetchError.value = `Failed to fetch course: ${res.status}`; course.value = null; return }
    course.value = await res.json()
  } catch (err) {
    fetchError.value = String(err); course.value = null
  } finally { loading.value = false }
}

onMounted(async () => {
  if (!classId.value) { fetchError.value = 'No class ID provided.'; return }
  await fetchCourse(classId.value)
})

watch(() => route.params.courseId, async (newId) => {
  if (!newId) { fetchError.value = 'No class ID provided.'; course.value = null; return }
  await fetchCourse(newId)
})
</script>

<style scoped>
.course-link { cursor: pointer; text-decoration: none; color: #333; padding: 0.6rem 0.9rem; min-width: 88px; text-align: center; border-radius: 8px; line-height: 1; font-size: 0.95rem; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; }
.course-link.active { background: #fff; font-weight: 600; color: var(--gs-red, #dc3545); border-bottom: 3px solid var(--gs-red, #dc3545); }
.teacher-course-view { padding-top: 0 }
.course-header-card { border: 1px solid #e0e0e0; padding: 15px; border-radius: 8px; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
</style>
