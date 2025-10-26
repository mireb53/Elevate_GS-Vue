<template>
  <!-- File Preview Modal -->
  <FilePreviewModal ref="filePreviewModal" />
  
  <div class="modal fade" tabindex="-1" ref="modalEl">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><span v-if="detail">{{ detail.title }}</span><span v-else>Loading...</span></h5>
          <button type="button" class="btn-close" @click="hide"></button>
        </div>
        <div class="modal-body">
          <!-- Success Message -->
          <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ successMessage }}
            <button type="button" class="btn-close" @click="successMessage = ''"></button>
          </div>
          
          <!-- Error Message -->
          <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
            <button type="button" class="btn-close" @click="errorMessage = ''"></button>
          </div>
          
          <div v-if="loading" class="text-center py-4"><div class="spinner-border" role="status"></div></div>
          <div v-else-if="!detail" class="text-center text-muted py-4">No details available.</div>

          <div v-else>
            <div class="row">
              <div :class="detail.type === 'Quiz' ? 'col-12' : 'col-md-8'">
                <h3>{{ detail.title }}</h3>
                <p class="text-muted">Due: <span v-html="computedDueDisplay"></span> <small v-if="computedDueStatus" class="text-muted">&middot; <span :class="{'text-danger': isOverdue}">{{ computedDueStatus }}</span></small></p>
                <hr>
                <div v-html="detailDescription"></div>

                <div v-if="detail.type === 'Quiz' && detail.quiz && detail.quiz.questions" class="mt-4">
                  <h5>Quiz Questions ({{ detail.quiz.questions.length }})</h5>
                  
                  <!-- Submitted Banner -->
                  <div v-if="submission" class="alert alert-success mb-4">
                    <div class="text-center">
                      <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                      <h5 class="d-inline mb-0">Quiz Submitted Successfully!</h5>
                      <div class="mt-3">
                        <p class="text-muted mb-2">Submitted on {{ new Date(submission.submission_time).toLocaleString() }}</p>
                        <div v-if="submission.grade && typeof submission.grade.score !== 'undefined'" class="mt-3">
                          <div class="d-inline-block p-3 bg-white rounded shadow-sm">
                            <h3 class="mb-1 text-success">
                              <strong>{{ submission.grade.score }} / {{ submission.grade.totalPoints || detail.points_possible || 100 }}</strong>
                            </h3>
                            <p class="mb-0 text-muted small">Your Score</p>
                          </div>
                        </div>
                        <div v-else class="text-muted small mt-2">
                          <i class="bi bi-clock me-1"></i>Score will be available shortly
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Quiz Info -->
                  <div v-else class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Total Points: {{ detail.points_possible || detail.quiz.questions.reduce((s,q) => s + (q.points||0), 0) }}
                  </div>
                  
                  <!-- Questions List (no accordion, just scroll) -->
                  <div class="quiz-questions-list">
                    <div v-for="(q, idx) in detail.quiz.questions" :key="idx" class="question-card card mb-4 shadow-sm" :class="submission && getQuestionStatus(q, idx)">
                      <div class="card-header d-flex justify-content-between align-items-center" :class="submission ? (isAnswerCorrect(q, idx) ? 'bg-success-subtle' : 'bg-danger-subtle') : 'bg-light'">
                        <div class="d-flex align-items-center">
                          <span class="me-3">
                            <i v-if="submission && isAnswerCorrect(q, idx)" class="bi bi-check-circle-fill text-success fs-5"></i>
                            <i v-else-if="submission && !isAnswerCorrect(q, idx)" class="bi bi-x-circle-fill text-danger fs-5"></i>
                            <i v-else-if="answers[idx] !== undefined && answers[idx] !== null && answers[idx] !== ''" class="bi bi-check-circle-fill text-success fs-5"></i>
                            <i v-else class="bi bi-circle text-muted fs-5"></i>
                          </span>
                          <h6 class="mb-0">Question {{ idx+1 }}</h6>
                          <span v-if="submission" class="ms-2 badge" :class="isAnswerCorrect(q, idx) ? 'bg-success' : 'bg-danger'">
                            {{ isAnswerCorrect(q, idx) ? 'Correct' : 'Incorrect' }}
                          </span>
                        </div>
                        <span class="badge bg-primary">{{ q.points || 1 }} {{ q.points===1 ? 'point' : 'points' }}</span>
                      </div>
                      <div class="card-body">
                        <p class="fw-bold mb-3">{{ q.text || q.question }}</p>
                        <!-- Render interactive inputs bound to answers -->
                          
                          <!-- Multiple Choice -->
                          <div v-if="q.type === 'multiple'">
                            <div v-for="(choice, cidx) in (q.choices || (q.config && q.config.choices) || [])" :key="`mc-${idx}-${cidx}`" class="form-check mb-2 p-2 rounded" :class="submission && getChoiceClass(q, idx, choice)">
                              <input 
                                class="form-check-input" 
                                type="radio" 
                                :name="`mc-question-${idx}`" 
                                :id="`mc-q${idx}-opt${cidx}`" 
                                :value="choice"
                                v-model="answers[idx]"
                                :disabled="!!submission" />
                              <label class="form-check-label d-flex align-items-center" :for="`mc-q${idx}-opt${cidx}`">
                                <span class="badge bg-primary me-2">{{ String.fromCharCode(65 + cidx) }}</span>
                                <span class="flex-grow-1">{{ choice }}</span>
                                <span v-if="submission && choice === getCorrectAnswer(q)" class="badge bg-success ms-2">
                                  <i class="bi bi-check-lg"></i> Correct Answer
                                </span>
                                <span v-else-if="submission && answers[idx] === choice && choice !== getCorrectAnswer(q)" class="badge bg-danger ms-2">
                                  <i class="bi bi-x-lg"></i> Your Answer
                                </span>
                              </label>
                            </div>
                            <div v-if="!submission" class="small text-muted mt-2">
                              Current selection: {{ answers[idx] || 'None' }}
                            </div>
                          </div>
                          
                          <!-- True or False -->
                          <div v-else-if="q.type === 'truefalse'">
                            <div class="form-check mb-2 p-2 rounded" :class="submission && getChoiceClass(q, idx, 'True')">
                              <input 
                                class="form-check-input" 
                                type="radio" 
                                :name="`tf-question-${idx}`" 
                                :id="`tf-q${idx}-true`" 
                                value="True"
                                v-model="answers[idx]"
                                :disabled="!!submission" />
                              <label class="form-check-label d-flex align-items-center" :for="`tf-q${idx}-true`">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span class="flex-grow-1">True</span>
                                <span v-if="submission && getCorrectAnswer(q) === 'True'" class="badge bg-success ms-2">
                                  <i class="bi bi-check-lg"></i> Correct Answer
                                </span>
                                <span v-else-if="submission && answers[idx] === 'True' && getCorrectAnswer(q) !== 'True'" class="badge bg-danger ms-2">
                                  <i class="bi bi-x-lg"></i> Your Answer
                                </span>
                              </label>
                            </div>
                            <div class="form-check mb-2 p-2 rounded" :class="submission && getChoiceClass(q, idx, 'False')">
                              <input 
                                class="form-check-input" 
                                type="radio" 
                                :name="`tf-question-${idx}`" 
                                :id="`tf-q${idx}-false`" 
                                value="False"
                                v-model="answers[idx]"
                                :disabled="!!submission" />
                              <label class="form-check-label d-flex align-items-center" :for="`tf-q${idx}-false`">
                                <i class="bi bi-x-circle text-danger me-2"></i>
                                <span class="flex-grow-1">False</span>
                                <span v-if="submission && getCorrectAnswer(q) === 'False'" class="badge bg-success ms-2">
                                  <i class="bi bi-check-lg"></i> Correct Answer
                                </span>
                                <span v-else-if="submission && answers[idx] === 'False' && getCorrectAnswer(q) !== 'False'" class="badge bg-danger ms-2">
                                  <i class="bi bi-x-lg"></i> Your Answer
                                </span>
                              </label>
                            </div>
                          </div>
                          
                          <!-- Identification, Essay, Fill in Blanks -->
                          <div v-else-if="['identification', 'essay', 'fillblank'].includes(q.type)">
                            <textarea 
                              class="form-control" 
                              v-model="answers[idx]" 
                              rows="3"
                              :placeholder="q.type === 'essay' ? 'Type your essay answer here...' : 'Type your answer here...'"
                              :disabled="!!submission"></textarea>
                            <div v-if="submission && getCorrectAnswer(q)" class="alert alert-info mt-2">
                              <strong><i class="bi bi-lightbulb me-2"></i>Correct Answer:</strong> {{ getCorrectAnswer(q) }}
                            </div>
                          </div>
                          
                          <!-- Enumeration -->
                          <div v-else-if="q.type === 'enumeration'">
                            <textarea 
                              class="form-control" 
                              v-model="answers[idx]" 
                              rows="5"
                              placeholder="Enter your answers, one per line"
                              :disabled="!!submission"></textarea>
                            <small class="text-muted">Enter one answer per line</small>
                            <div v-if="submission && getCorrectAnswer(q)" class="alert alert-info mt-2">
                              <strong><i class="bi bi-lightbulb me-2"></i>Correct Answer:</strong> {{ getCorrectAnswer(q) }}
                            </div>
                          </div>
                          
                          <!-- Fallback for other types -->
                          <div v-else>
                            <textarea 
                              class="form-control" 
                              v-model="answers[idx]" 
                              rows="3"
                              placeholder="Type your answer here..."
                              :disabled="!!submission"></textarea>
                            <div v-if="submission && getCorrectAnswer(q)" class="alert alert-info mt-2">
                              <strong><i class="bi bi-lightbulb me-2"></i>Correct Answer:</strong> {{ getCorrectAnswer(q) }}
                            </div>
                          </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Quiz Submit Button (shown below questions if not submitted) -->
                  <div v-if="!submission" class="mt-4 text-center">
                    <div v-if="!allQuestionsAnswered" class="alert alert-warning mb-3">
                      <i class="bi bi-exclamation-triangle me-2"></i>
                      Please answer all {{ detail.quiz.questions.length }} questions before submitting
                    </div>
                    <button 
                      type="button"
                      class="btn btn-success btn-lg" 
                      @click="handleSubmission(detail.id)"
                      :disabled="submitting || !allQuestionsAnswered">
                      <span v-if="submitting">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Submitting...
                      </span>
                      <span v-else>
                        <i class="bi bi-check-circle me-2"></i>Done
                      </span>
                    </button>
                    <div v-if="allQuestionsAnswered" class="text-success small mt-2">
                      <i class="bi bi-check-circle me-1"></i>
                      All questions answered ({{ answeredCount }}/{{ detail.quiz.questions.length }})
                    </div>
                  </div>
                </div>

                <!-- Attachments Section (hidden for Quiz) -->
                <div v-if="detail.type !== 'Quiz'" class="mt-4">
                  <h5>Attachments</h5>
                  <div v-if="attachments.length" class="list-group">
                    <div v-for="(f, i) in attachments" :key="i" class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <a href="#" @click.prevent="previewFile(f.url, f.name)" class="me-2">
                          <i class="bi bi-file-earmark me-1"></i>{{ f.name }}
                        </a>
                      </div>
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" @click="previewFile(f.url, f.name)">
                          <i class="bi bi-eye me-1"></i>Preview
                        </button>
                        <a class="btn btn-outline-secondary" :href="f.url" download>
                          <i class="bi bi-download"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-muted small">No attachments.</div>
                </div>

                <!-- Rubric Section (hidden for Quiz) -->
                <div v-if="detail.type !== 'Quiz' && detail.id" class="mt-4">
                  <RubricViewer :classwork-id="detail.id" />
                </div>
              </div>
              <div v-if="detail.type !== 'Quiz'" class="col-md-4">
                <!-- Submission Status Card (hidden for Quiz) -->
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">Your Work</h5>
                    
                    <!-- Submitted State -->
                    <div v-if="submission">
                      <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Submitted</strong>
                        <div class="small text-muted mt-1">
                          {{ new Date(submission.submission_time).toLocaleString() }}
                        </div>
                      </div>
                      
                      <!-- Submitted Files (hidden for Quiz) -->
                      <div v-if="detail.type !== 'Quiz' && hasSubmittedFiles" class="mb-3">
                        <label class="form-label fw-bold">Attached Files:</label>
                        <div class="list-group list-group-flush">
                          <div v-for="(file, idx) in submission.files" :key="idx" class="list-group-item px-0">
                            <i class="bi bi-file-earmark me-2"></i>
                            <a href="#" @click.prevent="previewSubmissionFile(file)">
                              {{ file.originalName || file.storedName || 'File' }}
                            </a>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Quiz Answers Summary (shown for Quiz) -->
                      <div v-if="detail.type === 'Quiz'" class="mb-3">
                        <label class="form-label fw-bold">Your Answers:</label>
                        <div class="alert alert-info small">
                          <i class="bi bi-check-circle me-2"></i>
                          Quiz submitted successfully
                        </div>
                      </div>
                      
                      <!-- Grade Display -->
                      <div class="mb-3">
                        <label class="form-label fw-bold">Grade:</label>
                        <div v-if="submission.grade !== null && submission.grade !== undefined" class="text-success fs-5">
                          {{ submission.grade }} / {{ detail.points_possible || 100 }}
                        </div>
                        <div v-else class="text-muted">
                          Not graded yet
                        </div>
                      </div>
                      
                      <!-- Unsubmit Button -->
                      <button class="btn btn-outline-danger w-100" @click="doUnsubmit">
                        <i class="bi bi-x-circle me-2"></i>Unsubmit
                      </button>
                    </div>
                    
                    <!-- Not Submitted State -->
                    <div v-else-if="needsSubmission(detail)">
                      <p class="text-muted small mb-3">{{ detail.type === 'Quiz' ? 'Answer all questions to submit' : 'No submission yet' }}</p>
                      
                      <!-- Submission Form -->
                      <form @submit.prevent="handleSubmission(detail.id)">
                        <!-- File Attachment (hidden for Quiz) -->
                        <div v-if="detail.type !== 'Quiz'" class="mb-3">
                          <label class="form-label">Attach File (Optional)</label>
                          <input class="form-control" type="file" ref="fileRef" />
                        </div>
                        
                        <!-- Comment (optional for Quiz, keep for others) -->
                        <div v-if="detail.type !== 'Quiz'" class="mb-3">
                          <label class="form-label">Private Comment (Optional)</label>
                          <textarea class="form-control" rows="3" ref="commentRef" placeholder="Add a comment for your teacher..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" :disabled="submitting">
                          <span v-if="submitting">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            {{ detail.type === 'Quiz' ? 'Submitting...' : 'Submitting...' }}
                          </span>
                          <span v-else>
                            <i class="bi bi-check-circle me-2"></i>{{ detail.type === 'Quiz' ? 'Done' : 'Submit Work' }}
                          </span>
                        </button>
                      </form>
                    </div>
                    
                    <!-- No Submission Required -->
                    <div v-else>
                      <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        This activity doesn't require a submission
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="hide">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import FilePreviewModal from '@/components/FilePreviewModal.vue'
import RubricViewer from '@/components/RubricViewer.vue'
import { API_BASE } from '@/services/apiBase'

const modalEl = ref(null)
const filePreviewModal = ref(null)
const loading = ref(false)
const detail = ref(null)
const attachments = ref([])
const submission = ref(null)
const submissionSectionHtml = ref('')
const answers = ref({})
const fileRef = ref(null)
const commentRef = ref(null)
const submitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

function escapeHtml(str) { return (str||'').replace(/[&<>"]+/g, (s)=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]||s)) }

function showSuccess(msg) {
  successMessage.value = msg
  setTimeout(() => { successMessage.value = '' }, 3000)
}

function showError(msg) {
  errorMessage.value = msg
  setTimeout(() => { errorMessage.value = '' }, 5000)
}

// Update answer with proper reactivity
function updateAnswer(questionIndex, value) {
  answers.value = {
    ...answers.value,
    [questionIndex]: value
  }
  console.log('Answer updated:', questionIndex, value, answers.value)
}

// Get correct answer for a question
function getCorrectAnswer(question) {
  if (!question) return null
  // Check multiple possible locations for the correct answer
  return question.answer || (question.config && question.config.correctAnswer) || null
}

// Check if student's answer is correct
function isAnswerCorrect(question, questionIndex) {
  const studentAnswer = answers.value[questionIndex]
  const correctAnswer = getCorrectAnswer(question)
  
  if (!studentAnswer || !correctAnswer) return false
  
  // For text-based answers, do case-insensitive comparison
  if (['identification', 'fillblank', 'essay'].includes(question.type)) {
    return String(studentAnswer).trim().toLowerCase() === String(correctAnswer).trim().toLowerCase()
  }
  
  // For multiple choice and true/false, exact match
  return studentAnswer === correctAnswer
}

// Get CSS class for choice highlighting
function getChoiceClass(question, questionIndex, choice) {
  const correctAnswer = getCorrectAnswer(question)
  const studentAnswer = answers.value[questionIndex]
  
  if (choice === correctAnswer) {
    return 'bg-success-subtle border border-success'
  }
  
  if (choice === studentAnswer && choice !== correctAnswer) {
    return 'bg-danger-subtle border border-danger'
  }
  
  return ''
}

// Get question status class
function getQuestionStatus(question, questionIndex) {
  if (isAnswerCorrect(question, questionIndex)) {
    return 'border-success'
  }
  return 'border-danger'
}

function normalizeAttachments(cw){
  const BACKEND = API_BASE || ''
  let arr = []
  if (!cw) return []
  // Try common locations and shapes for material files
  if (Array.isArray(cw.materialFiles)) arr = cw.materialFiles
  else if (Array.isArray(cw.materials)) arr = cw.materials
  else if (Array.isArray(cw.material)) arr = cw.material
  else if (cw.materialFile) arr = [cw.materialFile]
  else if (cw.material) {
    if (typeof cw.material === 'string') arr = [{ url: cw.material }]
    else arr = [cw.material]
  }
  // try parsing extra_json for legacy payloads
  if ((!arr || !arr.length) && cw.extra_json) {
    try {
      const ex = typeof cw.extra_json === 'string' ? JSON.parse(cw.extra_json) : cw.extra_json
      if (Array.isArray(ex.materialFiles)) arr = ex.materialFiles
      else if (Array.isArray(ex.materials)) arr = ex.materials
      else if (ex.materialFile) arr = [ex.materialFile]
    } catch(e) { /* ignore parse errors */ }
  }

  return (arr||[]).map(f => {
    const name = f.originalName || f.original_name || f.fileName || f.file_name || f.name || f.storedName || f.stored_name || f.filename || 'file'
    // possible url candidates in different APIs
    const urlCandidates = [f.url, f.fileUrl, f.file_url, f.downloadUrl, f.download_url, f.path, f.filePath, f.file_path]
    let url = urlCandidates.find(u => u)
    if (url && typeof url === 'string') {
      url = String(url)
      if (/^https?:\/\//i.test(url)) return { name, url }
      // relative path starting with slash
      if (url.startsWith('/')) return { name, url: BACKEND.replace(/\/$/, '') + url }
      // otherwise treat as a relative stored name
      return { name, url: BACKEND.replace(/\/$/, '') + '/' + encodeURI(url) }
    }
    // fallback to storedName/fileName
    const stored = f.storedName || f.stored_name || f.stored || f.fileName || f.file_name || f.file
    if (stored) return { name, url: BACKEND.replace(/\/$/, '') + '/uploads/' + encodeURIComponent(stored) }
    // last fallback: attempt to use originalName as file name
    return { name, url: BACKEND.replace(/\/$/, '') + '/uploads/' + encodeURIComponent(String(name)) }
  })
}

function buildQuestionOptions(question, questionIndex){
  const cfg = question.config || {}
  if(question.type === 'multiple' && Array.isArray(cfg.choices)){
    return `<div class="list-group">${cfg.choices.map((c, idx)=>`<div class="list-group-item"><input class="form-check-input me-2" type="radio" disabled> ${escapeHtml(c)}</div>`).join('')}</div><small class="text-muted mt-2 d-block"><i class="bi bi-info-circle"></i> Submit your work to answer this question</small>`
  }
  if(question.type === 'truefalse'){
    return `<div class="list-group"><div class="list-group-item">True</div><div class="list-group-item">False</div></div>`
  }
  return '<p class="text-muted">Question preview not available</p>'
}

function buildSubmissionSectionHtml(cw, sub){
  const t = String((cw.type||'')).toLowerCase()
  if(t === 'lesson' || t === 'attendance'){
    return `<div class="card"><div class="card-body"><h5 class="card-title">This item doesn't require a submission</h5><p class="text-muted small mb-0">No submission needed</p></div></div>`
  }
  if(sub){
    return `<div class="card"><div class="card-body"><h5 class="card-title">Your Work</h5><p class="text-success">Submitted on ${new Date(sub.submission_time).toLocaleString()}</p>${(Array.isArray(sub.files)&&sub.files.length)? sub.files.map(f=>{ const url = f.url||('/uploads/'+(f.storedName||'')); const name = f.originalName||f.storedName||'file'; const safeUrl = String(url).replace(/'/g, "%27"); const safeName = String(name).replace(/'/g, "%27"); return `<a class="btn btn-sm btn-outline-secondary me-1 mb-1" href="#" onclick="window.openFilePreview('${safeUrl}','${safeName}'); return false;">${escapeHtml(name)}</a>` }).join('') : '<span class="text-muted small">No files attached.</span>'}<p class="mt-2">Grade: ${sub.grade !== null && sub.grade !== undefined ? sub.grade : 'Not graded yet'}</p></div></div>`
  }
  // When no submission exists, the actual submission form is rendered by Vue template (not injected)
  return `<div class="card"><div class="card-body"><h5 class="card-title">Your Work</h5><p class="text-muted small mb-0">No submission yet. Use the form below to attach your work.</p></div></div>`
}

const detailDescription = computed(()=>{
  if(!detail.value) return '<p class="text-muted">No description.</p>'
  return detail.value.description || '<p class="text-muted">No description.</p>'
})

// Check how many questions are answered
const answeredCount = computed(() => {
  if (!detail.value || detail.value.type !== 'Quiz' || !detail.value.quiz || !detail.value.quiz.questions) {
    return 0
  }
  let count = 0
  detail.value.quiz.questions.forEach((q, idx) => {
    const ans = answers.value[idx]
    if (ans !== undefined && ans !== null && ans !== '') {
      count++
    }
  })
  return count
})

// Check if all questions are answered
const allQuestionsAnswered = computed(() => {
  if (!detail.value || detail.value.type !== 'Quiz' || !detail.value.quiz || !detail.value.quiz.questions) {
    return true // Not a quiz, so no validation needed
  }
  const totalQuestions = detail.value.quiz.questions.length
  return answeredCount.value === totalQuestions
})

const computedDueDisplay = computed(()=>{
  if(!detail.value) return 'No due date'
  const raw = detail.value.due_at || detail.value.due || detail.value.dueDisplay || detail.value.dueDate
  if(!raw) return 'No due date'
  try{
    const d = new Date(raw)
    if(isNaN(d)) return String(raw)
    return d.toLocaleString()
  }catch(e){ return String(raw) }
})

const isOverdue = computed(()=>{
  if(!detail.value) return false
  const raw = detail.value.due_at || detail.value.due || detail.value.dueDate
  if(!raw) return false
  const d = new Date(raw)
  if(isNaN(d)) return false
  return Date.now() > d.getTime()
})

const computedDueStatus = computed(()=>{
  if(!detail.value) return ''
  const raw = detail.value.due_at || detail.value.due || detail.value.dueDate
  if(!raw) return ''
  const d = new Date(raw)
  if(isNaN(d)) return ''
  const delta = d.getTime() - Date.now()
  const abs = Math.abs(delta)
  const days = Math.floor(abs / (1000*60*60*24))
  const hours = Math.floor((abs % (1000*60*60*24)) / (1000*60*60))
  if(delta > 0){
    if(days > 0) return `Due in ${days} day${days>1?'s':''}`
    if(hours > 0) return `Due in ${hours} hour${hours>1?'s':''}`
    return 'Due soon'
  } else {
    if(days > 0) return `Overdue by ${days} day${days>1?'s':''}`
    if(hours > 0) return `Overdue by ${hours} hour${hours>1?'s':''}`
    return 'Overdue'
  }
})

const hasSubmittedFiles = computed(() => {
  if (!submission.value) return false
  // Check if submission has files array with at least one file
  return Array.isArray(submission.value.files) && submission.value.files.length > 0
})

const currentLoadingId = ref(null)
const visible = ref(false)

async function loadAndShow(id, forceReload = false){
  if(!id) return
  // if we already have the same detail loaded, just show the modal without reloading
  if(!forceReload && detail.value && String(detail.value.id) === String(id)){
    // if modal already visible, do nothing
    if(visible.value) return
    show()
    return
  }
  // if there's already a load in progress for this id, avoid starting another
  if(currentLoadingId.value && String(currentLoadingId.value) === String(id)) return
  currentLoadingId.value = id
  loading.value = true
  detail.value = null
  attachments.value = []
  // Do not preset submissionSectionHtml to a loading placeholder; the main modal spinner is sufficient
  submissionSectionHtml.value = ''
  const BACKEND = window.BACKEND_API_BASE_URL || 'http://localhost:3000'
  try{
  const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
  const headers = userId ? { 'x-user-id': userId } : {}
  const res = await fetch(`${API_BASE || ''}/api/classwork/${id}`, { headers })
    if(!res.ok) throw new Error('Failed to load classwork details')
    const cw = await res.json()
    detail.value = cw
    attachments.value = normalizeAttachments(cw)
      // initialize answers for quiz questions if present
      if (String((cw.type||'')).toLowerCase() === 'quiz' && cw.quiz && Array.isArray(cw.quiz.questions)) {
        const newAnswers = {}
        cw.quiz.questions.forEach((q, idx) => {
          // Initialize with empty string for better v-model binding
          newAnswers[idx] = ''
        })
        answers.value = newAnswers
        console.log('Initialized quiz answers:', answers.value)
      } else {
        answers.value = {}
      }
    try{
  const subRes = await fetch(`${API_BASE || ''}/api/classwork/${id}/submission/me`, { headers })
      if(subRes.ok){ 
        const sub = await subRes.json()
        if(sub && sub.submitted){ 
          submission.value = sub
          submissionSectionHtml.value = buildSubmissionSectionHtml(cw, sub)
          
          // Load submitted answers back into answers object for display
          if (String((cw.type||'')).toLowerCase() === 'quiz' && sub.answers) {
            try {
              const submittedAnswers = typeof sub.answers === 'string' ? JSON.parse(sub.answers) : sub.answers
              answers.value = { ...answers.value, ...submittedAnswers }
              console.log('Loaded submitted answers:', answers.value)
            } catch(e) {
              console.error('Failed to parse submitted answers:', e)
            }
          }
        }
      }
    }catch(e){}
    if(!submissionSectionHtml.value) submissionSectionHtml.value = buildSubmissionSectionHtml(cw, null)
  }catch(e){
    console.error('loadAndShow failed', e)
    submissionSectionHtml.value = `<p class="text-danger">Error loading details: ${e.message}</p>`
  }finally{
    loading.value = false
    currentLoadingId.value = null
    // show modal
    show()
  }
}

function show(){ if(!modalEl.value) return; const bs = new bootstrap.Modal(modalEl.value); bs.show() }

function hide(){ 
  // Warn if quiz has unanswered questions and not yet submitted
  if (detail.value && String((detail.value.type||'')).toLowerCase() === 'quiz' && !submission.value) {
    const hasAnswers = answeredCount.value > 0;
    if (hasAnswers) {
      const confirmed = confirm(
        `⚠️ Warning: You have answered ${answeredCount.value} question(s) but haven't submitted yet!\n\n` +
        `Your answers will be lost if you close this window.\n\n` +
        `Are you sure you want to close without submitting?`
      );
      if (!confirmed) return; // Don't close if user cancels
    }
  }
  
  if(!modalEl.value) return; 
  const inst = bootstrap.Modal.getInstance(modalEl.value); 
  if(inst) inst.hide(); 
  visible.value = false 
}

// intercept bootstrap modal show to set visible flag
function setupVisibilityHandlers(){
  if(!modalEl.value) return
  modalEl.value.addEventListener && modalEl.value.addEventListener('shown.bs.modal', () => { visible.value = true })
  modalEl.value.addEventListener && modalEl.value.addEventListener('hidden.bs.modal', () => { visible.value = false })
}

async function handleSubmission(classworkId){
  if (submitting.value) return
  
  // Validate quiz completion before submission
  if (String((detail.value?.type||'')).toLowerCase() === 'quiz'){
    if (!allQuestionsAnswered.value) {
      alert(`⚠️ Please answer all ${detail.value.quiz.questions.length} questions before submitting!\n\nYou have answered ${answeredCount.value} out of ${detail.value.quiz.questions.length} questions.`)
      return
    }
    
    // Confirm submission
    const confirmed = confirm(`✅ You have answered all questions!\n\nAre you ready to submit your quiz?\n\n⚠️ Note: Once submitted, you cannot change your answers.`)
    if (!confirmed) return
  }
  
  const BACKEND = API_BASE || ''
  submitting.value = true
  
  try{
    const comment = (commentRef.value && commentRef.value.value) || ''
    const fd = new FormData()
    
    // If this is a quiz, include answers JSON; otherwise include file attachment if present
    if (String((detail.value?.type||'')).toLowerCase() === 'quiz'){
      fd.append('answers', JSON.stringify(answers.value || {}))
      // include optional comment
      if (comment) fd.append('comment', comment)
    } else {
      const fileInput = (fileRef.value && fileRef.value.files ? fileRef.value : null)
      // For non-quiz items, file is optional - just mark as submitted
      if (fileInput && fileInput.files[0]) {
        fd.append('attachments', fileInput.files[0])
      }
      fd.append('comment', comment)
    }
    
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
  const res = await fetch(`${(API_BASE || '')}/api/classwork/${classworkId}/submit`, { method:'POST', body: fd, headers })
    
    if(!res.ok){ 
      let msg = 'Submission failed'
      try {
        const ct = res.headers.get('content-type') || ''
        if (ct.includes('application/json')) {
          const err = await res.json()
          msg = err.message || msg
        } else {
          const txt = await res.text()
          msg = (txt && txt.trim().slice(0,200)) || msg
        }
      } catch(_) {}
      throw new Error(msg)
    }
    
    // Show success message for quiz
    if (String((detail.value?.type||'')).toLowerCase() === 'quiz'){
      alert('✅ Quiz submitted successfully!\n\n🎯 Your answers have been recorded and graded automatically.\n📊 You can now view your score and review the correct answers.')
    } else {
      showSuccess('✅ Work submitted successfully!')
    }
    
    // reload details to refresh submission state (force reload)
    await loadAndShow(classworkId, true)
  } catch(e) { 
    console.error('Submission error', e)
    showError('❌ Error: ' + e.message)
  } finally {
    submitting.value = false
  }
}

async function unsubmitWork(classworkId, submissionId){
  if (submitting.value) return
  
  const BACKEND = API_BASE || ''
  submitting.value = true
  
  try{
    if(!submissionId) throw new Error('No submission id')
    
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
  const res = await fetch(`${(API_BASE || '')}/api/submissions/${submissionId}`, { method:'DELETE', headers })
    
    if(!res.ok) throw new Error('Failed to unsubmit')
    
    showSuccess('✅ Submission removed. You can submit again.')
    
    // reload details
    await loadAndShow(classworkId)
  } catch(e) { 
    console.error('Unsubmit error', e)
    showError('❌ Error: ' + e.message)
  } finally {
    submitting.value = false
  }
}

function submitForm(){
  // For compatibility keep helper to trigger the Vue form programmatically
  const fileEl = fileRef.value
  if(!fileEl) return alert('Submission form not available')
  // call handleSubmission with the current detail id
  return handleSubmission(detail.value.id)
}

async function doUnsubmit(){
  if(!submission.value) return
  if(!confirm('Are you sure you want to unsubmit?')) return
  await unsubmitWork(detail.value.id, submission.value.id)
}

const needsSubmission = (cw) => { const t = String((cw?.type||'')).toLowerCase(); return !(t === 'lesson' || t === 'attendance') }

function previewFile(url, name) {
  if (filePreviewModal.value && filePreviewModal.value.show) {
    filePreviewModal.value.show(url, name)
  }
}

function previewSubmissionFile(file) {
  const BACKEND = API_BASE || ''
  const url = file.url || (file.storedName ? `${BACKEND}/uploads/${file.storedName}` : null)
  const name = file.originalName || file.storedName || 'file'
  
  if (url && filePreviewModal.value && filePreviewModal.value.show) {
    filePreviewModal.value.show(url, name)
  }
}

defineExpose({ showFor: loadAndShow, hide })

onMounted(()=>{ setupVisibilityHandlers() })
</script>

<style scoped>
.modal-body .list-group-item { display:flex; justify-content:space-between; align-items:center }

/* Quiz Questions Styling */
.quiz-questions-list {
  max-height: 70vh;
  overflow-y: auto;
  padding-right: 10px;
}

.question-card {
  border: 2px solid #e0e0e0;
  transition: all 0.2s ease;
}

.question-card:hover {
  border-color: #0d6efd;
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.question-card .card-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 2px solid #dee2e6;
}

/* Custom scrollbar for quiz questions */
.quiz-questions-list::-webkit-scrollbar {
  width: 8px;
}

.quiz-questions-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.quiz-questions-list::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.quiz-questions-list::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Correct/Incorrect Answer Highlighting */
.bg-success-subtle {
  background-color: #d1e7dd !important;
}

.bg-danger-subtle {
  background-color: #f8d7da !important;
}

.border-success {
  border-color: #198754 !important;
  border-width: 2px !important;
}

.border-danger {
  border-color: #dc3545 !important;
  border-width: 2px !important;
}

.form-check.bg-success-subtle {
  border-left: 4px solid #198754;
}

.form-check.bg-danger-subtle {
  border-left: 4px solid #dc3545;
}
</style>
