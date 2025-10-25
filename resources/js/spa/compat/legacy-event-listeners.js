export function registerLegacyEventListeners(router){
  try {
    // When legacy code asks to load classwork, navigate to the SPA manage-classwork route
    window.addEventListener('legacy:loadClassworkCards', (e) => {
      try {
        const classId = new URLSearchParams(window.location.search).get('classId') || (e && e.detail && e.detail.classId) || null;
        if (classId && router && typeof router.push === 'function') {
          router.push({ name: 'manage-classwork', params: { courseId: classId } }).catch(()=>{});
        }
      } catch(err) { /* ignore */ }
    });

    // Legacy request to open submissions modal: navigate to course manage page and include hash
    window.addEventListener('legacy:openSubmissionsModal', (e) => {
      try {
        const classworkId = e && e.detail && (e.detail.id || e.detail.classworkId) || '';
        const classId = new URLSearchParams(window.location.search).get('classId') || (e && e.detail && e.detail.classId) || '';
        if (classId && router && typeof router.push === 'function') {
          router.push({ name: 'manage-classwork', params: { courseId: classId }, hash: `#submissions-${classworkId}` }).catch(()=>{});
        }
      } catch(err) {/* ignore */}
    });

    // Legacy request to open rubric modal: attempt to show rubric modal in DOM
    window.addEventListener('legacy:openRubricModal', (e) => {
      try {
        const el = document.getElementById('rubricGradeModal');
        if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
      } catch(err) { /* ignore */ }
    });
  } catch(e){ console.error('registerLegacyEventListeners failed', e); }
}
