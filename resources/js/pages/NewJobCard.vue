<template>
  <div class="max-w-4xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-800">New Job Card</h2>
      <router-link to="/job-cards" class="text-sm text-gray-500 hover:text-gray-700">← Back</router-link>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <!-- Basic Info -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="label">Job Title *</label>
          <input v-model="form.title" required type="text" placeholder="e.g., Business Cards — Dialog Axiata" class="input w-full" />
        </div>
        <div>
          <label class="label">Customer</label>
          <select v-model="form.customer_id" class="input w-full">
            <option value="">Walk-in / No customer</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">Order Reference</label>
          <select v-model="form.order_id" class="input w-full">
            <option value="">None</option>
            <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.invoice_number }}</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="label">Product Description</label>
          <textarea v-model="form.product_description" rows="2" class="input w-full" placeholder="Describe the print job in detail…"></textarea>
        </div>
      </div>

      <!-- Print Specs -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Print Specifications</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="label">Paper Type</label>
            <input v-model="form.paper_type" type="text" placeholder="Art Paper, Bond…" class="input w-full" />
          </div>
          <div>
            <label class="label">GSM</label>
            <input v-model.number="form.gsm" type="number" placeholder="170" class="input w-full" />
          </div>
          <div>
            <label class="label">Size</label>
            <input v-model="form.size" type="text" placeholder="A4, A5, 3.5x2in" class="input w-full" />
          </div>
          <div>
            <label class="label">Qty Ordered</label>
            <input v-model.number="form.quantity_ordered" type="number" placeholder="1000" class="input w-full" />
          </div>
          <div>
            <label class="label">Color Count</label>
            <input v-model="form.color_count" type="text" placeholder="4+4, 4+0, 1+0" class="input w-full" />
          </div>
          <div>
            <label class="label">Printing Method</label>
            <select v-model="form.printing_method" class="input w-full">
              <option value="">Select</option>
              <option value="offset">Offset</option>
              <option value="digital">Digital</option>
              <option value="screen">Screen</option>
              <option value="flexo">Flexo</option>
            </select>
          </div>
          <div>
            <label class="label">Machine</label>
            <select v-model="form.machine_id" class="input w-full">
              <option value="">Not assigned</option>
              <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Operator</label>
            <select v-model="form.assigned_operator_id" class="input w-full">
              <option value="">Not assigned</option>
              <option v-for="u in operators" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label">Printing Instructions</label>
          <textarea v-model="form.printing_instructions" rows="3" class="input w-full" placeholder="Special press settings, ink requirements…"></textarea>
        </div>
        <div>
          <label class="label">Finishing Instructions</label>
          <textarea v-model="form.finishing_instructions" rows="3" class="input w-full" placeholder="Lamination, binding, cutting…"></textarea>
        </div>
      </div>

      <!-- Finishing Checklist -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Finishing Operations</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <label v-for="op in finishingOps" :key="op.key" class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.finishing[op.key]" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400" />
            <span class="text-sm text-gray-700">{{ op.label }}</span>
          </label>
        </div>
        <div v-if="form.finishing.lamination" class="mt-3 grid grid-cols-2 gap-3">
          <div>
            <label class="label">Lamination Type</label>
            <select v-model="form.finishing.lamination_type" class="input w-full">
              <option value="">Select</option>
              <option value="gloss">Gloss</option>
              <option value="matte">Matte</option>
              <option value="soft_touch">Soft Touch</option>
            </select>
          </div>
        </div>
        <div v-if="form.finishing.binding" class="mt-3 grid grid-cols-2 gap-3">
          <div>
            <label class="label">Binding Type</label>
            <select v-model="form.finishing.binding_type" class="input w-full">
              <option value="">Select</option>
              <option value="saddle_stitch">Saddle Stitch</option>
              <option value="perfect">Perfect Binding</option>
              <option value="spiral">Spiral / Coil</option>
              <option value="hard_cover">Hard Cover</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Dates -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 md:grid-cols-3 gap-4">
        <div>
          <label class="label">Scheduled Date</label>
          <input v-model="form.scheduled_date" type="date" class="input w-full" />
        </div>
        <div>
          <label class="label">Due Date</label>
          <input v-model="form.due_date" type="date" class="input w-full" />
        </div>
        <div>
          <label class="label">Artwork Status</label>
          <select v-model="form.artwork_status" class="input w-full">
            <option value="pending">Pending</option>
            <option value="received">Received</option>
            <option value="reviewing">Under Review</option>
            <option value="approved">Approved</option>
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="input w-full" placeholder="Any additional notes…"></textarea>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 justify-end">
        <router-link to="/job-cards" class="btn-secondary">Cancel</router-link>
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Creating…' : 'Create Job Card' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const saving = ref(false)
const customers = ref([])
const machines  = ref([])
const operators = ref([])
const orders    = ref([])

const finishingOps = [
  { key: 'cutting', label: 'Cutting' }, { key: 'folding', label: 'Folding' },
  { key: 'binding', label: 'Binding' }, { key: 'lamination', label: 'Lamination' },
  { key: 'uv_coating', label: 'UV Coating' }, { key: 'foiling', label: 'Foiling' },
  { key: 'die_cutting', label: 'Die Cutting' }, { key: 'packaging', label: 'Packaging' },
]

const form = ref({
  title: '', customer_id: '', order_id: '', product_description: '',
  paper_type: '', gsm: null, size: '', quantity_ordered: null,
  color_count: '', printing_method: '', machine_id: '', assigned_operator_id: '',
  printing_instructions: '', finishing_instructions: '',
  scheduled_date: '', due_date: '', artwork_status: 'pending', notes: '',
  finishing: {
    cutting: false, folding: false, binding: false, lamination: false,
    uv_coating: false, foiling: false, die_cutting: false, packaging: false,
    lamination_type: '', binding_type: '',
  },
})

async function submit() {
  saving.value = true
  try {
    const { data } = await axios.post('/api/job-cards', form.value)
    router.push(`/job-cards/${data.id}`)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to create job card')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const [c, m, s] = await Promise.all([
    axios.get('/api/customers/all'),
    axios.get('/api/press-machines/all'),
    axios.get('/api/sales', { params: { status: 'draft', per_page: 50 } }),
  ])
  customers.value = c.data
  machines.value  = m.data
  orders.value    = s.data.data ?? []

  // Load operators (users with operator/manager roles)
  try {
    const u = await axios.get('/api/users')
    operators.value = (u.data.data ?? u.data).filter(u =>
      ['machine_operator', 'production_manager', 'admin', 'owner'].includes(u.role)
    )
  } catch {}
})
</script>

<style scoped>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input { @apply border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none; }
.btn-primary { @apply bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50; }
.btn-secondary { @apply border border-gray-200 text-gray-600 hover:bg-gray-50 px-5 py-2 rounded-lg text-sm font-semibold transition-colors; }
</style>
