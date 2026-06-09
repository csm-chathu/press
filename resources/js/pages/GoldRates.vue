<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Gold Rate Management</h2>
        <p class="text-sm text-gray-500 mt-0.5">Set today's gold rate in LKR per gram (24K). Prices auto-calculate from this.</p>
      </div>
    </div>

    <!-- Today's rate card -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card space-y-4">
        <h3 class="font-semibold text-gray-700 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-gold-500"></span>
          Set Today's Rate
        </h3>
        <div>
          <label class="form-label">Date</label>
          <input v-model="form.date" type="date" class="form-input" />
        </div>
        <div>
          <label class="form-label">Rate per gram — 24K (LKR) *</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium text-sm">LKR</span>
            <input v-model.number="form.rate_per_gram" type="number" min="1" step="0.01"
              class="form-input pl-12" placeholder="e.g. 22000" />
          </div>
        </div>
        <p v-if="saveError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ saveError }}</p>
        <button @click="saveRate" :disabled="saving || !form.rate_per_gram" class="btn-primary w-full">
          {{ saving ? 'Saving…' : (todayRate ? 'Update Today\'s Rate' : 'Set Today\'s Rate') }}
        </button>

        <!-- Karat equivalents preview -->
        <div v-if="form.rate_per_gram" class="border-t pt-4 space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Price preview per 1 gram</p>
          <div class="grid grid-cols-2 gap-2">
            <div v-for="(purity, karat) in karats" :key="karat"
              class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
              <span class="text-sm font-medium text-gray-700 uppercase">{{ karat }}</span>
              <span class="text-sm font-semibold text-gold-700">
                LKR {{ lkr(form.rate_per_gram * purity) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Current rate info -->
      <div class="card space-y-4">
        <h3 class="font-semibold text-gray-700">Current Rate Status</h3>
        <div v-if="todayRate" class="space-y-3">
          <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs text-green-600 font-semibold uppercase tracking-wider">Today's rate is set ✓</p>
            <p class="text-3xl font-bold text-green-700 mt-1">LKR {{ lkr(todayRate.rate_per_gram) }}</p>
            <p class="text-sm text-green-600 mt-0.5">per gram — 24K · {{ formatDate(todayRate.date) }}</p>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div v-for="(purity, karat) in karats" :key="karat"
              class="flex flex-col bg-gold-50 rounded-lg px-3 py-2">
              <span class="text-xs font-semibold text-gold-600 uppercase">{{ karat }}</span>
              <span class="text-sm font-bold text-gold-800">LKR {{ lkr(todayRate.rate_per_gram * purity) }}/g</span>
            </div>
          </div>
        </div>
        <div v-else class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
          <p class="text-sm font-medium text-yellow-700">⚠ No rate set for today yet.</p>
          <p class="text-xs text-yellow-600 mt-1">Set the rate on the left to enable automatic price calculation in products.</p>
        </div>
      </div>
    </div>

    <!-- History -->
    <div class="card p-0 overflow-hidden">
      <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-gray-700">Rate History (Last 30 Days)</h3>
      </div>
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Date</th>
            <th class="table-th">Rate (24K / gram)</th>
            <th class="table-th">18K / gram</th>
            <th class="table-th">22K / gram</th>
            <th class="table-th">Set By</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in history" :key="r.id" class="hover:bg-gray-50"
            :class="isToday(r.date) ? 'bg-gold-50' : ''">
            <td class="table-td font-medium">
              {{ formatDate(r.date) }}
              <span v-if="isToday(r.date)" class="ml-2 badge bg-gold-100 text-gold-700 text-xs">Today</span>
            </td>
            <td class="table-td font-semibold text-gold-700">LKR {{ lkr(r.rate_per_gram) }}</td>
            <td class="table-td text-gray-600">LKR {{ lkr(r.rate_per_gram * 0.75) }}</td>
            <td class="table-td text-gray-600">LKR {{ lkr(r.rate_per_gram * (22/24)) }}</td>
            <td class="table-td text-gray-400 text-xs">{{ r.created_by?.name ?? '—' }}</td>
          </tr>
          <tr v-if="!history.length">
            <td colspan="5" class="table-td text-center text-gray-400 py-8">No rate history yet</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const todayRate = ref(null)
const history   = ref([])
const karats    = ref({})
const saving    = ref(false)
const saveError = ref('')

const form = reactive({
  date: new Date().toISOString().split('T')[0],
  rate_per_gram: '',
})

function lkr(val) {
  return Number(val).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function isToday(d) {
  return new Date(d).toDateString() === new Date().toDateString()
}

async function load() {
  const { data } = await axios.get('/api/gold-rates')
  todayRate.value    = data.today
  history.value      = data.history
  karats.value       = data.karats
  if (data.today) form.rate_per_gram = data.today.rate_per_gram
}

async function saveRate() {
  saving.value = true; saveError.value = ''
  try {
    const { data } = await axios.post('/api/gold-rates', form)
    todayRate.value = data
    await load()
  } catch (e) {
    saveError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
