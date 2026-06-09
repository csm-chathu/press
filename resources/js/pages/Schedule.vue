<template>
  <div class="space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <button @click="prevWeek" class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
          <ChevronLeftIcon class="w-4 h-4 text-gray-600" />
        </button>
        <div class="px-4 py-2 bg-white rounded-lg border border-gray-200 text-sm font-semibold text-gray-800 min-w-[200px] text-center">
          Week of {{ weekLabel }}
        </div>
        <button @click="nextWeek" class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
          <ChevronRightIcon class="w-4 h-4 text-gray-600" />
        </button>
        <button @click="goToday" class="px-3 py-2 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">Today</button>
      </div>
      <div class="flex items-center gap-2 text-xs text-gray-500">
        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-100 text-red-700 font-medium">⚠ Overbooked (3+)</span>
        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-amber-100 text-amber-700 font-medium">★ Today</span>
        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-100 text-red-700 font-medium">RUSH</span>
      </div>
    </div>

    <div class="flex gap-4 items-start">

      <!-- Main schedule grid -->
      <div class="flex-1 min-w-0 space-y-4">

        <!-- Unscheduled jobs pool -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Unscheduled Jobs
              <span v-if="unscheduledJobs.length" class="ml-2 text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-semibold">{{ unscheduledJobs.length }}</span>
            </h3>
            <p class="text-xs text-gray-400">Drag jobs to the calendar below to schedule them</p>
          </div>
          <div
            class="p-3 min-h-[60px] flex flex-wrap gap-2 transition-colors"
            :class="dropTarget === 'unscheduled' ? 'bg-blue-50 ring-2 ring-blue-300 ring-inset' : ''"
            @dragover.prevent="dropTarget = 'unscheduled'"
            @dragleave="dropTarget = null"
            @drop.prevent="dropToUnscheduled">
            <div v-if="!unscheduledJobs.length && dropTarget !== 'unscheduled'" class="text-xs text-gray-400 py-2 px-1">All jobs are scheduled this week</div>
            <div
              v-for="job in unscheduledJobs" :key="job.id"
              :draggable="true"
              @dragstart="startDrag(job)"
              @dragend="dragging = null"
              :class="jobChipClass(job)"
              class="px-2.5 py-1.5 rounded-lg text-xs font-medium cursor-grab active:cursor-grabbing select-none border shadow-sm hover:shadow transition-shadow max-w-[200px]">
              <div class="flex items-center gap-1 min-w-0">
                <span v-if="job.is_priority" class="text-red-600 font-bold shrink-0">!</span>
                <span class="truncate font-semibold">{{ job.job_number }}</span>
              </div>
              <div class="truncate text-[11px] opacity-80 mt-0.5">{{ job.title }}</div>
            </div>
          </div>
        </div>

        <!-- Week grid -->
        <div v-if="loading" class="flex items-center justify-center py-16 bg-white rounded-xl border border-gray-200">
          <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
        </div>

        <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <!-- Day headers -->
          <div class="grid border-b border-gray-200" :style="`grid-template-columns: 160px repeat(7, 1fr)`">
            <div class="px-3 py-2.5 text-xs font-semibold text-gray-500 border-r border-gray-100 bg-gray-50">Machine</div>
            <div v-for="day in weekDays" :key="day.date"
              :class="[
                'px-2 py-2.5 text-center text-xs font-semibold border-r border-gray-100 last:border-r-0',
                day.isToday ? 'bg-amber-50 text-amber-700' : 'bg-gray-50 text-gray-500'
              ]">
              <div>{{ day.dayName }}</div>
              <div :class="day.isToday ? 'text-amber-600 font-bold' : 'text-gray-400'" class="text-[11px]">{{ day.dateLabel }}</div>
            </div>
          </div>

          <!-- Machine rows -->
          <div v-for="machine in machines" :key="machine.id" class="grid border-b border-gray-100 last:border-b-0"
            :style="`grid-template-columns: 160px repeat(7, 1fr)`">
            <!-- Machine name -->
            <div class="px-3 py-2 border-r border-gray-100 bg-gray-50/50 flex flex-col justify-center">
              <p class="text-xs font-semibold text-gray-700 leading-tight">{{ machine.name }}</p>
              <p class="text-[11px] text-gray-400 capitalize mt-0.5">{{ machine.machine_type }}</p>
            </div>
            <!-- Day cells -->
            <div v-for="day in weekDays" :key="day.date"
              :class="[
                'min-h-[80px] p-1.5 border-r border-gray-100 last:border-r-0 transition-colors',
                day.isToday ? 'bg-amber-50/40' : '',
                dropTarget === cellKey(machine.id, day.date) ? 'bg-blue-50 ring-2 ring-inset ring-blue-300' : '',
                isOverbooked(machine.id, day.date) ? 'bg-red-50/60' : '',
              ]"
              @dragover.prevent="dropTarget = cellKey(machine.id, day.date)"
              @dragleave="dropTarget = null"
              @drop.prevent="dropToCell(machine.id, day.date)">

              <!-- Overbooked warning -->
              <div v-if="isOverbooked(machine.id, day.date)" class="text-[10px] text-red-500 font-semibold mb-1 px-0.5">⚠ Overbooked</div>

              <!-- Job chips in this cell -->
              <div
                v-for="job in cellJobs(machine.id, day.date)" :key="job.id"
                :draggable="true"
                @dragstart="startDrag(job)"
                @dragend="dragging = null"
                :class="jobChipClass(job)"
                class="mb-1 px-2 py-1 rounded text-[11px] font-medium cursor-grab active:cursor-grabbing select-none border hover:shadow transition-shadow">
                <div class="flex items-center gap-1 min-w-0">
                  <span v-if="job.is_priority" class="text-red-600 font-bold shrink-0 text-[10px]">!</span>
                  <span class="truncate font-semibold text-[10px]">{{ job.job_number }}</span>
                </div>
                <div class="truncate text-[10px] opacity-75">{{ job.title }}</div>
                <div v-if="job.customer" class="text-[9px] opacity-60 truncate">{{ job.customer.name }}</div>
              </div>
            </div>
          </div>

          <div v-if="!machines.length" class="px-4 py-10 text-center text-sm text-gray-400">
            No active machines found. Add machines in Press Settings.
          </div>
        </div>
      </div>

      <!-- Right sidebar -->
      <div class="w-72 shrink-0 space-y-4">

        <!-- Due Date Alerts -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-2.5 border-b border-gray-100 flex items-center gap-2">
            <ExclamationTriangleIcon class="w-4 h-4 text-red-500" />
            <h3 class="text-sm font-semibold text-gray-700">Due Date Alerts</h3>
            <span v-if="alerts.length" class="ml-auto text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-semibold">{{ alerts.length }}</span>
          </div>
          <div v-if="alertsLoading" class="flex justify-center py-6">
            <div class="w-5 h-5 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
          </div>
          <div v-else-if="alerts.length" class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
            <router-link
              v-for="alert in alerts" :key="alert.id"
              :to="`/job-cards/${alert.id}`"
              :class="alert.overdue ? 'hover:bg-red-50' : 'hover:bg-amber-50'"
              class="block px-3 py-2.5 transition-colors">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ alert.job_number }}</p>
                  <p class="text-[11px] text-gray-500 truncate mt-0.5">{{ alert.title }}</p>
                  <p v-if="alert.customer" class="text-[10px] text-gray-400 truncate">{{ alert.customer }}</p>
                </div>
                <div class="shrink-0 text-right">
                  <span :class="alert.overdue ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'"
                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-full block mb-1">
                    {{ alert.overdue ? 'OVERDUE' : 'DUE SOON' }}
                  </span>
                  <p class="text-[10px] text-gray-400">{{ fmtDate(alert.due_date) }}</p>
                </div>
              </div>
            </router-link>
          </div>
          <div v-else class="px-4 py-6 text-center text-xs text-gray-400">
            No upcoming due date alerts
          </div>
        </div>

        <!-- Operator Workload -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-2.5 border-b border-gray-100 flex items-center gap-2">
            <UsersIcon class="w-4 h-4 text-blue-500" />
            <h3 class="text-sm font-semibold text-gray-700">Operator Workload</h3>
          </div>
          <div v-if="workloadLoading" class="flex justify-center py-6">
            <div class="w-5 h-5 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
          </div>
          <div v-else-if="workload.length" class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
            <div v-for="op in workload" :key="op.id" class="px-3 py-2.5 flex items-center gap-3">
              <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700 shrink-0">
                {{ op.name.charAt(0) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-800 truncate">{{ op.name }}</p>
                <p class="text-[10px] text-gray-400 capitalize">{{ op.role.replace(/_/g, ' ') }}</p>
              </div>
              <div class="shrink-0 text-right">
                <span :class="op.job_count === 0 ? 'bg-gray-100 text-gray-500' : op.job_count >= 5 ? 'bg-red-100 text-red-700' : op.job_count >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'"
                  class="text-xs font-bold px-2 py-0.5 rounded-full">
                  {{ op.job_count }} {{ op.job_count === 1 ? 'job' : 'jobs' }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="px-4 py-6 text-center text-xs text-gray-400">No operators found</div>
        </div>

        <!-- Legend -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-2">
          <p class="text-xs font-semibold text-gray-600 mb-2">Status Colors</p>
          <div v-for="item in statusLegend" :key="item.label" class="flex items-center gap-2">
            <span :class="item.cls" class="w-3 h-3 rounded shrink-0 border"></span>
            <span class="text-[11px] text-gray-600">{{ item.label }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ChevronLeftIcon, ChevronRightIcon,
  ExclamationTriangleIcon, UsersIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'

// ── Week state ─────────────────────────────────────────────────────
const today       = new Date()
const currentWeek = ref(mondayOf(today))

function mondayOf(d) {
  const dt = new Date(d)
  const day = dt.getDay()
  const diff = (day === 0 ? -6 : 1 - day)
  dt.setDate(dt.getDate() + diff)
  dt.setHours(0, 0, 0, 0)
  return dt
}

function isoDate(d) {
  return d.toISOString().split('T')[0]
}

function prevWeek() {
  const d = new Date(currentWeek.value)
  d.setDate(d.getDate() - 7)
  currentWeek.value = d
  load()
}
function nextWeek() {
  const d = new Date(currentWeek.value)
  d.setDate(d.getDate() + 7)
  currentWeek.value = d
  load()
}
function goToday() {
  currentWeek.value = mondayOf(new Date())
  load()
}

const weekDays = computed(() => {
  const days = []
  const todayStr = isoDate(new Date())
  for (let i = 0; i < 7; i++) {
    const d = new Date(currentWeek.value)
    d.setDate(d.getDate() + i)
    const dateStr = isoDate(d)
    days.push({
      date:      dateStr,
      dayName:   d.toLocaleDateString('en-US', { weekday: 'short' }),
      dateLabel: d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' }),
      isToday:   dateStr === todayStr,
    })
  }
  return days
})

const weekLabel = computed(() => {
  const end = new Date(currentWeek.value)
  end.setDate(end.getDate() + 6)
  return currentWeek.value.toLocaleDateString('en-US', { day: 'numeric', month: 'short' }) +
    ' – ' + end.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
})

// ── Data ───────────────────────────────────────────────────────────
const loading        = ref(true)
const alertsLoading  = ref(true)
const workloadLoading = ref(true)
const machines       = ref([])
const jobs           = ref([])
const alerts         = ref([])
const workload       = ref([])

// ── Drag & Drop ────────────────────────────────────────────────────
const dragging   = ref(null)
const dropTarget = ref(null)

function startDrag(job) {
  dragging.value = job
  dropTarget.value = null
}

function cellKey(machineId, date) {
  return `${machineId}::${date}`
}

function cellJobs(machineId, date) {
  return jobs.value.filter(j =>
    j.scheduled_date === date &&
    j.machine_id     === machineId
  )
}

function isOverbooked(machineId, date) {
  return cellJobs(machineId, date).length >= 3
}

const unscheduledJobs = computed(() =>
  jobs.value.filter(j => !j.scheduled_date || !j.machine_id)
)

async function dropToCell(machineId, date) {
  dropTarget.value = null
  const job = dragging.value
  dragging.value   = null
  if (!job) return
  await reschedule(job, machineId, date)
}

async function dropToUnscheduled() {
  dropTarget.value = null
  const job = dragging.value
  dragging.value   = null
  if (!job) return
  await reschedule(job, null, null)
}

async function reschedule(job, machineId, date) {
  const oldMachine = job.machine_id
  const oldDate    = job.scheduled_date
  // Optimistic update
  job.machine_id      = machineId
  job.scheduled_date  = date
  if (machineId && machines.value.find(m => m.id === machineId)) {
    job.machine = machines.value.find(m => m.id === machineId)
  }
  try {
    await axios.patch(`/api/job-cards/${job.id}/reschedule`, {
      machine_id:     machineId,
      scheduled_date: date,
    })
  } catch {
    // Revert
    job.machine_id     = oldMachine
    job.scheduled_date = oldDate
    alert('Failed to reschedule job')
  }
}

// ── Load ───────────────────────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/schedule', {
      params: { week: isoDate(currentWeek.value) },
    })
    machines.value = data.machines
    jobs.value     = data.jobs
  } finally {
    loading.value = false
  }
}

async function loadAlerts() {
  alertsLoading.value = true
  try {
    const { data } = await axios.get('/api/schedule/alerts')
    alerts.value = data
  } finally {
    alertsLoading.value = false
  }
}

async function loadWorkload() {
  workloadLoading.value = true
  try {
    const { data } = await axios.get('/api/schedule/workload')
    workload.value = data
  } finally {
    workloadLoading.value = false
  }
}

// ── Helpers ────────────────────────────────────────────────────────
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { day: 'numeric', month: 'short' })
}

const statusColors = {
  waiting:        { bg: 'bg-gray-100', border: 'border-gray-300', text: 'text-gray-700' },
  designing:      { bg: 'bg-blue-50',  border: 'border-blue-200', text: 'text-blue-800' },
  proof_approval: { bg: 'bg-yellow-50',border: 'border-yellow-300',text: 'text-yellow-800'},
  plate_making:   { bg: 'bg-orange-50',border: 'border-orange-300',text: 'text-orange-800'},
  printing:       { bg: 'bg-purple-50',border: 'border-purple-300',text: 'text-purple-800'},
  finishing:      { bg: 'bg-indigo-50',border: 'border-indigo-300',text: 'text-indigo-800'},
  quality_check:  { bg: 'bg-teal-50',  border: 'border-teal-300', text: 'text-teal-800' },
  ready:          { bg: 'bg-green-50', border: 'border-green-300', text: 'text-green-800'},
}

function jobChipClass(job) {
  const c = statusColors[job.status] ?? statusColors.waiting
  const priority = job.is_priority ? 'ring-1 ring-red-400' : ''
  return `${c.bg} ${c.border} ${c.text} ${priority}`
}

const statusLegend = [
  { label: 'Waiting',        cls: 'bg-gray-100 border-gray-300' },
  { label: 'Designing',      cls: 'bg-blue-50 border-blue-200' },
  { label: 'Proof Approval', cls: 'bg-yellow-50 border-yellow-300' },
  { label: 'Plate Making',   cls: 'bg-orange-50 border-orange-300' },
  { label: 'Printing',       cls: 'bg-purple-50 border-purple-300' },
  { label: 'Finishing',      cls: 'bg-indigo-50 border-indigo-300' },
  { label: 'Quality Check',  cls: 'bg-teal-50 border-teal-300' },
  { label: 'Ready',          cls: 'bg-green-50 border-green-300' },
]

onMounted(() => {
  load()
  loadAlerts()
  loadWorkload()
})
</script>
