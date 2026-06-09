<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4 py-12">

    <!-- Header -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 bg-amber-500 rounded-2xl shadow-lg mb-4">
        <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-800">LMUC Press</h1>
      <p class="text-sm text-gray-500 mt-1">Job Tracking</p>
    </div>

    <!-- Search / display card -->
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">

        <!-- Search form -->
        <form @submit.prevent="track" class="flex gap-2 mb-5">
          <input v-model="jobNumber" type="text" placeholder="Enter job number (e.g. JC-20260606-0001)"
            class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent outline-none" />
          <button type="submit" :disabled="loading"
            class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold disabled:opacity-50 flex items-center gap-1">
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Track
          </button>
        </form>

        <!-- Error -->
        <div v-if="error" class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 px-4 py-3 rounded-xl mb-4">
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ error }}
        </div>

        <!-- Result -->
        <template v-if="job">
          <div class="border border-gray-100 rounded-xl overflow-hidden">
            <!-- Status banner -->
            <div :class="statusColor(job.status)" class="px-4 py-3 flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold opacity-70 uppercase tracking-wide">Current Status</p>
                <p class="text-lg font-bold">{{ job.status_label }}</p>
              </div>
              <div class="flex items-center justify-center w-12 h-12 bg-white/20 rounded-full">
                <span class="text-2xl">{{ statusIcon(job.status) }}</span>
              </div>
            </div>

            <!-- Details -->
            <div class="p-4 space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Job Number</span>
                <span class="font-semibold font-mono text-gray-800">{{ job.job_number }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Title</span>
                <span class="font-semibold text-gray-800 text-right max-w-xs">{{ job.title }}</span>
              </div>
              <div v-if="job.quantity_ordered" class="flex justify-between">
                <span class="text-gray-500">Quantity</span>
                <span class="font-semibold text-gray-800">{{ job.quantity_ordered?.toLocaleString() }} pcs</span>
              </div>
              <div v-if="job.due_date" class="flex justify-between">
                <span class="text-gray-500">Expected Date</span>
                <span class="font-semibold text-gray-800">{{ fmtDate(job.due_date) }}</span>
              </div>
              <div v-if="job.machine" class="flex justify-between">
                <span class="text-gray-500">Machine</span>
                <span class="font-semibold text-gray-800">{{ job.machine }}</span>
              </div>
            </div>

            <!-- Progress bar -->
            <div class="px-4 pb-4">
              <div class="flex justify-between text-xs text-gray-400 mb-1">
                <span>Waiting</span><span>Delivered</span>
              </div>
              <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full transition-all duration-500" :style="{ width: progressWidth(job.status) }"></div>
              </div>
              <div class="flex justify-between text-xs text-gray-300 mt-1">
                <span v-for="s in statusSteps" :key="s"
                  :class="stepActive(job.status, s) ? 'text-amber-500 font-semibold' : ''"
                  class="text-center" style="width:11%">·</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else-if="!loading && !error" class="text-center py-6 text-gray-400 text-sm">
          Enter a job number above to check its status
        </div>
      </div>

      <p class="text-center text-xs text-gray-400 mt-4">LMUC Press · For queries call us directly</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route     = useRoute()
const jobNumber = ref(route.params.number ?? '')
const job       = ref(null)
const loading   = ref(false)
const error     = ref('')

const statusSteps = ['waiting','designing','proof_approval','plate_making','printing','finishing','quality_check','ready','delivered']
const statusIdx   = (s) => statusSteps.indexOf(s)

function progressWidth(s) {
  const idx = statusIdx(s)
  if (idx < 0) return '0%'
  return `${Math.round((idx / (statusSteps.length - 1)) * 100)}%`
}

function stepActive(current, step) {
  return statusIdx(current) >= statusIdx(step)
}

function statusColor(s) {
  return {
    waiting: 'bg-gray-100 text-gray-700',
    designing: 'bg-blue-100 text-blue-800',
    proof_approval: 'bg-yellow-100 text-yellow-800',
    plate_making: 'bg-orange-100 text-orange-800',
    printing: 'bg-purple-100 text-purple-800',
    finishing: 'bg-indigo-100 text-indigo-800',
    quality_check: 'bg-teal-100 text-teal-800',
    ready: 'bg-green-100 text-green-800',
    delivered: 'bg-gray-100 text-gray-500',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

function statusIcon(s) {
  return { waiting: '⏳', designing: '🎨', proof_approval: '👁', plate_making: '⚙️',
    printing: '🖨', finishing: '✂️', quality_check: '🔍', ready: '📦', delivered: '✅' }[s] ?? '📋'
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-LK', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function track() {
  if (!jobNumber.value.trim()) return
  error.value = ''
  job.value   = null
  loading.value = true
  try {
    const { data } = await axios.get(`/api/public/track/${jobNumber.value.trim()}`)
    job.value = data
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Job not found'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (jobNumber.value) track()
})
</script>
