<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Gold Buy-Back</h2>
        <p class="text-sm text-gray-500 mt-0.5">Purchase gold from customers — record assay results, KYC, and pricing</p>
      </div>
      <button @click="openModal(null)" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Buy-Back
      </button>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap">
      <input v-model="filters.search" placeholder="Customer name or BB number…" class="form-input w-56" @input="load" />
      <select v-model="filters.status" class="form-input w-36" @change="load">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="completed">Completed</option>
        <option value="rejected">Rejected</option>
      </select>
      <button @click="filters.search=''; filters.status=''; load()" class="btn-secondary text-sm">Clear</button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">BB #</th>
            <th class="table-th">Customer</th>
            <th class="table-th">Item</th>
            <th class="table-th">Net Weight</th>
            <th class="table-th">Assay</th>
            <th class="table-th">Final Price</th>
            <th class="table-th">KYC</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="b in buybacks.data" :key="b.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs text-gray-500">{{ b.buyback_number }}</td>
            <td class="table-td">
              <p class="font-medium text-sm">{{ b.customer?.name }}</p>
              <p class="text-xs text-gray-400">{{ b.customer?.phone }}</p>
            </td>
            <td class="table-td text-sm">
              <p>{{ b.description }}</p>
              <span class="badge bg-gray-100 text-gray-600 text-xs capitalize">{{ b.item_type }}</span>
            </td>
            <td class="table-td text-sm font-mono">{{ b.net_weight }}g <span class="text-gold-600 text-xs">({{ b.declared_karat }})</span></td>
            <td class="table-td text-sm">
              <span v-if="b.assay_method" class="badge bg-purple-100 text-purple-700 text-xs uppercase">{{ b.assay_method }}</span>
              <span v-if="b.assay_karat" class="block text-xs text-gray-500 mt-0.5">{{ b.assay_karat }}K tested</span>
              <span v-if="!b.assay_method" class="text-gray-300 text-xs">—</span>
            </td>
            <td class="table-td font-semibold text-gold-700">LKR {{ lkr(b.final_price) }}</td>
            <td class="table-td">
              <span v-if="b.kyc_verified" class="badge bg-green-100 text-green-700 text-xs">✓ KYC</span>
              <span v-else class="badge bg-yellow-100 text-yellow-700 text-xs">Pending</span>
            </td>
            <td class="table-td">
              <span :class="statusClass(b.status)" class="badge text-xs capitalize">{{ b.status }}</span>
            </td>
            <td class="table-td">
              <div class="flex gap-1.5">
                <button @click="openModal(b)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                </button>
                <button @click="del(b)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!buybacks.data?.length">
            <td colspan="9" class="table-td text-center text-gray-400 py-10">No buy-back records found</td>
          </tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ buybacks.total ?? 0 }} records</span>
        <div class="flex gap-2">
          <button @click="page--; load()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">← Prev</button>
          <button @click="page++; load()" :disabled="page>=buybacks.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
            <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit Buy-Back' : 'New Buy-Back' }}</h3>
            <button @click="showModal=false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-6 space-y-6">

            <!-- Customer & item -->
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="form-label">Customer *</label>
                <select v-model="form.customer_id" class="form-input" required>
                  <option value="">Select customer…</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">
                    {{ c.name }} {{ c.phone ? `· ${c.phone}` : '' }}
                    {{ c.kyc_verified ? '✓ KYC' : '' }}
                  </option>
                </select>
                <p v-if="selectedCustomer && !selectedCustomer.kyc_verified" class="text-xs text-yellow-600 mt-1">
                  ⚠ This customer has not completed KYC verification.
                </p>
              </div>
              <div>
                <label class="form-label">Description *</label>
                <input v-model="form.description" class="form-input" placeholder="e.g. 22K Gold Necklace" />
              </div>
              <div>
                <label class="form-label">Item Type *</label>
                <select v-model="form.item_type" class="form-input">
                  <option value="jewelry">Jewelry</option>
                  <option value="coin">Coin / Medallion</option>
                  <option value="bar">Bar / Ingot</option>
                  <option value="scrap">Scrap Gold</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>

            <!-- Weight -->
            <div class="border rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Weight Details</p>
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="form-label">Gross Weight (g) *</label>
                  <input v-model.number="form.gross_weight" type="number" min="0" step="0.001" class="form-input" @input="calcNet" />
                </div>
                <div>
                  <label class="form-label">Deductions (g)</label>
                  <input v-model.number="form.deduction_weight" type="number" min="0" step="0.001" class="form-input" @input="calcNet" placeholder="Stones, solder…" />
                </div>
                <div>
                  <label class="form-label">Net Gold Weight (g)</label>
                  <input v-model.number="form.net_weight" type="number" min="0" step="0.001" class="form-input bg-gold-50" />
                </div>
              </div>
              <div>
                <label class="form-label">Declared Karat *</label>
                <select v-model="form.declared_karat" class="form-input w-40">
                  <option value="unknown">Unknown</option>
                  <option value="9k">9K</option>
                  <option value="14k">14K</option>
                  <option value="18k">18K</option>
                  <option value="22k">22K</option>
                  <option value="24k">24K</option>
                </select>
              </div>
            </div>

            <!-- Assay / Testing -->
            <div class="border rounded-xl p-4 space-y-3 bg-purple-50/30">
              <p class="text-sm font-semibold text-gray-700">Assay & Testing Results</p>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Test Method</label>
                  <select v-model="form.assay_method" class="form-input">
                    <option value="">Not tested</option>
                    <option value="visual">Visual Inspection</option>
                    <option value="acid">Acid Test</option>
                    <option value="xrf">XRF Spectrometry</option>
                    <option value="fire_assay">Fire Assay</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Tested Karat (actual)</label>
                  <input v-model.number="form.assay_karat" type="number" min="0" max="24" step="0.1" class="form-input" placeholder="e.g. 21.6" />
                </div>
                <div v-if="form.assay_method === 'xrf'">
                  <label class="form-label">XRF Reading (0–1)</label>
                  <input v-model.number="form.xrf_reading" type="number" min="0" max="1" step="0.0001" class="form-input" placeholder="e.g. 0.9167" />
                </div>
                <div>
                  <label class="form-label">Est. Melt Loss (%)</label>
                  <input v-model.number="form.melt_loss_percent" type="number" min="0" max="100" step="0.1" class="form-input" placeholder="e.g. 2.5" />
                </div>
              </div>
              <div>
                <label class="form-label">Assay Notes</label>
                <textarea v-model="form.assay_notes" rows="2" class="form-input" placeholder="Acid test: reacted to 18K solution. Stone weight estimated 1.2g…"></textarea>
              </div>
            </div>

            <!-- Pricing -->
            <div class="border rounded-xl p-4 space-y-3 bg-gold-50/40">
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700">Pricing</p>
                <span v-if="goldRate" class="text-xs text-gold-600">Market rate: LKR {{ lkr(goldRate.rate_per_gram) }}/g (24K)</span>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Rate/g Used (LKR)</label>
                  <input v-model.number="form.rate_per_gram" type="number" min="0" step="0.01" class="form-input" @input="calcPrice" />
                </div>
                <div>
                  <label class="form-label">Buying Price/g (LKR)</label>
                  <input v-model.number="form.buying_price_per_gram" type="number" min="0" step="0.01" class="form-input" @input="calcPrice" />
                </div>
                <div>
                  <label class="form-label">Offered Total (LKR)</label>
                  <input v-model.number="form.offered_total" type="number" min="0" step="0.01" class="form-input bg-gray-50" readonly />
                </div>
                <div>
                  <label class="form-label">Final Price (LKR) *</label>
                  <input v-model.number="form.final_price" type="number" min="0" step="0.01" class="form-input font-semibold" />
                </div>
                <div>
                  <label class="form-label">Payment Method</label>
                  <select v-model="form.payment_method" class="form-input">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Compliance & Status -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Status *</label>
                <select v-model="form.status" class="form-input">
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="completed">Completed</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
              <div class="flex flex-col justify-end gap-3">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="checkbox" v-model="form.kyc_verified" class="rounded text-gold-600" />
                  <span>KYC Verified <span class="text-xs text-gray-400">(ID checked)</span></span>
                </label>
                <label v-if="!editing" class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="checkbox" v-model="form.create_scrap" class="rounded text-gold-600" />
                  <span>Auto-create Scrap Item</span>
                </label>
              </div>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" rows="2" class="form-input"></textarea>
            </div>

            <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 sticky bottom-0">
            <button @click="showModal=false" class="btn-secondary">Cancel</button>
            <button @click="save" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
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
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/components/ConfirmModal.vue'

const buybacks       = ref({ data: [], total: 0, last_page: 1 })
const customers      = ref([])
const goldRate       = ref(null)
const showModal      = ref(false)
const editing        = ref(null)
const confirmDelete  = ref(null)
const confirmMessage = ref('')
const saving    = ref(false)
const formError = ref('')
const page      = ref(1)
const filters   = reactive({ search: '', status: '' })

const KARAT_PURITY = { '9k': 9/24, '14k': 14/24, '18k': 18/24, '22k': 22/24, '24k': 24/24 }

const blankForm = () => ({
  customer_id: '', description: '', item_type: 'jewelry',
  gross_weight: 0, deduction_weight: 0, net_weight: 0, declared_karat: 'unknown',
  assay_method: '', assay_karat: null, xrf_reading: null, melt_loss_percent: null, assay_notes: '',
  rate_per_gram: 0, buying_price_per_gram: 0, offered_total: 0, final_price: 0,
  payment_method: 'cash', kyc_verified: false, status: 'pending', notes: '', create_scrap: true,
})
const form = reactive(blankForm())

const selectedCustomer = computed(() => customers.value.find(c => c.id == form.customer_id))

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusClass(s) {
  return { pending: 'bg-yellow-100 text-yellow-700', approved: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' }[s] ?? 'bg-gray-100 text-gray-600'
}

function calcNet() {
  form.net_weight = Math.max(0, (form.gross_weight || 0) - (form.deduction_weight || 0))
  calcPrice()
}

function calcPrice() {
  const purity = KARAT_PURITY[form.declared_karat] ?? 1
  form.offered_total = Math.round(form.buying_price_per_gram * form.net_weight * purity * 100) / 100
  if (!editing.value) form.final_price = form.offered_total
}

function openModal(b) {
  editing.value = b
  formError.value = ''
  if (b) {
    Object.assign(form, { ...blankForm(), ...b, create_scrap: false })
  } else {
    Object.assign(form, blankForm())
    if (goldRate.value) {
      form.rate_per_gram         = goldRate.value.rate_per_gram
      form.buying_price_per_gram = Math.round(goldRate.value.rate_per_gram * 0.85 * 100) / 100 // 85% default offer
    }
  }
  showModal.value = true
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/gold-buybacks/${editing.value.id}`, form)
    } else {
      await axios.post('/api/gold-buybacks', form)
    }
    showModal.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { saving.value = false }
}

function del(b) {
  confirmDelete.value  = b
  confirmMessage.value = `Delete buy-back ${b.buyback_number}? This cannot be undone.`
}

async function doDelete() {
  await axios.delete(`/api/gold-buybacks/${confirmDelete.value.id}`)
  confirmDelete.value = null
  load()
}

async function load() {
  const { data } = await axios.get('/api/gold-buybacks', { params: { ...filters, page: page.value } })
  buybacks.value = data
}

onMounted(async () => {
  const [c, gr] = await Promise.all([
    axios.get('/api/customers/all'),
    axios.get('/api/gold-rates/today').catch(() => ({ data: null })),
  ])
  customers.value = c.data
  goldRate.value  = gr.data
  load()
})
</script>
