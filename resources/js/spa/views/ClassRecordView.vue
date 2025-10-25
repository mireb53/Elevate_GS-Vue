<template>
  <div class="cr-container">
    <!-- Topbar and layout -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0" id="pageTitle">Class Records</h1>
    </div>
    <ul class="nav nav-tabs" id="crTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-courses" data-bs-toggle="tab" data-bs-target="#pane-courses" type="button" role="tab">Courses</button>
      </li>
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white shadow-sm" id="crTabsContent">
      <div class="tab-pane fade show active" id="pane-courses" role="tabpanel" aria-labelledby="tab-courses">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="input-group" style="max-width:360px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input v-model="search" class="form-control" placeholder="Search courses or sections..." />
          </div>
          <div class="d-flex align-items-center gap-2">
            <button @click="loadCourses" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
          </div>
        </div>
        <div v-if="loading" class="col-12 text-muted">Loading...</div>
        <div v-else>
          <div v-if="filteredCoursesCount === 0" class="text-muted text-center py-4">No courses found.</div>
          <div class="row g-3" v-else>
            <div v-for="(c, idx) in filteredCourses" :key="c.id" class="col-12 col-sm-6 col-md-4 col-lg-3">
              <div class="card h-100 shadow-sm course-card">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ idx+1 }}</span>
                    <span :class="courseBadgeClass(c.status)">{{ c.status || 'active' }}</span>
                  </div>
                  <h6 class="card-title mb-1">{{ c.name || 'Untitled' }}</h6>
                  <div class="text-muted small mb-3">{{ c.section || 'No section' }}<span v-if="c.subject"> • {{ c.subject }}</span></div>
                  <div class="mb-3">
                    <div class="small text-muted mb-1"><i class="bi bi-person-video3 me-1"></i>{{ c.teacher && String(c.teacher).trim() ? c.teacher : '—' }}</div>
                    <div class="small text-muted"><i class="bi bi-people me-1"></i>{{ c.studentCount || 0 }} students</div>
                  </div>
                  <div class="mt-auto d-grid gap-2">
                    <button class="btn btn-sm btn-primary" @click="viewExcelGradesheet(c)"><i class="bi bi-table me-1"></i> View Excel</button>
                    <button class="btn btn-sm btn-outline-success" @click="downloadExcelGradesheet(c)"><i class="bi bi-download me-1"></i> Download</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="openRosterModal(c)"><i class="bi bi-people me-1"></i> View Roster</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Files tab removed per request -->
    </div>
    <!-- Excel Gradesheet Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showExcel }" style="display: block;" v-if="showExcel" @click.self="closeExcelModal">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="text-white">
              <h5 class="modal-title mb-1">
                <i class="bi bi-table me-2"></i>GRADING SHEET
              </h5>
              <small>{{ excelClassInfo.name || '' }}<span v-if="excelClassInfo.section"> • {{ excelClassInfo.section }}</span></small>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="closeExcelModal"></button>
          </div>
          <div class="modal-body">
            <!-- Course Information -->
            <div class="mb-4">
              <div class="row mb-2">
                <div class="col-md-6">
                  <strong>Course Code:</strong> {{ excelClassInfo.subject || '___________' }}
                </div>
                <div class="col-md-6">
                  <strong>Course Name:</strong> {{ excelClassInfo.name || '_______________________________' }}
                </div>
              </div>
              <div class="row">
                <div class="col-md-2">
                  <strong>Units:</strong> _____
                </div>
                <div class="col-md-3">
                  <strong>Semester:</strong> ___________
                </div>
                <div class="col-md-3">
                  <strong>Summer:</strong> _________
                </div>
                <div class="col-md-4">
                  <strong>School Year:</strong> ___________
                </div>
              </div>
            </div>
            <!-- Student Grades Table -->
            <div class="table-responsive">
              <table class="table table-bordered mb-4">
                <thead class="table-light">
                  <tr>
                    <th class="text-center">NAME OF STUDENTS</th>
                    <th class="text-center">PROGRAM</th>
                    <th class="text-center">MIDTERM GRADE</th>
                    <th class="text-center">FINAL GRADE</th>
                    <th class="text-center">REMARKS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="excelStudentsCount === 0">
                    <td colspan="5" class="text-center text-muted py-5">
                      <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                      No students enrolled yet
                    </td>
                  </tr>
                  <tr v-for="student in excelStudents" :key="student.email || student.id">
                    <td>{{ student.first_name }} {{ student.last_name }}</td>
                    <td class="text-center">{{ excelClassProgram }}</td>
                    <td class="text-center">{{ student.midterm_grade }}</td>
                    <td class="text-center">{{ student.final_grade }}</td>
                    <td class="text-center">{{ student.remarks }}</td>
                  </tr>
                  <tr v-for="n in Math.max(0, 28 - excelStudentsCount)" :key="'empty-' + n">
                    <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Grading System -->
            <div class="mt-4">
              <h6 class="fw-bold">GRADING SYSTEM:</h6>
              <div class="row mt-3">
                <div class="col-md-4">
                  <table class="table table-sm table-bordered">
                    <thead>
                      <tr class="text-center">
                        <th>Percent</th>
                        <th>Grade</th>
                      </tr>
                    </thead>
                    <tbody class="text-center">
                      <tr><td>100</td><td>1.0</td></tr>
                      <tr><td>99</td><td>1.15</td></tr>
                      <tr><td>98</td><td>1.2</td></tr>
                      <tr><td>97</td><td>1.25</td></tr>
                      <tr><td>96</td><td>1.3</td></tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-4">
                  <table class="table table-sm table-bordered">
                    <thead>
                      <tr class="text-center">
                        <th>Percent</th>
                        <th>Grade</th>
                      </tr>
                    </thead>
                    <tbody class="text-center">
                      <tr><td>95</td><td>1.35</td></tr>
                      <tr><td>94</td><td>1.4</td></tr>
                      <tr><td>93</td><td>1.45</td></tr>
                      <tr><td>92</td><td>1.5</td></tr>
                      <tr><td>91</td><td>1.55</td></tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-4">
                  <table class="table table-sm table-bordered">
                    <thead>
                      <tr class="text-center">
                        <th>Percent</th>
                        <th>Grade</th>
                      </tr>
                    </thead>
                    <tbody class="text-center">
                      <tr><td>90</td><td>1.6</td></tr>
                      <tr><td>89</td><td>1.65</td></tr>
                      <tr><td>88</td><td>1.7</td></tr>
                      <tr><td>87</td><td>1.75</td></tr>
                      <tr><td>86</td><td>1.8</td></tr>
                      <tr><td>85</td><td>1.85</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <!-- Professor Signature -->
            <div class="mt-5 text-center">
              <div>__________________________________</div>
              <div class="mt-1">Professor</div>
            </div>
          </div>
          <div class="modal-footer">
            <div class="me-auto">
              <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                {{ excelStudentsCount }} students enrolled
              </small>
            </div>
            <button type="button" class="btn btn-secondary" @click="closeExcelModal">Close</button>
            <button type="button" class="btn btn-success" @click="downloadExcelGradesheet(excelClassInfo)">
              <i class="bi bi-download me-1"></i> Download Excel
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- Roster Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showRoster }" style="display: block;" v-if="showRoster" @click.self="closeRosterModal">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title fw-semibold">Class roster</h5>
              <div class="text-muted small">{{ rosterSubtitle }}</div>
            </div>
            <button type="button" class="btn-close" @click="closeRosterModal"></button>
          </div>
          <div class="modal-body pt-3">
            <div class="card border-0 bg-light-subtle mb-3">
              <div class="card-body d-flex align-items-center gap-3">
                <div v-if="rosterLoading">
                  <div class="spinner-border spinner-border-sm text-danger" role="status" aria-hidden="true"></div>
                  <span class="small text-muted">Loading instructor…</span>
                </div>
                <div v-else>
                  <div v-if="rosterTeacher">
                    <div class="avatar bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-weight:600;">{{ rosterTeacher.initials }}</div>
                    <div>
                      <div class="fw-semibold">{{ rosterTeacher.fullName }}</div>
                      <div class="small text-muted">{{ rosterTeacher.email }}</div>
                    </div>
                  </div>
                  <div v-else class="small text-muted">No instructor data available.</div>
                </div>
              </div>
            </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0 fw-semibold">Students</h6>
              <span class="text-muted small">{{ rosterStudentsCount }} student<span v-if="rosterStudentsCount !== 1">s</span></span>
            </div>
            <div class="border rounded-3">
              <div v-if="rosterLoading" class="text-center text-muted py-4">Fetching students…</div>
              <div v-else-if="rosterStudentsCount === 0" class="text-center text-muted py-4">No students enrolled yet.</div>
              <div v-else>
                <div class="list-group list-group-flush">
                  <div v-for="(student, idx) in rosterStudents" :key="student.email || idx" class="list-group-item py-3">
                    <div class="d-flex align-items-center gap-3">
                      <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center" style="width:42px;height:42px;font-weight:600;">{{ student.initials }}</div>
                      <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="fw-semibold">{{ student.fullName }}</span>
                          <span class="badge bg-light text-muted border">{{ idx + 1 }}</span>
                        </div>
                        <div class="small text-muted">{{ student.email }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeRosterModal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Upload Form and Record Cards -->
    <div class="row mt-5">
      <div class="col-lg-9 col-md-8">
        <div class="row g-4">
          <div v-for="record in uploadedRecords" :key="record.id" class="col-sm-12 col-md-6 col-lg-4 d-flex">
            <div class="card flex-fill shadow-sm rounded-4 w-100" style="min-height: 270px;">
              <div class="card-header text-white fw-semibold" style="background-color: #ab1818; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                {{ record.course }}
              </div>
              <div class="card-body d-flex flex-column justify-content-between">
                <div>
                  <p class="mb-1"><strong>Class:</strong> {{ record.className }}</p>
                  <p class="mb-1"><strong>Section:</strong> {{ record.section }}</p>
                  <p class="mb-2"><strong>File:</strong> {{ record.fileName }}</p>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <button class="btn btn-sm btn-outline-primary" @click="showExcelPreview(record.id)"><i class="bi bi-eye"></i> Preview</button>
                  <button class="btn btn-sm btn-outline-success" @click="submitRecord(record)"><i class="bi bi-upload"></i> Submit</button>
                </div>
              </div>
              <div class="card-footer bg-light small text-muted d-flex justify-content-between align-items-center">
                <span><i class="bi bi-circle-half"></i> Offline Editable</span>
                <span><i class="bi bi-clock-history"></i> Auto-sync</span>
              </div>
            </div>
          </div>
        </div>
        <!-- Excel Preview Table -->
  <div v-if="previewHeadersCount" class="mt-4">
          <table class="table table-bordered table-sm align-middle text-center">
            <thead class="table-light">
              <tr>
                <th v-for="h in previewHeaders" :key="h">{{ h }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in previewRows" :key="idx">
                <td v-for="(cell, i) in row" :key="i" contenteditable="true">{{ cell }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Sidebar upload card removed as requested -->
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import * as XLSX from 'xlsx';
import { API_BASE } from '../services/apiBase'

// Use central API_BASE so dev proxy / runtime overrides work correctly
const API = API_BASE

const search = ref('');
const loading = ref(false);
const courses = ref([]);

// Roster modal state
const showRoster = ref(false);
const rosterLoading = ref(false);
const rosterTeacher = ref(null);
const rosterStudents = ref([]);
const rosterSubtitle = ref('');

// Excel gradesheet modal state
const showExcel = ref(false);
const excelLoading = ref(false);
const excelClassInfo = ref({});
const excelStudents = ref([]);
const excelClassProgram = ref('');

// Upload form and record cards state
const uploadForm = ref({ course: '', className: '', section: '', file: null });
const fileInput = ref(null);
const uploadedRecords = ref([]);
const uploadedHeaders = ref({});
const uploadedGradeData = ref({});
const previewHeaders = ref([]);
const previewRows = ref([]);

function getLoggedInUserId() {
  return localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId');
}

function getLoggedInUserRole() {
  return (localStorage.getItem('loggedInUserRole') || localStorage.getItem('userRole') || 'student');
}

function courseMatches(c, q) {
  if (!q) return true;
  q = q.toLowerCase();
  return (c.name || '').toLowerCase().includes(q)
    || (c.section || '').toLowerCase().includes(q)
    || (c.subject || '').toLowerCase().includes(q)
    || (c.teacher || '').toLowerCase().includes(q);
}

const filteredCourses = computed(() => courses.value.filter(c => courseMatches(c, search.value)));

// Defensive counts to avoid reading .length on undefined during render
const filteredCoursesCount = computed(() => Array.isArray(filteredCourses.value) ? filteredCourses.value.length : 0);

function courseBadgeClass(status) {
  if (status === 'pending') return 'badge bg-warning-subtle text-warning-emphasis';
  if (status === 'archived') return 'badge bg-secondary-subtle text-secondary';
  return 'badge bg-success-subtle text-success-emphasis';
}

async function loadCourses() {
  loading.value = true;
  try {
    const userId = getLoggedInUserId();
    const authHeaders = userId ? { 'x-user-id': userId } : {};
    const role = getLoggedInUserRole();
    console.debug('[ClassRecord] loadCourses start, userId=', getLoggedInUserId(), 'role=', role, 'API=', API)
    // If admin, use admin courses endpoint to get all courses
    if (role === 'admin') {
      const url = `${API}/api/admin/courses?all=true`;
      console.debug('[ClassRecord] fetching admin courses', url);
      const res = await fetch(url, { credentials: 'include', headers: authHeaders });
      if (!res.ok) {
        console.error('[ClassRecord] admin courses fetch failed', res.status, res.statusText);
        throw new Error('Failed to load admin courses');
      }
      const json = await res.json();
      // backend may return { data: [...] } or an array directly
      const arr = Array.isArray(json) ? json : (Array.isArray(json.data) ? json.data : []);
      console.debug('[ClassRecord] admin courses payload', arr.length);
      courses.value = arr.map(c => ({
        id: c.class_id || c.id || c.classId,
        name: c.class_name || c.name || c.title,
        section: c.section || c.section_name || '',
        subject: c.subject_code || c.subject || c.course_code || '',
        status: c.status || 'active',
        teacher: c.teacher || c.teacher_display || c.teacherName || c.teacherEmail || '',
        studentCount: c.studentCount || c.students || c.enrolled || 0
      }));
    } else {
      // Fetch both owned and joined courses for non-admin users
      const userId = getLoggedInUserId();
      const mineUrl = `${API}/api/classes${userId ? `?userId=${encodeURIComponent(userId)}` : ''}`;
      const joinedUrl = `${API}/api/joined-classes${userId ? `?userId=${encodeURIComponent(userId)}` : ''}`;
      console.debug('[ClassRecord] fetching mine/joined', mineUrl, joinedUrl);
      const [mineRes, joinedRes] = await Promise.all([
        fetch(mineUrl, { credentials: 'include', headers: authHeaders }),
        fetch(joinedUrl, { credentials: 'include', headers: authHeaders })
      ]);
      if (!mineRes.ok) console.error('[ClassRecord] /api/classes failed', mineRes.status, mineRes.statusText);
      if (!joinedRes.ok) console.error('[ClassRecord] /api/joined-classes failed', joinedRes.status, joinedRes.statusText);
      const mine = mineRes.ok ? await mineRes.json() : [];
      const joined = joinedRes.ok ? await joinedRes.json() : [];
      // Normalize
      const norm = (arr, isJoined) => arr.map(c => ({
        id: c.class_id,
        name: c.class_name || c.name,
        section: c.section,
        subject: c.subject_code,
        status: c.status || 'active',
        teacher: c.teacher_display || c.teacher || '',
        studentCount: c.studentCount || c.students || 0
      }));
      courses.value = norm(mine, false).concat(norm(joined, true));
    }
  } catch (e) {
    console.error('[ClassRecord] loadCourses error', e);
    courses.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await loadCourses();
});

// file listing removed (admin files tab is not part of the Vue view)

function initialsFor(name = '') {
  return name.split(' ').filter(Boolean).slice(0,2).map(part => part.charAt(0).toUpperCase()).join('');
}

function closeRosterModal() {
  showRoster.value = false;
  rosterTeacher.value = null;
  rosterStudents.value = [];
  rosterSubtitle.value = '';
}

async function openRosterModal(classInfo) {
  showRoster.value = true;
  rosterLoading.value = true;
  rosterTeacher.value = null;
  rosterStudents.value = [];
  rosterSubtitle.value = [classInfo.section ? `Section ${classInfo.section}` : null, classInfo.subject ? classInfo.subject : null].filter(Boolean).join(' • ');
  try {
    const uid = getLoggedInUserId();
    const rosterUrl = `${API}/api/classes/${encodeURIComponent(classInfo.id)}/people${uid ? `?userId=${encodeURIComponent(uid)}` : ''}`;
    const response = await fetch(rosterUrl, { credentials: 'include' });
    if (!response.ok) throw new Error(`Failed with status ${response.status}`);
    const data = await response.json();
    // Instructor
    const instructor = (Array.isArray(data.instructors) && data.instructors.length ? data.instructors[0] : data.instructor) || null;
    if (instructor) {
      const rawName = [instructor.first_name, instructor.last_name].filter(Boolean).join(' ').trim() || instructor.email || 'Instructor';
      rosterTeacher.value = {
        initials: initialsFor(rawName) || 'T',
        fullName: rawName,
        email: instructor.email || ''
      };
    } else {
      rosterTeacher.value = null;
    }
    // Students
    rosterStudents.value = Array.isArray(data.students) ? data.students.map((student) => {
      const rawName = [student.first_name, student.last_name].filter(Boolean).join(' ').trim() || 'Student';
      return {
        initials: initialsFor(rawName) || 'S',
        fullName: rawName,
        email: student.email || ''
      };
    }) : [];
  } catch (error) {
    rosterTeacher.value = null;
    rosterStudents.value = [];
  } finally {
    rosterLoading.value = false;
  }
}

async function viewExcelGradesheet(classInfo) {
  showExcel.value = true;
  excelLoading.value = true;
  excelClassInfo.value = classInfo;
  excelStudents.value = [];
  excelClassProgram.value = '';
  try {
    const userId = getLoggedInUserId();
    if (!userId) {
      alert('Please login to view gradesheet');
      showExcel.value = false;
      return;
    }
    const res = await fetch(`${API}/api/classes/${classInfo.id}/gradesheet`, {
      headers: { 'x-user-id': userId },
      credentials: 'include'
    });
    if (!res.ok) throw new Error('Failed to load gradesheet');
    const data = await res.json();
    excelStudents.value = data.students || [];
    excelClassProgram.value = data.classProgram || '';
  } catch (error) {
    excelStudents.value = [];
    excelClassProgram.value = '';
    alert('Failed to load gradesheet: ' + error.message);
    showExcel.value = false;
  } finally {
    excelLoading.value = false;
  }
}

function closeExcelModal() {
  showExcel.value = false;
  excelClassInfo.value = {};
  excelStudents.value = [];
  excelClassProgram.value = '';
}

async function downloadExcelGradesheet(classInfo) {
  const userId = getLoggedInUserId();
  if (!userId) {
    alert('Please login to download gradesheet');
    return;
  }
  try {
    const res = await fetch(`${API}/api/classes/${classInfo.id}/gradebook/export`, {
      headers: { 'x-user-id': userId },
      credentials: 'include'
    });
    if (!res.ok) throw new Error('Failed to download gradesheet');
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `gradebook_${classInfo.name || 'class'}_${classInfo.id}.xlsx`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  } catch (error) {
    alert('Failed to download gradesheet: ' + error.message);
  }
}

function onFileChange(e) {
  const file = e.target.files[0];
  uploadForm.value.file = file;
}

function handleUploadFormSubmit() {
  const file = uploadForm.value.file;
  if (!file) return alert('Please select a file.');
  const reader = new FileReader();
  reader.onload = function (event) {
    const data = new Uint8Array(event.target.result);
    const workbook = XLSX.read(data, { type: 'array' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    const headers = jsonData[0];
    const rows = jsonData.slice(1);
    const id = Date.now();
    uploadedHeaders.value[id] = headers;
    uploadedGradeData.value[id] = rows;
    uploadedRecords.value.push({
      id,
      course: uploadForm.value.course,
      className: uploadForm.value.className,
      section: uploadForm.value.section,
      fileName: file.name
    });
    // Reset form
    uploadForm.value = { course: '', className: '', section: '', file: null };
    if (fileInput.value) fileInput.value.value = '';
  };
  reader.readAsArrayBuffer(file);
}

function showExcelPreview(id) {
  previewHeaders.value = uploadedHeaders.value[id] || [];
  previewRows.value = uploadedGradeData.value[id] || [];
}

function submitRecord(record) {
  alert(`Class record for ${record.className} submitted.`);
}
</script>

<style scoped>
/* Center the class record view content and constrain width for readability */
.cr-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1rem;
}

@media (max-width: 767.98px) {
  .cr-container { padding: 0.5rem; }
}
</style>