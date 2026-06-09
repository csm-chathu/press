<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-start justify-between gap-4 flex-wrap no-print">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Shift Summary Report</h2>
        <p class="text-sm text-gray-500 mt-0.5">Daily cashier shift performance overview</p>
      </div>
      <button @click="print" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:border-amber-300 hover:text-amber-700 no-print">
        <i class="fas fa-print"></i> Print
      </button>
    </div>

    <!-- Filters -->
    <div class="card py-3 flex gap-3 items-end flex-wrap no-print">
      <div>
        <label class="form-label">From</label>
        <input v-model="dateFrom" type="date" class="form-input" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="dateTo" type="date" class="form-input" />
      </div>
      <button @click="load" :disabled="loading" class="btn-primary text-sm">
        {{ loading ? 'Loading…' : 'Generate' }}
      </button>
    </div>

    <!-- KPI cards -->
    <div v-if="totals" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Shifts</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ totals.shift_count }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Revenue</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ lkr(totals.total_revenue) }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Handover</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ lkr(totals.total_handover) }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Leftover</p>
        <p class="text-2xl font-bold text-green-700 mt-1">{{ lkr(totals.total_leftover) }}</p>
      </div>
    </div>

    <!-- No data -->
    <div v-if="!loading && shifts.length === 0 && totals" class="card p-10 text-center text-gray-400">
      No closed shifts found for the selected period.
    </div>

    <!-- Shifts grouped by date -->
    <template v-for="(group, date) in groupedByDate" :key="date">
      <div class="card p-0 overflow-hidden">
        <!-- Day header -->
        <div class="px-5 py-3 bg-gray-50 border-b flex items-center justify-between">
          <span class="font-semibold text-gray-700 text-sm">{{ formatDate(date) }}</span>
          <div class="flex gap-4 text-xs text-gray-500">
            <span>{{ group.length }} shift{{ group.length > 1 ? 's' : '' }}</span>
            <span class="font-semibold text-amber-700">Revenue: LKR {{ lkr(sumField(group, 'total_revenue')) }}</span>
            <span class="font-semibold text-red-600">Handover: LKR {{ lkr(sumField(group, 'handover_amount')) }}</span>
            <span class="font-semibold text-green-700">Leftover: LKR {{ lkr(sumField(group, 'leftover_amount')) }}</span>
          </div>
        </div>

        <table class="w-full text-sm">
          <thead class="border-b bg-white">
            <tr>
              <th class="table-th">Cashier</th>
              <th class="table-th">Opened</th>
              <th class="table-th">Closed</th>
              <th class="table-th text-right">Opening</th>
              <th class="table-th text-right">Sales</th>
              <th class="table-th text-right">Bills</th>
              <th class="table-th text-right">Cash</th>
              <th class="table-th text-right">Card</th>
              <th class="table-th text-right">Cash Outs</th>
              <th class="table-th text-right">Expected</th>
              <th class="table-th text-right">Closing</th>
              <th class="table-th text-right">Variance</th>
              <th class="table-th text-right">Handover</th>
              <th class="table-th text-right">Leftover</th>
              <th class="table-th">Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="s in group" :key="s.id" class="hover:bg-gray-50">
              <td class="table-td font-medium">{{ s.cashier }}</td>
              <td class="table-td text-xs text-gray-500">{{ formatTime(s.opened_at) }}</td>
              <td class="table-td text-xs text-gray-500">{{ formatTime(s.closed_at) }}</td>
              <td class="table-td text-right">{{ lkr(s.opening_cash) }}</td>
              <td class="table-td text-right font-semibold text-amber-700">{{ lkr(s.total_revenue) }}</td>
              <td class="table-td text-right">{{ s.total_sales }}</td>
              <td class="table-td text-right">{{ lkr(s.cash_sales) }}</td>
              <td class="table-td text-right">{{ lkr(s.card_sales) }}</td>
              <td class="table-td text-right" :class="s.total_cash_outs > 0 ? 'text-orange-600' : 'text-gray-400'">
                {{ s.total_cash_outs > 0 ? lkr(s.total_cash_outs) : '—' }}
              </td>
              <td class="table-td text-right text-gray-500">{{ lkr(s.expected_cash) }}</td>
              <td class="table-td text-right">{{ lkr(s.closing_cash) }}</td>
              <td class="table-td text-right font-semibold"
                  :class="s.variance < -0.01 ? 'text-red-600' : s.variance > 0.01 ? 'text-green-700' : 'text-gray-400'">
                {{ s.variance >= 0 ? '+' : '' }}{{ lkr(s.variance) }}
              </td>
              <td class="table-td text-right text-red-600 font-medium">{{ lkr(s.handover_amount) }}</td>
              <td class="table-td text-right font-semibold"
                  :class="s.leftover_amount > 0 ? 'text-amber-700' : 'text-gray-400'">
                {{ s.leftover_amount > 0 ? lkr(s.leftover_amount) : '—' }}
              </td>
              <td class="table-td text-xs text-gray-400 max-w-[120px] truncate" :title="s.notes ?? ''">
                {{ s.notes || '—' }}
              </td>
            </tr>
          </tbody>
          <!-- Day totals row -->
          <tfoot v-if="group.length > 1" class="border-t-2 border-gray-200 bg-gray-50 text-xs font-semibold">
            <tr>
              <td class="table-td text-gray-500" colspan="4">Day Total</td>
              <td class="table-td text-right text-amber-700">{{ lkr(sumField(group, 'total_revenue')) }}</td>
              <td class="table-td text-right">{{ sumField(group, 'total_sales') }}</td>
              <td class="table-td text-right">{{ lkr(sumField(group, 'cash_sales')) }}</td>
              <td class="table-td text-right">{{ lkr(sumField(group, 'card_sales')) }}</td>
              <td class="table-td text-right text-orange-600">{{ lkr(sumField(group, 'total_cash_outs')) }}</td>
              <td class="table-td text-right"></td>
              <td class="table-td text-right"></td>
              <td class="table-td text-right"
                  :class="sumField(group,'variance') < -0.01 ? 'text-red-600' : 'text-green-700'">
                {{ sumField(group, 'variance') >= 0 ? '+' : '' }}{{ lkr(sumField(group, 'variance')) }}
              </td>
              <td class="table-td text-right text-red-600">{{ lkr(sumField(group, 'handover_amount')) }}</td>
              <td class="table-td text-right text-amber-700">{{ lkr(sumField(group, 'leftover_amount')) }}</td>
              <td class="table-td"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </template>

    <!-- Grand totals -->
    <div v-if="totals && shifts.length" class="card p-0 overflow-hidden">
      <div class="px-5 py-3 bg-amber-50 border-b border-amber-200">
        <span class="font-bold text-amber-800 text-sm">Grand Total — {{ dateFrom }} to {{ dateTo }}</span>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 divide-x divide-gray-100">
        <div v-for="(item, idx) in grandTotalCells" :key="idx" class="px-4 py-3 text-center">
          <p class="text-xs text-gray-400 font-medium">{{ item.label }}</p>
          <p class="text-sm font-bold mt-0.5" :class="item.cls">{{ item.value }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const today    = new Date().toISOString().slice(0, 10)
const dateFrom = ref(today)
const dateTo   = ref(today)
const loading  = ref(false)
const shifts   = ref([])
const totals   = ref(null)

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dateStr) {
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-LK', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
}

function formatTime(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

function sumField(arr, field) {
  return arr.reduce((acc, r) => acc + (r[field] ?? 0), 0)
}

const groupedByDate = computed(() => {
  const groups = {}
  for (const s of shifts.value) {
    if (!groups[s.date]) groups[s.date] = []
    groups[s.date].push(s)
  }
  return groups
})

const grandTotalCells = computed(() => {
  if (!totals.value) return []
  const t = totals.value
  const varCls = t.total_variance < -0.01 ? 'text-red-600' : t.total_variance > 0.01 ? 'text-green-700' : 'text-gray-500'
  return [
    { label: 'Shifts',    value: t.shift_count,                               cls: 'text-gray-700' },
    { label: 'Revenue',   value: 'LKR ' + lkr(t.total_revenue),              cls: 'text-amber-700' },
    { label: 'Cash',      value: 'LKR ' + lkr(t.cash_sales),                 cls: 'text-gray-700' },
    { label: 'Card',      value: 'LKR ' + lkr(t.card_sales),                 cls: 'text-gray-700' },
    { label: 'Cash Outs', value: 'LKR ' + lkr(t.total_cash_outs),            cls: 'text-orange-600' },
    { label: 'Variance',  value: (t.total_variance >= 0 ? '+' : '') + 'LKR ' + lkr(t.total_variance), cls: varCls },
    { label: 'Handover',  value: 'LKR ' + lkr(t.total_handover),             cls: 'text-red-600' },
    { label: 'Leftover',  value: 'LKR ' + lkr(t.total_leftover),             cls: 'text-green-700' },
  ]
})

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/cashier-shifts/report', {
      params: { from: dateFrom.value, to: dateTo.value }
    })
    shifts.value = data.shifts
    totals.value = data.totals
  } catch {
    shifts.value = []
    totals.value = null
  } finally {
    loading.value = false
  }
}

function print() { window.print() }

onMounted(load)
</script>

<style>
@media print {
  .no-print { display: none !important; }
}
</style>
