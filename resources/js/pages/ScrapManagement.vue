<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Scrap Gold Management</h2>
        <p class="text-sm text-gray-500 mt-0.5">Manage scrap gold from buy-backs and converted items — track refining & melting</p>
      </div>
      <button @click="showConvert = true" class="btn-secondary flex items-center gap-2">
        <ArrowPathIcon class="w-4 h-4" /> Convert Product to Scrap
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-4 gap-4" v-if="summary">
      <div v-for="s in summaryCards" :key="s.label" class="card text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wider">{{ s.label }}</p>
        <p class="text-2xl font-bold text-gold-700 mt-1">{{ s.value }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ s.sub }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap">
      <select v-model="filters.status" class="form-input w-44" @change="load">
        <option value="">All Statuses</option>
        <option value="available">Available</option>
        <option value="sent_for_refining">Sent for Refining</option>
        <option value="melted">Melted</option>
        <option value="sold">Sold</option>
      </select>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">SKU</th>
            <th class="table-th">Description</th>
            <th class="table-th">Source</th>
            <th class="table-th">Karat</th>
            <th class="table-th">Weight</th>
            <th class="table-th">Est. Value</th>
            <th class="table-th">Status</th>
            <th class="table-th">Refinery</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="s in scraps.data" :key="s.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs text-gray-500">{{ s.sku }}</td>
            <td class="table-td text-sm font-medium">{{ s.description }}</td>
            <td class="table-td text-xs">
              <span v-if="s.source_type === 'buyback'" class="badge bg-blue-100 text-blue-700">
                Buy-back {{ s.buyback?.buyback_number }}
              </span>
              <span v-else-if="s.source_type === 'converted_product'" class="badge bg-purple-100 text-purple-700">
                Converted
              </span>
              <span v-else class="badge bg-gray-100 text-gray-600">Other</span>
              <p class="text-gray-400 mt-0.5">{{ s.buyback?.customer?.name ?? s.product?.name }}</p>
            </td>
            <td class="table-td text-gold-700 font-semibold uppercase text-sm">{{ s.karat }}</td>
            <td class="table-td font-mono text-sm">{{ s.weight_g }}g</td>
            <td class="table-td text-sm font-semibold">LKR {{ lkr(s.estimated_value) }}</td>
            <td class="table-td">
              <span :class="statusClass(s.status)" class="badge text-xs">{{ statusLabel(s.status) }}</span>
            </td>
            <td class="table-td text-xs text-gray-500">{{ s.refinery_name ?? '—' }}</td>
            <td class="table-td">
              <div class="flex gap-1.5">
                <button @click="openEdit(s)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <PencilSquareIcon class="w-3.5 h-3.5" /> Update
                </button>
                <button @click="del(s)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!scraps.data?.length">
            <td colspan="9" class="table-td text-center text-gray-400 py-10">No scrap items found</td>
          </tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ scraps.total ?? 0 }} items</span>
        <div class="flex gap-2">
          <button @click="page--; load()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">← Prev</button>
          <button @click="page++; load()" :disabled="page>=scraps.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>

    <!-- Edit status modal -->
    <teleport to="body">
      <div v-if="showEdit" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showEdit=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
          <div class="flex items-center justify-between p-6 border-b">
            <h3 class="font-semibold text-gray-800">Update Scrap Item — {{ editForm.sku }}</h3>
            <button @click="showEdit=false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="form-label">Status</label>
              <select v-model="editForm.status" class="form-input">
                <option value="available">Available</option>
                <option value="sent_for_refining">Sent for Refining</option>
                <option value="melted">Melted</option>
                <option value="sold">Sold</option>
              </select>
            </div>
            <div v-if="editForm.status === 'sent_for_refining' || editForm.status === 'melted'">
              <label class="form-label">Refinery Name</label>
              <input v-model="editForm.refinery_name" class="form-input" placeholder="Name of refinery" />
            </div>
            <div v-if="editForm.status === 'sent_for_refining' || editForm.status === 'melted'">
              <label class="form-label">Refinery Notes</label>
              <textarea v-model="editForm.refinery_notes" rows="2" class="form-input" placeholder="Receipt number, expected return date…"></textarea>
            </div>
            <div>
              <label class="form-label">Est. Value (LKR)</label>
              <input v-model.number="editForm.estimated_value" type="number" min="0" step="0.01" class="form-input" />
            </div>
            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="editForm.notes" rows="2" class="form-input"></textarea>
            </div>
            <p v-if="editError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ editError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
            <button @click="showEdit=false" class="btn-secondary">Cancel</button>
            <button @click="saveEdit" :disabled="editSaving" class="btn-primary">{{ editSaving ? 'Saving…' : 'Save' }}</button>
          </div>
        </div>
      </div>

      <!-- Convert product modal -->
      <div v-if="showConvert" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showConvert=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
          <div class="flex items-center justify-between p-6 border-b">
            <h3 class="font-semibold text-gray-800">Convert Product to Scrap</h3>
            <button @click="showConvert=false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-6 space-y-4">
            <p class="text-sm text-gray-500">Select a product to remove 1 unit from inventory and create a scrap record for melting or refining.</p>
            <div>
              <label class="form-label">Product *</label>
              <select v-model="convertForm.product_id" class="form-input" @change="fillConvertWeight">
                <option value="">Select product…</option>
                <option v-for="p in products" :key="p.id" :value="p.id" :disabled="p.stock_quantity < 1">
                  {{ p.name }} ({{ p.karat ?? 'N/A' }}, {{ p.weight ?? '?' }}g — Stock: {{ p.stock_quantity }})
                </option>
              </select>
            </div>
            <div>
              <label class="form-label">Weight to Scrap (g) *</label>
              <input v-model.number="convertForm.weight_g" type="number" min="0.001" step="0.001" class="form-input" />
            </div>
            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="convertForm.notes" rows="2" class="form-input" placeholder="Reason for conversion…"></textarea>
            </div>
            <p v-if="convertError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ convertError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
            <button @click="showConvert=false" class="btn-secondary">Cancel</button>
            <button @click="doConvert" :disabled="convertSaving" class="btn-primary">{{ convertSaving ? 'Converting…' : 'Convert to Scrap' }}</button>
          </div>
        </div>
      </div>
    </teleport>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { ArrowPathIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/components/ConfirmModal.vue'

const scraps         = ref({ data: [], total: 0, last_page: 1 })
const products       = ref([])
const page           = ref(1)
const filters        = reactive({ status: '' })
const confirmDelete  = ref(null)
const confirmMessage = ref('')

// Edit state
const showEdit  = ref(false)
const editSaving = ref(false)
const editError  = ref('')
const editForm   = reactive({ id: null, sku: '', status: 'available', refinery_name: '', refinery_notes: '', estimated_value: 0, notes: '' })

// Convert product state
const showConvert   = ref(false)
const convertSaving = ref(false)
const convertError  = ref('')
const convertForm   = reactive({ product_id: '', weight_g: 0, notes: '' })

const summary = computed(() => scraps.value.data.length > 0)
const summaryCards = computed(() => {
  const available = scraps.value.data.filter(s => s.status === 'available')
  const refining  = scraps.value.data.filter(s => s.status === 'sent_for_refining')
  const totalW    = scraps.value.data.reduce((s, i) => s + (i.weight_g || 0), 0)
  const totalV    = scraps.value.data.reduce((s, i) => s + (i.estimated_value || 0), 0)
  return [
    { label: 'Total Items', value: scraps.value.total, sub: 'all statuses' },
    { label: 'Available', value: available.length, sub: `${available.reduce((s,i)=>s+(i.weight_g||0),0).toFixed(2)}g` },
    { label: 'At Refinery', value: refining.length, sub: `${refining.reduce((s,i)=>s+(i.weight_g||0),0).toFixed(2)}g` },
    { label: 'Total Weight', value: totalW.toFixed(2)+'g', sub: `LKR ${lkr(totalV)} est.` },
  ]
})

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function statusClass(s) {
  return { available: 'bg-green-100 text-green-700', sent_for_refining: 'bg-blue-100 text-blue-700',
    melted: 'bg-orange-100 text-orange-700', sold: 'bg-gray-100 text-gray-600' }[s] ?? 'bg-gray-100 text-gray-600'
}
function statusLabel(s) {
  return { available: 'Available', sent_for_refining: 'At Refinery', melted: 'Melted', sold: 'Sold' }[s] ?? s
}

function openEdit(s) {
  Object.assign(editForm, { id: s.id, sku: s.sku, status: s.status, refinery_name: s.refinery_name ?? '', refinery_notes: s.refinery_notes ?? '', estimated_value: s.estimated_value, notes: s.notes ?? '' })
  editError.value = ''
  showEdit.value  = true
}

async function saveEdit() {
  editSaving.value = true; editError.value = ''
  try {
    await axios.put(`/api/scrap-items/${editForm.id}`, editForm)
    showEdit.value = false
    load()
  } catch (e) {
    editError.value = e.response?.data?.message ?? 'Error saving'
  } finally { editSaving.value = false }
}

function fillConvertWeight() {
  const p = products.value.find(x => x.id == convertForm.product_id)
  if (p) convertForm.weight_g = p.weight ?? 0
}

async function doConvert() {
  convertSaving.value = true; convertError.value = ''
  try {
    await axios.post('/api/scrap-items/convert-product', convertForm)
    showConvert.value = false
    Object.assign(convertForm, { product_id: '', weight_g: 0, notes: '' })
    load()
  } catch (e) {
    convertError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { convertSaving.value = false }
}

function del(s) {
  confirmDelete.value  = s
  confirmMessage.value = `Delete scrap item ${s.sku}? This cannot be undone.`
}

async function doDelete() {
  await axios.delete(`/api/scrap-items/${confirmDelete.value.id}`)
  confirmDelete.value = null
  load()
}

async function load() {
  const { data } = await axios.get('/api/scrap-items', { params: { ...filters, page: page.value } })
  scraps.value = data
}

onMounted(async () => {
  const [p] = await Promise.all([axios.get('/api/products', { params: { per_page: 200 } })])
  products.value = p.data.data
  load()
})
</script>
