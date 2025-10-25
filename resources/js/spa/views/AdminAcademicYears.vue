<template>
  <div class="admin-academic-years-view">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
      <div>
        <h1 class="h3 mb-1">Academic Years</h1>
        <p class="text-muted small mb-0">Manage academic year reference files and set the active year for course creation</p>
      </div>
      <button class="btn btn-primary" @click="openUploadModal">
        <i class="bi bi-cloud-upload me-2"></i>Upload Academic Year
      </button>
    </div>

    <!-- Alert Messages -->
    <div v-if="message" :class="['alert', messageType, 'alert-dismissible', 'fade', 'show']" role="alert">
      {{ message }}
      <button type="button" class="btn-close" @click="message = ''"></button>
    </div>

    <!-- Active Academic Year Info -->
    <div v-if="activeYear" class="alert alert-info d-flex align-items-center mb-4">
      <i class="bi bi-info-circle-fill me-2" style="font-size: 1.5rem;"></i>
      <div>
        <strong>Active Academic Year:</strong> {{ activeYear.year_name }}
        <span class="badge bg-success ms-2">{{ activeYear.version }}</span>
        <br>
        <small class="text-muted">This year will be auto-filled in teacher course creation forms.</small>
      </div>
    </div>

    <div v-else class="alert alert-warning d-flex align-items-center mb-4">
      <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
      <div>
        <strong>No Active Academic Year Set</strong><br>
        <small>Teachers will not be able to create courses until an academic year is set as active.</small>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-2">Loading academic years...</p>
    </div>

    <!-- Academic Years Table -->
    <div v-else class="table-responsive border rounded shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">Status</th>
            <th>Academic Year</th>
            <th>Version</th>
            <th>File</th>
            <th>Notes</th>
            <th>Uploaded By</th>
            <th>Uploaded On</th>
            <th style="width: 180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="academicYears.length === 0">
            <td colspan="8" class="text-center text-muted py-5">
              <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
              <p class="mb-0 mt-2">No academic years uploaded yet.</p>
              <button class="btn btn-sm btn-primary mt-2" @click="openUploadModal">
                <i class="bi bi-plus-circle me-1"></i>Upload First Academic Year
              </button>
            </td>
          </tr>
          <tr v-else v-for="year in academicYears" :key="year.id">
            <td class="text-center">
              <span v-if="year.status === 'active'" class="text-success" title="Active">
                <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
              </span>
              <span v-else class="text-muted" title="Inactive">
                <i class="bi bi-circle" style="font-size: 1.5rem;"></i>
              </span>
            </td>
            <td>
              <strong>{{ year.year_name }}</strong>
              <span v-if="year.status === 'active'" class="badge bg-success ms-2">Active</span>
            </td>
            <td>
              <span class="badge bg-secondary">{{ year.version }}</span>
            </td>
            <td>
              <a v-if="year.file_path" :href="year.file_path" target="_blank" class="text-decoration-none">
                <i class="bi bi-file-earmark-pdf me-1"></i>{{ year.file_name || 'Download' }}
              </a>
              <span v-else class="text-muted">—</span>
            </td>
            <td>
              <small class="text-muted">{{ year.notes || '—' }}</small>
            </td>
            <td>
              <small>{{ year.uploaded_by_name || 'Unknown' }}</small>
            </td>
            <td>
              <small>{{ formatDate(year.created_at) }}</small>
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <button 
                  v-if="year.status !== 'active'"
                  class="btn btn-outline-success" 
                  @click="setActive(year.id, year.year_name)"
                  title="Set as Active">
                  <i class="bi bi-check-circle"></i>
                </button>
                <button 
                  class="btn btn-outline-primary" 
                  @click="viewFile(year.file_path)"
                  :disabled="!year.file_path"
                  title="View File">
                  <i class="bi bi-eye"></i>
                </button>
                <button 
                  class="btn btn-outline-danger" 
                  @click="confirmDelete(year.id, year.year_name)"
                  title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Upload Academic Year</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="uploadAcademicYear">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label" for="yearName">Academic Year <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="yearName" 
                  v-model="uploadForm.yearName" 
                  placeholder="e.g., 2025-2026" 
                  required>
                <div class="form-text">Format: YYYY-YYYY (e.g., 2025-2026)</div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="uploadFile">Upload File <span class="text-danger">*</span></label>
                <input 
                  type="file" 
                  class="form-control" 
                  id="uploadFile" 
                  @change="handleFileChange"
                  accept=".pdf,.doc,.docx,.xls,.xlsx"
                  required>
                <div class="form-text">Accepted: PDF, Word, Excel files</div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="notes">Notes/Description</label>
                <textarea 
                  class="form-control" 
                  id="notes" 
                  v-model="uploadForm.notes" 
                  rows="3"
                  placeholder="Optional description or notes about this academic year..."></textarea>
              </div>

              <div class="form-check">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="setActiveCheck" 
                  v-model="uploadForm.setAsActive">
                <label class="form-check-label" for="setActiveCheck">
                  Set as Active Academic Year
                </label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="uploading">
                <span v-if="uploading" class="spinner-border spinner-border-sm me-2"></span>
                {{ uploading ? 'Uploading...' : 'Upload' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Delete Academic Year</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="mb-0">Are you sure you want to delete <strong>{{ deleteYearName }}</strong>?</p>
            <p class="text-muted small mb-0 mt-2">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" @click="deleteAcademicYear" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { API_BASE } from '../services/apiBase'

const API = API_BASE

// State
const academicYears = ref([])
const loading = ref(false)
const message = ref('')
const messageType = ref('alert-success')

// Upload form
const uploadForm = ref({
  yearName: '',
  file: null,
  notes: '',
  setAsActive: false
})
const uploading = ref(false)

// Delete state
const deleteYearId = ref(null)
const deleteYearName = ref('')
const deleting = ref(false)

// Computed
const activeYear = computed(() => academicYears.value.find(y => y.status === 'active'))

// Helpers
function getLoggedInUserId() {
  return localStorage.getItem('loggedInUserId') || localStorage.getItem('userId')
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// Load academic years
async function loadAcademicYears() {
  loading.value = true
  try {
    const userId = getLoggedInUserId()
    const res = await fetch(`${API}/api/admin/academic-years`, {
      credentials: 'include',
      headers: userId ? { 'x-user-id': userId } : {}
    })
    if (!res.ok) throw new Error('Failed to load academic years')
    const data = await res.json()
    academicYears.value = Array.isArray(data) ? data : []
  } catch (err) {
    console.error('Load academic years error:', err)
    showMessage('Failed to load academic years.', 'alert-danger')
  } finally {
    loading.value = false
  }
}

// Open upload modal
function openUploadModal() {
  uploadForm.value = {
    yearName: '',
    file: null,
    notes: '',
    setAsActive: !activeYear.value // Auto-check if no active year
  }
  const modal = document.getElementById('uploadModal')
  if (modal && window.bootstrap) {
    const bsModal = new window.bootstrap.Modal(modal)
    bsModal.show()
  }
}

// Handle file change
function handleFileChange(event) {
  const file = event.target.files[0]
  uploadForm.value.file = file || null
}

// Upload academic year
async function uploadAcademicYear() {
  if (!uploadForm.value.yearName || !uploadForm.value.file) {
    showMessage('Please fill all required fields.', 'alert-danger')
    return
  }

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('yearName', uploadForm.value.yearName)
    formData.append('file', uploadForm.value.file)
    formData.append('notes', uploadForm.value.notes || '')
    formData.append('setAsActive', uploadForm.value.setAsActive ? 'true' : 'false')

    const userId = getLoggedInUserId()
    const res = await fetch(`${API}/api/admin/academic-years`, {
      method: 'POST',
      credentials: 'include',
      headers: userId ? { 'x-user-id': userId } : {},
      body: formData
    })

    const data = await res.json()
    if (!res.ok) {
      throw new Error(data.message || 'Failed to upload')
    }

    showMessage(data.message || 'Academic year uploaded successfully!', 'alert-success')
    
    // Close modal
    const modal = document.getElementById('uploadModal')
    if (modal && window.bootstrap) {
      const bsModal = window.bootstrap.Modal.getInstance(modal)
      if (bsModal) bsModal.hide()
    }

    // Reload data
    await loadAcademicYears()
  } catch (err) {
    console.error('Upload error:', err)
    showMessage(err.message || 'Failed to upload academic year.', 'alert-danger')
  } finally {
    uploading.value = false
  }
}

// Set active academic year
async function setActive(id, yearName) {
  if (!confirm(`Set "${yearName}" as the active academic year?\n\nThe previous active year will become inactive.`)) {
    return
  }

  try {
    const userId = getLoggedInUserId()
    const res = await fetch(`${API}/api/admin/academic-years/${id}/activate`, {
      method: 'PUT',
      credentials: 'include',
      headers: { 
        'Content-Type': 'application/json',
        ...(userId ? { 'x-user-id': userId } : {})
      }
    })

    const data = await res.json()
    if (!res.ok) {
      throw new Error(data.message || 'Failed to set active')
    }

    showMessage(data.message || `"${yearName}" is now the active academic year.`, 'alert-success')
    await loadAcademicYears()
  } catch (err) {
    console.error('Set active error:', err)
    showMessage(err.message || 'Failed to set active academic year.', 'alert-danger')
  }
}

// View file
function viewFile(filePath) {
  if (!filePath) return
  window.open(filePath, '_blank')
}

// Confirm delete
function confirmDelete(id, yearName) {
  deleteYearId.value = id
  deleteYearName.value = yearName
  const modal = document.getElementById('deleteModal')
  if (modal && window.bootstrap) {
    const bsModal = new window.bootstrap.Modal(modal)
    bsModal.show()
  }
}

// Delete academic year
async function deleteAcademicYear() {
  if (!deleteYearId.value) return

  deleting.value = true
  try {
    const userId = getLoggedInUserId()
    const res = await fetch(`${API}/api/admin/academic-years/${deleteYearId.value}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: userId ? { 'x-user-id': userId } : {}
    })

    const data = await res.json()
    if (!res.ok) {
      throw new Error(data.message || 'Failed to delete')
    }

    showMessage(data.message || 'Academic year deleted successfully.', 'alert-success')
    
    // Close modal
    const modal = document.getElementById('deleteModal')
    if (modal && window.bootstrap) {
      const bsModal = window.bootstrap.Modal.getInstance(modal)
      if (bsModal) bsModal.hide()
    }

    // Reload data
    await loadAcademicYears()
  } catch (err) {
    console.error('Delete error:', err)
    showMessage(err.message || 'Failed to delete academic year.', 'alert-danger')
  } finally {
    deleting.value = false
    deleteYearId.value = null
    deleteYearName.value = ''
  }
}

// Show message
function showMessage(msg, type = 'alert-success') {
  message.value = msg
  messageType.value = type
  setTimeout(() => {
    message.value = ''
  }, 5000)
}

// Mounted
onMounted(() => {
  loadAcademicYears()
})
</script>

<style scoped>
.admin-academic-years-view {
  padding: 0;
}

.table-hover tbody tr:hover {
  background-color: #f8f9fa;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}
</style>
