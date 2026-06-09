<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Stock Movement Ledger</h2>
      <p class="text-sm text-gray-500">Every stock affecting action is logged here.</p>
    </div>

    <div class="card flex flex-wrap gap-3 items-end">
      <div>
        <label class="form-label">Movement Type</label>
        <select v-model="filters.movement_type" class="form-input w-44" @change="load">
          <option value="">All</option>
          <option value="IN">IN</option>
          <option value="OUT">OUT</option>
          <option value="DAMAGE">DAMAGE</option>
          <option value="RETURN">RETURN</option>
          <option value="TRANSFER">TRANSFER</option>
          <option value="ADJUSTMENT">ADJUSTMENT</option>
          <option value="OPEN_BOTTLE">OPEN_BOTTLE</option>
          <option value="DEPOSIT_IN">DEPOSIT_IN</option>
          <option value="DEPOSIT_OUT">DEPOSIT_OUT</option>
        </select>
      </div>
      <div>
        <label class="form-label">From</label>
        <input v-model="filters.date_from" type="date" class="form-input" @change="load" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="filters.date_to" type="date" class="form-input" @change="load" />
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Date</th>
            <th class="table-th">Item</th>
            <th class="table-th">Movement</th>
            <th class="table-th">Qty</th>
            <th class="table-th">Balance After</th>
            <th class="table-th">Reference</th>
            <th class="table-th">Notes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="m in movements.data" :key="m.id">
            <td class="table-td">{{ formatDate(m.moved_at) }}</td>
            <td class="table-td">{{ m.product?.name }}<div class="text-xs text-gray-400">{{ m.product?.sku }}</div></td>
            <td class="table-td"><span class="badge" :class="movementClass(m.movement_type)">{{ m.movement_type }}</span></td>
            <td class="table-td">{{ m.quantity }}</td>
            <td class="table-td">{{ m.balance_after }}</td>
            <td class="table-td text-xs">{{ m.reference_type }} #{{ m.reference_id }}</td>
            <td class="table-td text-xs text-gray-500">{{ m.notes }}</td>
          </tr>
          <tr v-if="!movements.data?.length"><td colspan="7" class="table-td text-center text-gray-400">No stock movement records found</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'

const movements = ref({ data: [] })
const filters = reactive({
  movement_type: '',
  date_from: '',
  date_to: '',
})

function formatDate(v) {
  return new Date(v).toLocaleString('en-LK')
}

function movementClass(type) {
  return {
    IN: 'bg-green-100 text-green-700',
    OUT: 'bg-red-100 text-red-700',
    DAMAGE: 'bg-orange-100 text-orange-700',
    RETURN: 'bg-blue-100 text-blue-700',
    OPEN_BOTTLE: 'bg-purple-100 text-purple-700',
  }[type] || 'bg-gray-100 text-gray-700'
}

async function load() {
  const { data } = await axios.get('/api/stock-movements', { params: filters })
  movements.value = data
}

onMounted(load)
</script>
