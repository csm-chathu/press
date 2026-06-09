<template>
  <div class="space-y-5">

    <!-- Header + actions -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
          <input v-model="search" type="search" placeholder="Search invoice, customer…"
            class="form-input pl-8 w-52" @input="debouncedFetch" />
        </div>
        <input v-model="dateFrom" type="date" class="form-input w-36" @change="resetAndFetch" title="From date" />
        <span class="text-gray-400 text-xs">to</span>
        <input v-model="dateTo"   type="date" class="form-input w-36" @change="resetAndFetch" title="To date" />
        <select v-model="statusFilter" class="form-input w-32" @change="resetAndFetch">
          <option value="">All status</option>
          <option value="paid">Paid</option>
          <option value="pending">Pending</option>
          <option value="partial">Partial</option>
          <option value="refunded">Refunded</option>
        </select>
        <div class="flex items-center gap-1">
          <button @click="setQuick('week')"
            :class="quickFilter === 'week' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
            class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">This Week</button>
          <button @click="setQuick('today')"
            :class="quickFilter === 'today' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
            class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">Today</button>
          <button @click="setQuick('month')"
            :class="quickFilter === 'month' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
            class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">This Month</button>
        </div>
        <button v-if="search || statusFilter || quickFilter" @click="clearFilters"
          class="text-xs text-gray-400 hover:text-gray-600 underline">Clear</button>
      </div>
      <router-link :to="newBillRoute"
        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-sm shadow-sm transition-colors shrink-0">
        <PlusIcon class="w-4 h-4" /> New Bill
      </router-link>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
          <ReceiptPercentIcon class="w-5 h-5 text-blue-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Sales</p>
          <p class="text-2xl font-bold text-gray-800">{{ sales.total ?? 0 }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
          <BanknotesIcon class="w-5 h-5 text-amber-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Revenue</p>
          <p class="text-xl font-bold text-amber-700">LKR {{ totalRevenue }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
          <CheckCircleIcon class="w-5 h-5 text-green-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Paid</p>
          <p class="text-2xl font-bold text-green-700">{{ paidCount }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
          <ChartBarIcon class="w-5 h-5 text-purple-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Avg Sale</p>
          <p class="text-xl font-bold text-purple-700">LKR {{ avgSale }}</p>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th w-32">Table</th>
              <th class="table-th w-28">Date</th>
              <th class="table-th w-36 text-right">Total</th>
              <th class="table-th w-32">Payment</th>
              <th class="table-th w-24">Status</th>
              <th class="table-th w-28 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading">
              <td colspan="6" class="table-td text-center py-10 text-gray-400">
                <div class="flex items-center justify-center gap-2">
                  <ArrowPathIcon class="w-4 h-4 animate-spin" /> Loading…
                </div>
              </td>
            </tr>
            <template v-else>
              <tr v-for="s in sales.data" :key="s.id"
                class="hover:bg-amber-50/40 transition-colors cursor-default group">
                <td class="table-td">
                  <span v-if="s.table?.name" class="text-sm font-medium text-gray-800">{{ s.table.name }}</span>
                  <span v-else class="text-xs text-gray-400">—</span>
                </td>
                <td class="table-td text-xs text-gray-500">
                  <div>{{ formatDate(s.sold_at) }}</div>
                  <div class="text-gray-400">{{ formatTime(s.sold_at) }}</div>
                </td>
                <td class="table-td text-right">
                  <span class="font-bold text-amber-700">LKR {{ Number(s.total).toLocaleString() }}</span>
                </td>
                <td class="table-td">
                  <span :class="paymentMethodClass(s)"
                    class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                    {{ paymentMethodLabel(s) }}
                  </span>
                  <p v-if="s.card_reference" class="text-xs text-gray-400 mt-0.5 font-mono">{{ s.card_reference }}</p>
                </td>
                <td class="table-td">
                  <span :class="statusClass(s.payment_status)" class="badge capitalize">{{ s.payment_status }}</span>
                </td>
                <td class="table-td text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <router-link v-if="s.status === 'draft'" :to="editDraftRoute(s.id)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200">
                      <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                    </router-link>
                    <router-link v-if="s.payment_status !== 'pending'" :to="`/sales/${s.id}`"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                      <PrinterIcon class="w-3.5 h-3.5" /> Receipt
                    </router-link>
                    <button v-if="!isCashier" @click="del(s)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!sales.data?.length">
                <td colspan="6" class="table-td text-center py-12">
                  <div class="flex flex-col items-center gap-2 text-gray-400">
                    <ReceiptPercentIcon class="w-10 h-10 opacity-30" />
                    <span>No sales found</span>
                    <router-link to="/sales/new" class="text-amber-600 hover:underline text-sm font-medium">Create your first bill →</router-link>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-5 py-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-sm text-gray-500 bg-gray-50/50">
        <span class="text-xs">
          Showing <strong class="text-gray-700">{{ sales.from ?? 0 }}–{{ sales.to ?? 0 }}</strong>
          of <strong class="text-gray-700">{{ sales.total ?? 0 }}</strong> records
        </span>
        <div class="flex items-center gap-1">
          <button @click="page--; fetchData()" :disabled="page<=1"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border border-gray-200 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            <ChevronLeftIcon class="w-3.5 h-3.5" /> Prev
          </button>
          <span class="px-3 py-1.5 text-xs font-semibold text-gray-700">Page {{ page }} / {{ sales.last_page ?? 1 }}</span>
          <button @click="page++; fetchData()" :disabled="page>=(sales.last_page ?? 1)"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border border-gray-200 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            Next <ChevronRightIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import {
  PlusIcon, TrashIcon, EyeIcon, MagnifyingGlassIcon,
  ReceiptPercentIcon, BanknotesIcon, CheckCircleIcon, PrinterIcon,
  ChartBarIcon, ArrowPathIcon, ChevronLeftIcon, ChevronRightIcon, PencilSquareIcon,
} from '@heroicons/vue/24/outline'
import ConfirmModal from '@/components/ConfirmModal.vue'

const auth         = useAuthStore()
const isCashier    = computed(() => auth.user?.role === 'cashier')
const newBillRoute = '/sales/new'
const editDraftRoute = (id) => ({ name: 'sales.new', query: { draft: id } })

const sales          = ref({ data: [] })
const search         = ref('')
const page           = ref(1)
const dateFrom       = ref('')
const dateTo         = ref('')
const statusFilter   = ref('')
const confirmDelete  = ref(null)
const confirmMessage = ref('')
const quickFilter  = ref('')
const loading      = ref(false)

function toDateStr(d) {
  return d.toISOString().slice(0, 10)
}

function setQuick(range) {
  const today = new Date()
  if (range === 'today') {
    dateFrom.value = toDateStr(today)
    dateTo.value   = toDateStr(today)
  } else if (range === 'week') {
    const mon = new Date(today)
    mon.setDate(today.getDate() - ((today.getDay() + 6) % 7))
    dateFrom.value = toDateStr(mon)
    dateTo.value   = toDateStr(today)
  } else if (range === 'month') {
    dateFrom.value = toDateStr(new Date(today.getFullYear(), today.getMonth(), 1))
    dateTo.value   = toDateStr(today)
  }
  quickFilter.value = range
  page.value = 1
  fetchData()
}

function resetAndFetch() { page.value = 1; fetchData() }

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(resetAndFetch, 400) }

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/sales', {
      params: {
        page:        page.value,
        search:      search.value,
        date_from:   dateFrom.value,
        date_to:     dateTo.value,
        status:      statusFilter.value,
      },
    })
    sales.value = data
  } finally { loading.value = false }
}

function clearFilters() {
  search.value = ''; dateFrom.value = ''; dateTo.value = ''; statusFilter.value = ''; quickFilter.value = ''
  page.value = 1; fetchData()
}

const totalRevenue = computed(() => {
  const sum = (sales.value.data ?? []).reduce((acc, s) => acc + Number(s.total), 0)
  return Number(sum).toLocaleString()
})
const paidCount = computed(() => (sales.value.data ?? []).filter(s => s.payment_status === 'paid').length)
const avgSale = computed(() => {
  const d = sales.value.data ?? []
  if (!d.length) return '0'
  return Number(d.reduce((a, s) => a + Number(s.total), 0) / d.length).toLocaleString('en-LK', { maximumFractionDigits: 0 })
})

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
}
function formatTime(d) {
  return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

function statusClass(s) {
  return {
    paid:     'bg-green-100 text-green-700',
    pending:  'bg-yellow-100 text-yellow-700',
    partial:  'bg-blue-100 text-blue-700',
    refunded: 'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-700'
}
function methodClass(m) {
  return {
    cash:          'bg-green-50 text-green-600',
    card:          'bg-blue-50 text-blue-600',
    bank_transfer: 'bg-purple-50 text-purple-600',
    cheque:        'bg-orange-50 text-orange-600',
  }[m] ?? 'bg-gray-50 text-gray-600'
}

function paymentMethodLabel(sale) {
  const payments = sale.payments ?? []
  const methods = [...new Set(payments.map(payment => payment.payment_method).filter(Boolean))]

  if (methods.length > 1) {
    return `Split: ${methods.map(method => method.replace('_', ' ')).join(' + ')}`
  }

  if (methods.length === 1) {
    return methods[0].replace('_', ' ')
  }

  return sale.payment_method?.replace('_', ' ') ?? 'Unpaid'
}

function paymentMethodClass(sale) {
  const payments = sale.payments ?? []
  const methods = [...new Set(payments.map(payment => payment.payment_method).filter(Boolean))]

  if (methods.length > 1) {
    return 'bg-amber-50 text-amber-700'
  }

  return methodClass(methods[0] ?? sale.payment_method)
}

function del(s) {
  confirmDelete.value  = s
  confirmMessage.value = `Delete invoice ${s.invoice_number}? This will restore stock and cannot be undone.`
}

async function doDelete() {
  await axios.delete(`/api/sales/${confirmDelete.value.id}`)
  confirmDelete.value = null
  fetchData()
}

onMounted(() => setQuick('week'))
</script>
