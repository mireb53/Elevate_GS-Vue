<template>
  <div class="container mb-4">
    <div class="d-flex flex-column flex-lg-row gap-4">
      <div class="flex-grow-1">
        <h3 class="mb-3">Your Calendar</h3>
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <button @click="prevMonth" class="btn btn-sm btn-outline-secondary" title="Previous month"><i class="bi bi-chevron-left"></i></button>
              <div class="fw-semibold">{{ monthTitle }}</div>
              <button @click="nextMonth" class="btn btn-sm btn-outline-secondary" title="Next month"><i class="bi bi-chevron-right"></i></button>
            </div>
            <button @click="goToday" class="btn btn-sm btn-outline-primary">Today</button>
          </div>
          <div class="card-body">
            <div class="mini-cal">
              <div class="mini-cal-row mini-cal-header">
                <div v-for="d in days" :key="d">{{ d }}</div>
              </div>
              <div v-for="(row, i) in calendarRows" :key="i" class="mini-cal-row">
                <div v-for="cell in row" :key="cell.key" :class="cell.class">
                  <div v-if="cell.day" class="date">{{ cell.day }}</div>
                  <span v-if="cell.hasEvent && !(role === 'student' && cell.dotClass === 'normal')" class="dot" :class="cell.dotClass" :title="cell.preview || (cell.eventCount + ' event(s)')"></span>
                </div>
              </div>
            </div>
            <div class="small text-muted mt-2">• A dot indicates you have deadlines/events that day. <span class="ms-2"><span class="dot upcoming"></span> upcoming due</span> <span class="ms-2"><span class="dot overdue"></span> overdue</span><span v-if="role !== 'student'" class="ms-2"><span class="dot normal"></span> other</span></div>
          </div>
        </div>
      </div>
      <div class="flex-grow-1">
        <h3 class="mb-3">Agenda</h3>
        <div id="calendarEvents" class="list-group">
          <template v-if="agenda.length === 0">
            <div class="list-group-item text-muted">No events yet.</div>
          </template>
          <template v-else>
            <template v-for="(group, idx) in agenda" :key="group.day">
              <div class="list-group-item active"><strong>{{ group.dayLabel }}</strong></div>
              <a v-for="ev in group.events" :key="ev.id" href="#" class="list-group-item list-group-item-action" :class="{
                'border-start border-primary border-3': ev.type === 'class-announcement',
                'due-entry': ev.type === 'due',
                'upcoming-entry': ev.type === 'due' && ev.dueStatus === 'upcoming',
                'overdue-entry': ev.type === 'due' && ev.dueStatus === 'overdue'
              }" @click.prevent="onEventClick(ev)">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1">
                    <i v-if="ev.type === 'class-announcement'" class="bi bi-megaphone-fill me-1"></i>
                    <i v-else-if="ev.type === 'due' && ev.dueStatus === 'upcoming'" class="bi bi-clock-fill text-success me-1"></i>
                    <i v-else-if="ev.type === 'due' && ev.dueStatus === 'overdue'" class="bi bi-exclamation-circle-fill text-danger me-1"></i>
                    {{ ev.title }}
                    <span v-if="ev.type === 'class-announcement' && ev.priority" :class="'priority-badge priority-' + ev.priority">{{ ev.priority.toUpperCase() }}</span>
                  </h6>
                  <small>{{ formatDateTime(ev.start) }}</small>
                </div>
                <p class="mb-1 small">{{ ev.type === 'due' ? 'Due' : (ev.type === 'class-announcement' ? 'Announcement' : ev.type) }}</p>
                <div class="small text-truncate mb-1" v-if="ev.message">{{ ev.message }}</div>
                <small v-if="ev.className" class="text-muted">{{ ev.className }}</small>
              </a>
            </template>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'

const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']
const cur = ref(new Date())
const eventsCache = ref([])
const userId = localStorage.getItem('loggedInUserId') || sessionStorage.getItem('userId')
const role = localStorage.getItem('loggedInUserRole') || 'student'

const API_BASE = window.BACKEND_API_BASE_URL || `http://${window.location.hostname||'localhost'}:3000`

const monthTitle = computed(() => cur.value.toLocaleString(undefined, { month: 'long', year: 'numeric' }))

function getDayKey(date) {
  const d = (date instanceof Date) ? date : new Date(date)
  if (isNaN(d?.getTime?.() || NaN)) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const calendarRows = computed(() => {
  const y = cur.value.getFullYear()
  const m = cur.value.getMonth()
  const first = new Date(y, m, 1)
  const startIdx = first.getDay()
  const total = new Date(y, m+1, 0).getDate()
  let dayNum = 1
  const rows = []
  for(let week=0; week<6; week++) {
    const row = []
    for(let dow=0; dow<7; dow++) {
      if(week===0 && dow<startIdx) {
        row.push({ key: `empty-${week}-${dow}`, class: 'mini-cal-cell muted' })
      } else if(dayNum>total) {
        row.push({ key: `empty2-${week}-${dow}`, class: 'mini-cal-cell muted' })
      } else {
        const d = new Date(y, m, dayNum)
        const key = getDayKey(d)
        const isToday = (new Date()).toDateString() === d.toDateString()
        const evs = eventsCache.value.filter(ev => {
          const k = getDayKey(ev?.start)
          return k && k === key
        })
        // determine dot class: if any due events, mark upcoming vs overdue
        let dotClass = 'normal'
        const hasDue = evs.some(x => x.type === 'due')
        if (hasDue) {
          // find the nearest due event date for the day
          const dueEv = evs.find(x => x.type === 'due')
          if (dueEv && dueEv.start) {
            const dt = new Date(dueEv.start)
            const now = new Date()
            dotClass = dt < now ? 'overdue' : 'upcoming'
          } else {
            dotClass = 'upcoming'
          }
        }
        row.push({
          key: `day-${dayNum}`,
          class: 'mini-cal-cell' + (isToday ? ' today' : ''),
          day: dayNum,
          hasEvent: evs.length > 0,
          hasDue,
          dotClass,
          eventCount: evs.length,
          // preview text used by the tooltip/title on the dot
          preview: evs.length > 0 ? (evs[0].message || evs[0].title || (evs.length + ' event(s)')) : ''
        })
        dayNum++
      }
    }
    rows.push(row)
    if(dayNum>total) break
  }
  return rows
})

const agenda = computed(() => {
  if (!eventsCache.value.length) return []
  // Show only due items for student role; teachers/admins see all events
  const role = localStorage.getItem('loggedInUserRole') || 'student'
  const filteredEvents = eventsCache.value.filter(ev => {
    if (role === 'student') return ev.type === 'due'
    return true
  })

  const byDay = {}
  filteredEvents.forEach(ev => {
    const day = getDayKey(ev?.start)
    if (!day) return
    ;(byDay[day] ||= []).push(ev)
  })
  const dayKeys = Object.keys(byDay).sort()
  return dayKeys.map(day => ({
    day,
    dayLabel: new Date(day).toDateString(),
    events: byDay[day].map(ev => {
      // annotate due events with dueStatus: 'upcoming' | 'overdue'
      if (ev.type === 'due') {
        try {
          const dt = new Date(ev.start)
          ev.dueStatus = dt < new Date() ? 'overdue' : 'upcoming'
        } catch(e) { ev.dueStatus = 'upcoming' }
      }
      return ev
    })
  }))
})

function prevMonth() {
  cur.value = new Date(cur.value.getFullYear(), cur.value.getMonth() - 1, 1)
}
function nextMonth() {
  cur.value = new Date(cur.value.getFullYear(), cur.value.getMonth() + 1, 1)
}
function goToday() {
  cur.value = new Date()
}

function formatDateTime(dt) {
  return new Date(dt).toLocaleString()
}

const router = useRouter()

function onEventClick(ev) {
  if (ev.classId) {
    // Prefer navigating students into the course view and open the classwork tab
    const role = localStorage.getItem('loggedInUserRole') || 'student'
    // try to derive a classworkId if present in the event id (backend uses `due-<id>`)
    let classworkId = ev.classworkId || null
    if (!classworkId && ev.id && String(ev.id).startsWith('due-')) {
      const parts = String(ev.id).split('-')
      if (parts.length >= 2) classworkId = parts[1]
    }

    if (role === 'student') {
      // send student to the StudentCourse route and open classwork tab + optionally detail
      router.push({ name: 'StudentCourse', params: { courseId: ev.classId }, query: { tab: 'classwork', classworkId } })
      return
    }

    // For teachers/admins fallback to manage-classwork or class-records depending on role
    if (role === 'teacher') {
      router.push({ name: 'manage-classwork', params: { courseId: ev.classId }, query: { classworkId } }).catch(()=>{})
      return
    }

    // default: keep old behavior (class-records) for other roles
    router.push({ path: '/class-records', query: { classId: ev.classId } })
  }
}

async function loadCalendar() {
  const headers = userId ? { 'x-user-id': userId } : {}
  const res = await fetch(API_BASE + '/api/calendar', { credentials:'include', headers })
  if (!res.ok) return
  const { events = [] } = await res.json()
  eventsCache.value = Array.isArray(events) ? events : []
}

onMounted(() => {
  loadCalendar()
})

watch(cur, loadCalendar)

// DEBUG: expose eventsCache contents in console after load for troubleshooting
// (temporary - remove after verification)
function _debugPrintEvents() {
  // give a tiny delay to ensure eventsCache updated by loadCalendar
  setTimeout(() => {
    try { console.debug('[CalendarView] eventsCache snapshot:', eventsCache.value); } catch(e){}
  }, 250);
}

// call once on mount
onMounted(() => { _debugPrintEvents(); });
</script>

<style scoped>
.mini-cal { display: grid; gap: .25rem; }
.mini-cal-row { display: grid; grid-template-columns: repeat(7, 1fr); gap: .25rem; }
.mini-cal-header > div { font-size: .85rem; text-align:center; color:#6c757d; font-weight:600; }
.mini-cal-cell { position: relative; min-height: 52px; border: 1px solid #eee; border-radius: .4rem; padding: .25rem .35rem; background: #fff; }
.mini-cal-cell .date { font-size: .9rem; font-weight: 600; }
.mini-cal-cell.today { outline: 2px solid #ab1818; }
.mini-cal-cell.muted { background: #f8f9fa; border: 1px dashed #eee; }
.mini-cal-cell .dot { position:absolute; bottom:.35rem; left:50%; transform:translateX(-50%); width:8px; height:8px; border-radius:50%; }
.mini-cal-cell .dot.upcoming { background: #28a745; box-shadow: 0 0 6px rgba(40,167,69,0.18); }
.mini-cal-cell .dot.overdue { background: #dc3545; box-shadow: 0 0 6px rgba(220,53,69,0.25); }
.mini-cal-cell .dot.normal { background: #6c757d; }
.due-entry { border-left: 4px solid rgba(220,53,69,0.12) !important; }
.upcoming-entry { border-left: 4px solid rgba(40,167,69,0.12) !important; }
.overdue-entry { border-left: 4px solid rgba(220,53,69,0.2) !important; background: rgba(220,53,69,0.02); }
.priority-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-left: 6px; }
.priority-high { background: #dc3545; color: white; }
.priority-medium { background: #ffc107; color: #000; }
.priority-low { background: #28a745; color: white; }
</style>
