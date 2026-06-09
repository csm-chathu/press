<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex gap-3">
        <input v-model="search" type="search" placeholder="PO number…" class="form-input w-44" @input="debouncedFetch" />
        <select v-model="supplierFilter" class="form-input w-44" @change="fetch">
          <option value="">All suppliers</option>
          <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <router-link to="/purchases/new" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Purchase
      </router-link>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th w-8"></th>
            <th class="table-th">PO Number</th>
            <th class="table-th">Supplier</th>
            <th class="table-th">Date</th>
            <th class="table-th text-right">Total</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="p in purchases.data" :key="p.id">
            <tr class="hover:bg-gray-50 border-b border-gray-100 cursor-pointer" @click="toggleExpand(p)">
              <td class="table-td w-8 text-center">
                <ChevronRightIcon class="w-4 h-4 text-gray-400 transition-transform inline-block"
                  :class="{ 'rotate-90': expandedId === p.id }" />
              </td>
              <td class="table-td font-mono text-xs font-semibold text-gray-700">{{ p.purchase_number }}</td>
              <td class="table-td">{{ p.supplier?.name }}</td>
              <td class="table-td text-gray-500 text-xs">{{ new Date(p.purchased_at).toLocaleDateString() }}</td>
              <td class="table-td text-right font-semibold">LKR {{ Number(p.total).toLocaleString() }}</td>
              <td class="table-td">
                <span :class="statusClass(p.status)" class="badge capitalize">{{ p.status }}</span>
              </td>
              <td class="table-td" @click.stop>
                <div class="flex items-center gap-1.5">
                  <button @click="downloadPdf(p)" :disabled="downloadingId === p.id"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" :class="downloadingId === p.id ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path v-if="downloadingId !== p.id" stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                      <circle v-else class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    </svg>
                    PDF
                  </button>
                  <button @click="del(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>

            <!-- Expanded items row -->
            <tr v-if="expandedId === p.id" class="bg-amber-50/50">
              <td colspan="7" class="px-6 py-3">
                <div v-if="loadingItems" class="flex items-center gap-2 text-sm text-gray-400 py-2">
                  <ArrowPathIcon class="w-4 h-4 animate-spin" /> Loading items…
                </div>
                <div v-else-if="(expandedItems[p.id] ?? []).length === 0" class="text-sm text-gray-400 py-2">
                  No items on this purchase order.
                </div>
                <table v-else class="w-full text-sm">
                  <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wide">
                      <th class="pb-2 text-left font-semibold">Product</th>
                      <th class="pb-2 text-right font-semibold">Qty</th>
                      <th class="pb-2 text-right font-semibold">Unit Cost</th>
                      <th class="pb-2 text-right font-semibold">Line Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-amber-100">
                    <tr v-for="item in expandedItems[p.id]" :key="item.id">
                      <td class="py-1.5 text-gray-700 font-medium">{{ item.product?.name ?? '—' }}</td>
                      <td class="py-1.5 text-right text-gray-600">{{ item.quantity }}</td>
                      <td class="py-1.5 text-right text-gray-600">LKR {{ Number(item.unit_cost).toLocaleString() }}</td>
                      <td class="py-1.5 text-right font-semibold text-amber-700">LKR {{ Number(item.total).toLocaleString() }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="border-t border-amber-200">
                      <td colspan="3" class="pt-2 text-xs text-gray-500 font-semibold text-right">Order Total</td>
                      <td class="pt-2 text-right font-bold text-amber-700">LKR {{ Number(p.total).toLocaleString() }}</td>
                    </tr>
                  </tfoot>
                </table>
              </td>
            </tr>
          </template>

          <tr v-if="!purchases.data?.length">
            <td colspan="7" class="table-td text-center text-gray-400 py-8">No purchases</td>
          </tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ purchases.total ?? 0 }} records</span>
        <div class="flex gap-2">
          <button @click="page--; fetch()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetch()" :disabled="page>=purchases.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, TrashIcon, ChevronRightIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/components/ConfirmModal.vue'

const purchases      = ref({ data: [] })
const suppliers      = ref([])
const search         = ref(''); const page = ref(1)
const supplierFilter = ref('')
const expandedId     = ref(null)
const expandedItems  = ref({})
const loadingItems   = ref(false)
const confirmDelete  = ref(null)
const confirmMessage = ref('')
const downloadingId  = ref(null)

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value=1; fetch() }, 400) }

async function fetch() {
  const { data } = await axios.get('/api/purchases', { params: { page: page.value, search: search.value, supplier_id: supplierFilter.value } })
  purchases.value = data
}

async function toggleExpand(p) {
  if (expandedId.value === p.id) {
    expandedId.value = null
    return
  }
  expandedId.value = p.id
  if (!expandedItems.value[p.id]) {
    loadingItems.value = true
    try {
      const { data } = await axios.get(`/api/purchases/${p.id}`)
      expandedItems.value[p.id] = data.items ?? []
    } finally {
      loadingItems.value = false
    }
  }
}

function statusClass(s) {
  return {
    received:         'bg-green-100 text-green-700',
    completed:        'bg-green-100 text-green-700',
    pending:          'bg-yellow-100 text-yellow-700',
    partial:          'bg-blue-100 text-blue-700',
    partial_received: 'bg-blue-100 text-blue-700',
    cancelled:        'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

async function downloadPdf(p) {
  downloadingId.value = p.id
  try {
    const response = await axios.get(`/api/purchases/${p.id}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const a = document.createElement('a')
    a.href = url; a.download = `PO-${p.purchase_number}.pdf`; a.click()
    URL.revokeObjectURL(url)
  } catch { alert('Failed to generate PDF') }
  finally { downloadingId.value = null }
}

function del(p) {
  confirmDelete.value  = p
  confirmMessage.value = `Delete purchase order ${p.purchase_number}? This cannot be undone.`
}

async function doDelete() {
  const p = confirmDelete.value
  confirmDelete.value = null
  await axios.delete(`/api/purchases/${p.id}`)
  delete expandedItems.value[p.id]
  if (expandedId.value === p.id) expandedId.value = null
  fetch()
}

onMounted(async () => {
  const { data } = await axios.get('/api/suppliers/all')
  suppliers.value = data
  fetch()
})
</script>
