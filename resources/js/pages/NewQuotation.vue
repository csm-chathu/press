<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h2 class="text-lg font-semibold text-gray-800">New Quotation</h2>
      <div class="flex items-center gap-2">
        <!-- Load template -->
        <select v-if="templates.length" @change="loadTemplate($event.target.value)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none text-gray-600">
          <option value="">Load Template…</option>
          <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
        <!-- Save as template -->
        <button type="button" @click="saveAsTemplate" :disabled="savingTemplate"
          class="border border-gray-200 text-gray-600 hover:bg-gray-50 px-3 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50">
          {{ savingTemplate ? 'Saving…' : '+ Save as Template' }}
        </button>
        <router-link to="/quotations" class="text-sm text-gray-500 hover:text-gray-700">← Back</router-link>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Customer & Title -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="label">Job Title</label>
          <input v-model="form.title" required type="text" placeholder="e.g., Annual Report 2025 — 500 copies"
            class="input w-full" />
        </div>
        <div>
          <label class="label">Customer</label>
          <select v-model="form.customer_id" class="input w-full">
            <option value="">Walk-in / No customer</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} {{ c.phone ? '· ' + c.phone : '' }}</option>
          </select>
        </div>
        <div>
          <label class="label">Product Type</label>
          <select v-model="form.product_type" class="input w-full">
            <option value="">Select type</option>
            <option v-for="t in productTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
      </div>

      <!-- Print Specifications -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Print Specifications</h3>

        <!-- Paper from Stock -->
        <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
          <label class="label text-blue-700">Paper from Stock</label>
          <div class="flex gap-3 items-start flex-wrap">
            <div class="flex-1 min-w-48">
              <select v-model="selectedPaperProductId" @change="onPaperProductSelected" class="input w-full">
                <option value="">— Select paper from inventory —</option>
                <option v-for="p in paperProducts" :key="p.id" :value="p.id">
                  {{ p.name }} ({{ p.gsm ? p.gsm + 'gsm · ' : '' }}{{ p.paper_size || p.base_unit }})
                </option>
              </select>
            </div>

            <!-- Stock badge -->
            <div v-if="selectedPaper" class="flex gap-2 flex-wrap items-center pt-1">
              <span :class="selectedPaper.stock_quantity > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                class="text-xs font-semibold px-2 py-1 rounded-full">
                {{ selectedPaper.stock_quantity.toLocaleString() }} sheets in stock
              </span>
              <span v-if="selectedPaper.bundle_size" class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-1 rounded-full">
                {{ Math.floor(selectedPaper.stock_quantity / selectedPaper.bundle_size) }} bundles
                ({{ selectedPaper.bundle_size }} sheets each)
              </span>
              <span class="text-xs text-gray-500">Rs. {{ fmt(selectedPaper.purchase_price) }}/sheet</span>
            </div>

            <!-- Yield result -->
            <div v-if="yieldResult" class="w-full mt-2 space-y-2">
              <!-- Job requirement row -->
              <div class="p-3 bg-white rounded-lg border border-blue-200 text-xs text-gray-700 flex flex-wrap gap-4 items-center">
                <span class="font-semibold text-blue-700 text-sm">Job Requirement</span>
                <span>
                  <span class="text-gray-500">Layout:</span>
                  <strong class="text-blue-700 ml-1">{{ yieldResult.pieces_per_sheet }} up</strong>
                  ({{ yieldResult.cols }}×{{ yieldResult.rows }}, {{ yieldResult.orientation }})
                </span>
                <span>
                  <span class="text-gray-500">Net sheets:</span>
                  <strong class="ml-1">{{ yieldResult.sheets_net?.toLocaleString() }}</strong>
                </span>
                <span>
                  <span class="text-gray-500">+ wastage:</span>
                  <strong class="text-amber-700 ml-1">{{ yieldResult.sheets_with_wastage?.toLocaleString() }} sheets</strong>
                </span>
                <span>
                  <span class="text-gray-500">Efficiency:</span>
                  <strong class="ml-1">{{ yieldResult.efficiency_pct }}%</strong>
                </span>
                <span v-if="yieldResult.bundles_needed">
                  <span class="text-gray-500">Bundles needed:</span>
                  <strong class="ml-1">{{ yieldResult.bundles_needed }}</strong>
                </span>
                <span v-if="yieldResult.stock_sufficient === false" class="text-red-600 font-semibold">⚠ Insufficient stock!</span>
                <span v-else-if="yieldResult.stock_sufficient" class="text-green-600 font-semibold">✓ Stock sufficient</span>
                <button type="button" @click="applyYieldCost"
                  class="ml-auto bg-amber-500 hover:bg-amber-600 text-white text-xs px-3 py-1 rounded-lg font-semibold transition-colors">
                  Apply Paper Cost (Rs. {{ fmt(yieldPaperCost) }})
                </button>
              </div>

              <!-- Stock capacity row -->
              <div v-if="selectedPaper && yieldResult.pieces_per_sheet > 0"
                class="p-3 bg-green-50 rounded-lg border border-green-200 text-xs flex flex-wrap gap-6 items-center">
                <span class="font-semibold text-green-700 text-sm">From Current Stock</span>
                <span>
                  <span class="text-gray-500">Max pieces producible:</span>
                  <strong class="text-green-700 ml-1 text-sm">
                    {{ (selectedPaper.stock_quantity * yieldResult.pieces_per_sheet).toLocaleString() }} pcs
                  </strong>
                </span>
                <span>
                  <span class="text-gray-500">From</span>
                  <strong class="mx-1">{{ selectedPaper.stock_quantity.toLocaleString() }}</strong>
                  <span class="text-gray-500">sealed sheets ×</span>
                  <strong class="mx-1">{{ yieldResult.pieces_per_sheet }}</strong>
                  <span class="text-gray-500">per sheet</span>
                </span>
                <span v-if="selectedPaper.bundle_size">
                  <span class="text-gray-500">Per bundle ({{ selectedPaper.bundle_size }} sheets):</span>
                  <strong class="text-green-700 ml-1">{{ (selectedPaper.bundle_size * yieldResult.pieces_per_sheet).toLocaleString() }} pcs</strong>
                </span>
                <span>
                  <span class="text-gray-500">Sheets needed for {{ (form.quantity || 0).toLocaleString() }} pcs:</span>
                  <strong class="ml-1"
                    :class="yieldResult.stock_sufficient === false ? 'text-red-600' : 'text-gray-800'">
                    {{ yieldResult.sheets_with_wastage?.toLocaleString() }}
                    / {{ selectedPaper.stock_quantity.toLocaleString() }} available
                  </strong>
                </span>
              </div>
            </div>

            <div v-else-if="selectedPaper && (!form.width_mm || !form.height_mm || !form.quantity)"
              class="w-full text-xs text-blue-500 mt-1">
              Fill in Width, Height and Quantity below to auto-calculate paper cost.
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="label">Paper Type</label>
            <input v-model="form.paper_type" type="text" placeholder="e.g., Art Paper, Bond" class="input w-full" />
          </div>
          <div>
            <label class="label">GSM</label>
            <input v-model.number="form.gsm" type="number" placeholder="e.g., 170" class="input w-full" />
          </div>
          <div>
            <label class="label">Size</label>
            <input v-model="form.size" type="text" placeholder="e.g., A4, A5, 3.5x2in" class="input w-full" />
          </div>
          <div>
            <label class="label">Quantity</label>
            <input v-model.number="form.quantity" @change="runYield" type="number" placeholder="500" class="input w-full" />
          </div>
          <div>
            <label class="label">Color Count</label>
            <select v-model.number="form.color_count" class="input w-full">
              <option :value="1">1 Color (Black)</option>
              <option :value="2">2 Colors</option>
              <option :value="4">4 Colors (CMYK)</option>
            </select>
          </div>
          <div>
            <label class="label">Printing Method</label>
            <select v-model="form.printing_method" class="input w-full">
              <option value="">Select method</option>
              <option value="offset">Offset</option>
              <option value="digital">Digital</option>
              <option value="screen">Screen</option>
              <option value="flexo">Flexo</option>
              <option value="letterpress">Letterpress</option>
            </select>
          </div>
          <div>
            <label class="label">Width (mm)</label>
            <input v-model.number="form.width_mm" @change="runYield" type="number" step="0.1" placeholder="210" class="input w-full" />
          </div>
          <div>
            <label class="label">Height (mm)</label>
            <input v-model.number="form.height_mm" @change="runYield" type="number" step="0.1" placeholder="297" class="input w-full" />
          </div>
        </div>
      </div>

      <!-- Cost Breakdown -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Cost Breakdown</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div>
            <label class="label">Plate Cost (Rs.)</label>
            <input v-model.number="form.plate_cost" @input="calc" type="number" step="0.01" class="input w-full" />
          </div>
          <div>
            <label class="label">Paper Cost (Rs.)</label>
            <input v-model.number="form.paper_cost" @input="calc" type="number" step="0.01" class="input w-full" />
          </div>
          <div>
            <label class="label">Ink Cost (Rs.)</label>
            <input v-model.number="form.ink_cost" @input="calc" type="number" step="0.01" class="input w-full" />
          </div>
          <div>
            <label class="label">Finishing Cost (Rs.)</label>
            <input v-model.number="form.finishing_cost" @input="calc" type="number" step="0.01" class="input w-full" />
          </div>
          <div>
            <label class="label">Labour Cost (Rs.)</label>
            <input v-model.number="form.labour_cost" @input="calc" type="number" step="0.01" class="input w-full" />
          </div>
          <div>
            <label class="label">Wastage %</label>
            <input v-model.number="form.wastage_percent" @input="calc" type="number" step="0.1" min="0" max="100" class="input w-full" />
          </div>
          <div>
            <label class="label">Profit Margin %</label>
            <input v-model.number="form.profit_margin_percent" @input="calc" type="number" step="0.1" min="0" max="200" class="input w-full" />
          </div>
          <div>
            <label class="label">Tax Rate %</label>
            <input v-model.number="form.tax_rate" @input="calc" type="number" step="0.1" min="0" max="100" class="input w-full" />
          </div>
          <div>
            <label class="label">Valid Until</label>
            <input v-model="form.valid_until" type="date" class="input w-full" />
          </div>
        </div>

        <!-- Calculated totals -->
        <div class="mt-5 bg-amber-50 rounded-lg p-4 grid grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-gray-500">Base Cost</p>
            <p class="font-semibold text-gray-800">Rs. {{ fmt(baseCost) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">With Wastage & Profit</p>
            <p class="font-semibold text-gray-800">Rs. {{ fmt(subtotal) }}</p>
          </div>
          <div>
            <p class="text-xs text-amber-700 font-semibold">Final Total (incl. tax)</p>
            <p class="text-xl font-bold text-amber-700">Rs. {{ fmt(total) }}</p>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label">Notes</label>
          <textarea v-model="form.notes" rows="3" class="input w-full" placeholder="Special instructions, remarks…"></textarea>
        </div>
        <div>
          <label class="label">Terms & Conditions</label>
          <textarea v-model="form.terms" rows="3" class="input w-full" placeholder="Payment terms, delivery…"></textarea>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 justify-end">
        <router-link to="/quotations" class="btn-secondary">Cancel</router-link>
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Saving…' : 'Save Quotation' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const saving = ref(false)
const customers = ref([])
const templates = ref([])
const savingTemplate = ref(false)
const paperProducts = ref([])
const selectedPaperProductId = ref('')
const selectedPaper = computed(() => paperProducts.value.find(p => p.id == selectedPaperProductId.value) || null)
const yieldResult = ref(null)
const yieldPaperCost = computed(() => {
  if (!yieldResult.value || !selectedPaper.value) return 0
  return Math.round(yieldResult.value.sheets_with_wastage * (selectedPaper.value.purchase_price || 0) * 100) / 100
})

const COST_FIELDS = ['plate_cost','paper_cost','ink_cost','finishing_cost','labour_cost','wastage_percent','profit_margin_percent','tax_rate']

const productTypes = [
  { value: 'business_cards',  label: 'Business Cards' },
  { value: 'brochures',       label: 'Brochures / Leaflets' },
  { value: 'flyers',          label: 'Flyers' },
  { value: 'banners',         label: 'Banners / Flex' },
  { value: 'booklets',        label: 'Booklets / Catalogues' },
  { value: 'annual_report',   label: 'Annual Report' },
  { value: 'letterheads',     label: 'Letterheads / Stationery' },
  { value: 'packaging',       label: 'Packaging / Boxes' },
  { value: 'labels',          label: 'Labels / Stickers' },
  { value: 'posters',         label: 'Posters' },
  { value: 'calendars',       label: 'Calendars' },
  { value: 'other',           label: 'Other' },
]

const form = ref({
  customer_id: '',
  title: '',
  product_type: '',
  paper_type: '',
  paper_product_id: null,
  gsm: null,
  size: '',
  width_mm: null,
  height_mm: null,
  quantity: null,
  color_count: 4,
  printing_method: '',
  plate_cost: 0,
  paper_cost: 0,
  ink_cost: 0,
  finishing_cost: 0,
  labour_cost: 0,
  wastage_percent: 5,
  profit_margin_percent: 20,
  tax_rate: 0,
  valid_until: '',
  notes: '',
  terms: 'Payment: 50% advance, 50% on delivery.\nDelivery: 7-10 working days.\nQuotation valid for 30 days.',
})

const baseCost = computed(() => {
  return (form.value.plate_cost || 0)
    + (form.value.paper_cost || 0)
    + (form.value.ink_cost || 0)
    + (form.value.finishing_cost || 0)
    + (form.value.labour_cost || 0)
})
const subtotal = computed(() => {
  const wastage = baseCost.value * ((form.value.wastage_percent || 0) / 100)
  const withWastage = baseCost.value + wastage
  const profit = withWastage * ((form.value.profit_margin_percent || 0) / 100)
  return withWastage + profit
})
const total = computed(() => subtotal.value * (1 + (form.value.tax_rate || 0) / 100))

function calc() {}

function onPaperProductSelected() {
  yieldResult.value = null
  const p = selectedPaper.value
  if (!p) return
  // Auto-fill spec fields from selected product
  if (p.name)       form.value.paper_type = p.name
  if (p.gsm)        form.value.gsm        = p.gsm
  if (p.paper_size) form.value.size       = p.paper_size
  form.value.paper_product_id = p.id
  runYield()
}

async function runYield() {
  const p = selectedPaper.value
  if (!p || !form.value.width_mm || !form.value.height_mm || !form.value.quantity) {
    yieldResult.value = null
    return
  }
  try {
    const { data } = await axios.post('/api/tools/paper-yield', {
      paper_product_id: p.id,
      job_w_mm:         form.value.width_mm,
      job_h_mm:         form.value.height_mm,
      quantity:         form.value.quantity,
      wastage_percent:  form.value.wastage_percent || 0,
      bleed_mm:         3,
    })
    yieldResult.value = data
  } catch { yieldResult.value = null }
}

function applyYieldCost() {
  form.value.paper_cost = yieldPaperCost.value
  calc()
}

function loadTemplate(id) {
  if (!id) return
  const t = templates.value.find(x => x.id == id)
  if (!t) return
  COST_FIELDS.forEach(k => { if (t[k] !== undefined && t[k] !== null) form.value[k] = t[k] })
}

async function saveAsTemplate() {
  const name = prompt('Template name:')
  if (!name?.trim()) return
  savingTemplate.value = true
  try {
    const payload = { name: name.trim() }
    COST_FIELDS.forEach(k => { payload[k] = form.value[k] || 0 })
    const { data } = await axios.post('/api/quotation-templates', payload)
    templates.value.push(data)
    alert('Template saved!')
  } catch (e) {
    alert(e.response?.data?.message ?? 'Save failed')
  } finally {
    savingTemplate.value = false
  }
}

function fmt(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }

async function submit() {
  saving.value = true
  try {
    const { data } = await axios.post('/api/quotations', form.value)
    router.push(`/quotations/${data.id}`)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Save failed')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const [custRes, tmplRes, paperRes] = await Promise.all([
    axios.get('/api/customers/all'),
    axios.get('/api/quotation-templates'),
    axios.get('/api/products?product_type=product&per_page=200'),
  ])
  customers.value     = custRes.data
  templates.value     = tmplRes.data
  // Filter to paper/board products only
  paperProducts.value = (paperRes.data.data || []).filter(p =>
    p.material_type === 'paper' || (p.category?.name || '').toLowerCase().includes('paper')
  )
})
</script>

<style scoped>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input { @apply border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none; }
.btn-primary { @apply bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50; }
.btn-secondary { @apply border border-gray-200 text-gray-600 hover:bg-gray-50 px-5 py-2 rounded-lg text-sm font-semibold transition-colors; }
</style>
