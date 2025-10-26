<template>
  <div class="gradebook-container">
    <!-- Period Percentage Configuration -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Grading Period Weights</h5>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">Midterm Percentage</label>
                <div class="input-group">
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="midtermPercentage" 
                    min="0" 
                    max="100"
                    @blur="validatePeriodPercentages"
                  />
                  <span class="input-group-text">%</span>
                </div>
              </div>
              <div class="col-6">
                <label class="form-label">Finals Percentage</label>
                <div class="input-group">
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="finalsPercentage" 
                    min="0" 
                    max="100"
                    @blur="validatePeriodPercentages"
                  />
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
            <div v-if="periodPercentageError" class="alert alert-warning mt-2 mb-0 py-2">
              <i class="bi bi-exclamation-triangle me-2"></i>{{ periodPercentageError }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Period Selection Cards -->
    <div class="row mb-4">
      <div class="col-md-6 mb-3">
        <div 
          class="period-card midterm-card" 
          :class="{ active: activePeriod === 'midterm' }"
          @click="togglePeriod('midterm')"
        >
          <div class="period-icon">🟩</div>
          <h4>Midterm</h4>
          <p class="mb-0">{{ midtermPercentage }}% of Final Grade</p>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div 
          class="period-card finals-card" 
          :class="{ active: activePeriod === 'finals' }"
          @click="togglePeriod('finals')"
        >
          <div class="period-icon">🟦</div>
          <h4>Finals</h4>
          <p class="mb-0">{{ finalsPercentage }}% of Final Grade</p>
        </div>
      </div>
    </div>

    <!-- Grading Tables (shown when a period is active) -->
    <div v-if="activePeriod" class="grading-section">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>{{ activePeriod === 'midterm' ? 'Midterm' : 'Finals' }} Grading Tables</h4>
        <button class="btn btn-primary btn-sm" @click="addNewTable">
          <i class="bi bi-plus-circle me-1"></i> Add Custom Table
        </button>
      </div>

      <!-- Total Percentage Warning -->
      <div v-if="tablesTotalPercentage > 100" class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Total grading percentage exceeds 100%. Current total: {{ tablesTotalPercentage.toFixed(1) }}%. Please adjust your table weights.
      </div>

      <!-- Default and Custom Tables -->
      <div v-for="table in currentTables" :key="table.id" class="mb-4">
        <div class="table-header-controls">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-3">
              <input 
                v-if="table.editable"
                type="text" 
                class="form-control form-control-sm table-name-input" 
                v-model="table.name"
              />
              <h5 v-else class="mb-0">{{ table.name }}</h5>
              <div class="input-group input-group-sm" style="width: 150px;">
                <input 
                  type="number" 
                  class="form-control" 
                  v-model.number="table.percentage" 
                  min="0" 
                  max="100"
                  @blur="validateTablePercentages"
                />
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="btn-group btn-group-sm">
              <button 
                class="btn btn-outline-primary" 
                @click="addColumn(table)"
                :title="`Add column to ${table.name}`"
              >
                <i class="bi bi-plus"></i> Add Column
              </button>
              <button 
                v-if="!table.isDefault"
                class="btn btn-outline-danger" 
                @click="removeTable(table.id)"
                title="Remove this table"
              >
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
          <div v-if="getTableTotalPercentage(table) > table.percentage" class="alert alert-warning py-2">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Total percentage for {{ table.name }} exceeds {{ table.percentage }}%. Adjust column percentages.
          </div>
        </div>

        <!-- Grading Table -->
        <div class="table-responsive gradebook-table-wrapper">
          <table class="table table-bordered gradebook-table">
            <thead>
              <!-- Main Column Headers -->
              <tr class="table-primary">
                <th rowspan="2" class="student-name-column">Student Name</th>
                <th 
                  v-for="col in table.columns" 
                  :key="col.id"
                  :colspan="col.subcolumns?.length || 1"
                  class="main-column-header"
                >
                  <div class="d-flex justify-content-between align-items-center">
                    <input 
                      v-if="!col.fixed"
                      type="text" 
                      class="form-control form-control-sm column-name-input" 
                      v-model="col.name"
                    />
                    <span v-else>{{ col.name }}</span>
                    <div class="d-flex align-items-center gap-2">
                      <div class="input-group input-group-sm" style="width: 100px;">
                        <input 
                          type="number" 
                          class="form-control" 
                          v-model.number="col.percentage" 
                          min="0" 
                          :max="table.percentage"
                          @blur="validateTablePercentages"
                        />
                        <span class="input-group-text">%</span>
                      </div>
                      <button 
                        class="btn btn-sm btn-outline-primary" 
                        @click="addSubcolumn(table, col)"
                        title="Add subcolumn"
                      >
                        <i class="bi bi-plus-circle"></i>
                      </button>
                      <button 
                        v-if="!col.fixed"
                        class="btn btn-sm btn-outline-danger" 
                        @click="removeColumn(table, col.id)"
                        title="Remove column"
                      >
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </div>
                  </div>
                </th>
                <th rowspan="2" class="total-column">Total ({{ table.percentage }}%)</th>
              </tr>
              <!-- Subcolumn Headers -->
              <tr class="table-secondary">
                <template v-for="col in table.columns" :key="'sub-' + col.id">
                  <th 
                    v-for="subcol in (col.subcolumns || [])" 
                    :key="subcol.id"
                    class="subcolumn-header"
                  >
                    <div class="d-flex justify-content-between align-items-center">
                      <input 
                        v-if="!subcol.autoGenerated"
                        type="text" 
                        class="form-control form-control-sm subcolumn-name-input" 
                        v-model="subcol.name"
                      />
                      <span v-else class="auto-generated-label">{{ subcol.name }}</span>
                      <button 
                        v-if="!subcol.autoGenerated"
                        class="btn btn-sm btn-link text-danger p-0" 
                        @click="removeSubcolumn(table, col.id, subcol.id)"
                        title="Remove subcolumn"
                      >
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </div>
                  </th>
                </template>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="student.id">
                <td class="student-name-column">{{ getStudentName(student) }}</td>
                <template v-for="col in table.columns" :key="'grade-' + col.id">
                  <td 
                    v-for="subcol in (col.subcolumns || [])" 
                    :key="'grade-' + subcol.id"
                    class="grade-cell"
                  >
                    <input 
                      type="number" 
                      class="form-control form-control-sm grade-input" 
                      :value="getGrade(student.id, table.id, col.id, subcol.id)"
                      min="0" 
                      max="100"
                      @input="updateGrade(student.id, table.id, col.id, subcol.id, $event)"
                    />
                  </td>
                </template>
                <td class="total-column">
                  <strong>{{ calculateStudentTableTotal(student.id, table).toFixed(2) }}</strong>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Computation Summary Table -->
      <div class="computation-summary mt-5">
        <h4 class="mb-3">{{ activePeriod === 'midterm' ? 'Midterm' : 'Finals' }} Grade Summary</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead class="table-dark">
              <tr>
                <th>Student Name</th>
                <th v-for="table in currentTables" :key="'summary-' + table.id">
                  {{ table.name }} ({{ table.percentage }}%)
                </th>
                <th>{{ activePeriod === 'midterm' ? 'Midterm' : 'Finals' }} Grade (100%)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="'summary-' + student.id">
                <td><strong>{{ getStudentName(student) }}</strong></td>
                <td v-for="table in currentTables" :key="'summary-val-' + table.id" class="text-end">
                  {{ calculateStudentTableTotal(student.id, table).toFixed(2) }}
                </td>
                <td class="text-end">
                  <strong class="text-primary">{{ calculateStudentPeriodGrade(student.id).toFixed(2) }}</strong>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Save Button -->
      <div class="text-end mt-4">
        <button class="btn btn-success btn-lg" @click="saveGradebook">
          <i class="bi bi-save me-2"></i> Save Gradebook
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const courseId = computed(() => route.params.courseId)

// Period configuration
const midtermPercentage = ref(50)
const finalsPercentage = ref(50)
const periodPercentageError = ref('')
const activePeriod = ref(null)

// Students data
const students = ref([])

// Gradebook data structure
const midtermTables = ref([])
const finalsTables = ref([])

// Grades storage: { studentId: { tableId: { columnId: { subcolumnId: grade } } } }
const grades = ref({})

// Computed: Current tables based on active period
const currentTables = computed(() => {
  return activePeriod.value === 'midterm' ? midtermTables.value : finalsTables.value
})

// Computed: Total percentage across all tables
const tablesTotalPercentage = computed(() => {
  return currentTables.value.reduce((sum, table) => sum + (table.percentage || 0), 0)
})

// Validate period percentages
function validatePeriodPercentages() {
  const total = midtermPercentage.value + finalsPercentage.value
  if (total !== 100) {
    periodPercentageError.value = `Warning: Midterm + Finals should equal 100%. Current total: ${total}%`
  } else {
    periodPercentageError.value = ''
  }
}

// Validate table percentages
function validateTablePercentages() {
  // This is called when table or column percentages change
  // The warnings are shown in the template using computed values
}

// Get table total percentage (sum of all column percentages)
function getTableTotalPercentage(table) {
  return table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0)
}

// Toggle period
function togglePeriod(period) {
  if (activePeriod.value === period) {
    activePeriod.value = null
  } else {
    activePeriod.value = period
    ensureDefaultTables()
  }
}

// Ensure default tables exist for the active period
function ensureDefaultTables() {
  const tables = activePeriod.value === 'midterm' ? midtermTables.value : finalsTables.value
  
  if (tables.length === 0) {
    // Create default tables
    const defaults = [
      {
        id: generateId(),
        name: 'Asynchronous',
        percentage: 35,
        isDefault: true,
        editable: false,
        columns: [
          {
            id: generateId(),
            name: 'Written Works',
            percentage: 35,
            fixed: true,
            subcolumns: []
          }
        ]
      },
      {
        id: generateId(),
        name: 'Synchronous',
        percentage: 35,
        isDefault: true,
        editable: false,
        columns: []
      },
      {
        id: generateId(),
        name: 'Major Exam',
        percentage: 30,
        isDefault: true,
        editable: false,
        columns: [
          {
            id: generateId(),
            name: 'Exam',
            percentage: 30,
            fixed: false,
            subcolumns: [
              {
                id: generateId(),
                name: `${activePeriod.value === 'midterm' ? 'Midterm' : 'Finals'} Exam 1`,
                autoGenerated: false
              }
            ]
          }
        ]
      }
    ]
    
    if (activePeriod.value === 'midterm') {
      midtermTables.value = defaults
    } else {
      finalsTables.value = defaults
    }
  }
}

// Add new custom table
function addNewTable() {
  const newTable = {
    id: generateId(),
    name: 'New Table',
    percentage: 0,
    isDefault: false,
    editable: true,
    columns: []
  }
  currentTables.value.push(newTable)
}

// Remove table
function removeTable(tableId) {
  const tables = activePeriod.value === 'midterm' ? midtermTables.value : finalsTables.value
  const index = tables.findIndex(t => t.id === tableId)
  if (index !== -1) {
    tables.splice(index, 1)
  }
}

// Add column to table
function addColumn(table) {
  const newColumn = {
    id: generateId(),
    name: 'New Column',
    percentage: 0,
    fixed: false,
    subcolumns: [
      {
        id: generateId(),
        name: 'Item 1',
        autoGenerated: false
      }
    ]
  }
  table.columns.push(newColumn)
}

// Remove column
function removeColumn(table, columnId) {
  const index = table.columns.findIndex(c => c.id === columnId)
  if (index !== -1) {
    table.columns.splice(index, 1)
  }
}

// Add subcolumn
function addSubcolumn(table, column) {
  if (!column.subcolumns) {
    column.subcolumns = []
  }
  const newSubcolumn = {
    id: generateId(),
    name: `Item ${column.subcolumns.length + 1}`,
    autoGenerated: false
  }
  column.subcolumns.push(newSubcolumn)
}

// Remove subcolumn
function removeSubcolumn(table, columnId, subcolumnId) {
  const column = table.columns.find(c => c.id === columnId)
  if (column && column.subcolumns) {
    const index = column.subcolumns.findIndex(s => s.id === subcolumnId)
    if (index !== -1) {
      column.subcolumns.splice(index, 1)
    }
  }
}

// Get grade value
function getGrade(studentId, tableId, columnId, subcolumnId) {
  return grades.value[studentId]?.[tableId]?.[columnId]?.[subcolumnId] || null
}

// Update grade
function updateGrade(studentId, tableId, columnId, subcolumnId, event) {
  const value = parseFloat(event.target.value) || null
  
  if (!grades.value[studentId]) grades.value[studentId] = {}
  if (!grades.value[studentId][tableId]) grades.value[studentId][tableId] = {}
  if (!grades.value[studentId][tableId][columnId]) grades.value[studentId][tableId][columnId] = {}
  
  grades.value[studentId][tableId][columnId][subcolumnId] = value
}

// Calculate student's total for a specific table
function calculateStudentTableTotal(studentId, table) {
  let total = 0
  
  table.columns.forEach(col => {
    const subcolumns = col.subcolumns || []
    if (subcolumns.length === 0) return
    
    // Calculate average of subcolumns
    let sum = 0
    let count = 0
    subcolumns.forEach(subcol => {
      const grade = getGrade(studentId, table.id, col.id, subcol.id)
      if (grade !== null && grade !== undefined) {
        sum += grade
        count++
      }
    })
    
    const average = count > 0 ? sum / count : 0
    const weighted = (average / 100) * (col.percentage || 0)
    total += weighted
  })
  
  return total
}

// Calculate student's total grade for the active period
function calculateStudentPeriodGrade(studentId) {
  let total = 0
  
  currentTables.value.forEach(table => {
    const tableTotal = calculateStudentTableTotal(studentId, table)
    total += tableTotal
  })
  
  return total
}

// Get student name
function getStudentName(student) {
  return `${student.first_name || ''} ${student.last_name || ''}`.trim() || student.email || 'Unknown Student'
}

// Generate unique ID
function generateId() {
  return Date.now() + Math.random().toString(36).substr(2, 9)
}

// Load students for the course
async function loadStudents() {
  try {
    const response = await fetch(`/api/classes/${courseId.value}/people`)
    if (response.ok) {
      const data = await response.json()
      students.value = data.students || []
    }
  } catch (error) {
    console.error('Error loading students:', error)
  }
}

// Load classwork for auto-generating Written Works subcolumns
async function loadClasswork() {
  try {
    const response = await fetch(`/api/classes/${courseId.value}/classwork`)
    if (response.ok) {
      const classwork = await response.json()
      autoGenerateWrittenWorksSubcolumns(classwork)
    }
  } catch (error) {
    console.error('Error loading classwork:', error)
  }
}

// Auto-generate Written Works subcolumns from classwork
function autoGenerateWrittenWorksSubcolumns(classwork) {
  // Find Written Works column in Asynchronous table for both periods
  const periods = [
    { tables: midtermTables.value, period: 'midterm' },
    { tables: finalsTables.value, period: 'finals' }
  ]
  
  periods.forEach(({ tables, period }) => {
    const asyncTable = tables.find(t => t.name === 'Asynchronous')
    if (!asyncTable) return
    
    const writtenWorksCol = asyncTable.columns.find(c => c.name === 'Written Works')
    if (!writtenWorksCol) return
    
    // Filter classwork by type (Quiz, Assignment, Activity)
    const relevantWork = classwork.filter(cw => 
      ['Quiz', 'Assignment', 'Activity'].includes(cw.type)
    )
    
    // Remove existing auto-generated subcolumns
    writtenWorksCol.subcolumns = writtenWorksCol.subcolumns?.filter(s => !s.autoGenerated) || []
    
    // Add new auto-generated subcolumns
    relevantWork.forEach(work => {
      writtenWorksCol.subcolumns.push({
        id: `auto-${work.id}`,
        name: work.title,
        autoGenerated: true,
        classworkId: work.id
      })
    })
  })
}

// Load gradebook data from server
async function loadGradebook() {
  try {
    const response = await fetch(`/api/classes/${courseId.value}/gradebook`)
    if (response.ok) {
      const data = await response.json()
      if (data.gradebook) {
        // Restore saved gradebook structure
        midtermPercentage.value = data.gradebook.midtermPercentage || 50
        finalsPercentage.value = data.gradebook.finalsPercentage || 50
        midtermTables.value = data.gradebook.midtermTables || []
        finalsTables.value = data.gradebook.finalsTables || []
        grades.value = data.gradebook.grades || {}
      }
    }
  } catch (error) {
    console.error('Error loading gradebook:', error)
  }
}

// Save gradebook data
async function saveGradebook() {
  const payload = {
    midtermPercentage: midtermPercentage.value,
    finalsPercentage: finalsPercentage.value,
    midtermTables: midtermTables.value,
    finalsTables: finalsTables.value,
    grades: grades.value
  }
  
  try {
    const response = await fetch(`/api/classes/${courseId.value}/gradebook`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    
    if (response.ok) {
      alert('Gradebook saved successfully!')
    } else {
      alert('Error saving gradebook')
    }
  } catch (error) {
    console.error('Error saving gradebook:', error)
    alert('Error saving gradebook')
  }
}

// Initialize
onMounted(async () => {
  await loadStudents()
  await loadGradebook()
  await loadClasswork()
})

// Watch for classwork changes (optional: implement real-time updates)
watch(() => route.query.refresh, () => {
  loadClasswork()
})
</script>

<style scoped>
.gradebook-container {
  padding: 1rem;
}

/* Period Cards */
.period-card {
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 3px solid transparent;
  background: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.period-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.period-card.active {
  border-color: #0d6efd;
  box-shadow: 0 4px 20px rgba(13,110,253,0.3);
}

.midterm-card.active {
  border-color: #28a745;
}

.finals-card.active {
  border-color: #007bff;
}

.period-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.period-card h4 {
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.period-card p {
  color: #6c757d;
  font-size: 0.9rem;
}

/* Table Controls */
.table-header-controls {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px 8px 0 0;
  border: 1px solid #dee2e6;
  border-bottom: none;
}

.table-name-input {
  max-width: 250px;
  font-weight: 600;
}

/* Gradebook Table */
.gradebook-table-wrapper {
  border-radius: 0 0 8px 8px;
  overflow-x: auto;
}

.gradebook-table {
  margin-bottom: 0;
  font-size: 0.9rem;
}

.gradebook-table th,
.gradebook-table td {
  vertical-align: middle;
  padding: 0.5rem;
}

.student-name-column {
  min-width: 180px;
  font-weight: 600;
  position: sticky;
  left: 0;
  background: white;
  z-index: 10;
}

.main-column-header {
  background: #e7f3ff;
  min-width: 150px;
}

.subcolumn-header {
  background: #f0f0f0;
  min-width: 120px;
  font-size: 0.85rem;
}

.total-column {
  background: #fff3cd;
  font-weight: 600;
  min-width: 120px;
  text-align: center;
}

.column-name-input,
.subcolumn-name-input {
  border: 1px dashed #6c757d;
  background: transparent;
  font-weight: 600;
}

.grade-cell {
  padding: 0.25rem !important;
}

.grade-input {
  text-align: center;
  font-size: 0.85rem;
  padding: 0.25rem;
}

.auto-generated-label {
  font-size: 0.85rem;
  font-style: italic;
  color: #6c757d;
}

/* Computation Summary */
.computation-summary {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
}

.computation-summary .table {
  background: white;
}

.computation-summary th {
  font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .period-card {
    padding: 1.5rem;
  }
  
  .period-icon {
    font-size: 2rem;
  }
  
  .gradebook-table {
    font-size: 0.8rem;
  }
  
  .student-name-column {
    min-width: 150px;
  }
}
</style>
