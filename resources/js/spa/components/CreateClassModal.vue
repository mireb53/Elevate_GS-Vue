<template>
  <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header">
          <h5 class="modal-title" id="createClassModalLabel">Create a Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form @submit.prevent="submitCreateClass">
          <div class="modal-body">
            <div v-if="message" :class="['alert', messageType]" role="alert">{{ message }}</div>
            
            <!-- Academic Year Warning -->
            <div v-if="!academicYear && !loadingAcademicYear" class="alert alert-warning d-flex align-items-center mb-3">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <div>
                <strong>No active academic year found.</strong><br>
                <small>Please contact the administrator to set an active academic year.</small>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="academicYear">Academic Year <span class="text-muted small">(auto-detected)</span></label>
              <input 
                type="text" 
                class="form-control bg-light" 
                id="academicYear" 
                v-model="academicYear" 
                readonly 
                required
                placeholder="Loading...">
              <div class="form-text">This is automatically set by the administrator</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="schoolYear">School Year <span class="text-muted small">(auto-set)</span></label>
              <input type="text" class="form-control bg-light" id="schoolYear" v-model="schoolYear" readonly required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="section">Section</label>
              <input type="text" class="form-control" id="section" v-model="section" required placeholder="Enter section (e.g. A, B, 1)">
            </div>
            <div class="mb-3">
              <label class="form-label" for="program">Program</label>
              <select class="form-select" id="program" v-model="program" required>
                <option value="">Select Program</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION AND SUPERVISION">MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION AND SUPERVISION</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS">MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE">MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO">MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH">MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN TLE">MASTER OF ARTS IN EDUCATION MAJOR IN TLE</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY">MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH">MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION">MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE AND COUNSELING">MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE AND COUNSELING</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM">MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM</option>
                <option value="MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION">MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label" for="courseType">Course Type</label>
              <select class="form-select" id="courseType" v-model="courseType" required>
                <option value="">Select Course Type</option>
                <option value="Basic Course">Basic Course</option>
                <option value="Major Course">Major Course</option>
                <option value="Thesis Course">Thesis Course</option>
              </select>
            </div>
            <!-- Basic Course Group -->
            <div class="mb-3" v-show="courseType === 'Basic Course'">
              <label class="form-label" for="basicCourse">Basic Course</label>
              <select class="form-select" id="basicCourse" v-model="basicCourse">
                <option value="">Select Basic Course</option>
                <option v-if="availableBasicCourses.length === 0" disabled>Please select a program first</option>
                <option v-for="course in availableBasicCourses" :key="course" :value="course">{{ course }}</option>
              </select>
            </div>
            <!-- Major Course Group -->
            <div class="mb-3" v-show="courseType === 'Major Course'">
              <label class="form-label" for="majorCourse">Major Course</label>
              <select class="form-select" id="majorCourse" v-model="majorCourse">
                <option value="">Select Major Course</option>
                <option v-if="availableMajorCourses.length === 0" disabled>Please select a program first</option>
                <option v-for="course in availableMajorCourses" :key="course" :value="course">{{ course }}</option>
              </select>
            </div>
            <!-- Thesis Course Group -->
            <div class="mb-3" v-show="courseType === 'Thesis Course'">
              <label class="form-label" for="thesisCourse">Thesis Course</label>
              <select class="form-select" id="thesisCourse" v-model="thesisCourse">
                <option value="">Select Thesis Course</option>
                <option v-if="availableThesisCourses.length === 0" disabled>Please select a program first</option>
                <option v-for="course in availableThesisCourses" :key="course" :value="course">{{ course }}</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              class="btn btn-primary" 
              type="submit" 
              :disabled="!academicYear || loadingAcademicYear">
              {{ loadingAcademicYear ? 'Loading...' : 'Create Class' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { API_BASE } from '../services/apiBase'

const academicYear = ref('')
const loadingAcademicYear = ref(false)
const schoolYear = ref('')
const program = ref('')
const courseType = ref('')
const basicCourse = ref('')
const majorCourse = ref('')
const thesisCourse = ref('')
const classCode = ref('')
const section = ref('')
const message = ref('')
const messageType = ref('alert-danger')

const userId = localStorage.getItem('loggedInUserId') || localStorage.getItem('userId')
const router = useRouter()

// Course data organized by program
const courseData = {
  'MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION AND SUPERVISION': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUAS 201 – Educational Leadership and Management – 3',
      'EDUAS 202 – Educational Planning and Development – 3',
      'EDUAS 203 – Dynamics, Organization, Theory, Research and Practice in Educational Administration – 3',
      'EDUAS 204 – Media and Technology Education with AI Integration – 3',
      'EDUAS 205 – Instructional Supervision and Curriculum Development – 3',
      'EDUAS 206 – School Personnel Administration and its Legal Aspects – 3',
      'EDUAS 207 – Current Issues and Problems in Philippine Education – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUMT 201 – Advanced Algebra – 3',
      'EDUMT 202 – Advanced Geometry – 3',
      'EDUMT 203 – Advanced Calculus – 3',
      'EDUMT 204 – Modern Mathematics – 3',
      'EDUMT 205 – Seminar in Mathematics Education – 3',
      'EDUMT 206 – Probability and Statistics – 3',
      'EDUMT 207 – Research Problems in Mathematics Education – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUSC 201 – Research in Science Education – 3',
      'EDUSC 202 – Advanced General Science – 3',
      'EDUSC 203 – Modern Physics – 3',
      'EDUSC 204 – Chemistry of the Environment – 3',
      'EDUSC 205 – Biology and Ecology – 3',
      'EDUSC 206 – Science Curriculum and Instruction – 3',
      'EDUSC 207 – Seminar in Environmental Science – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUFI 201 – Pagpaplanong Pampagtuturo ng Filipino – 3',
      'EDUFI 202 – Pagsasaling Pampanitikan – 3',
      'EDUFI 203 – Barayti at Baryasyon ng Filipino – 3',
      'EDUFI 204 – Pagtuturo ng Panitikan – 3',
      'EDUFI 205 – Seminar sa Pagsasalin at Panitikan – 3',
      'EDUFI 206 – Pamamaraan ng Pagtuturo ng Filipino – 3',
      'EDUFI 207 – Pananaliksik sa Filipino – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUMH 201 – Advanced Methods in Physical Education – 3',
      'EDUMH 202 – Trends and Issues in MAPEH – 3',
      'EDUMH 203 – Advanced Coaching and Officiating – 3',
      'EDUMH 204 – Sports Psychology and Management – 3',
      'EDUMH 205 – Creative Movement and Dance Education – 3',
      'EDUMH 206 – Health Education and Promotion – 3',
      'EDUMH 207 – Research in MAPEH – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN TLE': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUTL 201 – Research in TLE Education – 3',
      'EDUTL 202 – Advanced Methods in TLE – 3',
      'EDUTL 203 – Curriculum Development in TLE – 3',
      'EDUTL 204 – Entrepreneurship and Livelihood Education – 3',
      'EDUTL 205 – Instructional Materials and Technology in TLE – 3',
      'EDUTL 206 – Trends and Issues in TLE – 3',
      'EDUTL 207 – Seminar in TLE Supervision – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUHS 201 – Philippine Historiography – 3',
      'EDUHS 202 – Local and Oral History – 3',
      'EDUHS 203 – Asian Civilization – 3',
      'EDUHS 204 – Western Civilization – 3',
      'EDUHS 205 – History of Ideas – 3',
      'EDUHS 206 – Philippine Institutional History – 3',
      'EDUHS 207 – Seminar in Teaching History – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUEN 201 – Advanced Grammar – 3',
      'EDUEN 202 – Theories of Language Learning and Teaching – 3',
      'EDUEN 203 – Literature for Language Development – 3',
      'EDUEN 204 – Language Assessment and Evaluation – 3',
      'EDUEN 205 – Research in English Language Education – 3',
      'EDUEN 206 – Teaching English with Technology – 3',
      'EDUEN 207 – Seminar in Literature and Culture – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUPR 201 – Child Growth and Development – 3',
      'EDUPR 202 – Early Childhood Curriculum – 3',
      'EDUPR 203 – Play and Learning – 3',
      'EDUPR 204 – Assessment in Early Childhood – 3',
      'EDUPR 205 – Learning Environment and Classroom Management – 3',
      'EDUPR 206 – Family, School and Community Partnership – 3',
      'EDUPR 207 – Research in Early Childhood Education – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE AND COUNSELING': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUGC 201 – Theories of Counseling – 3',
      'EDUGC 202 – Counseling Techniques and Strategies – 3',
      'EDUGC 203 – Psychological Testing and Assessment – 3',
      'EDUGC 204 – Group Dynamics and Counseling – 3',
      'EDUGC 205 – Career Guidance and Counseling – 3',
      'EDUGC 206 – Ethical and Legal Issues in Counseling – 3',
      'EDUGC 207 – Research in Guidance and Counseling – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUAL 201 – Philosophy and Framework of ALS – 3',
      'EDUAL 202 – Program Planning and Management in ALS – 3',
      'EDUAL 203 – Curriculum Development for ALS – 3',
      'EDUAL 204 – Instructional Materials and Technology in ALS – 3',
      'EDUAL 205 – Assessment and Evaluation in ALS – 3',
      'EDUAL 206 – Trends and Issues in Nonformal Education – 3',
      'EDUAL 207 – Research in ALS – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  },
  'MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION': {
    basic: [
      'EDUCN 204 – Statistics in Education – 3',
      'EDUCN 210 – Methods in Educational Research – 3',
      'EDUCN 212 – Foundations of Education – 3'
    ],
    major: [
      'EDUSP 201 – Nature and Needs of Learners with Disabilities – 3',
      'EDUSP 202 – Assessment of Children with Special Needs – 3',
      'EDUSP 203 – Curriculum Adaptation and Modification – 3',
      'EDUSP 204 – Behavior Management – 3',
      'EDUSP 205 – Collaboration and Inclusion Practices – 3',
      'EDUSP 206 – Trends and Issues in Special Education – 3',
      'EDUSP 207 – Research in Special Education – 3'
    ],
    thesis: [
      'EDUC 229 – Thesis Seminar – 3',
      'EDUC 300 – Thesis Writing – 3'
    ]
  }
}

// Computed properties for filtered courses
const availableBasicCourses = computed(() => {
  return program.value && courseData[program.value] ? courseData[program.value].basic : []
})

const availableMajorCourses = computed(() => {
  return program.value && courseData[program.value] ? courseData[program.value].major : []
})

const availableThesisCourses = computed(() => {
  return program.value && courseData[program.value] ? courseData[program.value].thesis : []
})

// Watch for program changes to reset course selections
watch(program, () => {
  basicCourse.value = ''
  majorCourse.value = ''
  thesisCourse.value = ''
})

// Fetch active academic year
async function fetchActiveAcademicYear() {
  loadingAcademicYear.value = true
  try {
    const res = await fetch(`${API_BASE}/api/academic-years/active`, {
      credentials: 'include',
      headers: { 'x-user-id': userId }
    })
    
    if (res.ok) {
      const data = await res.json()
      if (data && data.year_name) {
        academicYear.value = data.year_name
      } else {
        academicYear.value = ''
        message.value = 'No active academic year found. Please contact the administrator.'
        messageType.value = 'alert-warning'
      }
    } else {
      academicYear.value = ''
    }
  } catch (err) {
    console.error('Failed to fetch active academic year:', err)
    academicYear.value = ''
  } finally {
    loadingAcademicYear.value = false
  }
}

onMounted(() => {
  // Fetch active academic year
  fetchActiveAcademicYear()
  
  // Auto-set school year (e.g., 2025-2026)
  const now = new Date()
  const year = now.getFullYear()
  schoolYear.value = `${year}-${year + 1}`
  // Generate a random class code (could be improved)
  classCode.value = Math.random().toString(36).substring(2, 8).toUpperCase()
})

async function submitCreateClass() {
  message.value = ''
  
  // Validate academic year
  if (!academicYear.value) {
    message.value = 'No active academic year found. Please contact the administrator to set an active academic year.'
    messageType.value = 'alert-danger'
    return
  }
  
  if (!program.value || !courseType.value || !section.value) {
    message.value = 'All fields are required.'
    messageType.value = 'alert-danger'
    return
  }
  // Validate course group
  let selectedCourse = ''
  if (courseType.value === 'Basic Course') {
    if (!basicCourse.value) {
      message.value = 'Please select a Basic Course.'
      messageType.value = 'alert-danger'
      return
    }
    selectedCourse = basicCourse.value
  } else if (courseType.value === 'Major Course') {
    if (!majorCourse.value) {
      message.value = 'Please select a Major Course.'
      messageType.value = 'alert-danger'
      return
    }
    selectedCourse = majorCourse.value
  } else if (courseType.value === 'Thesis Course') {
    if (!thesisCourse.value) {
      message.value = 'Please select a Thesis Course.'
      messageType.value = 'alert-danger'
      return
    }
    selectedCourse = thesisCourse.value
  }
  try {
    const res = await fetch(`${API_BASE}/api/classes`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'x-user-id': userId },
      body: JSON.stringify({
        className: selectedCourse,
        section: section.value,
        classCode: classCode.value,
        program: program.value,
        courseType: courseType.value,
        schoolYear: schoolYear.value,
        academicYear: academicYear.value,
        description: '',
        courseName: selectedCourse
      })
    })
    const data = await res.json()
      if (res.ok) {
      message.value = data.message || 'Class created successfully!'
      messageType.value = 'alert-success'
      // Close modal (Bootstrap) if present, fallback to clicking dismiss
      const modalEl = document.getElementById('createClassModal')
      try {
        if (modalEl) {
          if (window.bootstrap && window.bootstrap.Modal) {
            const inst = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl)
            inst.hide()
          } else {
            modalEl.querySelector('[data-bs-dismiss="modal"]')?.click()
          }
        }
      } catch (e) { /* ignore */ }

      // Navigate to the newly created class if active, otherwise to teacher dashboard
      try {
        // Notify global listeners that courses changed
        try { window.dispatchEvent(new CustomEvent('courses:updated', { detail: { action: 'created', classId: data && data.classId ? data.classId : null } })); } catch(e) {}
        if (data && data.classId && data.status === 'active') {
          await router.push({ name: 'TeacherCourse', params: { courseId: String(data.classId) } })
        } else {
          await router.push('/teacher/dashboard')
        }
      } catch (e) { /* ignore navigation errors */ }

      // Reset form fields
      section.value = ''
      program.value = ''
      courseType.value = ''
      basicCourse.value = ''
      majorCourse.value = ''
      thesisCourse.value = ''
      classCode.value = Math.random().toString(36).substring(2, 8).toUpperCase()
    } else {
      message.value = data.message || 'Failed to create class.'
      messageType.value = 'alert-danger'
    }
  } catch (err) {
    message.value = 'Network error.'
    messageType.value = 'alert-danger'
  }
}
</script>

<style scoped>
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
</style>
