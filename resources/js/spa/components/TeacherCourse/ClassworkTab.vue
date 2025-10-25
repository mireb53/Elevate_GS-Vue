<template>
  <div>
    <!-- Header and create controls are provided by the SPA ClassworkView; legacy header removed to avoid duplication -->

    <!-- SPA classwork view (preferred) -->
    <div v-if="useSpa">
      <ClassworkView :key="embedKey" :courseId="embedCourseId" />
    </div>
    <!-- legacy script will render into this container when SPA is disabled -->
    <div v-else id="classworkContent"></div>

    <!-- Submissions modal (legacy expects #submissionsModal and #submissionsTable) -->
    <div class="modal fade" id="submissionsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Submissions <span id="submissionsModalTitle"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2 d-flex gap-2">
              <button class="btn btn-sm btn-outline-secondary" id="showNotSubmittedBtn"><i class="bi bi-person-slash me-1"></i>Not Submitted</button>
            </div>
            <table class="table table-sm" id="submissionsTable"><thead><tr><th>Student</th><th>Submitted At</th><th>Files</th><th>Score</th><th>Comment</th><th>Actions</th></tr></thead><tbody></tbody></table>
          </div>
          <div class="modal-footer"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
      </div>
    </div>

    <!-- Rubric grading modal (legacy expects #rubricGradeModal) -->
    <div class="modal fade" id="rubricGradeModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="bi bi-clipboard-check me-2"></i>
              Grade with Rubric<span id="rubricModalTitle"></span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="rubricContainer"></div>
            <hr>
            <div class="mb-3">
              <label class="form-label fw-bold">
                <i class="bi bi-chat-left-text me-2"></i>Overall Feedback (Optional)
              </label>
              <textarea 
                id="rubricOverallComment" 
                class="form-control" 
                rows="3" 
                placeholder="Add feedback for the student..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-2"></i>Cancel
            </button>
            <button class="btn btn-primary" id="saveRubricGradeBtn">
              <i class="bi bi-check-circle me-2"></i>Save Grade
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Inline feedback toast placeholder -->
    <div id="inlineFeedbackToast" style="position:fixed;bottom:20px;right:20px;z-index:1080"></div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const props = defineProps({ classId: [String, Number], course: Object });
import ClassworkView from '../../views/ClassworkView.vue'
import { ref } from 'vue'
import { computed } from 'vue'
import { API_BASE } from '../../services/apiBase'
const SCRIPT_URL = '/Teacherview/myclasswork.js';
const API = () => (API_BASE);
const legacyLoaded = ref(false);
// Use SPA embed by default. Toggle to false to try legacy renderer.
const useSpa = ref(true)
const embedKey = ref(props.classId || Date.now())
const embedCourseId = computed(() => {
  return props.classId || (props.course && (props.course.id || props.course.class_id)) || localStorage.getItem('currentClassId') || localStorage.getItem('classId') || undefined
})

function loadExternalScript(url) {
  return new Promise((resolve) => {
    if (document.querySelector(`script[src="${url}"]`)) return resolve(true);
    const s = document.createElement('script');
    s.src = url;
    s.async = true;
    s.onload = () => resolve(true);
    s.onerror = () => resolve(false);
    document.body.appendChild(s);
  });
}

function ensureLegacyModal() {
  if (document.getElementById('createFormModal')) return;
  const wrapper = document.createElement('div');
  wrapper.innerHTML = '<div class="modal fade" id="createFormModal" tabindex="-1" aria-hidden="true">'
    + '<div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow">'
    + '<div class="modal-header"><h5 class="modal-title" id="modalFormTitle">Create</h5>'
    + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
    + '<div class="modal-body" id="modalFormBody"><div class="text-muted">Loading…</div></div>'
    + '<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>'
    + '</div></div></div>';
  document.body.appendChild(wrapper);
}

// Ensure the URL contains a `classId` query parameter so embedded ClassworkView picks it up
function ensureQueryClassId(candidate) {
  try {
    const existing = route.query.classId || route.query.courseId || (typeof route.query.class_id !== 'undefined' ? route.query.class_id : null)
    if (!existing && candidate) {
      // Use router.replace so navigation stays within the SPA and history is preserved
      try {
        router.replace({ query: Object.assign({}, route.query, { classId: candidate }) }).catch(() => {})
      } catch (e) { /* ignore */ }
    }
  } catch(e) { /* ignore */ }
}

function renderFallbackLoading(){
  const c = document.getElementById('classworkContent'); if(!c) return; c.innerHTML = '';
}

function renderFallbackError(){ const c = document.getElementById('classworkContent'); if(!c) return; c.innerHTML = '<div class="alert alert-danger">Failed to load classwork.</div>'; }

async function fetchClassworkItemsFallback(){
  // Try multiple ways to resolve class id
  let cId = props.classId || new URLSearchParams(window.location.search).get('classId') || new URLSearchParams(window.location.search).get('class_id') || new URLSearchParams(window.location.search).get('id');
  if(!cId){ cId = document.querySelector('[data-class-id]')?.getAttribute('data-class-id'); }
  if(!cId){ cId = localStorage.getItem('currentClassId') || localStorage.getItem('classId'); }
  if(!cId){ console.warn('Classwork fallback: no classId found'); return []; }
  const res = await fetch(`${API()}/api/classes/${cId}/classwork`);
  if(!res.ok) throw new Error('fetch failed');
  const rows = await res.json();
  return Array.isArray(rows) ? rows : [];
}

function renderFallbackItems(items){
  const c = document.getElementById('classworkContent'); if(!c) return;
  if(!items.length){ c.innerHTML = ''; return; }
  const html = items.map(it => {
    const type = it.type || 'Lesson';
    const due = it.due_at ? new Date(it.due_at).toLocaleString() : (it.due||'');
    return `<div class="card p-3 mb-3 shadow-sm" style="cursor:pointer;">
      <div style="height:5px;background:${typeColor(type)};margin:-16px -16px 16px -16px;border-radius:3px 3px 0 0"></div>
      <div class="d-flex justify-content-between align-items-start gap-3">
        <div class="flex-grow-1">
          <h6 class="mb-1">${escapeHtml(it.title||it.name||'Untitled')}</h6>
          <small class="text-muted">${escapeHtml(type)}${due? ' • Due '+escapeHtml(due):''}</small>
          <p class="small mt-2 mb-2 text-truncate">${escapeHtml(it.description||'')}</p>
        </div>
        <div class="d-flex flex-column gap-2 ms-auto" style="min-width:120px;">
          <button class="btn btn-sm btn-outline-primary" data-id="${it.id}" onclick="(function(e){ e.stopPropagation(); }) (event)"><i class="bi bi-eye"></i></button>
        </div>
      </div>
    </div>`;
  }).join('');
  c.innerHTML = html;
}

function openModal(type) {
  if (window.openModalForm && typeof window.openModalForm === 'function') {
    window.openModalForm(type);
    return;
  }
  // fallback: ensure a simple modal exists and show it
  ensureLegacyModal();
  const modalEl = document.getElementById('createFormModal');
  if (modalEl && window.bootstrap) {
    const t = document.getElementById('modalFormTitle');
    const b = document.getElementById('modalFormBody');
    if (t) t.textContent = `Create ${type}`;
    if (b) b.innerHTML = `<div class="text-muted">Create ${type} (basic)</div>`;
    try { new bootstrap.Modal(modalEl).show(); } catch (e) { console.error(e); }
  }
}

// Initialize on mount and re-init when the classId prop changes (user clicks another course)
onMounted(() => {
  const candidate = props.classId || (props.course && (props.course.id || props.course.class_id)) || localStorage.getItem('currentClassId') || localStorage.getItem('classId')
  ensureQueryClassId(candidate)
  if (useSpa.value) {
    embedKey.value = candidate || Date.now()
    // SPA view will mount automatically via the template
  } else {
    initLegacy();
  }
});

watch(() => props.classId, (newVal, oldVal) => {
  if (!newVal || newVal === oldVal) return;
  const candidate = newVal
  ensureQueryClassId(candidate)
  if (useSpa.value) {
    // bump key so the SPA view reloads for new class
    embedKey.value = candidate || Date.now()
  } else {
    legacyLoaded.value = false;
    const c = document.getElementById('classworkContent'); if (c) c.innerHTML = '';
    try { initLegacy(); } catch (e) { console.error('Re-init legacy classwork failed', e); }
  }
});
</script>
