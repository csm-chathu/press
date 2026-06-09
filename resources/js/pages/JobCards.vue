<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-3 flex-wrap">
        <input v-model="search" @input="debouncedFetch" type="text" placeholder="Job number, title, customer…"
          class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none w-64" />
        <select v-model="statusFilter" @change="fetch(1)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none">
          <option value="">All Status</option>
          <option v-for="(label, val) in statuses" :key="val" :value="val">{{ label }}</option>
        </select>
      </div>
      <router-link to="/job-cards/new"
        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors">
        <PlusIcon class="w-4 h-4" /> New Job Card
      </router-link>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Job #</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Machine</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="job in jobs" :key="job.id" :class="isOverdue(job) ? 'bg-red-50' : 'hover:bg-gray-50'" class="transition-colors">
              <td class="px-4 py-3 font-mono text-xs font-semibold text-amber-700">{{ job.job_number }}</td>
              <td class="px-4 py-3 max-w-[160px]">
                <div class="flex items-center gap-1.5">
                  <span v-if="job.is_priority" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 flex-shrink-0">RUSH</span>
                  <span class="truncate font-medium text-gray-800">{{ job.title }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 max-w-[120px] truncate">{{ job.customer?.name ?? '—' }}</td>
              <td class="px-4 py-3 text-xs text-gray-500">{{ job.machine?.name ?? '—' }}</td>
              <td class="px-4 py-3">
                <select :value="job.status" @change="(e) => quickStatus(job, e.target.value)"
                  :class="statusBadge(job.status)" class="text-xs font-semibold px-2 py-0.5 rounded-full border-0 cursor-pointer outline-none">
                  <option v-for="(label, val) in statuses" :key="val" :value="val">{{ label }}</option>
                </select>
              </td>
              <td class="px-4 py-3 text-xs" :class="isOverdue(job) ? 'text-red-600 font-bold' : 'text-gray-500'">
                {{ job.due_date ?? '—' }}
                <span v-if="isOverdue(job)" class="ml-1 text-red-500">⚠ OVERDUE</span>
              </td>
              <td class="px-4 py-3">
                <router-link :to="`/job-cards/${job.id}`" class="text-xs text-blue-600 hover:underline">Details</router-link>
              </td>
            </tr>
            <tr v-if="!jobs.length && !loading">
              <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No job cards found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
        <span>{{ meta.total }} job cards</span>
        <div class="flex gap-2">
          <button :disabled="meta.current_page === 1" @click="fetch(meta.current_page - 1)"
            class="px-3 py-1.5 border border-gray-200 rounded text-xs disabled:opacity-40 hover:bg-gray-50">Prev</button>
          <button :disabled="meta.current_page === meta.last_page" @click="fetch(meta.current_page + 1)"
            class="px-3 py-1.5 border border-gray-200 rounded text-xs disabled:opacity-40 hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon } from '@heroicons/vue/24/outline'

const jobs         = ref([])
const loading      = ref(false)
const search       = ref('')
const statusFilter = ref('')
const meta         = ref({ current_page: 1, last_page: 1, total: 0 })

const statuses = {
  waiting: 'Waiting', designing: 'Designing', proof_approval: 'Proof Approval',
  plate_making: 'Plate Making', printing: 'Printing', finishing: 'Finishing',
  quality_check: 'Quality Check', ready: 'Ready for Dispatch', delivered: 'Delivered',
}

const statusBadgeMap = {
  waiting: 'bg-gray-100 text-gray-600', designing: 'bg-blue-100 text-blue-700',
  proof_approval: 'bg-yellow-100 text-yellow-700', plate_making: 'bg-orange-100 text-orange-700',
  printing: 'bg-purple-100 text-purple-700', finishing: 'bg-indigo-100 text-indigo-700',
  quality_check: 'bg-teal-100 text-teal-700', ready: 'bg-green-100 text-green-700',
  delivered: 'bg-gray-100 text-gray-500',
}
function statusBadge(s) { return statusBadgeMap[s] ?? 'bg-gray-100 text-gray-500' }
function isOverdue(j) { return j.due_date && new Date(j.due_date) < new Date() && !['ready', 'delivered'].includes(j.status) }

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetch(1), 350)
}

async function fetch(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/job-cards', {
      params: { page, search: search.value, status: statusFilter.value, per_page: 20 },
    })
    jobs.value = data.data
    meta.value = data.meta ?? { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally {
    loading.value = false
  }
}

async function quickStatus(job, status) {
  if (status === job.status) return
  try {
    await axios.patch(`/api/job-cards/${job.id}/status`, { status })
    job.status = status
  } catch (e) {
    alert(e.response?.data?.message ?? 'Status update failed')
  }
}

onMounted(() => fetch())
</script>
