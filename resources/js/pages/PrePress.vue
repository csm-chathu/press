<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-800">Pre-Press Management</h2>
      <div class="flex gap-3">
        <select v-model="statusFilter" @change="load" class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-amber-400">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="artwork_received">Artwork Received</option>
          <option value="proof_sent">Proof Sent</option>
          <option value="revision_requested">Revision Requested</option>
          <option value="proof_approved">Proof Approved</option>
          <option value="plates_ready">Plates Ready</option>
        </select>
      </div>
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
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Artwork</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Proof</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Plates</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rev.</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="task in tasks" :key="task.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <router-link :to="`/job-cards/${task.job_card_id}`" class="font-mono text-xs text-amber-700 hover:underline">
                {{ task.job_card?.job_number }}
              </router-link>
              <p class="text-xs text-gray-500 truncate max-w-[120px]">{{ task.job_card?.title }}</p>
            </td>
            <td class="px-4 py-3 text-xs text-gray-600">{{ task.job_card?.customer?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <span :class="prepressStatusBadge(task.status)" class="text-xs px-2 py-0.5 rounded-full font-medium">{{ task.status?.replace(/_/g,' ') }}</span>
            </td>
            <td class="px-4 py-3 text-xs" :class="task.artwork_uploaded_at ? 'text-green-600' : 'text-gray-400'">
              {{ task.artwork_uploaded_at ? '✓ Received' : '⏳ Pending' }}
            </td>
            <td class="px-4 py-3 text-xs" :class="task.proof_approved_at ? 'text-green-600' : task.proof_sent_at ? 'text-yellow-600' : 'text-gray-400'">
              {{ task.proof_approved_at ? '✓ Approved' : task.proof_sent_at ? '⏳ Sent' : '—' }}
            </td>
            <td class="px-4 py-3 text-xs" :class="task.plate_status === 'completed' ? 'text-green-600' : task.plate_status === 'in_progress' ? 'text-blue-600' : 'text-gray-400'">
              {{ task.plate_status?.replace(/_/g,' ') ?? '—' }}
            </td>
            <td class="px-4 py-3 text-xs text-center font-semibold" :class="task.revision_count > 0 ? 'text-orange-600' : 'text-gray-400'">
              {{ task.revision_count }}
            </td>
            <td class="px-4 py-3">
              <button @click="openUpdate(task)" class="text-xs text-blue-600 hover:underline">Update</button>
            </td>
          </tr>
          <tr v-if="!tasks.length">
            <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">No pre-press tasks found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Update Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg mx-4">
        <h3 class="font-semibold text-gray-800 mb-4">Update Pre-Press Status</h3>
        <form @submit.prevent="saveUpdate" class="space-y-4">
          <div>
            <label class="label">Status</label>
            <select v-model="updateForm.status" class="input w-full">
              <option value="pending">Pending</option>
              <option value="artwork_received">Artwork Received</option>
              <option value="proof_sent">Proof Sent</option>
              <option value="revision_requested">Revision Requested</option>
              <option value="proof_approved">Proof Approved</option>
              <option value="plates_ready">Plates Ready</option>
            </select>
          </div>
          <div>
            <label class="label">Plate Status</label>
            <select v-model="updateForm.plate_status" class="input w-full">
              <option value="not_started">Not Started</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Plate Count</label>
              <input v-model.number="updateForm.plate_count" type="number" class="input w-full" />
            </div>
            <div>
              <label class="label">Revision Count</label>
              <input v-model.number="updateForm.revision_count" type="number" class="input w-full" />
            </div>
          </div>
          <div>
            <label class="label">Notes</label>
            <textarea v-model="updateForm.notes" rows="2" class="input w-full"></textarea>
          </div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Update' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const tasks        = ref([])
const loading      = ref(false)
const statusFilter = ref('')
const showModal    = ref(false)
const saving       = ref(false)
const selectedTask = ref(null)
const updateForm   = ref({ status: '', plate_status: '', plate_count: 0, revision_count: 0, notes: '' })

const prepressStatusMap = {
  pending: 'bg-gray-100 text-gray-600', artwork_received: 'bg-blue-100 text-blue-700',
  proof_sent: 'bg-yellow-100 text-yellow-700', revision_requested: 'bg-orange-100 text-orange-700',
  proof_approved: 'bg-teal-100 text-teal-700', plates_ready: 'bg-green-100 text-green-700',
}
function prepressStatusBadge(s) { return prepressStatusMap[s] ?? 'bg-gray-100 text-gray-500' }

function openUpdate(task) {
  selectedTask.value = task
  updateForm.value = {
    status: task.status, plate_status: task.plate_status ?? 'not_started',
    plate_count: task.plate_count ?? 0, revision_count: task.revision_count ?? 0, notes: task.notes ?? '',
  }
  showModal.value = true
}

async function saveUpdate() {
  saving.value = true
  try {
    const { data } = await axios.put(`/api/prepress-tasks/${selectedTask.value.id}`, updateForm.value)
    const idx = tasks.value.findIndex(t => t.id === selectedTask.value.id)
    if (idx !== -1) tasks.value[idx] = { ...tasks.value[idx], ...data }
    showModal.value = false
  } catch (e) {
    alert(e.response?.data?.message ?? 'Update failed')
  } finally {
    saving.value = false
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/prepress-tasks', { params: { status: statusFilter.value } })
    tasks.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input { @apply border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none; }
.btn-primary { @apply bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50; }
.btn-secondary { @apply border border-gray-200 text-gray-600 hover:bg-gray-50 px-5 py-2 rounded-lg text-sm font-semibold transition-colors; }
</style>
