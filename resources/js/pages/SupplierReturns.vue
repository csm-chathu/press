<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Supplier Returns</h2>
        <p class="text-sm text-gray-500">Return damaged or wrong goods and issue supplier credit notes.</p>
      </div>
      <button class="btn-primary" @click="showForm = !showForm">{{ showForm ? 'Hide Form' : 'New Return' }}</button>
    </div>

    <div v-if="showForm" class="card space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
          <label class="form-label">Supplier *</label>
          <select v-model="form.supplier_id" class="form-input">
            <option value="">Select supplier</option>
            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Status *</label>
          <select v-model="form.status" class="form-input">
            <option value="draft">Draft</option>
            <option value="approved">Approved</option>
            <option value="sent">Sent</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label class="form-label">Reason</label>
          <input v-model="form.reason" class="form-input" placeholder="Broken / Wrong item / Expired" />
        </div>
        <div>
          <label class="form-label">Credit Note</label>
          <input v-model="form.credit_note_number" class="form-input" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
          <label class="form-label">Product</label>
          <select v-model="itemRow.product_id" class="form-input">
            <option value="">Select product</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.stock_quantity }})</option>
          </select>
        </div>
        <div>
          <label class="form-label">Qty</label>
          <input v-model.number="itemRow.quantity" type="number" min="0.001" step="0.001" class="form-input" />
        </div>
        <div>
          <label class="form-label">Unit Cost</label>
          <input v-model.number="itemRow.unit_cost" type="number" min="0" step="0.01" class="form-input" />
        </div>
        <div>
          <label class="form-label">Item Reason</label>
          <input v-model="itemRow.reason" class="form-input" />
        </div>
        <div class="flex items-end">
          <button class="btn-secondary w-full" @click="addItem">Add Item</button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr>
              <th class="table-th">Product</th>
              <th class="table-th">Qty</th>
              <th class="table-th">Unit Cost</th>
              <th class="table-th">Total</th>
              <th class="table-th">Reason</th>
              <th class="table-th">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(i, idx) in form.items" :key="idx">
              <td class="table-td">{{ i.product_name }}</td>
              <td class="table-td">{{ i.quantity }}</td>
              <td class="table-td">{{ i.unit_cost }}</td>
              <td class="table-td">{{ Number(i.quantity * i.unit_cost).toFixed(2) }}</td>
              <td class="table-td">{{ i.reason }}</td>
              <td class="table-td"><button class="text-red-600" @click="form.items.splice(idx,1)">Remove</button></td>
            </tr>
            <tr v-if="!form.items.length"><td colspan="6" class="table-td text-center text-gray-400">No items added</td></tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-end gap-2">
        <button class="btn-secondary" @click="showForm = false">Cancel</button>
        <button class="btn-primary" :disabled="saving || !form.items.length" @click="submit">{{ saving ? 'Saving...' : 'Submit Return' }}</button>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Return #</th>
            <th class="table-th">Supplier</th>
            <th class="table-th">Status</th>
            <th class="table-th">Amount</th>
            <th class="table-th">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in returns.data" :key="r.id">
            <td class="table-td font-mono text-xs">{{ r.return_number }}</td>
            <td class="table-td">{{ r.supplier?.name }}</td>
            <td class="table-td"><span class="badge bg-orange-100 text-orange-700">{{ r.status }}</span></td>
            <td class="table-td">LKR {{ Number(r.total_amount).toLocaleString() }}</td>
            <td class="table-td">{{ formatDate(r.returned_at) }}</td>
          </tr>
          <tr v-if="!returns.data?.length"><td colspan="5" class="table-td text-center text-gray-400">No supplier returns yet</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'

const returns = ref({ data: [] })
const suppliers = ref([])
const products = ref([])
const showForm = ref(false)
const saving = ref(false)

const form = reactive({
  supplier_id: '',
  status: 'draft',
  reason: '',
  credit_note_number: '',
  items: [],
})

const itemRow = reactive({ product_id: '', quantity: 1, unit_cost: 0, reason: '' })

function formatDate(value) {
  return new Date(value).toLocaleDateString('en-LK')
}

function addItem() {
  const p = products.value.find(x => x.id == itemRow.product_id)
  if (!p) return
  form.items.push({
    product_id: p.id,
    product_name: p.name,
    quantity: Number(itemRow.quantity),
    unit_cost: Number(itemRow.unit_cost || p.purchase_price || 0),
    reason: itemRow.reason || form.reason,
  })
  itemRow.product_id = ''
  itemRow.quantity = 1
  itemRow.unit_cost = 0
  itemRow.reason = ''
}

async function load() {
  const [retRes, supRes, prodRes] = await Promise.all([
    axios.get('/api/supplier-returns'),
    axios.get('/api/suppliers/all'),
    axios.get('/api/products', { params: { per_page: 300 } }),
  ])
  returns.value = retRes.data
  suppliers.value = supRes.data
  products.value = prodRes.data.data
}

async function submit() {
  saving.value = true
  try {
    await axios.post('/api/supplier-returns', form)
    showForm.value = false
    form.supplier_id = ''
    form.status = 'draft'
    form.reason = ''
    form.credit_note_number = ''
    form.items = []
    await load()
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
