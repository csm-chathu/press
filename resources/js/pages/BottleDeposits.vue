<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Bottle Deposit Management</h2>
        <p class="text-sm text-gray-500">Track collected deposits and process refunds, credits, or supplier returns.</p>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
      <div class="card">
        <p class="text-xs text-gray-500">Collected</p>
        <p class="text-xl font-bold text-emerald-700">LKR {{ fmt(summary.collected) }}</p>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500">Refunded to Customers</p>
        <p class="text-xl font-bold text-red-700">LKR {{ fmt(summary.refunded) }}</p>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500">Credited to Customers</p>
        <p class="text-xl font-bold text-blue-700">LKR {{ fmt(summary.credited) }}</p>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500">Returned to Suppliers</p>
        <p class="text-xl font-bold text-purple-700">LKR {{ fmt(summary.supplier_returned) }}</p>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500">Outstanding (Customers)</p>
        <p class="text-xl font-bold text-amber-700">LKR {{ fmt(summary.outstanding) }}</p>
      </div>
    </div>

    <!-- Available empty bottles -->
    <div class="card">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Available Empty Bottles</h3>
        <p class="text-xs text-gray-400">Returned by customers, ready to send back to supplier</p>
      </div>
      <div v-if="available.length === 0" class="text-sm text-gray-400 py-2">No empty bottles available to return right now.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="table-th">Product</th>
              <th class="table-th text-right">Qty Available</th>
              <th class="table-th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in available" :key="row.product_id">
              <td class="table-td font-medium text-gray-800">{{ row.product?.name }}</td>
              <td class="table-td text-right font-semibold text-purple-700">{{ row.qty_available }}</td>
              <td class="table-td text-right">
                <button class="text-xs text-purple-600 hover:underline" @click="prefillSupplierReturn(row)">Return to Supplier →</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Return to supplier form -->
    <div class="card space-y-3">
      <h3 class="font-semibold text-gray-700">Return Bottles to Supplier</h3>
      <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div>
          <label class="form-label">Supplier *</label>
          <select v-model="supplierForm.supplier_id" class="form-input">
            <option value="">Select supplier</option>
            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Product *</label>
          <select v-model="supplierForm.product_id" class="form-input">
            <option value="">Select product</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <p v-if="supplierForm.product_id && maxQty > 0" class="text-xs text-gray-400 mt-1">Available: {{ maxQty }} bottles</p>
          <p v-else-if="supplierForm.product_id && maxQty === 0" class="text-xs text-red-500 mt-1">No bottles available for this product</p>
        </div>
        <div>
          <label class="form-label">Qty *</label>
          <input v-model.number="supplierForm.quantity" type="number" min="0.001" step="1" :max="maxQty" class="form-input" />
        </div>
        <div>
          <label class="form-label">Amount/Bottle *</label>
          <input v-model.number="supplierForm.amount_per_bottle" type="number" min="0" step="1" class="form-input" />
        </div>
        <div>
          <label class="form-label">Link to PO (optional)</label>
          <select v-model="supplierForm.purchase_id" class="form-input">
            <option value="">None</option>
            <option v-for="po in purchases" :key="po.id" :value="po.id">{{ po.purchase_number }} — {{ po.supplier?.name }}</option>
          </select>
        </div>
        <div class="space-y-1">
          <p v-if="supplierReturnTotal > 0" class="text-xs font-semibold text-purple-700 text-right">
            Total credit: LKR {{ fmt(supplierReturnTotal) }}
          </p>
          <button class="btn-primary w-full" :disabled="savingSupplier || !supplierForm.supplier_id || !supplierForm.product_id || supplierForm.quantity <= 0 || maxQty === 0"
            @click="processSupplierReturn">
            {{ savingSupplier ? 'Processing…' : 'Return to Supplier' }}
          </button>
        </div>
      </div>
      <div>
        <label class="form-label">Notes</label>
        <input v-model="supplierForm.notes" class="form-input" placeholder="Optional notes" />
      </div>
      <p v-if="supplierError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ supplierError }}</p>
    </div>

    <!-- Customer return form -->
    <div class="card space-y-3">
      <h3 class="font-semibold text-gray-700">Process Customer Return</h3>
      <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div>
          <label class="form-label">Customer</label>
          <select v-model="returnForm.customer_id" class="form-input">
            <option value="">Walk-in</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Product *</label>
          <select v-model="returnForm.product_id" class="form-input">
            <option value="">Select product</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Qty *</label>
          <input v-model.number="returnForm.quantity" type="number" min="0.001" step="0.001" class="form-input" />
        </div>
        <div>
          <label class="form-label">Amount/Bottle *</label>
          <input v-model.number="returnForm.amount_per_bottle" type="number" min="0" step="0.01" class="form-input" />
        </div>
        <div>
          <label class="form-label">Method *</label>
          <select v-model="returnForm.method" class="form-input">
            <option value="refund">Cash Refund</option>
            <option value="credit">Credit Note</option>
          </select>
        </div>
        <div>
          <button class="btn-primary w-full" :disabled="saving" @click="processReturn">{{ saving ? 'Processing…' : 'Process Return' }}</button>
        </div>
      </div>
    </div>

    <!-- Transaction log -->
    <div class="card p-0 overflow-hidden">
      <div class="px-4 py-3 border-b bg-gray-50 flex items-center gap-3">
        <h3 class="font-semibold text-gray-700 text-sm">Transaction Log</h3>
        <select v-model="typeFilter" class="form-input w-44 text-sm py-1" @change="load">
          <option value="">All types</option>
          <option value="collect">Collected</option>
          <option value="refund">Refunded (Customer)</option>
          <option value="credit">Credited (Customer)</option>
          <option value="supplier_return">Returned to Supplier</option>
        </select>
      </div>
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Type</th>
            <th class="table-th">Customer / Supplier</th>
            <th class="table-th">Product</th>
            <th class="table-th">Qty</th>
            <th class="table-th">Amount</th>
            <th class="table-th">PO / Sale</th>
            <th class="table-th">Status</th>
            <th class="table-th">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="d in deposits.data" :key="d.id">
            <td class="table-td">
              <span :class="typeClass(d.type)" class="badge capitalize">{{ typeLabel(d.type) }}</span>
            </td>
            <td class="table-td">{{ d.type === 'supplier_return' ? (d.supplier?.name || '—') : (d.customer?.name || 'Walk-in') }}</td>
            <td class="table-td">{{ d.product?.name }}</td>
            <td class="table-td">{{ d.quantity }}</td>
            <td class="table-td font-semibold">LKR {{ Number(d.total_amount).toLocaleString() }}</td>
            <td class="table-td text-xs text-gray-500">
              <span v-if="d.purchase?.purchase_number">{{ d.purchase.purchase_number }}</span>
              <span v-else-if="d.sale_id">Sale #{{ d.sale_id }}</span>
              <span v-else>—</span>
            </td>
            <td class="table-td"><span class="badge bg-gray-100 text-gray-700">{{ d.status }}</span></td>
            <td class="table-td text-xs">{{ formatDate(d.processed_at) }}</td>
          </tr>
          <tr v-if="!deposits.data?.length">
            <td colspan="8" class="table-td text-center text-gray-400 py-6">No bottle deposit records yet</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import axios from 'axios'

const deposits     = ref({ data: [] })
const summary      = ref({})
const available    = ref([])
const customers    = ref([])
const products     = ref([])
const suppliers    = ref([])
const purchases    = ref([])
const saving       = ref(false)
const savingSupplier = ref(false)
const supplierError  = ref('')
const typeFilter   = ref('')

const returnForm = reactive({
  customer_id: '',
  product_id: '',
  quantity: 1,
  amount_per_bottle: 100,
  method: 'refund',
})

const supplierForm = reactive({
  supplier_id: '',
  purchase_id: '',
  product_id: '',
  quantity: 1,
  amount_per_bottle: 100,
  notes: '',
})

const maxQty = computed(() => {
  if (!supplierForm.product_id) return 0
  const row = available.value.find(r => r.product_id == supplierForm.product_id)
  return row ? row.qty_available : 0
})

const supplierReturnTotal = computed(() =>
  (supplierForm.quantity || 0) * (supplierForm.amount_per_bottle || 0)
)

function fmt(v) { return Number(v || 0).toLocaleString() }

function formatDate(v) { return new Date(v).toLocaleString('en-LK') }

function typeLabel(t) {
  return { collect: 'Collected', refund: 'Refund', credit: 'Credit', supplier_return: 'Supplier Return' }[t] ?? t
}

function typeClass(t) {
  return {
    collect:         'bg-emerald-100 text-emerald-700',
    refund:          'bg-red-100 text-red-700',
    credit:          'bg-blue-100 text-blue-700',
    supplier_return: 'bg-purple-100 text-purple-700',
  }[t] ?? 'bg-gray-100 text-gray-700'
}

function prefillSupplierReturn(row) {
  supplierForm.product_id = row.product_id
  supplierForm.quantity   = row.qty_available
}

async function load() {
  const [depositRes, summaryRes, availableRes, customerRes, productRes, supplierRes, poRes] = await Promise.all([
    axios.get('/api/bottle-deposits', { params: { type: typeFilter.value || undefined } }),
    axios.get('/api/bottle-deposits/summary'),
    axios.get('/api/bottle-deposits/available'),
    axios.get('/api/customers/all'),
    axios.get('/api/products', { params: { per_page: 300 } }),
    axios.get('/api/suppliers/all'),
    axios.get('/api/purchases', { params: { per_page: 200, receivable: 1 } }),
  ])
  deposits.value  = depositRes.data
  summary.value   = summaryRes.data
  available.value = availableRes.data
  customers.value = customerRes.data
  products.value  = productRes.data.data
  suppliers.value = supplierRes.data
  purchases.value = poRes.data.data
}

async function processReturn() {
  saving.value = true
  try {
    await axios.post('/api/bottle-deposits/return', returnForm)
    await load()
  } finally {
    saving.value = false
  }
}

async function processSupplierReturn() {
  savingSupplier.value = true
  supplierError.value  = ''
  try {
    await axios.post('/api/bottle-deposits/supplier-return', {
      supplier_id:       supplierForm.supplier_id,
      purchase_id:       supplierForm.purchase_id || undefined,
      product_id:        supplierForm.product_id,
      quantity:          supplierForm.quantity,
      amount_per_bottle: supplierForm.amount_per_bottle,
      notes:             supplierForm.notes || undefined,
    })
    supplierForm.product_id = ''
    supplierForm.quantity   = 1
    supplierForm.notes      = ''
    await load()
  } catch (e) {
    supplierError.value = e.response?.data?.message ?? 'Failed to process return'
  } finally {
    savingSupplier.value = false
  }
}

onMounted(load)
</script>
