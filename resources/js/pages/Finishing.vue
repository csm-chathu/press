<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h2 class="text-lg font-semibold text-gray-800">Finishing / Post-Press</h2>
      <select v-model="statusFilter" @change="load" class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-amber-400">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Job Card</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Operations</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Completed</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="task in tasks" :key="task.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <router-link :to="`/job-cards/${task.job_card_id}`" class="font-mono text-xs text-amber-700 hover:underline">
                {{ task.job_card?.job_number }}
              </router-link>
              <p class="text-xs text-gray-500 max-w-[120px] truncate">{{ task.job_card?.title }}</p>
            </td>
            <td class="px-4 py-3 text-xs text-gray-600">{{ task.job_card?.customer?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-1">
                <span v-for="op in getOps(task)" :key="op" class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full">{{ op }}</span>
                <span v-if="!getOps(task).length" class="text-xs text-gray-400">None</span>
              </div>
              <p v-if="task.other_instructions" class="text-xs text-gray-400 mt-1 truncate">{{ task.other_instructions }}</p>
            </td>
            <td class="px-4 py-3">
              <span :class="task.status === 'completed' ? 'bg-green-100 text-green-700' : task.status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ task.status }}</span>
            </td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ task.completed_at ? new Date(task.completed_at).toLocaleDateString() : '—' }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button v-if="task.status !== 'completed'" @click="markDone(task)" class="text-xs text-green-600 hover:underline">Mark Done</button>
                <button v-if="task.status === 'pending'" @click="markStarted(task)" class="text-xs text-blue-600 hover:underline">Start</button>
              </div>
            </td>
          </tr>
          <tr v-if="!tasks.length">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">No finishing tasks found</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const tasks        = ref([])
const loading      = ref(false)
const statusFilter = ref('')

const OPS = ['cutting','folding','binding','lamination','uv_coating','foiling','die_cutting','packaging']

function getOps(task) {
  return OPS.filter(k => task[k]).map(k => k.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase()))
}

async function markStarted(task) {
  try {
    await axios.put(`/api/finishing-tasks/${task.id}`, { status: 'in_progress' })
    task.status = 'in_progress'
  } catch (e) { alert(e.response?.data?.message ?? 'Failed') }
}

async function markDone(task) {
  if (!confirm('Mark this finishing task as completed?')) return
  try {
    await axios.put(`/api/finishing-tasks/${task.id}`, { status: 'completed' })
    task.status = 'completed'
    task.completed_at = new Date().toISOString()
  } catch (e) { alert(e.response?.data?.message ?? 'Failed') }
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/finishing-tasks', { params: { status: statusFilter.value } })
    tasks.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
