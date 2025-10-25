// Lightweight shim to provide compatibility functions for legacy templates that call
// window.loadClassworkCards(), window.openSubmissionsModal(), window.openRubricModal().
// These stubs will navigate to SPA routes where possible or call into available Vue functions.

export function registerLegacyClassworkShim(router) {
  // loadClassworkCards: navigate to the SPA classwork route if params available
  window.loadClassworkCards = function() {
    try {
      const classId = new URLSearchParams(window.location.search).get('classId');
      if (classId && router && typeof router.push === 'function') {
        // Prefer teacher manage-classwork route
        router.push({ name: 'manage-classwork', params: { courseId: classId } }).catch(()=>{});
        return;
      }
      // fallback: no-op
      console.warn('loadClassworkCards shim: no router or classId')
    } catch(e) { console.error(e) }
  }

  window.openSubmissionsModal = function(item) {
    try {
      const classworkId = item && item.id ? item.id : (item||'');
      // Navigate to the teacher course manage/classwork route and include anchor
      const classId = new URLSearchParams(window.location.search).get('classId') || (item && item.classId) || '';
      if (classId && router && typeof router.push === 'function') {
        router.push({ name: 'manage-classwork', params: { courseId: classId }, hash: `#submissions-${classworkId}` }).catch(()=>{});
        return;
      }
      console.warn('openSubmissionsModal shim: no router or classId')
    } catch(e) { console.error(e) }
  }

  window.openRubricModal = function(submissionId) {
    // Best-effort: try to focus the rubric element by id if present
    try {
      const el = document.getElementById('rubricGradeModal');
      if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
    } catch(e) { console.error(e) }
  }
}
