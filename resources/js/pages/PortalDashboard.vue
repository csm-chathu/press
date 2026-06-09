<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Top nav -->
    <nav class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-gray-800">LMUC Press</p>
          <p class="text-xs text-gray-500">Client Portal</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-right hidden sm:block">
          <p class="text-sm font-medium text-gray-800">{{ auth.user?.name }}</p>
          <p class="text-xs text-gray-500">{{ auth.user?.email }}</p>
        </div>
        <button @click="logout" class="text-xs text-red-500 hover:text-red-700 border border-red-100 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-colors">
          Logout
        </button>
      </div>
    </nav>

    <!-- Tabs -->
    <div class="bg-white border-b border-gray-100 px-4">
      <div class="flex gap-0 max-w-4xl mx-auto">
        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
          :class="activeTab === tab.key ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          class="px-4 py-3 text-sm font-medium border-b-2 transition-colors">
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto p-4 space-y-4">

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
      </div>

      <!-- Jobs tab -->
      <template v-else-if="activeTab === 'jobs'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="font-semibold text-gray-700 text-sm">Your Jobs</h2>
          </div>
          <div v-if="jobs.length" class="divide-y divide-gray-50">
            <div v-for="job in jobs" :key="job.id">
              <div class="px-4 py-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                  <p class="font-semibold text-gray-800 text-sm truncate">{{ job.title }}</p>
                  <p class="text-xs text-gray-500 font-mono mt-0.5">{{ job.job_number }}</p>
                  <p v-if="job.due_date" class="text-xs text-gray-400 mt-0.5">Due: {{ fmtDate(job.due_date) }}</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                  <p v-if="job.quantity_ordered" class="text-xs text-gray-500 hidden sm:block">{{ job.quantity_ordered?.toLocaleString() }} pcs</p>
                  <span :class="jobStatusColor(job.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap">
                    {{ jobStatusLabel(job.status) }}
                  </span>
                  <!-- Proof approval action button -->
                  <button v-if="job.status === 'proof_approval' && !job._decided"
                    @click="job._showProof = !job._showProof"
                    class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg font-semibold transition-colors">
                    Review Proof
                  </button>
                  <span v-if="job._decided" :class="job._decided === 'approved' ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'"
                    class="text-xs px-2.5 py-1 rounded-full font-semibold capitalize">{{ job._decided }}</span>
                </div>
              </div>
              <!-- Proof approval panel -->
              <div v-if="job._showProof && !job._decided" class="mx-4 mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-xl space-y-3">
                <p class="text-sm font-semibold text-yellow-800">Proof Approval — {{ job.title }}</p>
                <p class="text-xs text-yellow-700">Your print proof is ready for review. Please approve to proceed to plate making, or reject with feedback.</p>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Notes / Feedback (optional)</label>
                  <textarea v-model="proofNotes[job.id]" rows="2" placeholder="Any changes needed, feedback for the designer…"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                  <button @click="submitProof(job, 'approved')" :disabled="proofSubmitting[job.id]"
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50">
                    ✓ Approve Proof
                  </button>
                  <button @click="submitProof(job, 'rejected')" :disabled="proofSubmitting[job.id]"
                    class="flex-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50">
                    ✗ Reject / Request Changes
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="px-4 py-10 text-center text-sm text-gray-400">No jobs found for your account</div>
        </div>
      </template>

      <!-- Quotations tab -->
      <template v-else-if="activeTab === 'quotations'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="font-semibold text-gray-700 text-sm">Your Quotations</h2>
          </div>
          <div v-if="quotations.length" class="divide-y divide-gray-50">
            <div v-for="qt in quotations" :key="qt.id" class="px-4 py-4 flex items-center justify-between gap-4">
              <div class="min-w-0">
                <p class="font-semibold text-gray-800 text-sm truncate">{{ qt.title }}</p>
                <p class="text-xs text-gray-500 font-mono mt-0.5">{{ qt.quotation_number }}</p>
                <p v-if="qt.valid_until" class="text-xs text-gray-400 mt-0.5">Valid until: {{ fmtDate(qt.valid_until) }}</p>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <p class="text-sm font-semibold text-gray-800 hidden sm:block">Rs. {{ fmt(qt.total) }}</p>
                <span :class="qtStatusColor(qt.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">{{ qt.status }}</span>
              </div>
            </div>
          </div>
          <div v-else class="px-4 py-10 text-center text-sm text-gray-400">No quotations found for your account</div>
        </div>
      </template>

      <!-- Orders tab -->
      <template v-else-if="activeTab === 'orders'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="font-semibold text-gray-700 text-sm">Your Orders</h2>
          </div>
          <div v-if="orders.length" class="divide-y divide-gray-50">
            <div v-for="ord in orders" :key="ord.id" class="px-4 py-4 flex items-center justify-between gap-4">
              <div class="min-w-0">
                <p class="font-semibold text-gray-800 text-sm font-mono">{{ ord.invoice_number }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ fmtDate(ord.sold_at) }}</p>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <p class="text-sm font-semibold text-gray-800">Rs. {{ fmt(ord.total) }}</p>
                <span :class="ord.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                  class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">{{ ord.payment_status }}</span>
              </div>
            </div>
          </div>
          <div v-else class="px-4 py-10 text-center text-sm text-gray-400">No orders found for your account</div>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const auth   = useAuthStore()
const router = useRouter()

const activeTab       = ref('jobs')
const loading         = ref(true)
const jobs            = ref([])
const quotations      = ref([])
const orders          = ref([])
const proofNotes      = ref({})
const proofSubmitting = ref({})

const tabs = [
  { key: 'jobs',       label: 'Job Status' },
  { key: 'quotations', label: 'Quotations' },
  { key: 'orders',     label: 'Orders' },
]

const statusLabels = {
  waiting: 'Waiting', designing: 'Designing', proof_approval: 'Proof Approval',
  plate_making: 'Plate Making', printing: 'Printing', finishing: 'Finishing',
  quality_check: 'QC', ready: 'Ready for Dispatch', delivered: 'Delivered',
}
function jobStatusLabel(s) { return statusLabels[s] ?? s }
function jobStatusColor(s) {
  return { waiting: 'bg-gray-100 text-gray-600', designing: 'bg-blue-100 text-blue-700',
    proof_approval: 'bg-yellow-100 text-yellow-700', plate_making: 'bg-orange-100 text-orange-700',
    printing: 'bg-purple-100 text-purple-700', finishing: 'bg-indigo-100 text-indigo-700',
    quality_check: 'bg-teal-100 text-teal-700', ready: 'bg-green-100 text-green-700',
    delivered: 'bg-gray-100 text-gray-500' }[s] ?? 'bg-gray-100 text-gray-600'
}
function qtStatusColor(s) {
  return { draft: 'bg-gray-100 text-gray-600', sent: 'bg-blue-100 text-blue-700',
    approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-600',
    converted: 'bg-amber-100 text-amber-700' }[s] ?? 'bg-gray-100 text-gray-500'
}
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-LK', { day: 'numeric', month: 'short', year: 'numeric' })
}
function fmt(v) { return Number(v ?? 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }

async function submitProof(job, decision) {
  proofSubmitting.value[job.id] = true
  try {
    await axios.post(`/api/portal/jobs/${job.id}/proof-decision`, {
      decision,
      notes: proofNotes.value[job.id] || null,
    })
    job._decided   = decision
    job._showProof = false
  } catch (e) {
    alert(e.response?.data?.message ?? 'Submission failed')
  } finally {
    proofSubmitting.value[job.id] = false
  }
}

async function logout() {
  await auth.logout()
  router.push('/portal/login')
}

onMounted(async () => {
  try {
    const [jRes, qRes, oRes] = await Promise.all([
      axios.get('/api/portal/jobs'),
      axios.get('/api/portal/quotations'),
      axios.get('/api/portal/orders'),
    ])
    jobs.value       = jRes.data.data
    quotations.value = qRes.data.data
    orders.value     = oRes.data.data
  } finally {
    loading.value = false
  }
})
</script>
