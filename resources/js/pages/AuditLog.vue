<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Audit Log</h2>
        <p class="text-sm text-gray-500 mt-0.5">Full history of gold rate changes, deleted transactions, user actions</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap">
      <input v-model="filters.action" placeholder="Filter by action…" class="form-input w-48" @input="load" />
      <input v-model="filters.date_from" type="date" class="form-input w-44" @change="load" />
      <input v-model="filters.date_to" type="date" class="form-input w-44" @change="load" />
      <button @click="clearFilters" class="btn-secondary text-sm">Clear</button>
    </div>

    <!-- Quick action filters -->
    <div class="flex gap-2 flex-wrap">
      <button v-for="a in quickActions" :key="a.value"
        @click="filters.action = a.value; load()"
        :class="filters.action === a.value ? 'bg-gold-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
        class="px-3 py-1 rounded-full text-xs font-medium transition-colors">
        {{ a.label }}
      </button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Time</th>
            <th class="table-th">User</th>
            <th class="table-th">Action</th>
            <th class="table-th">Description</th>
            <th class="table-th">Old Value</th>
            <th class="table-th">New Value</th>
            <th class="table-th">IP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
            <td class="table-td text-xs text-gray-500 font-mono whitespace-nowrap">{{ formatTime(log.created_at) }}</td>
            <td class="table-td text-sm">
              <span class="font-medium">{{ log.user?.name ?? '—' }}</span>
            </td>
            <td class="table-td">
              <span :class="actionClass(log.action)" class="badge text-xs font-mono">
                {{ log.action }}
              </span>
            </td>
            <td class="table-td text-sm text-gray-600 max-w-xs truncate">{{ log.description }}</td>
            <td class="table-td text-xs text-gray-400 font-mono max-w-28 truncate">
              {{ log.old_values ? JSON.stringify(log.old_values) : '—' }}
            </td>
            <td class="table-td text-xs text-gray-700 font-mono max-w-28 truncate">
              {{ log.new_values ? JSON.stringify(log.new_values) : '—' }}
            </td>
            <td class="table-td text-xs text-gray-400 font-mono">{{ log.ip_address ?? '—' }}</td>
          </tr>
          <tr v-if="!logs.length">
            <td colspan="7" class="table-td text-center text-gray-400 py-10">No audit logs found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta" class="flex items-center justify-between text-sm text-gray-500">
      <span>Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}</span>
      <div class="flex gap-2">
        <button @click="page--; load()" :disabled="page <= 1" class="btn-secondary text-xs px-3">← Prev</button>
        <button @click="page++; load()" :disabled="page >= meta.last_page" class="btn-secondary text-xs px-3">Next →</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const logs    = ref([])
const meta    = ref(null)
const page    = ref(1)

const filters = reactive({ action: '', date_from: '', date_to: '' })

const quickActions = [
  { label: 'All', value: '' },
  { label: 'Gold Rate Changes', value: 'gold_rate_updated' },
  { label: 'Sales Created', value: 'sale_created' },
  { label: 'Sales Deleted', value: 'sale_deleted' },
  { label: 'Users', value: 'user_' },
]

async function load() {
  const { data } = await axios.get('/api/audit-logs', { params: { ...filters, page: page.value, per_page: 50 } })
  logs.value = data.data
  meta.value = data.meta
}

function clearFilters() { filters.action = ''; filters.date_from = ''; filters.date_to = ''; page.value = 1; load() }

function formatTime(ts) {
  return new Date(ts).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function actionClass(action) {
  if (action.includes('delete') || action.includes('deleted')) return 'bg-red-100 text-red-700'
  if (action.includes('rate'))    return 'bg-gold-100 text-gold-700'
  if (action.includes('created')) return 'bg-green-100 text-green-700'
  if (action.includes('updated')) return 'bg-blue-100 text-blue-700'
  return 'bg-gray-100 text-gray-600'
}

onMounted(load)
</script>
