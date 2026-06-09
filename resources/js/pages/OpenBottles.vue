<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Open Bottle Tracking</h2>
        <p class="text-sm text-gray-500">Track opening volume and remaining volume for shot/peg service.</p>
      </div>
      <button class="btn-primary" @click="showOpenForm = !showOpenForm">{{ showOpenForm ? 'Hide Open Form' : 'Open Bottle' }}</button>
    </div>

    <div v-if="showOpenForm" class="card grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
      <div>
        <label class="form-label">Product</label>
        <select v-model="openForm.product_id" class="form-input">
          <option value="">Select product</option>
          <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.stock_quantity }})</option>
        </select>
      </div>
      <div>
        <label class="form-label">Opening Volume (ml)</label>
        <input v-model.number="openForm.opening_volume_ml" type="number" min="1" class="form-input" />
      </div>
      <div>
        <label class="form-label">Notes</label>
        <input v-model="openForm.notes" class="form-input" />
      </div>
      <div>
        <button class="btn-primary w-full" :disabled="saving" @click="openBottle">{{ saving ? 'Opening...' : 'Open' }}</button>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Product</th>
            <th class="table-th">Opened (ml)</th>
            <th class="table-th">Remaining (ml)</th>
            <th class="table-th">Status</th>
            <th class="table-th">Opened At</th>
            <th class="table-th">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="b in bottles.data" :key="b.id">
            <td class="table-td">{{ b.product?.name }}</td>
            <td class="table-td">{{ b.opening_volume_ml }}</td>
            <td class="table-td">
              <div class="flex items-center gap-2">
                <span>{{ b.remaining_volume_ml }}</span>
                <input v-if="b.status === 'open'" v-model.number="pourMap[b.id]" type="number" min="1" class="form-input w-24" placeholder="ml" />
              </div>
            </td>
            <td class="table-td">
              <span class="badge"
                :class="{
                  'bg-blue-100 text-blue-700': b.status === 'open',
                  'bg-gray-100 text-gray-600': b.status === 'closed',
                  'bg-red-100 text-red-600': b.status === 'empty',
                }">{{ b.status }}</span>
            </td>
            <td class="table-td">{{ formatDate(b.opened_at) }}</td>
            <td class="table-td">
              <div class="flex items-center gap-2">
                <button v-if="b.status === 'open'" class="btn-secondary py-1 px-2 text-xs" @click="pour(b)">Pour</button>
                <button v-if="b.status === 'open'" class="btn-danger py-1 px-2 text-xs" @click="closeBottle(b)">Close</button>
              </div>
            </td>
          </tr>
          <tr v-if="!bottles.data?.length"><td colspan="6" class="table-td text-center text-gray-400">No open bottle records yet</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'

const bottles = ref({ data: [] })
const products = ref([])
const saving = ref(false)
const showOpenForm = ref(false)
const pourMap = reactive({})

const openForm = reactive({
  product_id: '',
  opening_volume_ml: 750,
  notes: '',
})

function formatDate(value) {
  return new Date(value).toLocaleString('en-LK')
}

async function load() {
  const [bottleRes, productRes] = await Promise.all([
    axios.get('/api/open-bottles'),
    axios.get('/api/products', { params: { per_page: 300, product_type: 'Liquor' } }),
  ])
  bottles.value = bottleRes.data
  products.value = productRes.data.data
}

async function openBottle() {
  saving.value = true
  try {
    await axios.post('/api/open-bottles/open', openForm)
    showOpenForm.value = false
    openForm.product_id = ''
    openForm.opening_volume_ml = 750
    openForm.notes = ''
    await load()
  } finally {
    saving.value = false
  }
}

async function pour(bottle) {
  const volume = Number(pourMap[bottle.id] || 0)
  if (!volume) return
  await axios.post(`/api/open-bottles/${bottle.id}/pour`, { volume_ml: volume })
  pourMap[bottle.id] = ''
  await load()
}

async function closeBottle(bottle) {
  await axios.post(`/api/open-bottles/${bottle.id}/close`)
  await load()
}

onMounted(load)
</script>
