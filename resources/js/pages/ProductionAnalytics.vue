<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Production Analytics</h2>
        <p class="text-sm text-gray-500 mt-0.5">Output, waste, and efficiency across machines</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <input v-model="dateFrom" type="date" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-amber-400" @change="load" />
        <span class="text-gray-400 text-sm">to</span>
        <input v-model="dateTo" type="date" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-amber-400" @change="load" />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Total Output</p>
          <p class="text-2xl font-bold text-amber-600 mt-1">{{ data.summary.total_output.toLocaleString() }}</p>
          <p class="text-xs text-gray-400 mt-0.5">units produced</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Total Waste</p>
          <p class="text-2xl font-bold text-red-500 mt-1">{{ data.summary.total_waste.toLocaleString() }}</p>
          <p class="text-xs text-gray-400 mt-0.5">units wasted</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Efficiency</p>
          <p class="text-2xl font-bold mt-1" :class="data.summary.efficiency >= 90 ? 'text-green-600' : data.summary.efficiency >= 75 ? 'text-amber-600' : 'text-red-500'">
            {{ data.summary.efficiency }}%
          </p>
          <p class="text-xs text-gray-400 mt-0.5">output / (output + waste)</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Production Runs</p>
          <p class="text-2xl font-bold text-gray-700 mt-1">{{ data.summary.total_runs }}</p>
          <p class="text-xs text-gray-400 mt-0.5">completed runs</p>
        </div>
      </div>

      <!-- Machine efficiency table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-700 text-sm">Machine Efficiency</h3>
        </div>
        <div v-if="data.by_machine.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
              <tr>
                <th class="table-th">Machine</th>
                <th class="table-th text-right">Runs</th>
                <th class="table-th text-right">Output</th>
                <th class="table-th text-right">Waste</th>
                <th class="table-th text-right">Efficiency</th>
                <th class="table-th" style="width:180px;">Progress</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="m in data.by_machine" :key="m.machine" class="hover:bg-gray-50">
                <td class="table-td font-medium text-gray-800">{{ m.machine }}</td>
                <td class="table-td text-right text-gray-600">{{ m.run_count }}</td>
                <td class="table-td text-right font-semibold text-gray-800">{{ m.total_output.toLocaleString() }}</td>
                <td class="table-td text-right text-red-500">{{ m.total_waste.toLocaleString() }}</td>
                <td class="table-td text-right font-bold" :class="m.efficiency >= 90 ? 'text-green-600' : m.efficiency >= 75 ? 'text-amber-600' : 'text-red-500'">
                  {{ m.efficiency }}%
                </td>
                <td class="table-td">
                  <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                      :class="m.efficiency >= 90 ? 'bg-green-500' : m.efficiency >= 75 ? 'bg-amber-500' : 'bg-red-500'"
                      :style="{ width: m.efficiency + '%' }"></div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="px-5 py-10 text-center text-sm text-gray-400">
          No completed production runs in this date range
        </div>
      </div>

      <!-- Jobs by status -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Jobs by Status</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
          <div v-for="(count, status) in data.jobs_by_status" :key="status"
            class="text-center p-3 rounded-xl border"
            :class="statusBg(status)">
            <p class="text-2xl font-bold">{{ count }}</p>
            <p class="text-xs mt-1 capitalize font-medium">{{ statusLabel(status) }}</p>
          </div>
        </div>
      </div>

      <!-- Daily trend -->
      <div v-if="data.daily_trend.length" class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Daily Output Trend</h3>
        <div class="overflow-x-auto">
          <div class="flex items-end gap-1 min-w-0" style="height:120px;">
            <div v-for="d in data.daily_trend" :key="d.date"
              class="flex flex-col items-center justify-end gap-0.5 flex-1 min-w-0 group cursor-default" style="min-width:12px; max-width:32px;">
              <div class="w-full relative" :style="{ height: barHeight(d.output, d.waste) + 'px' }">
                <div class="absolute bottom-0 inset-x-0 bg-amber-400 rounded-t-sm opacity-80" :style="{ height: outpHeight(d.output, d.waste) + '%' }"></div>
                <div class="absolute bottom-0 inset-x-0 bg-red-300 rounded-t-sm opacity-70" :style="{ height: wasteHeight(d.output, d.waste) + '%', top: outpHeight(d.output, d.waste) + '%' }"></div>
              </div>
              <p class="text-gray-400 text-[9px] rotate-45 origin-left whitespace-nowrap hidden sm:block">{{ d.date.slice(5) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-amber-400 rounded-sm inline-block"></span> Output</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-red-300 rounded-sm inline-block"></span> Waste</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading  = ref(true)
const dateFrom = ref(new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10))
const dateTo   = ref(new Date().toISOString().slice(0, 10))

const data = ref({
  summary:       { total_output: 0, total_waste: 0, total_runs: 0, efficiency: 0 },
  by_machine:    [],
  daily_trend:   [],
  jobs_by_status: {},
})

const statusLabels = {
  waiting: 'Waiting', designing: 'Designing', proof_approval: 'Proof Approval',
  plate_making: 'Plate Making', printing: 'Printing', finishing: 'Finishing',
  quality_check: 'QC', ready: 'Ready', delivered: 'Delivered',
}
function statusLabel(s) { return statusLabels[s] ?? s }
function statusBg(s) {
  return {
    waiting: 'border-gray-100 text-gray-600',
    designing: 'border-blue-100 text-blue-700',
    proof_approval: 'border-yellow-100 text-yellow-700',
    plate_making: 'border-orange-100 text-orange-700',
    printing: 'border-purple-100 text-purple-700',
    finishing: 'border-indigo-100 text-indigo-700',
    quality_check: 'border-teal-100 text-teal-700',
    ready: 'border-green-100 text-green-700',
    delivered: 'border-gray-100 text-gray-500',
  }[s] ?? 'border-gray-100 text-gray-600'
}

const maxVal = () => Math.max(...data.value.daily_trend.map(d => d.output + d.waste), 1)
function barHeight(output, waste) { return Math.round(((output + waste) / maxVal()) * 100) }
function outpHeight(output, waste) { return waste === 0 ? 100 : Math.round((output / (output + waste)) * 100) }
function wasteHeight(output, waste) { return 100 - outpHeight(output, waste) }

async function load() {
  loading.value = true
  try {
    const { data: res } = await axios.get('/api/production/analytics', {
      params: { date_from: dateFrom.value, date_to: dateTo.value },
    })
    data.value = res
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
