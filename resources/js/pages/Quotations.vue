<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-3 flex-wrap">
        <input v-model="search" @input="debouncedFetch" type="text" placeholder="Search by number, title, customer…"
          class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none w-64" />
        <select v-model="statusFilter" @change="fetch(1)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="sent">Sent</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="converted">Converted</option>
        </select>
      </div>
      <router-link to="/quotations/new"
        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors">
        <PlusIcon class="w-4 h-4" /> New Quotation
      </router-link>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
      </div>
      <table v-else class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Number</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Valid Until</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="q in quotations" :key="q.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-mono text-xs font-semibold text-amber-700">{{ q.quotation_number }}</td>
            <td class="px-4 py-3 text-gray-700">{{ q.customer?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-800 max-w-[200px] truncate">{{ q.title }}</td>
            <td class="px-4 py-3 font-semibold text-gray-800">Rs. {{ fmt(q.total) }}</td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ q.valid_until ?? '—' }}</td>
            <td class="px-4 py-3">
              <span :class="statusBadge(q.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize">{{ q.status }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <router-link :to="`/quotations/${q.id}`" class="text-xs text-blue-600 hover:underline">View</router-link>
                <button v-if="q.status !== 'converted'" @click="convertQuotation(q)"
                  class="text-xs text-green-600 hover:underline">Convert</button>
                <button v-if="['draft','sent'].includes(q.status)" @click="deleteQuotation(q)"
                  class="text-xs text-red-500 hover:underline">Delete</button>
              </div>
            </td>
          </tr>
          <tr v-if="!quotations.length && !loading">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No quotations found</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
        <span>Page {{ meta.current_page }} of {{ meta.last_page }} — {{ meta.total }} quotations</span>
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
import { useRouter } from 'vue-router'
import axios from 'axios'
import { PlusIcon } from '@heroicons/vue/24/outline'

const router       = useRouter()
const quotations   = ref([])
const loading      = ref(false)
const search       = ref('')
const statusFilter = ref('')
const meta         = ref({ current_page: 1, last_page: 1, total: 0 })

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetch(1), 350)
}

async function fetch(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/quotations', {
      params: { page, search: search.value, status: statusFilter.value, per_page: 20 },
    })
    quotations.value = data.data
    meta.value       = data.meta ?? { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally {
    loading.value = false
  }
}

async function convertQuotation(q) {
  if (!confirm(`Convert quotation ${q.quotation_number} to a Sales Order?`)) return
  try {
    const { data } = await axios.post(`/api/quotations/${q.id}/convert`)
    alert(`Converted! Order: ${data.order.invoice_number}`)
    await fetch()
    router.push('/sales')
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to convert')
  }
}

async function deleteQuotation(q) {
  if (!confirm(`Delete quotation ${q.quotation_number}?`)) return
  try {
    await axios.delete(`/api/quotations/${q.id}`)
    await fetch(meta.value.current_page)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Delete failed')
  }
}

const statusBadge = (s) => ({
  draft:     'bg-gray-100 text-gray-600',
  sent:      'bg-blue-100 text-blue-700',
  approved:  'bg-green-100 text-green-700',
  rejected:  'bg-red-100 text-red-600',
  converted: 'bg-amber-100 text-amber-700',
}[s] ?? 'bg-gray-100 text-gray-500')

function fmt(v) { return Number(v ?? 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }

onMounted(() => fetch())
</script>
