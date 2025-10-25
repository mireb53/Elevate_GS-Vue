<template>
  <div class="modal fade" tabindex="-1" ref="modalEl">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content" style="max-height: 90vh;">
        <div class="modal-header">
          <h5 class="modal-title">Create New {{ type }}</h5>
          <button type="button" class="btn-close" @click="hide"></button>
        </div>
        <form @submit.prevent="onSubmit">
          <div class="modal-body">
            <input type="hidden" v-model="localForm.type">
            <div class="mb-3">
              <label class="form-label">Title</label>
              <input type="text" class="form-control" v-model="localForm.title" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" v-model="localForm.description" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Due Date</label>
              <input type="datetime-local" class="form-control" v-model="localForm.dueDate" />
            </div>

            <!-- Quiz Builder Section (only for Quiz type) -->
            <div v-if="localForm.type === 'Quiz'" class="mb-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <label class="form-label mb-0">Quiz Questions</label>
                  <span v-if="quizQuestions.length > 0" class="badge bg-info">
                    {{ quizQuestions.length }} {{ quizQuestions.length === 1 ? 'question' : 'questions' }} • 
                    {{ totalQuizPoints }} points total
                  </span>
                </div>
              </div>
              
              <QuizBuilder v-model="quizQuestions" />
            </div>

            <!-- Rubric Builder Section (hidden for Quiz) -->
            <div v-if="localForm.type !== 'Quiz'" class="mb-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <label class="form-label mb-0">Grading Rubric (Optional)</label>
                  <span v-if="rubric.length > 0" class="badge bg-info">
                    {{ rubric.length }} {{ rubric.length === 1 ? 'criterion' : 'criteria' }} • 
                    {{ totalRubricPoints }} points total
                  </span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="toggleRubric">
                  <i class="bi" :class="showRubric ? 'bi-chevron-up' : 'bi-plus-lg'"></i>
                  {{ showRubric ? 'Hide Rubric' : 'Add Rubric' }}
                </button>
              </div>
              
              <div v-if="showRubric" class="card p-3 rubric-builder">
                <p class="text-muted small mb-3">
                  <i class="bi bi-info-circle me-1"></i>
                  Create grading criteria to grade students on specific aspects (e.g., Cleanliness, Accuracy, etc.)
                </p>
                
                <div v-if="rubric.length === 0" class="alert alert-light text-center">
                  <i class="bi bi-clipboard-plus fs-1 text-muted"></i>
                  <p class="mb-2">No criteria added yet</p>
                  <button type="button" class="btn btn-sm btn-primary" @click="addCriterion">
                    <i class="bi bi-plus-lg me-1"></i>
                    Add First Criterion
                  </button>
                </div>
                
                <div v-else class="rubric-criteria-list">
                  <div v-for="(criterion, index) in rubric" :key="index" class="criterion-card mb-3 p-3 border rounded shadow-sm">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary">{{ index + 1 }}</span>
                        <h6 class="mb-0">{{ criterion.criterion_name || 'Untitled Criterion' }}</h6>
                        <span class="badge bg-success">{{ criterion.max_points || 0 }} pts</span>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-danger" @click="removeCriterion(index)" title="Remove criterion">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                    
                    <div class="row g-2">
                      <div class="col-md-6">
                        <label class="form-label small fw-bold">Criterion Name <span class="text-danger">*</span></label>
                        <input 
                          type="text" 
                          class="form-control" 
                          v-model="criterion.criterion_name"
                          placeholder="e.g., Cleanliness, Accuracy, Completeness"
                          required
                        />
                      </div>
                      
                      <div class="col-md-3">
                        <label class="form-label small fw-bold">Max Points <span class="text-danger">*</span></label>
                        <input 
                          type="number" 
                          class="form-control" 
                          v-model.number="criterion.max_points"
                          min="1"
                          max="1000"
                          required
                        />
                      </div>
                      
                      <div class="col-md-3">
                        <label class="form-label small fw-bold">Weight</label>
                        <div class="form-control-plaintext">
                          <span class="badge bg-secondary">
                            {{ calculateWeight(criterion.max_points) }}%
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <div class="mt-2">
                      <label class="form-label small fw-bold">Description (Optional)</label>
                      <textarea 
                        class="form-control" 
                        v-model="criterion.description"
                        rows="2"
                        placeholder="What does this criterion measure? What makes a good score?"
                      ></textarea>
                    </div>
                  </div>
                  
                  <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-secondary" @click="addCriterion">
                      <i class="bi bi-plus-lg me-1"></i>
                      Add Another Criterion
                    </button>
                    
                    <div class="total-points-display">
                      <div class="d-flex align-items-center gap-3">
                        <span class="text-muted">Total Points:</span>
                        <span class="badge bg-success fs-5">{{ totalRubricPoints }} points</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attachments (hidden for Quiz type) -->
            <div v-if="localForm.type !== 'Quiz'" class="mb-3">
              <label class="form-label">Attachments</label>
              <input type="file" class="form-control" multiple @change="onFilesSelected">
              <div class="mt-2">
                <div v-for="(f, i) in files" :key="i" class="d-flex align-items-center mb-2">
                  <div class="flex-grow-1">
                    <div class="small">{{ f.name }}</div>
                    <div class="progress" style="height:8px;">
                      <div class="progress-bar" role="progressbar" :style="{width: (f.progress||0)+'%'}"></div>
                    </div>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="removeFile(i)">Remove</button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="hide">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">{{ submitting ? 'Creating...' : 'Create' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import { defineEmits, defineProps } from 'vue'
import QuizBuilder from './QuizBuilder.vue'

const props = defineProps({ type: { type: String, default: 'Lesson' }, initialForm: { type: Object, default: () => ({}) } })
const emit = defineEmits(['submit', 'shown', 'hidden'])

const modalEl = ref(null)
const submitting = ref(false)
const files = ref([])
const localForm = ref({ type: props.type, title: '', description: '', dueDate: '' })

// Quiz state
const quizQuestions = ref([])

const totalQuizPoints = computed(() => {
  return quizQuestions.value.reduce((sum, q) => sum + (parseInt(q.points) || 0), 0)
})

// Rubric state
const showRubric = ref(false)
const rubric = ref([])

const totalRubricPoints = computed(() => {
  return rubric.value.reduce((sum, c) => sum + (c.max_points || 0), 0)
})

function calculateWeight(points) {
  if (totalRubricPoints.value === 0) return 0
  return ((points || 0) / totalRubricPoints.value * 100).toFixed(1)
}

watch(() => props.initialForm, (v) => { if (v) localForm.value = { ...localForm.value, ...v } }, { immediate: true })
watch(() => props.type, (t) => { localForm.value.type = t })

function onFilesSelected(e){ const sel = Array.from(e.target.files||[]); sel.forEach(f => files.value.push({ file: f, name: f.name, progress: 0, uploadedMeta: null })); e.target.value = '' }
function removeFile(i){ files.value.splice(i,1) }

function toggleRubric() {
  showRubric.value = !showRubric.value
  // Don't auto-add criterion - let user click to add
}

function addCriterion() {
  rubric.value.push({
    criterion_name: '',
    description: '',
    max_points: 10
  })
}

function removeCriterion(index) {
  rubric.value.splice(index, 1)
}

function show(){ if (!modalEl.value) return; const bs = new bootstrap.Modal(modalEl.value); bs.show(); emit('shown') }
function reset(){
  // reset internal form state and files
  localForm.value = { type: props.type, title: '', description: '', dueDate: '' }
  files.value = []
  quizQuestions.value = []
  rubric.value = []
  showRubric.value = false
  submitting.value = false
}

function hide(){ if (!modalEl.value) return; const inst = bootstrap.Modal.getInstance(modalEl.value); if (inst) inst.hide(); emit('hidden'); reset() }

function onSubmit(){ 
  submitting.value = true
  
  // Include rubric and quiz if present
  const payload = { 
    form: Object.assign({}, localForm.value), 
    files: files.value.slice()
  }
  
  // Add quiz questions for Quiz type
  if (localForm.value.type === 'Quiz' && quizQuestions.value.length > 0) {
    payload.quiz = { questions: quizQuestions.value }
  }
  
  if (showRubric.value && rubric.value.length > 0) {
    payload.rubric = rubric.value.filter(c => c.criterion_name && c.max_points > 0)
  }
  
  emit('submit', payload)
  submitting.value = false
}

defineExpose({ show, hide })
</script>

<style scoped>
.progress { 
  background: #f3f3f3;
}

.modal-body {
  max-height: calc(90vh - 200px);
  overflow-y: auto;
}

.rubric-builder {
  background: #f8f9fa;
  border: 2px dashed #dee2e6;
  max-height: 600px;
  overflow-y: auto;
}

.rubric-criteria-list {
  max-height: 500px;
  overflow-y: auto;
}

.criterion-card {
  background: white;
  transition: all 0.2s ease;
}

.criterion-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
  transform: translateY(-2px);
}

.total-points-display {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  color: white;
}

.total-points-display .text-muted {
  color: rgba(255,255,255,0.9) !important;
  font-weight: 600;
}

.total-points-display .badge {
  background: rgba(255,255,255,0.2) !important;
  color: white;
  font-weight: 700;
  padding: 0.5rem 1rem;
}

.badge {
  font-weight: 600;
}

/* Custom scrollbar for rubric builder */
.rubric-builder::-webkit-scrollbar,
.rubric-criteria-list::-webkit-scrollbar {
  width: 8px;
}

.rubric-builder::-webkit-scrollbar-track,
.rubric-criteria-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.rubric-builder::-webkit-scrollbar-thumb,
.rubric-criteria-list::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.rubric-builder::-webkit-scrollbar-thumb:hover,
.rubric-criteria-list::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
