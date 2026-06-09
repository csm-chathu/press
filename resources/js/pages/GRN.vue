<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Goods Received Notes</h2>
        <p class="text-sm text-gray-500">Receive stock against purchase orders with partial receive support.</p>
      </div>
      <button class="btn-primary" @click="showForm = !showForm">{{ showForm ? 'Hide Form' : 'New GRN' }}</button>
    </div>

    <div v-if="showForm" class="card space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="form-label">Purchase Order *</label>
          <select v-model="form.purchase_id" class="form-input" @change="onPurchaseChange">
            <option value="">Select PO</option>
            <option v-for="po in purchases" :key="po.id" :value="po.id">{{ po.purchase_number }} — {{ po.supplier?.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Supplier Invoice</label>
          <input v-model="form.supplier_invoice_number" class="form-input" />
        </div>
        <div>
          <label class="form-label">Notes</label>
          <input v-model="form.notes" class="form-input" />
        </div>
      </div>

      <!-- PO items table -->
      <div v-if="loadingItems" class="flex items-center gap-2 text-sm text-gray-400 py-4">
        <svg class="w-4 h-4 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        Loading PO items…
      </div>

      <div v-else-if="form.items.length > 0" class="overflow-x-auto">
        <p class="text-xs text-gray-500 mb-2 font-medium uppercase tracking-wide">Items from PO — adjust quantities and costs as received</p>
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="table-th">Product</th>
              <th class="table-th">PO Qty</th>
              <th class="table-th text-right">Receive Qty</th>
              <th class="table-th text-right">Free Qty</th>
              <th class="table-th text-right">Unit Cost</th>
              <th class="table-th text-right">Selling Price</th>
              <th class="table-th">Batch</th>
              <th class="table-th">Expiry</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, i) in form.items" :key="i" class="hover:bg-amber-50/30">
              <td class="table-td font-medium text-gray-800">{{ item.product_name }}</td>
              <td class="table-td text-gray-500">{{ item.po_quantity }}</td>
              <td class="table-td text-right">
                <input v-model.number="item.quantity_received" type="number" min="1" step="1"
                  class="form-input text-right w-24" @keydown="blockDecimal" @input="forceInt(item, 'quantity_received', 1)" />
              </td>
              <td class="table-td text-right">
                <input v-model.number="item.free_quantity" type="number" min="0" step="1"
                  class="form-input text-right w-24" @keydown="blockDecimal" @input="forceInt(item, 'free_quantity', 0)" />
              </td>
              <td class="table-td text-right">
                <input v-model.number="item.unit_cost" type="number" min="1" step="1"
                  class="form-input text-right w-28" @keydown="blockDecimal" @input="forceInt(item, 'unit_cost', 1)" />
              </td>
              <td class="table-td text-right">
                <input v-model.number="item.selling_price" type="number" min="1" step="1"
                  class="form-input text-right w-28" @keydown="blockDecimal" @input="forceInt(item, 'selling_price', 1)" />
              </td>
              <td class="table-td"><input v-model="item.batch_number" class="form-input w-28" /></td>
              <td class="table-td"><input v-model="item.expiry_date" type="date" class="form-input" /></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="form.purchase_id && !loadingItems" class="text-sm text-gray-400 py-2">
        No items found on this PO.
      </div>

      <div class="flex justify-end gap-2">
        <button class="btn-secondary" @click="cancelForm">Cancel</button>
        <button class="btn-primary" :disabled="saving || form.items.length === 0" @click="submit">
          {{ saving ? 'Saving…' : 'Save GRN' }}
        </button>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">GRN #</th>
            <th class="table-th">PO #</th>
            <th class="table-th">Supplier</th>
            <th class="table-th">Status</th>
            <th class="table-th">Total</th>
            <th class="table-th">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="g in grns.data" :key="g.id">
            <td class="table-td font-mono text-xs">{{ g.grn_number }}</td>
            <td class="table-td">{{ g.purchase?.purchase_number }}</td>
            <td class="table-td">{{ g.supplier?.name }}</td>
            <td class="table-td"><span class="badge bg-blue-100 text-blue-700">{{ g.status }}</span></td>
            <td class="table-td">LKR {{ Number(g.total).toLocaleString() }}</td>
            <td class="table-td">{{ formatDate(g.received_at) }}</td>
          </tr>
          <tr v-if="!grns.data?.length"><td class="table-td text-center text-gray-400" colspan="6">No GRN records yet</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'

const grns         = ref({ data: [] })
const purchases    = ref([])
const showForm     = ref(false)
const saving       = ref(false)
const loadingItems = ref(false)

const form = reactive({
  purchase_id: '',
  supplier_invoice_number: '',
  notes: '',
  items: [],
})

function blockDecimal(e) {
  if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E') e.preventDefault()
}

function forceInt(item, field, min) {
  const v = Math.floor(Number(item[field]))
  item[field] = isNaN(v) || v < min ? min : v
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('en-LK')
}

async function load() {
  const [grnRes, purchaseRes] = await Promise.all([
    axios.get('/api/grns'),
    axios.get('/api/purchases', { params: { per_page: 200, receivable: 1 } }),
  ])
  grns.value = grnRes.data
  purchases.value = purchaseRes.data.data
}

async function onPurchaseChange() {
  form.items = []
  if (!form.purchase_id) return

  loadingItems.value = true
  try {
    const { data } = await axios.get(`/api/purchases/${form.purchase_id}`)
    form.items = (data.items ?? []).map(i => ({
      purchase_item_id:  i.id,
      product_id:        i.product_id,
      product_name:      i.product?.name ?? 'Item',
      po_quantity:       i.quantity,
      quantity_received: i.quantity,
      free_quantity:     0,
      unit_cost:         i.unit_cost,
      selling_price:     i.product?.selling_price ?? 0,
      batch_number:      '',
      expiry_date:       '',
    }))
  } finally {
    loadingItems.value = false
  }
}

function cancelForm() {
  showForm.value = false
  form.purchase_id = ''
  form.supplier_invoice_number = ''
  form.notes = ''
  form.items = []
}

async function submit() {
  saving.value = true
  try {
    await axios.post('/api/grns', {
      purchase_id:              form.purchase_id,
      supplier_invoice_number:  form.supplier_invoice_number,
      notes:                    form.notes,
      items:                    form.items,
    })
    cancelForm()
    await load()
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
