<template>
  <div class="modal fade" tabindex="-1" ref="modalEl">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header align-items-start">
            <div class="d-flex flex-column">
              <div class="d-flex align-items-center gap-2">
                <h5 class="modal-title mb-0">{{ title || 'Preview' }}</h5>
                <small class="text-muted">&nbsp;</small>
                <span v-if="type" class="badge bg-secondary ms-2">{{ type }}</span>
              </div>
              <small class="text-muted">Preview</small>
            </div>
            <button type="button" class="btn-close" @click="hide"></button>
          </div>

          <div class="modal-toolbar px-3 py-2 border-top border-bottom bg-light d-flex align-items-center gap-2">
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-secondary" @click="zoomOut" :disabled="!canZoom">-</button>
              <button class="btn btn-sm btn-outline-secondary" @click="resetZoom" :disabled="!canZoom">Reset</button>
              <button class="btn btn-sm btn-outline-secondary" @click="zoomIn" :disabled="!canZoom">+</button>
            </div>
            <div class="ms-auto d-flex gap-2">
              <a :href="url" :download="downloadName" class="btn btn-sm btn-primary">Download</a>
            </div>
          </div>

          <div class="modal-body p-3 preview-body">
            <div class="preview-loading" v-if="loading">
              <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            </div>

            <div v-else class="preview-container">
              <template v-if="type && type.startsWith('image/')">
                <div class="preview-inner" :style="imageTransformStyle">
                  <img :src="url" class="img-fluid d-block mx-auto preview-image" />
                </div>
              </template>

              <template v-else-if="type==='application/pdf'">
                <div class="pdf-wrapper">
                  <iframe :src="url" class="pdf-iframe" />
                </div>
              </template>

              <template v-else-if="type && type.startsWith('text/')">
                <div class="text-preview" :style="textTransformStyle"><pre>{{ textContent }}</pre></div>
              </template>

              <template v-else-if="docxHtml">
                <div class="docx-preview" :style="textTransformStyle" v-html="docxHtml"></div>
              </template>

              <template v-else>
                <div class="p-4 text-center text-muted">Preview not available. Use the Download link to get the file.</div>
              </template>
            </div>
          </div>
        </div>
      </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const modalEl = ref(null)
const url = ref('')
const title = ref('')
const type = ref('')
const textContent = ref('')
const docxHtml = ref('')
const downloadName = ref('')
const loading = ref(false)
const zoom = ref(1)
const minZoom = 0.25
const maxZoom = 3

const canZoom = computed(() => true)

function zoomIn(){ if(zoom.value < maxZoom) zoom.value = Math.min(maxZoom, +(zoom.value + 0.25).toFixed(2)) }
function zoomOut(){ if(zoom.value > minZoom) zoom.value = Math.max(minZoom, +(zoom.value - 0.25).toFixed(2)) }
function resetZoom(){ zoom.value = 1 }

const imageTransformStyle = computed(() => ({ transform: `scale(${zoom.value})`, transition: 'transform 120ms ease' }))
const textTransformStyle = computed(() => ({ transform: `scale(${zoom.value})`, transformOrigin: 'top left', transition: 'transform 120ms ease' }))

function loadMammoth(){
  return new Promise((resolve, reject) => {
    if (window.mammoth) return resolve(window.mammoth)
    const existing = document.querySelector('script[data-mammoth]')
    if (existing){
      existing.addEventListener('load', () => resolve(window.mammoth))
      existing.addEventListener('error', () => reject(new Error('Failed to load mammoth')))
      return
    }
    const s = document.createElement('script')
    s.setAttribute('data-mammoth', '1')
    s.src = 'https://unpkg.com/mammoth/mammoth.browser.min.js'
    s.onload = () => { if (window.mammoth) resolve(window.mammoth); else reject(new Error('Mammoth loaded but not available')) }
    s.onerror = () => reject(new Error('Failed to load mammoth'))
    document.head.appendChild(s)
  })
}

function hide(){
  const inst = bootstrap.Modal.getInstance(modalEl.value)
  if(inst) inst.hide()
}

async function show(newUrl, name){
  url.value = newUrl
  title.value = name || ''
  downloadName.value = name || ''
  loading.value = true
  textContent.value = ''
  docxHtml.value = ''
  
  // Simple extension -> mime map (avoid extra dependency)
  const ext = (name || '').split('.').pop() || ''
  const map = { 
    pdf: 'application/pdf', 
    png: 'image/png', 
    jpg: 'image/jpeg', 
    jpeg: 'image/jpeg', 
    gif: 'image/gif', 
    txt: 'text/plain', 
    md: 'text/markdown', 
    html: 'text/html',
    doc: 'application/msword',
    docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  }
  type.value = map[ext.toLowerCase()] || ''

  // If we already know the type from extension, use it. Otherwise attempt to fetch headers (HEAD) to detect Content-Type.
  if (!type.value) {
    try {
      // Try HEAD first to avoid downloading body
      const headRes = await fetch(url.value, { method: 'HEAD', credentials: 'include' })
      if (headRes && headRes.ok) {
        const ct = headRes.headers.get('content-type') || ''
        if (ct) type.value = ct.split(';')[0]
      }
    } catch (e) {
      // HEAD may fail on some servers or cross-origin; ignore and try a lightweight GET below
      console.warn('HEAD request failed:', e)
    }
  }

  // If still unknown, do a GET and inspect headers; only read body for text types
  if (!type.value) {
    try {
      const res = await fetch(url.value, { method: 'GET', credentials: 'include' })
      if (res && res.ok) {
        const ct = res.headers.get('content-type') || ''
        if (ct) type.value = ct.split(';')[0]
        // If text type, read body
        if (type.value && type.value.startsWith('text/')) {
          try { textContent.value = await res.text() } catch (e) { textContent.value = 'Failed to load preview' }
        }
      }
    } catch (e) {
      // ignore; we'll fall back to "Preview not available"
      console.warn('GET request failed:', e)
    }
  }

  // If type is text and not yet loaded, fetch it now
  if (type.value && type.value.startsWith('text/') && !textContent.value) {
    try {
      const res = await fetch(url.value, { credentials: 'include' })
      if (res && res.ok) textContent.value = await res.text()
    } catch (e) { 
      console.error('Failed to load text content:', e)
      textContent.value = 'Failed to load preview' 
    }
  }

  // DOCX support: if ext==docx or type indicates wordprocessingml
  const lcExt = (ext || '').toLowerCase()
  const isDocx = lcExt === 'docx' || (type.value && type.value.includes('wordprocessingml'))
  if (isDocx) {
    try {
      const mammoth = await loadMammoth()
      const res = await fetch(url.value, { credentials: 'include' })
      if (!res.ok) throw new Error('Failed to fetch file for preview')
      const ab = await res.arrayBuffer()
      // Convert to HTML while inlining images and applying a few style mappings
      const result = await mammoth.convertToHtml({ arrayBuffer: ab }, {
        convertImage: mammoth.images.inline(function(element){
          return element.read('base64').then(function(imageBuffer){
            return { src: 'data:' + element.contentType + ';base64,' + imageBuffer };
          });
        }),
        styleMap: [
          "p[style-name='Title'] => h1:fresh",
          "p[style-name='Subtitle'] => h2:fresh",
          "p[style-name='Heading 1'] => h2:fresh",
          "p[style-name='Heading 2'] => h3:fresh",
          "p[style-name='Heading 3'] => h4:fresh"
        ]
      })
      docxHtml.value = result.value || '<div class="text-muted p-3">No preview available</div>'
    } catch (e) {
      console.error('DOCX preview failed:', e)
      docxHtml.value = '<div class="text-muted p-3">Preview not available. Use the Download link to get the file.</div>'
    }
  }

  // Always turn off loading before showing modal
  loading.value = false
  
  // Show modal with centered options
  const m = new bootstrap.Modal(modalEl.value, {
    backdrop: true,
    keyboard: true,
    focus: true
  })
  m.show()
}

// expose a global helper so legacy HTML can call
window.openFilePreview = async function(u, n){
  // find the component instance via event; simple approach: dispatch a custom event the component listens for
  window.dispatchEvent(new CustomEvent('open-file-preview', { detail: { url: u, name: n } }))
}

// listen for the event to show the modal
window.addEventListener('open-file-preview', (e) => { const d = e.detail || {}; if(d.url) show(d.url, d.name) })

defineExpose({ show, hide })

</script>

<style scoped>
/* Modal positioning - ensure centered only when shown */
.modal.show {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 1rem;
}

.modal-dialog {
  margin: 0 auto;
  max-width: 90vw;
  width: 100%;
}

.modal-dialog.modal-xl {
  max-width: 1140px;
}

.modal-content {
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-body img{ max-width:100% }
pre{ margin:0; }
.preview-body{ 
  position:relative; 
  min-height:320px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.preview-loading{ 
  position:absolute; 
  inset:0; 
  display:flex; 
  align-items:center; 
  justify-content:center; 
  background:rgba(255,255,255,0.7); 
  z-index:5 
}
.preview-container{ 
  position:relative; 
  z-index:1;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.preview-inner{ 
  display:flex; 
  align-items:center; 
  justify-content:center; 
  overflow:auto;
  width: 100%;
}
.preview-image{ 
  max-height:75vh;
  max-width: 100%;
  height: auto;
  display: block;
  margin: 0 auto;
}
.pdf-wrapper{ 
  height:75vh;
  width: 100%;
}
.pdf-iframe{ 
  width:100%; 
  height:100%; 
  border:0 
}
.text-preview{ 
  width: 100%;
  max-height: 75vh;
  overflow: auto;
}
.text-preview pre{ 
  white-space:pre-wrap;
  text-align: left;
  margin: 0;
  padding: 1rem;
}
.docx-preview{ 
  padding:0.75rem; 
  max-height:75vh; 
  overflow:auto;
  width: 100%;
}
/* Styling for converted DOCX HTML */
.docx-preview h1, .docx-preview h2, .docx-preview h3, .docx-preview h4 { margin-top:1rem; margin-bottom:.5rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif }
.docx-preview p { margin: 0 0 .75rem 0; line-height:1.4; font-family: 'Calibri','Segoe UI', Arial, sans-serif }
.docx-preview img { max-width:100%; height:auto; display:block; margin: .5rem 0 }
.docx-preview table { width:100%; border-collapse:collapse; margin-bottom:1rem }
.docx-preview table td, .docx-preview table th { border:1px solid #ddd; padding:.35rem }
.docx-preview ul, .docx-preview ol { margin: 0 0 0 1.25rem }
</style>
