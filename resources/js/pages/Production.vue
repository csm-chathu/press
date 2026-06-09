<template>
  <div class="space-y-5">
    <!-- Quick stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="card in kpiCards" :key="card.label" class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">{{ card.label }}</p>
        <p class="text-2xl font-bold mt-1" :class="card.color">{{ card.value }}</p>
      </div>
    </div>

    <!-- Active Jobs Board (Kanban-like) -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-700">Production Queue</h3>
        <div class="flex gap-2">
          <select v-model="machineFilter" @change="loadActive" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-amber-400">
            <option value="">All Machines</option>
            <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>
      </div>

      <div v-if="loadingActive" class="flex items-center justify-center py-12">
        <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Job #</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Machine</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Due</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Advance</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="job in activeJobs" :key="job.id" :class="isOverdue(job) ? 'bg-red-50' : 'hover:bg-gray-50'">
              <td class="px-4 py-3 font-mono text-xs font-semibold text-amber-700">
                <router-link :to="`/job-cards/${job.id}`" class="hover:underline">{{ job.job_number }}</router-link>
              </td>
              <td class="px-4 py-3 font-medium text-gray-800 max-w-[150px] truncate">{{ job.title }}</td>
              <td class="px-4 py-3 text-gray-600 max-w-[120px] truncate">{{ job.customer?.name ?? '—' }}</td>
              <td class="px-4 py-3 text-xs text-gray-500">{{ job.machine?.name ?? '—' }}</td>
              <td class="px-4 py-3">
                <span :class="statusBadge(job.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ statusLabel(job.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs" :class="isOverdue(job) ? 'text-red-600 font-bold' : 'text-gray-500'">
                {{ job.due_date ?? '—' }}
              </td>
              <td class="px-4 py-3 text-xs text-gray-500">{{ job.status === 'printing' ? '▶ Running' : '—' }}</td>
            </tr>
            <tr v-if="!activeJobs.length">
              <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">No active jobs in production</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Machine Utilization -->
    <div v-if="machineStats.length" class="bg-white rounded-xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-700 text-sm mb-4">Machine Status</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="ms in machineStats" :key="ms.machine?.id" class="border border-gray-100 rounded-lg p-3">
          <p class="font-semibold text-gray-800 text-sm">{{ ms.machine?.name ?? 'Unknown' }}</p>
          <p class="text-xs text-gray-500 mt-1">Active jobs: <strong class="text-purple-600">{{ ms.active_jobs }}</strong></p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const activeJobs    = ref([])
const machineStats  = ref([])
const machines      = ref([])
const loadingActive = ref(false)
const machineFilter = ref('')
const dashData      = ref(null)

const statuses = {
  waiting: 'Waiting', designing: 'Designing', proof_approval: 'Proof Approval',
  plate_making: 'Plate Making', printing: 'Printing', finishing: 'Finishing',
  quality_check: 'Quality Check', ready: 'Ready', delivered: 'Delivered',
}
const statusBadgeMap = {
  waiting: 'bg-gray-100 text-gray-600', designing: 'bg-blue-100 text-blue-700',
  proof_approval: 'bg-yellow-100 text-yellow-700', plate_making: 'bg-orange-100 text-orange-700',
  printing: 'bg-purple-100 text-purple-700', finishing: 'bg-indigo-100 text-indigo-700',
  quality_check: 'bg-teal-100 text-teal-700', ready: 'bg-green-100 text-green-700',
  delivered: 'bg-gray-100 text-gray-500',
}
function statusLabel(s) { return statuses[s] ?? s }
function statusBadge(s) { return statusBadgeMap[s] ?? 'bg-gray-100 text-gray-600' }
function isOverdue(j)   { return j.due_date && new Date(j.due_date) < new Date() && !['ready','delivered'].includes(j.status) }

const kpiCards = computed(() => {
  const jobs = activeJobs.value
  return [
    { label: 'Total Active', value: jobs.length, color: 'text-purple-600' },
    { label: 'Printing Now', value: jobs.filter(j => j.status === 'printing').length, color: 'text-blue-600' },
    { label: 'Ready to Dispatch', value: jobs.filter(j => j.status === 'ready').length, color: 'text-green-600' },
    { label: 'Overdue', value: jobs.filter(j => isOverdue(j)).length, color: 'text-red-600' },
  ]
})

async function loadActive() {
  loadingActive.value = true
  try {
    const { data } = await axios.get('/api/job-cards/queue', { params: { machine_id: machineFilter.value || undefined } })
    activeJobs.value = data
  } finally {
    loadingActive.value = false
  }
}

async function loadDashboard() {
  const { data } = await axios.get('/api/production/dashboard')
  machineStats.value = data.machine_summary ?? []
}

onMounted(async () => {
  const m = await axios.get('/api/press-machines/all')
  machines.value = m.data
  await Promise.all([loadActive(), loadDashboard()])
})
</script>
