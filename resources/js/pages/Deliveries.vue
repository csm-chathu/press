<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-3 flex-wrap">
        <input v-model="search" @input="debouncedFetch" type="text" placeholder="Delivery number, customer…"
          class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none w-64" />
        <select v-model="statusFilter" @change="fetch(1)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="dispatched">Dispatched</option>
          <option value="delivered">Delivered</option>
          <option value="partial">Partial</option>
          <option value="returned">Returned</option>
        </select>
      </div>
      <router-link to="/deliveries/new"
        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors">
        <PlusIcon class="w-4 h-4" /> New Delivery
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
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Delivery #</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Qty</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="d in deliveries" :key="d.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-mono text-xs font-semibold text-amber-700">{{ d.delivery_number }}</td>
            <td class="px-4 py-3 text-gray-700">{{ d.customer?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ d.delivery_date }}</td>
            <td class="px-4 py-3 text-xs text-gray-500 capitalize">{{ d.delivery_method?.replace(/_/g,' ') }}</td>
            <td class="px-4 py-3 text-xs text-gray-700">{{ d.delivered_quantity }} / {{ d.total_quantity }}</td>
            <td class="px-4 py-3">
              <span :class="statusBadge(d.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize">{{ d.status }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button v-if="d.status === 'pending'" @click="markDispatched(d)" class="text-xs text-blue-600 hover:underline">Dispatch</button>
                <button v-if="['dispatched','partial'].includes(d.status)" @click="markDelivered(d)" class="text-xs text-green-600 hover:underline">Confirm Delivery</button>
              </div>
            </td>
          </tr>
          <tr v-if="!deliveries.length && !loading">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No deliveries found</td>
          </tr>
        </tbody>
      </table>

      <div v-if="meta.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
        <span>{{ meta.total }} deliveries</span>
        <div class="flex gap-2">
          <button :disabled="meta.current_page === 1" @click="fetch(meta.current_page - 1)" class="px-3 py-1.5 border border-gray-200 rounded text-xs disabled:opacity-40">Prev</button>
          <button :disabled="meta.current_page === meta.last_page" @click="fetch(meta.current_page + 1)" class="px-3 py-1.5 border border-gray-200 rounded text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon } from '@heroicons/vue/24/outline'

const deliveries   = ref([])
const loading      = ref(false)
const search       = ref('')
const statusFilter = ref('')
const meta         = ref({ current_page: 1, last_page: 1, total: 0 })

const statusBadgeMap = {
  pending: 'bg-gray-100 text-gray-600', dispatched: 'bg-blue-100 text-blue-700',
  delivered: 'bg-green-100 text-green-700', partial: 'bg-yellow-100 text-yellow-700',
  returned: 'bg-red-100 text-red-600',
}
function statusBadge(s) { return statusBadgeMap[s] ?? 'bg-gray-100 text-gray-500' }

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => fetch(1), 350) }

async function fetch(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/deliveries', { params: { page, search: search.value, status: statusFilter.value, per_page: 20 } })
    deliveries.value = data.data
    meta.value = data.meta ?? { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally {
    loading.value = false
  }
}

async function markDispatched(d) {
  try {
    await axios.put(`/api/deliveries/${d.id}`, { status: 'dispatched' })
    d.status = 'dispatched'
  } catch (e) { alert(e.response?.data?.message ?? 'Failed') }
}

async function markDelivered(d) {
  if (!confirm('Confirm delivery received by customer?')) return
  try {
    await axios.put(`/api/deliveries/${d.id}`, { status: 'delivered', delivered_quantity: d.total_quantity })
    d.status = 'delivered'
  } catch (e) { alert(e.response?.data?.message ?? 'Failed') }
}

onMounted(() => fetch())
</script>
