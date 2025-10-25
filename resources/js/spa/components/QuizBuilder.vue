<template>
  <div class="quiz-builder">
    <!-- Quiz Summary Header -->
    <div class="alert alert-info mb-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-clipboard-check me-2"></i>
          <strong>Total Questions: {{ state.questions.length }}</strong>
        </div>
        <div>
          <strong>Total Points: {{ totalPoints }}</strong>
        </div>
      </div>
    </div>

    <!-- Questions List -->
    <div v-for="(q, idx) in state.questions" :key="idx" class="question-card card mb-3 shadow-sm">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="bi bi-question-circle me-2"></i>
          Question {{ idx + 1 }}
        </h6>
        <button 
          type="button"
          class="btn btn-sm btn-outline-light" 
          @click="removeQuestion(idx)"
          :disabled="state.questions.length === 1">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      
      <div class="card-body">
        <!-- Question Text -->
        <div class="mb-3">
          <label class="form-label fw-bold">Question Text *</label>
          <textarea 
            v-model="q.text" 
            class="form-control" 
            rows="3"
            placeholder="Enter your question here..."
            @input="update"></textarea>
        </div>

        <!-- Question Type and Points Row -->
        <div class="row mb-3">
          <div class="col-md-8">
            <label class="form-label fw-bold">Question Type *</label>
            <select v-model="q.type" class="form-select" @change="update">
              <option value="multiple">📝 Multiple Choice</option>
              <option value="truefalse">✓ True or False</option>
              <option value="identification">📋 Identification</option>
              <option value="enumeration">📊 Enumeration</option>
              <option value="essay">✍️ Essay</option>
              <option value="fillblank">📝 Fill in the Blanks</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Points *</label>
            <input 
              type="number" 
              v-model.number="q.points" 
              class="form-control" 
              min="1"
              @input="update">
          </div>
        </div>

        <!-- Answer Options Based on Type -->
        <div class="answer-section">
          <label class="form-label fw-bold">Answer/Choices</label>
          
          <!-- Multiple Choice -->
          <div v-if="q.type === 'multiple'" class="multiple-choice">
            <div v-for="n in 4" :key="n" class="input-group mb-2">
              <span class="input-group-text">{{ String.fromCharCode(64 + n) }}</span>
              <input 
                v-model="q.choices[n-1]" 
                class="form-control" 
                :placeholder="`Choice ${String.fromCharCode(64 + n)}`"
                @input="update">
            </div>
            <div class="mt-2">
              <label class="form-label small text-muted">Correct Answer *</label>
              <select v-model="q.answer" class="form-select form-select-sm" @change="update">
                <option value="">-- Select Correct Answer --</option>
                <option v-for="n in 4" :key="n" :value="q.choices[n-1]">
                  {{ String.fromCharCode(64 + n) }}: {{ q.choices[n-1] || '(empty)' }}
                </option>
              </select>
            </div>
          </div>

          <!-- True or False -->
          <div v-else-if="q.type === 'truefalse'">
            <select v-model="q.answer" class="form-select" @change="update">
              <option value="">-- Select Answer --</option>
              <option value="True">✓ True</option>
              <option value="False">✗ False</option>
            </select>
          </div>

          <!-- Enumeration -->
          <div v-else-if="q.type === 'enumeration'">
            <textarea 
              v-model="q.answer" 
              class="form-control" 
              rows="4"
              placeholder="Enter correct answers, one per line&#10;Example:&#10;Apple&#10;Banana&#10;Orange"
              @input="update"></textarea>
            <small class="text-muted">Enter one answer per line</small>
          </div>

          <!-- Other Types (Identification, Essay, Fill in Blanks) -->
          <div v-else>
            <input 
              v-model="q.answer" 
              class="form-control" 
              :placeholder="getPlaceholder(q.type)"
              @input="update">
            <small v-if="q.type === 'essay'" class="text-muted">Optional: Provide sample answer or grading guide</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Question Button -->
    <div class="text-center">
      <button type="button" class="btn btn-success" @click="addQuestion">
        <i class="bi bi-plus-circle me-2"></i>Add Another Question
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'

const props = defineProps({ 
  modelValue: { 
    type: Array, 
    default: () => [] 
  } 
})

const emit = defineEmits(['update:modelValue'])

const state = reactive({ 
  questions: props.modelValue && props.modelValue.length 
    ? [...props.modelValue]
    : [{ text: '', type: 'multiple', points: 1, choices: ['', '', '', ''], answer: '' }] 
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newVal) => {
  if (newVal && Array.isArray(newVal) && newVal.length > 0) {
    state.questions = [...newVal]
  }
}, { deep: true })

const totalPoints = computed(() => {
  return state.questions.reduce((sum, q) => sum + (q.points || 0), 0)
})

function addQuestion() { 
  state.questions.push({ 
    text: '', 
    type: 'multiple', 
    points: 1, 
    choices: ['', '', '', ''], 
    answer: '' 
  })
  update()
}

function removeQuestion(i) { 
  if (state.questions.length > 1) {
    state.questions.splice(i, 1)
    update()
  }
}

function update() { 
  emit('update:modelValue', state.questions) 
}

function getPlaceholder(type) {
  const placeholders = {
    'identification': 'Enter the correct answer',
    'essay': 'Optional: Enter sample answer or grading rubric',
    'fillblank': 'Enter the correct answer'
  }
  return placeholders[type] || 'Enter answer'
}

// sync on mount
update()
</script>

<style scoped>
.quiz-builder {
  max-height: 60vh;
  overflow-y: auto;
  padding-right: 8px;
}

.question-card {
  border: 2px solid #e0e0e0;
  transition: all 0.3s ease;
}

.question-card:hover {
  border-color: #0d6efd;
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.card-header {
  background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
}

.answer-section {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

.multiple-choice .input-group-text {
  background: #0d6efd;
  color: white;
  font-weight: 600;
  min-width: 45px;
  justify-content: center;
}

.form-label.fw-bold {
  color: #495057;
  font-size: 0.9rem;
}

/* Custom scrollbar */
.quiz-builder::-webkit-scrollbar {
  width: 8px;
}

.quiz-builder::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.quiz-builder::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.quiz-builder::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
