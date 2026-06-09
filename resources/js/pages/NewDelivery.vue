<template>
  <div class="max-w-3xl mx-auto space-y-5">
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <ChevronLeftIcon class="w-5 h-5" />
      </button>
      <h2 class="text-lg font-semibold text-gray-800">New Delivery Note</h2>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <!-- Header -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Delivery Info</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Customer *</label>
            <select v-model="form.customer_id" required class="input w-full">
              <option value="">Select customer…</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Job Card *</label>
            <select v-model="form.job_card_id" required class="input w-full">
              <option value="">Select job card…</option>
              <option v-for="j in jobCards" :key="j.id" :value="j.id">{{ j.job_number }} — {{ j.title }}</option>
            </select>
          </div>
          <div>
            <label class="label">Delivery Date *</label>
            <input v-model="form.delivery_date" type="date" required class="input w-full" />
          </div>
          <div>
            <label class="label">Delivery Method</label>
            <select v-model="form.delivery_method" class="input w-full">
              <option value="own_vehicle">Own Vehicle</option>
              <option value="courier">Courier</option>
              <option value="customer_pickup">Customer Pickup</option>
              <option value="third_party">Third Party Logistics</option>
            </select>
          </div>
          <div>
            <label class="label">Total Quantity *</label>
            <input v-model.number="form.total_quantity" type="number" required min="1" class="input w-full" />
          </div>
          <div>
            <label class="label">Delivered Quantity</label>
            <input v-model.number="form.delivered_quantity" type="number" min="0" :max="form.total_quantity" class="input w-full" />
          </div>
        </div>
        <div>
          <label class="label">Delivery Address</label>
          <textarea v-model="form.delivery_address" rows="2" class="input w-full" placeholder="Full delivery address…"></textarea>
        </div>
        <div>
          <label class="label">Driver / Courier Name</label>
          <input v-model="form.driver_name" type="text" class="input w-full" placeholder="Driver or courier name" />
        </div>
        <div>
          <label class="label">Vehicle / Tracking Number</label>
          <input v-model="form.vehicle_number" type="text" class="input w-full" placeholder="Vehicle no. or AWB" />
        </div>
        <div>
          <label class="label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="input w-full" placeholder="Any special instructions…"></textarea>
        </div>
      </div>

      <!-- Items -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Delivery Items</h3>
          <button type="button" @click="addItem" class="text-xs text-amber-600 hover:underline font-semibold">+ Add Item</button>
        </div>
        <div v-for="(item, idx) in form.items" :key="idx" class="flex gap-3 items-end border border-gray-100 rounded-lg p-3 bg-gray-50">
          <div class="flex-1">
            <label class="label">Description *</label>
            <input v-model="item.description" type="text" required class="input w-full" placeholder="Item description" />
          </div>
          <div class="w-28">
            <label class="label">Quantity *</label>
            <input v-model.number="item.quantity" type="number" required min="1" class="input w-full" />
          </div>
          <div class="w-28">
            <label class="label">Unit</label>
            <input v-model="item.unit" type="text" class="input w-full" placeholder="pcs / kg" />
          </div>
          <button type="button" @click="form.items.splice(idx, 1)" class="text-red-400 hover:text-red-600 pb-2 transition-colors">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
        <p v-if="!form.items.length" class="text-xs text-gray-400 text-center py-3">No items added</p>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3">
        <button type="button" @click="$router.back()" class="btn-secondary">Cancel</button>
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Creating…' : 'Create Delivery Note' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { ChevronLeftIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const router   = useRouter()
const saving   = ref(false)
const customers = ref([])
const jobCards  = ref([])

const today = new Date().toISOString().slice(0, 10)

const form = ref({
  customer_id: '',
  job_card_id: '',
  delivery_date: today,
  delivery_method: 'own_vehicle',
  total_quantity: 1,
  delivered_quantity: 0,
  delivery_address: '',
  driver_name: '',
  vehicle_number: '',
  notes: '',
  items: [{ description: '', quantity: 1, unit: 'pcs' }],
})

function addItem() {
  form.value.items.push({ description: '', quantity: 1, unit: 'pcs' })
}

async function submit() {
  saving.value = true
  try {
    const { data } = await axios.post('/api/deliveries', form.value)
    router.push('/deliveries')
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Failed to create delivery note'
    alert(msg)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const [c, j] = await Promise.all([
    axios.get('/api/customers', { params: { per_page: 200 } }),
    axios.get('/api/job-cards', { params: { status: 'ready', per_page: 100 } }),
  ])
  customers.value = c.data.data ?? c.data
  jobCards.value  = j.data.data ?? j.data
})
</script>

<style scoped>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input { @apply border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none; }
.btn-primary { @apply bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50; }
.btn-secondary { @apply border border-gray-200 text-gray-600 hover:bg-gray-50 px-5 py-2 rounded-lg text-sm font-semibold transition-colors; }
</style>
