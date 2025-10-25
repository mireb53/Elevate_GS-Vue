<template>
  <div>
    <!-- Class Records Tab Content -->
    <div id="classRecordsContent">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="input-group" style="max-width:360px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input v-model="searchQuery" @input="renderEmptyStateCheck" class="form-control" placeholder="Search students or columns..." />
        </div>
        <div class="d-flex align-items-center gap-2">
          <button id="exportClassRecordsBtn" class="btn btn-sm btn-outline-secondary" @click="exportGradesheet"><i class="bi bi-file-earmark-excel me-1"></i> Export</button>
          <button id="viewExcelBtn" class="btn btn-sm btn-primary" @click="viewExcel"><i class="bi bi-table me-1"></i> View Excel</button>
        </div>
      </div>

      <div id="classRecordsTable">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
          <p class="text-muted mt-2">Loading gradesheet...</p>
        </div>
        <div v-else v-html="tableHtml"></div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';

const props = defineProps({ classId: { type: [String, Number], default: '' } });
const route = useRoute();

const loading = ref(false);
const tableHtml = ref('');
const searchQuery = ref('');

import { API_BASE } from '../../services/apiBase'

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text == null ? '' : String(text);
  return div.innerHTML;
}

function renderGradesheetTable(data) {
  const students = data.students || [];
  const classProgram = data.classProgram || '';

  if (!students.length) {
    return '<div class="text-center py-5 text-muted"><i class="bi bi-inbox display-4 d-block mb-3"></i><p>No students enrolled yet.</p></div>';
  }

  let html = '<div class="table-responsive"><table class="table table-bordered table-hover align-middle">';
  html += '<thead class="table-light"><tr>';
  html += '<th style="width: 50px;" class="text-center">#</th>';
  html += '<th style="min-width: 200px;">Student Name</th>';
  html += '<th style="min-width: 150px;" class="text-center">Program</th>';
  html += '<th style="min-width: 120px;" class="text-center">Midterm Grade</th>';
  html += '<th style="min-width: 120px;" class="text-center">Final Grade</th>';
  html += '<th style="min-width: 100px;" class="text-center">Remarks</th>';
  html += '</tr></thead><tbody>';

  students.forEach((student, index) => {
    const studentName = `${student.first_name || ''} ${student.last_name || ''}`.trim() || student.email || 'Student';
    const midterm = student.midterm_grade || '—';
    const final = student.final_grade || '—';
    const remarks = student.remarks || '—';

    html += `<tr>
      <td class="text-center">${index + 1}</td>
      <td>${escapeHtml(studentName)}</td>
      <td class="text-center">${escapeHtml(classProgram)}</td>
      <td class="text-center fw-bold">${escapeHtml(String(midterm))}</td>
      <td class="text-center fw-bold">${escapeHtml(String(final))}</td>
      <td class="text-center"><span class="badge ${remarks === 'PASSED' ? 'bg-success' : remarks === 'FAILED' ? 'bg-danger' : 'bg-secondary'}">${escapeHtml(remarks)}</span></td>
    </tr>`;
  });

  html += '</tbody></table></div>';
  html += `<div class="text-muted small mt-2"><i class="bi bi-people-fill me-1"></i>${students.length} student${students.length !== 1 ? 's' : ''} enrolled</div>`;

  return html;
}

async function loadClassRecords() {
  const classId = props.classId || route.params.courseId || route.query.classId;
  if (!classId) {
    tableHtml.value = '<div class="alert alert-warning">No class selected.</div>';
    return;
  }

  loading.value = true;
  tableHtml.value = '';

  try {
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId');
    const res = await fetch(`${API_BASE}/api/classes/${encodeURIComponent(classId)}/gradesheet`, {
      credentials: 'include',
      headers: userId ? { 'x-user-id': userId } : {}
    });

    if (!res.ok) throw new Error('Failed to load gradesheet');

    const data = await res.json();
    tableHtml.value = renderGradesheetTable(data);
  } catch (err) {
    console.error('Error loading class records:', err);
    tableHtml.value = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load gradesheet. Please try again.</div>';
  } finally {
    loading.value = false;
  }
}

async function exportGradesheet() {
  const classId = props.classId || route.params.courseId || route.query.classId;
  if (!classId) return alert('No class selected');
  try {
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId');
    const res = await fetch(`${API_BASE}/api/classes/${encodeURIComponent(classId)}/gradebook/export`, {
      credentials: 'include',
      headers: userId ? { 'x-user-id': userId } : {}
    });
    if (!res.ok) throw new Error('Export failed');
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `gradesheet_class_${classId}.xlsx`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  } catch (err) {
    console.error('Export failed:', err);
    alert('Failed to export gradesheet');
  }
}

async function viewExcel() {
  const classId = props.classId || route.params.courseId || route.query.classId;
  if (!classId) return alert('No class selected');
  try {
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId');
    const res = await fetch(`${API_BASE}/api/classes/${encodeURIComponent(classId)}/gradesheet`, {
      credentials: 'include',
      headers: userId ? { 'x-user-id': userId } : {}
    });
    if (!res.ok) throw new Error('Failed to load gradesheet');
    const data = await res.json();
    // use same modal markup as legacy - create a simple modal element and show
    showExcelViewerModal({ id: classId, name: data.className || '' }, data);
  } catch (err) {
    console.error('View Excel failed:', err);
    alert('Failed to load gradesheet for viewing');
  }
}

function showExcelViewerModal(classInfo, data) {
  let modalEl = document.getElementById('excelViewerModal');
  if (!modalEl) {
    modalEl = document.createElement('div');
    modalEl.id = 'excelViewerModal';
    modalEl.className = 'modal fade';
    modalEl.tabIndex = -1;
    document.body.appendChild(modalEl);
  }

  const students = data.students || [];
  const classProgram = data.classProgram || '';

  modalEl.innerHTML = `
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <div class="header-content-center w-100">
            <h5 class="modal-title mb-1"><i class="bi bi-table me-2"></i>GRADING SHEET</h5>
            <small>${escapeHtml(classInfo.name || '')}</small>
          </div>
          <button type="button" class="btn-close btn-close-white close-btn-absolute" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
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
                ${students.length === 0 ? `
                  <tr>
                    <td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No students enrolled yet</td>
                  </tr>
                ` : students.map((student) => `
                  <tr>
                    <td>${escapeHtml(student.first_name || '')} ${escapeHtml(student.last_name || '')}</td>
                    <td class="text-center">${escapeHtml(classProgram)}</td>
                    <td class="text-center">${escapeHtml(student.midterm_grade || '')}</td>
                    <td class="text-center">${escapeHtml(student.final_grade || '')}</td>
                    <td class="text-center">${escapeHtml(student.remarks || '')}</td>
                  </tr>`).join('')}
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <div class="me-auto"><small class="text-muted"><i class="bi bi-people-fill me-1"></i>${students.length} student${students.length !== 1 ? 's' : ''} enrolled</small></div>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" id="downloadFromModalBtn"><i class="bi bi-download me-1"></i> Download Excel</button>
        </div>
      </div>
    </div>
  `;

  // wire download button
  setTimeout(() => {
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    document.getElementById('downloadFromModalBtn')?.addEventListener('click', () => {
      exportGradesheet();
    });
  }, 50);
}

function renderEmptyStateCheck() {
  // simple filter - not exhaustive
  if (!searchQuery.value) return;
}

function onTeacherTabShown(e) {
  if (!e || !e.detail) return;
  if (e.detail.tab === 'classRecords') {
    loadClassRecords();
  }
}

onMounted(() => {
  // Attempt to load when mounted (so view works via route navigation)
  loadClassRecords();
  window.addEventListener('teacher-tab-shown', onTeacherTabShown);
});

onBeforeUnmount(() => {
  window.removeEventListener('teacher-tab-shown', onTeacherTabShown);
});
</script>

<style scoped>
/* Center the Excel Viewer Modal */
#excelViewerModal.modal {
  display: block;
}

#excelViewerModal.modal.show {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

#excelViewerModal .modal-dialog {
  margin: 1.75rem auto !important;
  display: flex;
  align-items: center;
  min-height: calc(100vh - 3.5rem);
}

#excelViewerModal .modal-dialog.modal-xl {
  max-width: 90% !important;
  width: 90% !important;
}

#excelViewerModal .modal-dialog.modal-dialog-centered {
  display: flex;
  align-items: center;
  min-height: calc(100% - 1rem * 2);
}

/* Improve modal content styling */
#excelViewerModal .modal-content {
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  border: none;
}

#excelViewerModal .modal-header {
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid rgba(255, 255, 255, 0.2);
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  position: relative;
}

#excelViewerModal .header-content-center {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

#excelViewerModal .header-content-center h5 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

#excelViewerModal .header-content-center small {
  display: block;
  margin-top: 0.25rem;
  opacity: 0.9;
}

#excelViewerModal .close-btn-absolute {
  position: absolute !important;
  right: 1.5rem;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
}

#excelViewerModal .modal-body {
  padding: 2rem;
  max-height: calc(100vh - 250px);
  overflow-y: auto;
}

#excelViewerModal .modal-footer {
  padding: 1.25rem 2rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

/* Table styling inside modal */
#excelViewerModal .table {
  margin-bottom: 0;
}

#excelViewerModal .table thead th {
  background: #f8f9fa;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.875rem;
  letter-spacing: 0.5px;
  padding: 1rem;
  border-bottom: 2px solid #dee2e6;
  position: sticky;
  top: 0;
  z-index: 10;
}

#excelViewerModal .table tbody td {
  padding: 0.875rem 1rem;
  vertical-align: middle;
}

#excelViewerModal .table tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.05);
}

/* Smooth scrollbar */
#excelViewerModal .modal-body::-webkit-scrollbar {
  width: 8px;
}

#excelViewerModal .modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

#excelViewerModal .modal-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

#excelViewerModal .modal-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
