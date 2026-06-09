<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Damage Management</h2>
        <p class="text-sm text-gray-500">Record and review broken bottles, leakage, expired food, and stock write-offs.</p>
      </div>
    </div>

    <div class="card space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div>
          <label class="form-label">Item *</label>
          <select v-model="form.product_id" class="form-input">
            <option value="">Select item</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.stock_quantity }})</option>
          </select>
        </div>
        <div>
          <label class="form-label">Qty *</label>
          <input v-model.number="form.quantity" type="number" min="0.001" step="0.001" class="form-input" />
        </div>
        <div>
          <label class="form-label">Reason *</label>
          <select v-model="form.reason" class="form-input">
            <option value="Broken">Broken</option>
            <option value="Leakage">Leakage</option>
            <option value="Expired">Expired</option>
            <option value="Missing stock">Missing stock</option>
            <option value="Quality issue">Quality issue</option>
          </select>
        </div>
        <div>
          <label class="form-label">Staff</label>
          <input v-model="form.staff_name" class="form-input" />
        </div>
        <div>
          <label class="form-label">Notes</label>
          <input v-model="form.notes" class="form-input" />
        </div>
        <div>
          <button class="btn-primary w-full" :disabled="saving" @click="submit">{{ saving ? 'Saving...' : 'Record Damage' }}</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input v-model="filters.search" @input="reload" class="form-input" placeholder="Search by reference, reason or item" />
        <select v-model="filters.status" @change="reload" class="form-input">
          <option value="">All statuses</option>
          <option value="reported">Reported</option>
          <option value="approved">Approved</option>
          <option value="written_off">Written off</option>
        </select>
        <select v-model="filters.per_page" @change="reload" class="form-input">
          <option :value="10">10 rows</option>
          <option :value="20">20 rows</option>
          <option :value="50">50 rows</option>
        </select>
        <button class="btn-secondary" @click="clearFilters">Clear</button>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Reference</th>
            <th class="table-th">Item</th>
            <th class="table-th">Qty</th>
            <th class="table-th">Reason</th>
            <th class="table-th">Status</th>
            <th class="table-th">Staff</th>
            <th class="table-th">Loss</th>
            <th class="table-th">Date</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="d in damages.data" :key="d.id">
            <td class="table-td font-mono text-xs">{{ d.reference_number }}</td>
            <td class="table-td">{{ d.product?.name }}</td>
            <td class="table-td">{{ d.quantity }}</td>
            <td class="table-td">{{ d.reason }}</td>
            <td class="table-td">
              <span :class="statusBadge(d.status)" class="badge capitalize">{{ d.status.replace('_', ' ') }}</span>
            </td>
            <td class="table-td">{{ d.staff_name || '-' }}</td>
            <td class="table-td">LKR {{ Number(d.estimated_loss).toLocaleString() }}</td>
            <td class="table-td">{{ formatDate(d.occurred_at) }}</td>
            <td class="table-td">
              <button class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200" @click="openEdit(d)">
                Edit
              </button>
            </td>
          </tr>
          <tr v-if="!damages.data?.length"><td colspan="9" class="table-td text-center text-gray-400">No damage reports yet</td></tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600">
        <span>{{ damages.from || 0 }}-{{ damages.to || 0 }} of {{ damages.total || 0 }}</span>
        <div class="flex gap-2">
          <button class="btn-secondary py-1 px-3 text-xs disabled:opacity-40" :disabled="page <= 1" @click="prev">Prev</button>
          <button class="btn-secondary py-1 px-3 text-xs disabled:opacity-40" :disabled="page >= (damages.last_page || 1)" @click="next">Next</button>
        </div>
      </div>
    </div>

    <teleport to="body">
      <div v-if="showEdit" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showEdit = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
          <div class="p-5 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Update Damage Report</h3>
            <button class="text-gray-400 hover:text-gray-600" @click="showEdit = false">X</button>
          </div>
          <div class="p-5 space-y-3">
            <div>
              <label class="form-label">Status</label>
              <select v-model="editForm.status" class="form-input">
                <option value="reported">Reported</option>
                <option value="approved">Approved</option>
                <option value="written_off">Written off</option>
              </select>
            </div>
            <div>
              <label class="form-label">Reason</label>
              <input v-model="editForm.reason" class="form-input" />
            </div>
            <div>
              <label class="form-label">Staff</label>
              <input v-model="editForm.staff_name" class="form-input" />
            </div>
            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="editForm.notes" rows="3" class="form-input"></textarea>
            </div>
          </div>
          <div class="p-5 border-t flex justify-end gap-2">
            <button class="btn-secondary" @click="showEdit = false">Cancel</button>
            <button class="btn-primary" :disabled="savingEdit" @click="saveEdit">{{ savingEdit ? 'Saving...' : 'Save Changes' }}</button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'

const products = ref([])
const damages = ref({ data: [] })
const saving = ref(false)
const savingEdit = ref(false)
const showEdit = ref(false)
const page = ref(1)

const filters = reactive({
  search: '',
  status: '',
  per_page: 20,
})

const form = reactive({
  product_id: '',
  quantity: 1,
  reason: 'Broken',
  staff_name: '',
  notes: '',
})

const editForm = reactive({
  id: null,
  status: 'reported',
  reason: '',
  staff_name: '',
  notes: '',
})

function formatDate(v) {
  return new Date(v).toLocaleString('en-LK')
}

function statusBadge(status) {
  if (status === 'written_off') return 'bg-red-100 text-red-700'
  if (status === 'approved') return 'bg-green-100 text-green-700'
  return 'bg-yellow-100 text-yellow-700'
}

async function loadProducts() {
  const productRes = await axios.get('/api/products', { params: { per_page: 300 } })
  products.value = productRes.data.data
}

async function reload() {
  const damageRes = await axios.get('/api/damage-reports', {
    params: {
      page: page.value,
      per_page: filters.per_page,
      search: filters.search,
      status: filters.status,
    },
  })
  damages.value = damageRes.data
}

function clearFilters() {
  filters.search = ''
  filters.status = ''
  filters.per_page = 20
  page.value = 1
  reload()
}

function prev() {
  if (page.value <= 1) return
  page.value -= 1
  reload()
}

function next() {
  if (page.value >= (damages.value.last_page || 1)) return
  page.value += 1
  reload()
}

async function submit() {
  saving.value = true
  try {
    await axios.post('/api/damage-reports', form)
    form.product_id = ''
    form.quantity = 1
    form.reason = 'Broken'
    form.staff_name = ''
    form.notes = ''
    page.value = 1
    await Promise.all([reload(), loadProducts()])
  } finally {
    saving.value = false
  }
}

function openEdit(damage) {
  editForm.id = damage.id
  editForm.status = damage.status
  editForm.reason = damage.reason || ''
  editForm.staff_name = damage.staff_name || ''
  editForm.notes = damage.notes || ''
  showEdit.value = true
}

async function saveEdit() {
  if (!editForm.id) return
  savingEdit.value = true
  try {
    await axios.put(`/api/damage-reports/${editForm.id}`, {
      status: editForm.status,
      reason: editForm.reason,
      staff_name: editForm.staff_name,
      notes: editForm.notes,
    })
    showEdit.value = false
    await reload()
  } finally {
    savingEdit.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadProducts(), reload()])
})
</script>
