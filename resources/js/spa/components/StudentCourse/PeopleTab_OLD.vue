<template>
  <div class="student-people-tab">
    <div v-if="loading" class="p-4 text-center">Loading people...</div>
    <div v-else>
      <section class="mb-4">
        <h5>Instructors <small class="text-muted">({{ instructors.length }})</small></h5>
        <div class="list-group mb-2">
          <div v-if="instructors.length === 0" class="list-group-item text-muted">No instructors assigned.</div>
          <div v-for="ins in instructors" :key="ins.id || ins.email" class="list-group-item d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
              <img v-if="ins.avatarUrl" :src="ins.avatarUrl" class="rounded-circle me-3" style="width:44px;height:44px;object-fit:cover" @error="(e) => handleImageError(e, ins)" />
              <div v-else class="initials-avatar me-3" :style="{ background: getInitialsColor(insDisplayName(ins)) }">{{ getInitials(insDisplayName(ins)) }}</div>
              <div>
                <div class="fw-bold">{{ insDisplayName(ins) }}</div>
                <div class="text-muted small">{{ ins.email || '' }}</div>
              </div>
            </div>
            <span class="badge" :class="roleBadgeClass(ins.role_in_class)">{{ (ins.role_in_class || '').toUpperCase() }}</span>
          </div>
        </div>
      </section>

      <section>
        <h5>Classmates <small class="text-muted">({{ students.length }})</small></h5>
        <div class="list-group">
          <div v-if="students.length === 0" class="list-group-item text-muted">You are the only student in this class.</div>
          <div v-for="stu in students" :key="stu.id || stu.email" class="list-group-item d-flex align-items-center">
            <img v-if="stu.avatarUrl" :src="stu.avatarUrl" class="rounded-circle me-3" style="width:40px;height:40px;object-fit:cover" @error="(e) => handleImageError(e, stu)" />
            <div v-else class="initials-avatar initials-small me-3" :style="{ background: getInitialsColor(studentDisplayName(stu)) }">{{ getInitials(studentDisplayName(stu)) }}</div>
            <div>
              <div>{{ studentDisplayName(stu) }}</div>
              <div class="small text-muted">{{ stu.email || '' }}</div>
            </div>
          </div>
        </div>
      </section>

      <div v-if="error" class="mt-3 text-danger">{{ error }}</div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref, watch, onMounted } from 'vue'
const props = defineProps({ classId: [String, Number] })
const currentId = ref(props.classId || '')
watch(() => props.classId, (v) => { if (v) currentId.value = v })

const loading = ref(false)
const instructors = ref([])
const students = ref([])
const error = ref('')

function insDisplayName(ins){
  const fn = ins.first_name || ins.firstName || ''
  const ln = ins.last_name || ins.lastName || ''
  return (fn + ' ' + ln).trim() || ins.email || 'Instructor'
}

function studentDisplayName(stu){
  const fn = stu.first_name || stu.firstName || ''
  const ln = stu.last_name || stu.lastName || ''
  return (fn + ' ' + ln).trim() || stu.email || 'Student'
}

function roleBadgeClass(role){
  if(!role) return 'bg-secondary text-white'
  if(role === 'owner') return 'bg-primary text-white'
  if(role === 'ta') return 'bg-info text-dark'
  return 'bg-secondary text-white'
}

async function loadPeopleForClass(id){
  loading.value = true
  instructors.value = []
  students.value = []
  error.value = ''
  if(!id){ loading.value=false; return }
  try{
    const BACKEND = window.BACKEND_API_BASE_URL || 'http://localhost:3000'
    const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
    const headers = userId ? { 'x-user-id': userId } : {}
    const res = await fetch(`${BACKEND}/api/classes/${id}/people`, { headers, credentials: 'include' })
    if(!res.ok) throw new Error('Failed to load people')
    const data = await res.json()
    instructors.value = data.instructors || []
    students.value = data.students || []
    // normalize avatars if any
    instructors.value.forEach(i=>{ i.avatarUrl = resolveAvatarUrl(i) })
    students.value.forEach(s=>{ s.avatarUrl = resolveAvatarUrl(s) })
  }catch(e){
    console.error('loadPeopleForClass', e)
    error.value = 'Error loading people.'
  }
  loading.value = false
}

function resolveAvatarUrl(person){
  if(!person) return null
  // Priority: google_picture (Gmail) → profile_picture (uploaded) → avatar → null (fallback to icon)
  if(person.google_picture && person.google_picture.startsWith('http')) return person.google_picture
  if(person.profile_picture && person.profile_picture.startsWith('http')) return person.profile_picture
  if(person.avatar && person.avatar.startsWith('http')) return person.avatar
  if(person.avatarUrl && person.avatarUrl.startsWith('http')) return person.avatarUrl
  // fallback to backend hosted profile images (relative paths)
  const BACKEND = window.BACKEND_API_BASE_URL || 'http://localhost:3000'
  if(person.google_picture) return person.google_picture // already a full URL
  if(person.profile_picture) return BACKEND.replace(/\/$/,'') + '/' + person.profile_picture.replace(/^\//,'')
  return null
}

function getInitials(name) {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(n => n.charAt(0))
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function getInitialsColor(name) {
  // Generate unique color based on name
  const colors = [
    ['#667eea', '#764ba2'], // Purple
    ['#f093fb', '#f5576c'], // Pink
    ['#4facfe', '#00f2fe'], // Blue
    ['#43e97b', '#38f9d7'], // Green
    ['#fa709a', '#fee140'], // Orange-Pink
    ['#30cfd0', '#330867'], // Teal-Purple
    ['#a8edea', '#fed6e3'], // Mint-Pink
    ['#ff9a9e', '#fecfef'], // Rose
    ['#ffecd2', '#fcb69f'], // Peach
    ['#ff6e7f', '#bfe9ff'], // Red-Blue
    ['#fbc2eb', '#a6c1ee'], // Lavender
    ['#fdcbf1', '#e6dee9'], // Light Purple
  ];
  
  let hash = 0;
  for (let i = 0; i < (name || '').length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  const colorIndex = Math.abs(hash) % colors.length;
  const [color1, color2] = colors[colorIndex];
  return `linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`;
}

function handleImageError(e, person) {
  // Replace broken image with initials
  e.target.style.display = 'none'
  const initialsDiv = document.createElement('div')
  initialsDiv.className = 'initials-avatar me-3'
  initialsDiv.textContent = getInitials(person.first_name || person.email || 'U')
  e.target.parentNode.insertBefore(initialsDiv, e.target)
}

onMounted(()=>{ if(currentId.value) loadPeopleForClass(currentId.value) })
watch(()=> currentId.value, (v,o)=>{ if(v && v!==o) loadPeopleForClass(v) })
</script>

<style scoped>
.student-people-tab { padding: 1rem; }
.student-people-tab img { width:44px; height:44px; object-fit:cover; }
.badge { font-size:0.75rem; }

/* Initials Avatar */
.initials-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  /* Background color now set dynamically via :style binding */
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1rem;
  flex-shrink: 0;
}

.initials-small {
  width: 40px;
  height: 40px;
  font-size: 0.9rem;
}
</style>