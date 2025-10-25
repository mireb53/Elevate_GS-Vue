<template>
  <div class="container-fluid mobile-optimized py-3">
    <!-- Mobile-optimized header -->
    <div class="page-header mb-3">
      <div class="header-title-section">
        <h3 class="mb-2 page-title">
          <span class="d-block d-md-inline">Midterm Sheet</span>
          <span class="d-none d-md-inline"> — </span>
          <span class="d-block d-md-inline text-muted fs-6">{{ classInfo?.class_name || 'Class' }}</span>
        </h3>
        <div class="badges-container d-flex flex-wrap gap-2 align-items-center">
          <small v-if="classInfo && classInfo.midterm_status">
            <span :class="['badge', midtermBadgeClass]">{{ classInfo.midterm_status }}</span>
          </small>
          <small v-if="midtermLocked">
            <span class="badge bg-danger">Locked</span>
          </small>
          <!-- PWA Status Indicators -->
          <small>
            <span v-if="!isOnline" class="badge bg-warning text-dark">
              <i class="bi bi-wifi-off"></i><span class="d-none d-sm-inline ms-1">Offline</span>
            </span>
            <span v-else class="badge bg-success">
              <i class="bi bi-wifi"></i><span class="d-none d-sm-inline ms-1">Online</span>
            </span>
          </small>
          <small v-if="pendingEditCount > 0">
            <span class="badge bg-info">
              <i class="bi bi-cloud-upload"></i> {{ pendingEditCount }}
            </span>
          </small>
        </div>
        <div v-if="midtermLocked" class="text-muted small mt-2">
          This midterm sheet is locked for automatic fetching. Manual edits and columns are still allowed.
        </div>
      </div>
      
      <!-- Mobile-optimized action buttons -->
      <div class="header-actions mt-3 mt-md-0">
        <!-- Mobile: Show dropdown menu -->
        <div class="d-md-none">
          <div class="btn-group w-100">
            <button v-if="pendingEditCount > 0" class="btn btn-info" @click="manualSync" :disabled="isSyncing">
              <span v-if="isSyncing" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-arrow-repeat"></i>
              <span class="ms-1">Sync ({{ pendingEditCount }})</span>
            </button>
            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="bi bi-three-dots-vertical"></i> Actions
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" @click.prevent="refresh">
                <i class="bi bi-arrow-clockwise me-2"></i>Refresh
              </a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="computeMidterm">
                <i class="bi bi-calculator me-2"></i>Compute Midterm
              </a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="markMidtermDone">
                <i class="bi bi-check-circle me-2"></i>Mark Done
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" @click.prevent="downloadExcel">
                <i class="bi bi-download me-2"></i>Download Excel
              </a></li>
            </ul>
          </div>
        </div>
        
        <!-- Desktop: Show all buttons -->
        <div class="d-none d-md-flex gap-2">
          <button v-if="pendingEditCount > 0" class="btn btn-info" @click="manualSync" :disabled="isSyncing">
            <span v-if="isSyncing" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-arrow-repeat me-1"></i>
            Sync Now
          </button>
          <button class="btn btn-primary" @click="refresh">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
          </button>
          <button class="btn btn-success" @click="computeMidterm" :disabled="computing">
            <span v-if="computing" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-calculator me-1"></i>
            {{ computing ? 'Computing...' : 'Compute' }}
          </button>
          <button class="btn btn-warning" @click="markMidtermDone">
            <i class="bi bi-check-circle me-1"></i>Mark Done
          </button>
          <button class="btn btn-outline-secondary" @click="downloadExcel">
            <i class="bi bi-download me-1"></i>Download
          </button>
        </div>
      </div>
    </div>

    <div v-if="nonFatalError" class="alert alert-danger">{{ nonFatalError }}</div>
    <div v-if="nonFatalError && nonFatalError.includes('(404)')" class="mb-3">
      <button class="btn btn-primary" @click="createSheet">Create Midterm Sheet</button>
    </div>

    <div v-if="loading" class="text-muted py-4 text-center">
      <div class="spinner-border text-primary me-2" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      Loading midterm sheet...
    </div>
    <div v-else>
      <div v-if="!(columns && columns.length)" class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>No columns yet. Create classwork or add a column.
      </div>
      <div v-if="columns && columns.length && !(rows && rows.length)" class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>No students enrolled yet. Students will appear here once they join the class.
      </div>

      <div v-if="columns && columns.length && rows && rows.length" class="table-responsive spreadsheet-like">
        <div class="alert alert-info alert-sm py-2 mb-2">
          <i class="bi bi-info-circle me-2"></i>
          <strong>Tip:</strong> Click cells in manual columns (attendance, performance) to edit. Auto-synced classwork scores are read-only.
        </div>
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th rowspan="2">#</th>
              <th rowspan="2">Student</th>
              <th v-for="group in filteredGroups" :key="group.name" :colspan="group.columns.length" class="text-center align-middle">{{ group.name }}</th>
              <th rowspan="2">Computed</th>
              <th rowspan="2">Final</th>
            </tr>
            <tr>
              <th v-for="col in (flattenedColumns || [])" :key="col.id" :class="{'attendance-col': col.type === 'attendance'}">
                <div class="d-flex flex-column">
                  <div class="d-flex align-items-center justify-content-center">
                    <strong>{{ col.label }}</strong>
                  </div>
                  <small class="text-muted text-center">{{ col.points_possible ? col.points_possible + ' pts' : '' }}</small>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, idx) in rows" :key="r.rowId">
              <td class="row-number">{{ idx + 1 }}</td>
              <td class="student-name">{{ r.name }}</td>
              <td v-for="(col, ci) in (flattenedColumns || [])" :key="col.id" :class="['cell-container', { 'attendance-col-cell': col.type === 'attendance' }]">
                <!-- Only manual columns are editable, auto_task columns are read-only -->
                <div v-if="col.type === 'auto_task'" class="cell readonly-cell" :title="'Auto-synced from classwork. Points: ' + (col.points_possible || 'N/A')">
                  <span class="cell-value">{{ getCellValue(r.rowId, col.id) || '-' }}</span>
                </div>
                <!-- Exam type: display as "score/total" format -->
                <div v-else-if="col.type === 'exam'" class="cell editable-cell" @click="enableEdit(r.rowId, col.id)" @keydown.enter.prevent="onCellEnter($event, r.rowId, col.id)" :title="'Exam score format: score/total (e.g., 30/50). Points possible: ' + (col.points_possible || 'N/A')">
                  <input v-if="isEditing(r.rowId, col.id)" ref="editInputs" :data-row="r.rowId" :data-col="col.id" :value="getCellValue(r.rowId, col.id)" @input="onCellInput($event, r.rowId, col.id)" @blur="onCellBlur(r.rowId, col.id)" class="form-control form-control-sm" style="min-width:80px" type="text" placeholder="30/50" />
                  <span v-else class="cell-value">
                    <span v-if="getCellValue(r.rowId, col.id) !== ''">{{ getCellValue(r.rowId, col.id) }}</span>
                    <span v-else class="text-muted small">Click to add</span>
                  </span>
                </div>
                <!-- Other types: regular number input -->
                <div v-else class="cell editable-cell" @click="enableEdit(r.rowId, col.id)" @keydown.enter.prevent="onCellEnter($event, r.rowId, col.id)" :title="'Click to edit. Points possible: ' + (col.points_possible || 'N/A')">
                  <input v-if="isEditing(r.rowId, col.id)" ref="editInputs" :data-row="r.rowId" :data-col="col.id" :value="getCellValue(r.rowId, col.id)" @input="onCellInput($event, r.rowId, col.id)" @blur="onCellBlur(r.rowId, col.id)" class="form-control form-control-sm" style="min-width:80px" type="number" step="0.01" :max="col.points_possible || 100" />
                  <span v-else class="cell-value">
                    <span v-if="getCellValue(r.rowId, col.id) !== ''">{{ getCellValue(r.rowId, col.id) }}</span>
                    <span v-else class="text-muted small">Click to add</span>
                  </span>
                </div>
              </td>
              <td :title="getComputationTooltip(r)" style="cursor: help;">
                <span v-if="r.computedMidterm !== null" class="badge bg-primary" style="font-size: 0.95em;">{{ r.computedMidterm }}%</span>
                <span v-else class="text-muted">-</span>
                <!-- Debug: {{ r.computedMidterm }} ({{ typeof r.computedMidterm }}) -->
              </td>
              <td>
                <input :value="getEditableValue(r.rowId, 'teacherOverride')" @input="onEditableInput($event, r.rowId, 'teacherOverride')" @blur="saveRow(r.rowId)" class="form-control form-control-sm" style="width:100px" :placeholder="r.computedMidterm || 'Override'" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-3 d-flex gap-2 align-items-center">
        <button class="btn btn-outline-primary" @click="openAddColumn">
          <i class="bi bi-plus-circle me-1"></i>Add Column
        </button>
        <span class="text-muted small">Add attendance, performance, or other manual columns</span>
      </div>

      <!-- Add Column Modal -->
      <div class="modal show" tabindex="-1" v-if="showAddColumn">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Column</h5>
              <button type="button" class="btn-close" aria-label="Close" @click="showAddColumn=false"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Column Name <span class="text-danger">*</span></label>
                <input v-model="newColumn.label" class="form-control" placeholder="e.g., Attendance Week 1, Quiz 2" required />
                <small class="text-muted">This will appear as the column header</small>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Type <span class="text-danger">*</span></label>
                <select v-model="newColumn.type" class="form-select" @change="onTypeChange">
                  <option value="manual">Manual</option>
                  <option value="attendance">Attendance</option>
                  <option value="performance">Performance</option>
                  <option value="exam">Exam</option>
                  <option value="other">Other</option>
                </select>
                <small class="text-muted">
                  <strong>Manual:</strong> General purpose column<br>
                  <strong>Attendance:</strong> Track student attendance (Synchronous category)<br>
                  <strong>Performance:</strong> Performance tasks/activities<br>
                  <strong>Exam:</strong> Exam scores with format "score/total" (Exam category)<br>
                  <strong>Other:</strong> Custom column type (choose Sync/Async category)
                </small>
              </div>
              
              <!-- Category Selection - Only show for "Other" type -->
              <div class="mb-3" v-if="newColumn.type === 'other'">
                <label class="form-label fw-bold">Grading Category <span class="text-danger">*</span></label>
                <select v-model="newColumn.categoryId" class="form-select">
                  <option :value="null" disabled>-- Select Category --</option>
                  <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">
                    {{ cat.category_name }} ({{ cat.weight }}%)
                  </option>
                </select>
                <small class="text-muted d-block">
                  Choose <strong>Synchronous</strong> or <strong>Asynchronous</strong> category
                </small>
                <small class="text-muted" v-if="availableCategories.length === 0">
                  <span class="text-warning">⚠️ No categories available. Loading...</span>
                </small>
              </div>
              
              <!-- Info message for auto-assigned categories -->
              <div class="mb-3" v-if="newColumn.type === 'attendance'">
                <div class="alert alert-info py-2 mb-0">
                  <i class="bi bi-info-circle me-1"></i>
                  This column will be automatically assigned to <strong>Synchronous (35%)</strong> category
                </div>
              </div>
              <div class="mb-3" v-if="newColumn.type === 'exam'">
                <div class="alert alert-info py-2 mb-0">
                  <i class="bi bi-info-circle me-1"></i>
                  This column will be automatically assigned to <strong>Exam (30%)</strong> category
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label fw-bold">Maximum Points</label>
                <input v-model.number="newColumn.pointsPossible" type="number" min="0" step="1" class="form-control" placeholder="e.g., 10, 50, 100" />
                <small class="text-muted">Leave blank if not applicable. Used for grade calculations.</small>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" @click="showAddColumn=false">Cancel</button>
              <button class="btn btn-primary" @click="addColumn" :disabled="!canAddColumn">
                <i class="bi bi-check-circle me-1"></i>Add Column
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { API_BASE } from '../services/apiBase'
export default {
  name: 'MidtermSheetView',
  props: { classId: [String, Number] },
  data() {
    return {
      loading: true,
      computing: false,
      classInfo: null,
      columns: [],
      rows: [],
      nonFatalError: null,
      editable: {},
      editing: {},
      // timers for debounced autosave
      _autosaveTimers: {},
      showAddColumn: false,
      newColumn: { label: '', type: 'manual', pointsPossible: null, categoryId: null, customName: '' },
      categories: [], // Available grading categories
      // PWA offline support
      isOnline: navigator.onLine,
      pendingEditCount: 0,
      isSyncing: false
    };
  },
  computed: {
    apiBase() { return API_BASE },
    // Validation for add column button
    canAddColumn() {
      if (!this.newColumn.label) return false;
      if (!this.newColumn.categoryId) return false;
      return true;
    },
    // Filter available categories based on column type
    availableCategories() {
      if (this.newColumn.type === 'other') {
        // For "Other" type, only show Synchronous and Asynchronous
        return this.categories.filter(cat => 
          cat.category_name === 'Synchronous' || cat.category_name === 'Asynchronous'
        );
      }
      // For all other types, show all categories
      return this.categories;
    },
    // Groups columns into Performance (performance/manual), Attendance, Exam, and Other
    groupedColumns() {
      if (!this.columns || !Array.isArray(this.columns)) return [];
      const defs = [
        { name: 'Performance', types: ['performance', 'manual'] },
        { name: 'Attendance', types: ['attendance'] },
        { name: 'Exam', types: ['exam'] }
      ];
      const groups = defs.map(d => ({ name: d.name, types: d.types, columns: [] }));
      const other = { name: 'Other', columns: [] };
      for (const col of this.columns || []) {
        const type = col && col.type;
        const idx = groups.findIndex(g => g.types.includes(type));
        if (idx >= 0) groups[idx].columns.push(col);
        else other.columns.push(col);
      }
      const res = groups.slice();
      if (other.columns.length) res.push(other);
      return res;
    },
    // Filter out groups with no columns to avoid referencing group.columns in template v-if
    filteredGroups() {
      return (this.groupedColumns || []).filter(g => g && Array.isArray(g.columns) && g.columns.length);
    },
    // Flattened list in the same order as groups for rendering rows
    flattenedColumns() {
      return (this.filteredGroups || []).reduce((acc, g) => acc.concat((g && Array.isArray(g.columns)) ? g.columns : []), []);
    }
    ,
    midtermLocked() {
      return this.classInfo && (this.classInfo.midterm_status === 'completed' || this.classInfo.midterm_status === 'locked');
    },
    midtermBadgeClass() {
      if (!this.classInfo || !this.classInfo.midterm_status) return 'bg-secondary';
      return this.classInfo.midterm_status === 'completed' ? 'bg-success' : (this.classInfo.midterm_status === 'ongoing' ? 'bg-primary' : 'bg-secondary');
    }
  },
  mounted() {
    console.log('MidtermSheetView mounted with classId:', this.classId);
    this.refresh();
    
    // Load and initialize offline grade manager for PWA support
    this.initializeOfflineManager();
  },
  beforeUnmount() {
    // Clean up offline manager event listeners
    if (window.offlineGradeManager) {
      window.removeEventListener('offline-grade-manager:online', this.handleOnline);
      window.removeEventListener('offline-grade-manager:offline', this.handleOffline);
      window.removeEventListener('offline-grade-manager:grade-synced', this.handleGradeSynced);
      window.removeEventListener('offline-grade-manager:sync-complete', this.handleSyncComplete);
    }
  },
  methods: {
    // PWA Offline Manager Integration
    async initializeOfflineManager() {
      // Load offline manager script if not already loaded
      if (!window.offlineGradeManager) {
        try {
          const script = document.createElement('script');
          script.src = '/js/offline-grade-manager.js';
          script.onload = async () => {
            console.log('[PWA] Offline grade manager loaded');
            await window.offlineGradeManager.init();
            this.setupOfflineListeners();
            await this.updatePendingCount();
          };
          script.onerror = (err) => {
            console.warn('[PWA] Failed to load offline grade manager:', err);
          };
          document.head.appendChild(script);
        } catch (err) {
          console.warn('[PWA] Error loading offline manager:', err);
        }
      } else {
        this.setupOfflineListeners();
        await this.updatePendingCount();
      }
    },
    
    setupOfflineListeners() {
      // Listen for connection status changes
      window.addEventListener('offline-grade-manager:online', this.handleOnline);
      window.addEventListener('offline-grade-manager:offline', this.handleOffline);
      window.addEventListener('offline-grade-manager:grade-synced', this.handleGradeSynced);
      window.addEventListener('offline-grade-manager:sync-complete', this.handleSyncComplete);
      
      // Set initial online status
      this.isOnline = navigator.onLine;
    },
    handleOnline() { this.isOnline = true; },
    handleOffline() { this.isOnline = false; },
    async updatePendingCount() {
      try {
        if (window.offlineGradeManager) {
          this.pendingEditCount = await window.offlineGradeManager.pendingEditCount();
        }
      } catch (e) { /* ignore */ }
    },
    async manualSync() {
      if (!window.offlineGradeManager) return;
      this.isSyncing = true;
      try {
        await window.offlineGradeManager.syncNow();
      } catch (_) { /* ignore */ }
      finally {
        this.isSyncing = false;
        await this.updatePendingCount();
      }
    },

    async refresh() {
      this.loading = true;
      this.nonFatalError = null;
      try {
        const cid = this.classId || this.$route.params.courseId || this.$route.params.id || null;
        if (!cid) { this.nonFatalError = 'No class id'; this.loading = false; return; }
        // Load class info
        try {
          const res = await fetch(`${this.apiBase}/api/classes/${cid}`, { credentials: 'include', headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' } });
          if (res.ok) this.classInfo = await res.json();
        } catch (_) { /* ignore */ }

        // Load categories
        try {
          const cRes = await fetch(`${this.apiBase}/api/classes/${cid}/grading-categories`, { credentials: 'include', headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' } });
          if (cRes.ok) {
            const payload = await cRes.json();
            this.categories = Array.isArray(payload) ? payload : (Array.isArray(payload.categories) ? payload.categories : []);
          }
        } catch (_) { this.categories = []; }

        // Load midterm sheet
        const res = await fetch(`${this.apiBase}/api/classes/${cid}/midterm-sheet`, { credentials: 'include', headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' } });
        if (!res.ok) {
          let t = '';
          try { t = await res.text(); } catch(_) { t = ''; }
          this.nonFatalError = `Failed to load midterm sheet (status ${res.status}) ${t ? '('+t+')' : ''}`;
          this.columns = [];
          this.rows = [];
        } else {
          const payload = await res.json();
          this.columns = Array.isArray(payload.columns) ? payload.columns : [];
          this.rows = Array.isArray(payload.rows) ? payload.rows : [];
        }
      } catch (e) {
        this.nonFatalError = 'Failed to load midterm sheet';
        this.columns = [];
        this.rows = [];
      } finally {
        this.loading = false;
      }
    },

    getCellValue(rowId, colId) {
      try {
        const r = this.rows.find(r => r.rowId === rowId);
        if (!r) return '';
        const key = `c_${colId}`;
        return typeof r[key] !== 'undefined' && r[key] !== null ? String(r[key]) : '';
      } catch (_) { return ''; }
    },
    isEditing(rowId, colId) {
      return !!(this.editing && this.editing[`${rowId}:${colId}`]);
    },
    enableEdit(rowId, colId) {
      if (!this.editing) this.editing = {};
      this.editing[`${rowId}:${colId}`] = true;
      this.$nextTick(() => {
        try {
          const input = Array.isArray(this.$refs.editInputs) ? this.$refs.editInputs.find(el => el && el.dataset && el.dataset.row == rowId && el.dataset.col == colId) : null;
          input && input.focus && input.focus();
        } catch (_) { /* ignore */ }
      });
    },
    onCellEnter(evt, rowId, colId) {
      evt && evt.preventDefault && evt.preventDefault();
      const input = evt && evt.target;
      input && input.blur && input.blur();
    },
    onCellInput(evt, rowId, colId) {
      const v = evt && evt.target ? evt.target.value : '';
      if (!this.editable[rowId]) this.editable[rowId] = {};
      const key = `c_${colId}`;
      this.editable[rowId][key] = v;
      this.queueAutosave(rowId);
    },
    onCellBlur(rowId, colId) {
      if (this.editing) this.editing[`${rowId}:${colId}`] = false;
      this.saveRow(rowId);
    },
    getEditableValue(rowId, key) {
      try { return (this.editable[rowId] && typeof this.editable[rowId][key] !== 'undefined') ? this.editable[rowId][key] : (this.rows.find(r => r.rowId === rowId)?.[key] ?? ''); } catch (_) { return ''; }
    },
    onEditableInput(evt, rowId, key) {
      const v = evt && evt.target ? evt.target.value : '';
      if (!this.editable[rowId]) this.editable[rowId] = {};
      this.editable[rowId][key] = v;
      this.queueAutosave(rowId);
    },
    queueAutosave(rowId) {
      try {
        if (!this._autosaveTimers) this._autosaveTimers = {};
        if (this._autosaveTimers[rowId]) clearTimeout(this._autosaveTimers[rowId]);
        this._autosaveTimers[rowId] = setTimeout(() => this.saveRow(rowId), 800);
      } catch (_) { /* ignore */ }
    },
    async saveRow(rowId) {
      try {
        const cid = this.classId || this.$route.params.courseId || this.$route.params.id || null;
        if (!cid) return;
        const payload = this.editable[rowId] || {};
        if (!Object.keys(payload).length) return;
        const res = await fetch(`${this.apiBase}/api/classes/${cid}/midterm-sheet/rows/${encodeURIComponent(rowId)}`, {
          method: 'PUT',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json', 'x-user-id': localStorage.getItem('loggedInUserId') || '' },
          body: JSON.stringify(payload)
        });
        if (!res.ok) {
          console.warn('Save row failed', res.status);
        }
      } catch (e) {
        console.warn('Save row error', e);
      }
    },
    getComputationTooltip(r) {
      if (!r) return '';
      return `Computed midterm based on weights and columns.`;
    },
    async computeMidterm() {
      if (this.computing) return;
      this.computing = true;
      try {
        const cid = this.classId || this.$route.params.courseId || this.$route.params.id || null;
        if (!cid) return;
        const res = await fetch(`${this.apiBase}/api/classes/${cid}/midterm-sheet/compute`, { method: 'POST', credentials: 'include', headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' } });
        if (res.ok) await this.refresh();
      } catch (_) { /* ignore */ } finally { this.computing = false; }
    },
    async markMidtermDone() {
      try {
        const cid = this.classId || this.$route.params.courseId || this.$route.params.id || null;
        if (!cid) return;
        await fetch(`${this.apiBase}/api/classes/${cid}/midterm-sheet/mark-done`, { method: 'POST', credentials: 'include', headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' } });
        await this.refresh();
      } catch (_) { /* ignore */ }
    },
    async downloadExcel() {
      try {
        const cid = this.classId || this.$route.params.courseId || this.$route.params.id || null;
        if (!cid) return;
        const res = await fetch(`${this.apiBase}/api/classes/${cid}/gradebook/midterm/download`, { credentials: 'include', headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' } });
        if (!res.ok) return alert('Failed to download');
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `midterm_${cid}.xlsx`; a.click();
        URL.revokeObjectURL(url);
      } catch (_) { alert('Failed to download'); }
    },
    async createSheet() {
      try {
        const cid = this.classId || this.$route.params.courseId || this.$route.params.id || null;
        if (!cid) return;
        const res = await fetch(`${this.apiBase}/api/classes/${cid}/midterm-sheet`, { method: 'POST', credentials: 'include', headers: { 'x-user-id': localStorage.getItem('loggedInUserId') || '' } });
        if (res.ok) await this.refresh();
      } catch (_) { /* ignore */ }
    },
    onTypeChange() {
      // Auto-assign categories if certain types selected
      if (this.newColumn.type === 'attendance') {
        const syncCat = this.categories.find(c => c.category_name === 'Synchronous');
        this.newColumn.categoryId = syncCat ? syncCat.id : null;
      } else if (this.newColumn.type === 'exam') {
        const examCat = this.categories.find(c => c.category_name && String(c.category_name).toLowerCase() === 'exam');
        this.newColumn.categoryId = examCat ? examCat.id : null;
      } else {
        this.newColumn.categoryId = null;
      }
    },
    openAddColumn() { this.showAddColumn = true; },
    async addColumn() {
      try {
        const cid = this.classId || this.$route.params.courseId || this.$route.params.id || null;
        if (!cid) return;
        const payload = {
          label: this.newColumn.label,
          type: this.newColumn.type,
          points_possible: this.newColumn.pointsPossible || null,
          category_id: this.newColumn.categoryId || null
        };
        const res = await fetch(`${this.apiBase}/api/classes/${cid}/midterm-sheet/columns`, {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json', 'x-user-id': localStorage.getItem('loggedInUserId') || '' },
          body: JSON.stringify(payload)
        });
        if (res.ok) {
          this.showAddColumn = false;
          this.newColumn = { label: '', type: 'manual', pointsPossible: null, categoryId: null, customName: '' };
          await this.refresh();
        } else {
          alert('Failed to add column');
        }
      } catch (_) { alert('Failed to add column'); }
    }
  }
}
</script>

<style scoped>
/* minimal styles to match app */
.page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; }
.student-name { min-width:220px; }
.row-number { width:40px; text-align:center; }
.cell-container { min-width:120px; }
.readonly-cell { background:#f8fafc; padding:4px 8px; border-radius:4px; }
.editable-cell { padding:4px; }
.attendance-col { background:#fff7ed; }
.attendance-col-cell { background:#fffaf0; }
</style>
