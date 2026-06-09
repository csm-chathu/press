<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Day-End Reconciliation</h2>
        <p class="text-sm text-gray-500 mt-0.5">Match physical stock weight against system records</p>
      </div>
      <input v-model="reportDate" type="date" class="form-input w-48" @change="load" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" v-if="dayData">
      <!-- Left: system stock summary -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Karat breakdown -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700 flex items-center gap-2">
            System Stock — {{ reportDate }}
            <span v-if="dayData.gold_rate" class="text-xs font-normal text-gold-600">
              (Rate: LKR {{ lkr(dayData.gold_rate.rate_per_gram) }}/g 24K)
            </span>
          </h3>
          <div class="grid grid-cols-3 gap-3">
            <div v-for="k in dayData.karat_breakdown" :key="k.karat"
              class="bg-gold-50 border border-gold-200 rounded-xl p-3 text-center">
              <p class="text-xs font-semibold text-gold-600 uppercase">{{ k.karat }}</p>
              <p class="text-lg font-bold text-gold-800">{{ k.weight_g }}g</p>
              <p class="text-xs text-gray-500">{{ k.pieces }} pieces</p>
            </div>
          </div>
          <div class="border-t pt-3 flex gap-6 text-sm">
            <div>
              <p class="text-gray-500 text-xs">Total System Weight</p>
              <p class="font-bold text-gray-800">{{ totalSystemWeight.toFixed(3) }}g</p>
            </div>
            <div>
              <p class="text-gray-500 text-xs">Physical Count</p>
              <div class="flex items-center gap-2">
                <input v-model.number="physicalWeight" type="number" min="0" step="0.001"
                  class="form-input w-28 py-1" placeholder="0.000" />
                <span class="text-xs text-gray-400">g</span>
              </div>
            </div>
            <div v-if="physicalWeight !== ''">
              <p class="text-gray-500 text-xs">Variance</p>
              <p class="font-bold text-lg"
                :class="variance === 0 ? 'text-green-600' : variance > 0 ? 'text-blue-600' : 'text-red-600'">
                {{ variance >= 0 ? '+' : '' }}{{ variance.toFixed(3) }}g
              </p>
            </div>
          </div>
        </div>

        <!-- Product-level physical count -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Item-by-Item Count</h3>
            <span class="text-xs text-gray-400">Enter physical quantity for each item</span>
          </div>
          <div class="max-h-96 overflow-y-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b sticky top-0">
                <tr>
                  <th class="table-th">SKU</th>
                  <th class="table-th">Product</th>
                  <th class="table-th">Karat</th>
                  <th class="table-th">Weight/pc</th>
                  <th class="table-th">System Qty</th>
                  <th class="table-th">Physical Qty</th>
                  <th class="table-th">Variance</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="p in dayData.products" :key="p.id"
                  :class="getItemVariance(p) !== 0 ? 'bg-red-50' : 'hover:bg-gray-50'">
                  <td class="table-td font-mono text-xs text-gray-500">{{ p.sku }}</td>
                  <td class="table-td text-sm font-medium">{{ p.name }}</td>
                  <td class="table-td text-gold-700 uppercase text-sm">{{ p.karat ?? '—' }}</td>
                  <td class="table-td text-sm font-mono">{{ p.weight ? p.weight + 'g' : '—' }}</td>
                  <td class="table-td">
                    <span class="badge bg-blue-100 text-blue-700">{{ p.stock_quantity }}</span>
                  </td>
                  <td class="table-td">
                    <input :value="itemCounts[p.id] ?? p.stock_quantity"
                      @input="itemCounts[p.id] = parseInt($event.target.value) || 0"
                      type="number" min="0" class="form-input w-20 py-1 text-sm" />
                  </td>
                  <td class="table-td">
                    <span v-if="getItemVariance(p) !== 0"
                      :class="getItemVariance(p) > 0 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'"
                      class="badge text-xs">
                      {{ getItemVariance(p) > 0 ? '+' : '' }}{{ getItemVariance(p) }}
                    </span>
                    <span v-else class="text-green-500 text-sm">✓</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right: save report + history -->
      <div class="space-y-4">
        <div class="card space-y-4">
          <h3 class="font-semibold text-gray-700">Submit Report</h3>
          <div class="space-y-1 text-sm border rounded-xl p-3 bg-gray-50">
            <div class="flex justify-between">
              <span class="text-gray-500">System weight</span>
              <span>{{ totalSystemWeight.toFixed(3) }}g</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Physical weight</span>
              <span class="font-semibold">{{ Number(physicalWeight || 0).toFixed(3) }}g</span>
            </div>
            <div class="flex justify-between border-t pt-1 mt-1">
              <span class="text-gray-500">Variance</span>
              <span class="font-bold" :class="variance === 0 ? 'text-green-600' : 'text-red-600'">
                {{ variance >= 0 ? '+' : '' }}{{ variance.toFixed(3) }}g
              </span>
            </div>
          </div>
          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="notes" rows="3" class="form-input" placeholder="Any discrepancies or notes…"></textarea>
          </div>
          <div class="flex gap-2">
            <button @click="saveReport('draft')" :disabled="saving" class="btn-secondary flex-1">
              {{ saving === 'draft' ? 'Saving…' : 'Save Draft' }}
            </button>
            <button @click="saveReport('submitted')" :disabled="saving" class="btn-primary flex-1">
              {{ saving === 'submitted' ? 'Submitting…' : 'Submit' }}
            </button>
          </div>
          <p v-if="saveMsg" class="text-sm text-green-600 bg-green-50 px-3 py-2 rounded-lg">{{ saveMsg }}</p>
          <p v-if="saveError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ saveError }}</p>
        </div>

        <!-- Recent reports -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700 text-sm">Recent Reports</h3>
          <div v-for="r in dayData.recent_reports" :key="r.id"
            class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 text-sm">
            <div>
              <p class="font-medium">{{ r.report_date }}</p>
              <p class="text-xs text-gray-400">Variance: {{ r.variance_weight ?? '—' }}g</p>
            </div>
            <span :class="r.status === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="badge text-xs">
              {{ r.status }}
            </span>
          </div>
          <p v-if="!dayData.recent_reports.length" class="text-xs text-gray-400">No reports yet</p>
        </div>
      </div>
    </div>
    <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const reportDate    = ref(new Date().toISOString().split('T')[0])
const dayData       = ref(null)
const physicalWeight = ref('')
const itemCounts    = ref({})
const notes         = ref('')
const saving        = ref(false)
const saveMsg       = ref('')
const saveError     = ref('')

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const totalSystemWeight = computed(() => {
  if (!dayData.value) return 0
  return dayData.value.products.reduce((s, p) => s + ((p.weight ?? 0) * p.stock_quantity), 0)
})

const variance = computed(() => {
  const pw = parseFloat(physicalWeight.value || 0)
  return pw - totalSystemWeight.value
})

function getItemVariance(p) {
  const physical = itemCounts.value[p.id] ?? p.stock_quantity
  return physical - p.stock_quantity
}

async function load() {
  dayData.value = null
  const { data } = await axios.get('/api/reports/day-end')
  dayData.value = data
  // Init item counts from system
  itemCounts.value = {}
  data.products.forEach(p => { itemCounts.value[p.id] = p.stock_quantity })
  physicalWeight.value = ''
}

async function saveReport(status) {
  saving.value = status; saveMsg.value = ''; saveError.value = ''
  try {
    await axios.post('/api/reports/day-end', {
      report_date:           reportDate.value,
      physical_gold_weight:  parseFloat(physicalWeight.value || 0),
      item_counts:           Object.entries(itemCounts.value).map(([id, qty]) => ({
        product_id: parseInt(id), physical_qty: qty,
      })),
      notes:  notes.value,
      status,
    })
    saveMsg.value = `Report ${status === 'submitted' ? 'submitted' : 'saved as draft'} successfully.`
    load()
  } catch (e) {
    saveError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { saving.value = false }
}

onMounted(load)
</script>
