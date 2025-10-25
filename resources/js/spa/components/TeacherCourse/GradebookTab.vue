<template>
  <div class="gradebook-container">
    <!-- Gradebook Tab Content -->
    <div id="gradebookContent" class="content-centered">
      <div class="row g-3 mb-3 justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="card p-3 h-100">
            <h5 class="mb-2 text-center">Midterm <span :class="['badge ms-2', midtermStatus === 'completed' ? 'bg-success' : 'bg-secondary']">{{ midtermStatus }}</span></h5>
            <div class="d-flex flex-column gap-2 mt-2">
              <button class="btn btn-outline-secondary" @click="viewMidtermExcel" :disabled="loading">
                <i class="bi bi-file-earmark-excel me-1"></i>Download Excel
              </button>
              <button class="btn btn-outline-primary" @click="showMidtermModal = true" :disabled="loading">
                <i class="bi bi-pencil-square me-1"></i>Edit Grades
              </button>
              <button class="btn btn-primary" @click="markMidtermComplete" :disabled="midtermStatus === 'completed' || loading">
                <i class="bi bi-check-circle me-1"></i>Mark Complete
              </button>
            </div>
            <p class="mt-3 mb-0 text-center"><small class="text-muted">Completed: <strong>{{ midtermCompletedDate || '-' }}</strong></small></p>
          </div>
        </div>
        <div class="col-md-6 col-lg-5">
          <div class="card p-3 h-100">
            <h5 class="mb-2 text-center">Final <span :class="['badge ms-2', finalStatus === 'completed' ? 'bg-success' : 'bg-secondary']">{{ finalStatus }}</span></h5>
            <div class="d-flex flex-column gap-2 mt-2">
              <button class="btn btn-outline-secondary" @click="viewFinal" :disabled="loading">
                <i class="bi bi-eye me-1"></i>View Full Sheet
              </button>
              <button class="btn btn-primary" @click="markFinalComplete" :disabled="finalStatus === 'completed' || loading">
                <i class="bi bi-check-circle me-1"></i>Mark Complete
              </button>
              <button class="btn btn-info" @click="downloadGradesheet" :disabled="loading">
                <i class="bi bi-download me-1"></i>Download Gradesheet
              </button>
            </div>
            <p class="mt-3 mb-0 text-center"><small class="text-muted">Completed: <strong>{{ finalCompletedDate || '-' }}</strong></small></p>
          </div>
        </div>
      </div>

      <div class="card p-3 mb-3 mx-auto" style="max-width: 900px;">
        <h6 class="mb-3 text-center">Grade Weights Configuration</h6>
        <div class="row g-3 align-items-center justify-content-center">
          <div class="col-md-5">
            <label class="form-label mb-1">Midterm Weight</label>
            <div class="input-group">
              <input v-model.number="midtermWeightInput" type="number" min="0" max="100" class="form-control text-center" />
              <span class="input-group-text">%</span>
            </div>
            <small class="text-muted">Current: <strong>{{ midtermWeight }}%</strong></small>
          </div>
          <div class="col-md-5">
            <label class="form-label mb-1">Final Weight</label>
            <div class="input-group">
              <input v-model.number="finalWeightInput" type="number" min="0" max="100" class="form-control text-center" />
              <span class="input-group-text">%</span>
            </div>
            <small class="text-muted">Current: <strong>{{ finalWeight }}%</strong></small>
          </div>
          <div class="col-md-12 text-center mt-3">
            <button class="btn btn-primary" @click="updateWeights" :disabled="loading">
              <i class="bi bi-save me-1"></i>Update Weights
            </button>
          </div>
        </div>
      </div>

      <div class="card p-3 mx-auto" style="max-width: 1200px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="mb-0">Final Grades Summary</h6>
          <div>
            <button class="btn btn-sm btn-outline-secondary me-2" @click="showAddColumn = true">
              <i class="bi bi-plus-circle me-1"></i>Add Manual Column
            </button>
            <button class="btn btn-sm btn-outline-secondary" @click="showSettings = true">
              <i class="bi bi-gear me-1"></i>Settings
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="table-light">
              <tr>
                <th class="text-center">#</th>
                <th>Name</th>
                <th>Program</th>
                <th class="text-center">Midterm</th>
                <th class="text-center">Final</th>
                <th class="text-center">Overall</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="text-center text-muted py-4">
                  <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                  Loading grades...
                </td>
              </tr>
              <tr v-else-if="!grades.length">
                <td colspan="6" class="text-center text-muted py-4">
                  <i class="bi bi-inbox me-2"></i>No grades available.
                </td>
              </tr>
              <tr v-else v-for="(grade, idx) in grades" :key="grade.id">
                <td class="text-center">{{ idx + 1 }}</td>
                <td><strong>{{ grade.name }}</strong></td>
                <td>{{ grade.program || '-' }}</td>
                <td class="text-center"><span class="badge bg-info">{{ grade.midterm || '-' }}</span></td>
                <td class="text-center"><span class="badge bg-warning">{{ grade.final || '-' }}</span></td>
                <td class="text-center"><span class="badge bg-success">{{ grade.overall || '-' }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- Midterm Modal -->
  <div class="modal-backdrop-custom" v-if="showMidtermModal" @click.self="showMidtermModal = false">
    <div class="modal-dialog-custom modal-xl-custom">
      <div class="modal-content-custom">
        <div class="modal-header-custom">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-table text-primary"></i>
            <h5 class="modal-title mb-0">Midterm Grading Sheet</h5>
          </div>
          <button type="button" class="btn-close-custom" aria-label="Close" @click="showMidtermModal = false">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body-custom">
          <!-- pass primitive string to avoid reactive Proxy exposure in child -->
          <MidtermSheetView :classId="String(resolvedClassId() || '')" />
        </div>
        <div class="modal-footer-custom">
          <button class="btn btn-secondary" @click="showMidtermModal = false">
            <i class="bi bi-x-circle me-1"></i>Close
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- Upload Preview Modal -->
  <div class="modal show" tabindex="-1" v-if="showUploadPreview" @click.self="showUploadPreview = false">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Upload Preview</h5>
          <button type="button" class="btn-close" aria-label="Close" @click="showUploadPreview = false"></button>
        </div>
        <div class="modal-body">
          <div v-if="uploadPreview && uploadPreview.preview && uploadPreview.preview.length">
            <p class="small text-muted">Preview expires at: {{ uploadPreview.expiresAt || '-' }}</p>
            <div class="list-group">
              <div class="list-group-item" v-for="entry in uploadPreview.preview" :key="entry.studentId">
                <div class="d-flex justify-content-between">
                  <strong>{{ entry.studentName || ('Student ' + entry.studentId) }}</strong>
                  <small class="text-muted">Changes: {{ entry.changes.length }}</small>
                </div>
                <ul class="mb-0 mt-2">
                  <li v-for="(ch, idx) in entry.changes" :key="(ch.columnKey || ch.field) + '_' + idx">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" :id="`chk_${entry.studentId}_${idx}`" v-model="ch.__accepted" />
                      <label class="form-check-label" :for="`chk_${entry.studentId}_${idx}`">
                        <span v-if="ch.field">{{ ch.field }}: </span>
                        <strong v-if="ch.columnKey">{{ ch.label || ch.columnKey }}</strong>
                        <span class="ms-2 text-muted">before: {{ ch.before }}</span>
                        <span class="ms-2">→ after: <strong>{{ ch.after }}</strong></span>
                      </label>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div v-else class="text-center text-muted">No changes to preview.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showUploadPreview = false">Cancel</button>
            <button class="btn btn-primary" @click="commitUploadPreview">Apply Selected Changes</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Add Manual Column Modal -->
  <div class="modal show" tabindex="-1" v-if="showAddColumn" @click.self="showAddColumn = false">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Manual Column</h5>
          <button type="button" class="btn-close" aria-label="Close" @click="showAddColumn = false"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Column Label</label>
            <input v-model="newColLabel" class="form-control" placeholder="e.g. Attendance Week 1" />
          </div>
          <div class="mb-2">
            <label class="form-label">Term</label>
            <select v-model="newColTerm" class="form-select">
              <option value="midterm">Midterm</option>
              <option value="final">Final</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Max Points (optional)</label>
            <input v-model.number="newColMax" type="number" min="0" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showAddColumn = false">Cancel</button>
          <button class="btn btn-primary" @click="addManualColumn">Add Column</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Settings Modal -->
  <div class="modal show" tabindex="-1" v-if="showSettings" @click.self="showSettings = false">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Gradebook Settings</h5>
          <button type="button" class="btn-close" aria-label="Close" @click="showSettings = false"></button>
        </div>
        <div class="modal-body">
          <p>Adjust midterm and final weights. Weights must sum to 100%.</p>
          <div class="d-flex gap-2">
            <div>
              <label class="form-label">Midterm Weight</label>
              <input v-model.number="midtermWeightInput" type="number" min="0" max="100" class="form-control" />
            </div>
            <div>
              <label class="form-label">Final Weight</label>
              <input v-model.number="finalWeightInput" type="number" min="0" max="100" class="form-control" />
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label">Advanced: Edit JSON</label>
            <textarea class="form-control" rows="4" v-model="weightsJson"></textarea>
            <small class="text-muted">Optional: edit JSON like { "midterm": 40, "final": 60 }</small>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showSettings = false">Close</button>
          <button class="btn btn-primary" @click="saveSettings">Save</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({ classId: [String, Number] })
import { useRoute, useRouter } from 'vue-router'
const route = useRoute()
const router = useRouter()
import MidtermSheetView from '../../views/MidtermSheetView.vue'
import GradebookSheet from './GradebookSheet.vue'
import GradebookToolbar from './GradebookToolbar.vue'
import GradebookUpload from './GradebookUpload.vue'
import { API_BASE } from '../../services/apiBase'

const showMidtermModal = ref(false)
const showUploadPreview = ref(false)
const uploadPreview = ref(null)

const loading = ref(false)
const midtermStatus = ref('pending')
const finalStatus = ref('pending')
const midtermCompletedDate = ref('')
const finalCompletedDate = ref('')
const midtermWeight = ref(40)
const finalWeight = ref(60)
const midtermWeightInput = ref(40)
const finalWeightInput = ref(60)
const grades = ref([])
const midtermData = ref(null)
const finalData = ref(null)
const showAddColumn = ref(false)
const newColLabel = ref('')
const newColTerm = ref('midterm')
const newColMax = ref(null)
const showSettings = ref(false)
const weightsJson = ref('')

// Use centralized API_BASE from services

// computed: total number of preview changes
const totalPreviewChanges = computed(() => {
  if (!uploadPreview.value || !Array.isArray(uploadPreview.value.preview)) return 0
  return uploadPreview.value.preview.reduce((acc, e) => acc + (Array.isArray(e.changes) ? e.changes.length : 0), 0)
})

function selectAllPreviewChanges() {
  if (!uploadPreview.value || !Array.isArray(uploadPreview.value.preview)) return
  for (const entry of uploadPreview.value.preview) {
    if (Array.isArray(entry.changes)) {
      for (const ch of entry.changes) ch.__accepted = true
    }
  }
}

function deselectAllPreviewChanges() {
  if (!uploadPreview.value || !Array.isArray(uploadPreview.value.preview)) return
  for (const entry of uploadPreview.value.preview) {
    if (Array.isArray(entry.changes)) {
      for (const ch of entry.changes) ch.__accepted = false
    }
  }
}

function acceptAllForStudent(entry) {
  if (!entry || !Array.isArray(entry.changes)) return
  for (const ch of entry.changes) ch.__accepted = true
}

async function onUploadFile(e) {
  const files = e.target.files;
  if (!files || !files.length) return;
  const file = files[0];
  const cid = resolvedClassId();
  if (!cid) return alert('Invalid class');
  if (!confirm('Upload this edited Excel and preview changes?')) return;
  loading.value = true;
    try {
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch(`${API_BASE}/api/classes/${cid}/gradebook/upload`, {
      method: 'POST',
      credentials: 'include',
      body: fd,
      headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
    });
    if (!res.ok) {
      let b = '';
      try { b = await res.json(); } catch(_) { b = await res.text().catch(()=>'') }
      return alert('Upload failed: ' + (b && b.message ? b.message : res.status));
    }
    const payload = await res.json();
    const token = payload.token;
    const count = payload.previewCount || 0;
    if (!token) return alert('Preview failed: no token returned.');
    if (count === 0) return alert('No changes detected.');

    // Fetch the persisted preview details from the server and show the preview modal
    try {
      const previewRes = await fetch(`${API_BASE}/api/classes/${cid}/gradebook/upload/preview?token=${encodeURIComponent(token)}`, {
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      });
      if (!previewRes.ok) {
        let b = '';
        try { b = await previewRes.json(); } catch(_) { b = await previewRes.text().catch(()=>'') }
        return alert('Failed to load preview: ' + (b && b.message ? b.message : previewRes.status));
      }
      const pv = await previewRes.json();
      // Ensure preview entries initialize as accepted by default so teachers can quickly deselect if needed
      if (pv && Array.isArray(pv.preview)) {
        for (const entry of pv.preview) {
          if (Array.isArray(entry.changes)) {
            for (const ch of entry.changes) {
              // avoid overwriting an explicit false value
              if (typeof ch.__accepted === 'undefined') ch.__accepted = true;
            }
          }
        }
      }
      // Attach token & expiresAt if server returned them
      uploadPreview.value = { ...(pv || {}), token };
      showUploadPreview.value = true;
    } catch (e) {
      console.error('Failed to fetch preview details', e);
      return alert('Failed to load preview details');
    }
  } catch (err) {
    console.error('Upload error', err);
    alert('Upload failed');
  } finally { loading.value = false; }
}

// wrapper for GradebookUpload component's file-selected event
function onUploadFileFromComponent(payload) {
  if (!payload || !payload.file) return;
  // create a synthetic event object with target.files for reuse
  onUploadFile({ target: { files: [payload.file] } });
}

function resolvedClassId() {
  return (props.classId || route.params.courseId || route.params.id || null)
}

async function loadGradebook() {
  const cid = resolvedClassId()
  if (!cid) return
  loading.value = true
  try {
    // Load grading weights from class record (grading_weights JSON) - fallback to defaults
    try {
      const classRes = await fetch(`${API_BASE}/api/classes/${cid}`, {
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      })
      if (classRes.ok) {
        const cls = await classRes.json()
        let gw = null
        try { gw = cls.grading_weights ? (typeof cls.grading_weights === 'string' ? JSON.parse(cls.grading_weights) : cls.grading_weights) : null } catch(e){ gw = null }
        if (gw && typeof gw === 'object') {
          midtermWeight.value = Number(gw.midterm) || 40
          finalWeight.value = Number(gw.final) || 60
        }
      }
    } catch (e) {
      console.warn('Failed to load grading weights from class row', e)
    }
    midtermWeightInput.value = midtermWeight.value
    finalWeightInput.value = finalWeight.value

    // Load class status & dates (use class endpoint which contains midterm_/final_ fields)
    try {
      const classRes = await fetch(`${API_BASE}/api/classes/${cid}`, {
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      })
      if (classRes.ok) {
        const cls = await classRes.json()
        midtermStatus.value = cls.midterm_status || 'pending'
        finalStatus.value = cls.final_status || 'pending'
        midtermCompletedDate.value = cls.midterm_completed_at ? new Date(cls.midterm_completed_at).toLocaleDateString() : ''
        finalCompletedDate.value = cls.final_completed_at ? new Date(cls.final_completed_at).toLocaleDateString() : ''
        try {
          const gw = cls.grading_weights ? (typeof cls.grading_weights === 'string' ? JSON.parse(cls.grading_weights) : cls.grading_weights) : null
          if (gw) { midtermWeight.value = Number(gw.midterm) || midtermWeight.value; finalWeight.value = Number(gw.final) || finalWeight.value }
        } catch(e){}
      }
    } catch (e) { console.warn('Failed to fetch class status', e) }

    // Load final grades (instructor view)
    try {
      const gradesRes = await fetch(`${API_BASE}/api/classes/${cid}/gradebook/final-grades`, {
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      })
      if (gradesRes.ok) {
        const payload = await gradesRes.json()
        const list = Array.isArray(payload.grades) ? payload.grades : payload
        grades.value = list.map(r => ({
          id: r.student_id || r.id,
          name: `${r.first_name || ''} ${r.last_name || ''}`.trim(),
          program: r.program || '-',
          midterm: (typeof r.midterm_grade !== 'undefined') ? r.midterm_grade : (r.midterm || null),
          final: (typeof r.final_grade !== 'undefined') ? r.final_grade : (r.final || null),
          overall: (typeof r.overall_grade !== 'undefined') ? r.overall_grade : (r.overall || null),
          remarks: r.remarks || ''
        }))
      }
    } catch (e) {
      console.warn('Failed to load final grades', e)
    }

    // Load midterm sheet metadata (to know if a sheet exists and route to editable view)
    try {
      const sheetRes = await fetch(`${API_BASE}/api/classes/${cid}/midterm-sheet`, {
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      })
      if (sheetRes.ok) midtermData.value = { exists: true }
      else midtermData.value = null
    } catch (e) { midtermData.value = null }

  } catch (e) {
    console.error('Load gradebook failed', e)
  } finally {
    loading.value = false
  }
}


function calculateOverall(mid, fin) {
  if (mid == null || fin == null) return '-'
  const m = Number(mid) || 0
  const f = Number(fin) || 0
  const mw = midtermWeight.value / 100
  const fw = finalWeight.value / 100
  return Math.round(m * mw + f * fw)
}

async function updateWeights() {
  loading.value = true
    try {
      // server expects midtermWeight and finalWeight via PUT /update-weights
      const res = await fetch(`${API_BASE}/api/classes/${props.classId}/gradebook/update-weights`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'x-user-id': localStorage.getItem('loggedInUserId') || ''
        },
        body: JSON.stringify({ midtermWeight: Number(midtermWeightInput.value), finalWeight: Number(finalWeightInput.value) })
      })
      if (res.ok) {
        midtermWeight.value = Number(midtermWeightInput.value)
        finalWeight.value = Number(finalWeightInput.value)
        // Recalculate overalls
        grades.value = grades.value.map(g => ({ ...g, overall: calculateOverall(g.midterm, g.final) }))
        alert('Weights updated')
      } else {
        let body = ''
        try { body = await res.json() } catch(_) { body = await res.text().catch(()=>'') }
        alert('Failed to update weights: ' + (body && body.message ? body.message : res.status))
      }
    } catch (e) {
      console.error(e)
      alert('Error updating weights')
    } finally {
      loading.value = false
    }
}

// Generate Midterm removed per user request

async function addManualColumn() {
  const cid = resolvedClassId()
  if (!cid) return alert('Invalid class')
  if (!newColLabel.value || !newColTerm.value) return alert('Provide label and term')
  loading.value = true
  try {
    const res = await fetch(`${API_BASE}/api/classes/${cid}/gradebook/add-task-column`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'x-user-id': localStorage.getItem('loggedInUserId') || '' },
      body: JSON.stringify({ taskName: newColLabel.value, maxPoints: newColMax.value || null, termType: newColTerm.value })
    })
    if (res.ok) {
      alert('Column added')
      showAddColumn.value = false
    } else {
      let b = ''
      try { b = await res.json() } catch(_) { b = await res.text().catch(()=>'') }
      alert('Failed to add column: ' + (b && b.message ? b.message : res.status))
    }
  } catch (e) {
    console.error(e)
    alert('Error adding column')
  } finally { loading.value = false }
}

function openSettings() {
  weightsJson.value = JSON.stringify({ midterm: midtermWeightInput.value, final: finalWeightInput.value })
  showSettings.value = true
}

async function saveSettings() {
  // Try parse weightsJson first, fallback to inputs
  let payload = null
  try { payload = JSON.parse(weightsJson.value) } catch(e) { payload = { midterm: midtermWeightInput.value, final: finalWeightInput.value } }
  if (Number(payload.midterm) + Number(payload.final) !== 100) return alert('Weights must sum to 100')
  loading.value = true
  try {
    const cid = resolvedClassId()
    const res = await fetch(`${API_BASE}/api/classes/${cid}/gradebook/update-weights`, {
      method: 'PUT',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'x-user-id': localStorage.getItem('loggedInUserId') || '' },
      body: JSON.stringify({ midtermWeight: Number(payload.midterm), finalWeight: Number(payload.final) })
    })
    if (res.ok) {
      midtermWeight.value = Number(payload.midterm)
      finalWeight.value = Number(payload.final)
      midtermWeightInput.value = Number(payload.midterm)
      finalWeightInput.value = Number(payload.final)
      showSettings.value = false
      alert('Settings saved')
    } else {
      let b = ''
      try { b = await res.json() } catch(_) { b = await res.text().catch(()=>'') }
      alert('Failed to save settings: ' + (b && b.message ? b.message : res.status))
    }
  } catch (e) {
    console.error(e)
    alert('Error saving settings')
  } finally { loading.value = false }
}

// Open or generate the literal Midterm Excel file and download it
function goToCategoryGrading() {
  const cid = resolvedClassId();
  if (!cid) return;
  router.push({ name: 'category-grading', params: { id: cid } });
}

async function viewMidtermExcel() {
  const cid = resolvedClassId();
  if (!cid) return;
  loading.value = true;
  try {
    // Ensure a midterm Excel exists (server will return existing info if already present)
    try {
      await fetch(`${API_BASE}/api/classes/${cid}/gradebook/generate-midterm`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      });
    } catch (e) {
      // Non-fatal: generation may fail due to migration/auth; we'll still attempt download below
      console.warn('generate-midterm request failed (non-fatal):', e);
    }

    // Attempt to download the midterm Excel
    const dlRes = await fetch(`${API_BASE}/api/classes/${cid}/gradebook/midterm/download`, {
      credentials: 'include',
      headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
    });

    if (dlRes.ok) {
      const blob = await dlRes.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `midterm_${cid}.xlsx`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    } else {
      let msg = '';
      try { msg = await dlRes.json(); } catch(_) { msg = await dlRes.text().catch(()=>''); }
      alert(`Failed to download midterm Excel: ${dlRes.status} ${msg && msg.message ? msg.message : msg}`);
    }
  } catch (e) {
    console.error('viewMidtermExcel error:', e);
    alert('Error opening midterm Excel');
  } finally {
    loading.value = false;
  }
}

async function markMidtermComplete() {
  loading.value = true
    try {
      const res = await fetch(`${API_BASE}/api/classes/${props.classId}/gradebook/mark-midterm-complete`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      })
      if (res.ok) {
        alert('Midterm marked complete')
        loadGradebook()
      } else {
        let body = ''
        try { body = await res.json() } catch(_) { body = await res.text().catch(()=>'') }
        alert('Failed to mark complete: ' + (body && body.message ? body.message : res.status))
      }
    } catch (e) {
      console.error(e)
      alert('Error marking complete')
    } finally {
      loading.value = false
    }
}

function viewFinal() {
  if (finalData.value) {
    window.open(finalData.value.url, '_blank')
  }
}

async function markFinalComplete() {
  loading.value = true
    try {
      const res = await fetch(`${API_BASE}/api/classes/${props.classId}/gradebook/mark-final-complete`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      })
      if (res.ok) {
        alert('Final marked complete')
        loadGradebook()
      } else {
        let body = ''
        try { body = await res.json() } catch(_) { body = await res.text().catch(()=>'') }
        alert('Failed to mark final complete: ' + (body && body.message ? body.message : res.status))
      }
    } catch (e) {
      console.error(e)
      alert('Error marking complete')
    } finally {
      loading.value = false
    }
}

async function downloadGradesheet() {
  loading.value = true
    try {
      const res = await fetch(`${API_BASE}/api/classes/${props.classId}/gradebook/download-gradesheet`, {
        credentials: 'include',
        headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' }
      })
      if (res.ok) {
        const blob = await res.blob()
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = 'gradesheet.xlsx'
        a.click()
        URL.revokeObjectURL(url)
      } else {
        let body = ''
        try { body = await res.json() } catch(_) { body = await res.text().catch(()=>'') }
        alert('Failed to download gradesheet: ' + (body && body.message ? body.message : res.status))
      }
    } catch (e) {
      console.error(e)
      alert('Error downloading gradesheet')
    } finally {
      loading.value = false
    }
}

onMounted(() => {
  loadGradebook()
})

// Commit preview: accept all changes
async function commitUploadPreview() {
  if (!uploadPreview.value || !uploadPreview.value.token) return;
  loading.value = true;
  try {
    const cid = resolvedClassId();
    // Build accepts payload from checked items
    const accepts = [];
    for (const entry of uploadPreview.value.preview || []) {
      const accepted = (entry.changes || []).filter(ch => ch.__accepted).map(ch => ({ columnKey: ch.columnKey, field: ch.field }));
      if (accepted.length) accepts.push({ studentId: entry.studentId, accepts: accepted.map(a => ({ columnKey: a.columnKey })) });
    }
    // If nothing checked, confirm accept-all
    let payloadAccepts = accepts;
    if (accepts.length === 0) {
      if (!confirm('No changes selected. Apply all changes?')) return;
      payloadAccepts = [];
    }

    const res = await fetch(`${API_BASE}/api/classes/${cid}/gradebook/upload/commit`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'x-user-id': localStorage.getItem('loggedInUserId') || '' },
      body: JSON.stringify({ token: uploadPreview.value.token, accepts: payloadAccepts })
    });
    if (!res.ok) {
      let b = '';
      try { b = await res.json(); } catch(_) { b = await res.text().catch(()=>'') }
      return alert('Commit failed: ' + (b && b.message ? b.message : res.status));
    }
    alert('Import applied successfully.');
    showUploadPreview.value = false;
    uploadPreview.value = null;
    loadGradebook();
  } catch (e) {
    console.error('Commit error', e);
    alert('Commit failed');
  } finally { loading.value = false }
}
</script>

<style scoped>
/* Centered Gradebook Layout */
.gradebook-container {
  min-height: 100vh;
  background: #f8f9fa;
  padding: 2rem 0;
}

.content-centered {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1rem;
}

.card {
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.card h5 {
  font-weight: 600;
  color: #2c3e50;
}

.card h6 {
  font-weight: 600;
  color: #2c3e50;
  font-size: 1.1rem;
}

/* Button improvements */
.btn {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Table improvements */
.table {
  margin-bottom: 0;
}

.table thead th {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

.table tbody tr {
  transition: background-color 0.15s;
}

.table tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.05);
}

/* Badge styling */
.badge {
  padding: 0.4em 0.8em;
  font-weight: 500;
  border-radius: 6px;
}

/* Input group styling */
.input-group {
  border-radius: 8px;
  overflow: hidden;
}

.input-group .form-control {
  font-weight: 600;
  font-size: 1.1rem;
}

/* Modern Modal Styles */
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 1050;
  animation: fadeIn 0.2s ease-out;
}

.modal-dialog-custom {
  width: 100%;
  max-width: 1400px;
  max-height: 95vh;
  margin: 0;
  animation: slideUp 0.3s ease-out;
}

.modal-content-custom {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  max-height: 95vh;
  overflow: hidden;
}

.modal-header-custom {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  flex-shrink: 0;
}

.modal-header-custom h5 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.btn-close-custom {
  background: transparent;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: #6b7280;
  font-size: 1.25rem;
  transition: all 0.2s;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
}

.btn-close-custom:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.modal-body-custom {
  padding: 0;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.modal-footer-custom {
  padding: 1rem 2rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #f9fafb;
  flex-shrink: 0;
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .modal-dialog-custom {
    max-width: 100%;
    max-height: 100vh;
    padding: 0;
  }
  
  .modal-content-custom {
    border-radius: 0;
    max-height: 100vh;
  }
  
  .modal-header-custom,
  .modal-footer-custom {
    padding: 1rem 1.25rem;
  }
  
  .modal-header-custom h5 {
    font-size: 1.1rem;
  }
}

/* Scrollbar styling for modal body */
.modal-body-custom::-webkit-scrollbar {
  width: 8px;
}

.modal-body-custom::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.modal-body-custom::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.modal-body-custom::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
