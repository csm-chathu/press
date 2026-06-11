<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <input ref="searchInput" v-model="search" type="search" placeholder="Search products…" class="form-input w-64" @input="debouncedFetch" />
        <select v-model="categoryFilter" class="form-input w-44" @change="resetAndFetch">
          <option value="">All categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="materialFilter" class="form-input w-36" @change="resetAndFetch">
          <option value="">All materials</option>
          <option value="paper">Paper / Board</option>
          <option value="ink">Ink</option>
          <option value="plate">Plate / Film</option>
          <option value="chemical">Chemical</option>
          <option value="packaging">Packaging</option>
          <option value="other">Other</option>
        </select>
        <label class="flex items-center gap-1 text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" v-model="lowStockOnly" @change="resetAndFetch" class="rounded text-gold-600" />
          Low stock only
        </label>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Product
      </button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <!-- Loading skeleton -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-400">
        <svg class="w-8 h-8 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <span class="text-sm">Loading products…</span>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th">Image</th>
              <th class="table-th">SKU</th>
              <th class="table-th">Name</th>
              <th class="table-th">Material</th>
              <th class="table-th">Category</th>
              <th class="table-th">GSM / Size</th>
              <th class="table-th">Unit</th>
              <th class="table-th">Stock</th>
              <th class="table-th">Bundle</th>
              <th class="table-th">Sell Price (LKR)</th>
              <th class="table-th">Status</th>
              <th class="table-th sticky right-0 bg-gray-50 border-l border-gray-200">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50">
              <td class="table-td">
                <button v-if="p.image" @click="zoomedImage = p.image" class="block w-10 h-10 rounded-md overflow-hidden border border-gray-200 hover:ring-2 hover:ring-amber-400 transition-all focus:outline-none" title="Click to zoom">
                  <img :src="p.image" alt="Product" class="w-full h-full object-cover" />
                </button>
                <div v-else class="w-10 h-10 rounded-md bg-gray-100 border border-gray-200"></div>
              </td>
              <td class="table-td font-mono text-xs">{{ p.sku }}</td>
              <td class="table-td font-medium">{{ p.name }}</td>
              <td class="table-td">
                <span :class="materialBadge(p.material_type).cls" class="badge capitalize text-xs">
                  {{ materialBadge(p.material_type).label }}
                </span>
              </td>
              <td class="table-td text-gray-500">{{ p.category?.name }}</td>
              <td class="table-td text-gray-600 text-xs">
                <span v-if="p.gsm || p.paper_size">{{ [p.gsm ? p.gsm+'gsm' : '', p.paper_size].filter(Boolean).join(' · ') }}</span>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="table-td text-gray-500 text-xs">{{ p.base_unit || '—' }}</td>
              <td class="table-td">
                <span :class="p.stock_quantity <= p.min_stock_level ? 'badge bg-red-100 text-red-700' : 'badge bg-green-100 text-green-700'">
                  {{ p.stock_quantity }}
                </span>
              </td>
              <td class="table-td text-xs text-amber-700">
                <span v-if="p.bundle_size">{{ p.bundle_size }}/bndl</span>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="table-td font-semibold text-gold-700">{{ Number(p.selling_price).toLocaleString() }}</td>
              <td class="table-td">
                <span :class="p.is_active ? 'badge bg-green-100 text-green-700' : 'badge bg-gray-100 text-gray-500'">
                  {{ p.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="table-td sticky right-0 bg-white border-l border-gray-200">
                <div class="flex items-center gap-2 whitespace-nowrap">
                  <button @click="reprintBarcode(p)" :disabled="printingId === p.id"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 disabled:opacity-60">
                    <svg v-if="printingId === p.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round">
                      <rect x="1" y="4" width="1.5" height="16" rx="0.5"/><rect x="4" y="4" width="3" height="16" rx="0.5"/>
                      <rect x="9" y="4" width="1.5" height="16" rx="0.5"/><rect x="12" y="4" width="3" height="16" rx="0.5"/>
                      <rect x="17" y="4" width="1.5" height="16" rx="0.5"/><rect x="20" y="4" width="2.5" height="16" rx="0.5"/>
                    </svg>
                    {{ printingId === p.id ? 'Printing…' : 'Print' }}
                  </button>
                  <button @click="openEdit(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </button>
                  <button @click="deleteProduct(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!products.data?.length">
              <td colspan="12" class="table-td text-center text-gray-400 py-8">No products found</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div v-if="!loading" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
        <span>{{ products.from }}–{{ products.to }} of {{ products.total }}</span>
        <div class="flex gap-2">
          <button @click="page--; fetchProducts()" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetchProducts()" :disabled="page >= products.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <ProductModal v-if="showModal" :product="editing" :categories="categories" :suppliers="suppliers"
      :taxes="taxes"
      @close="showModal = false" @saved="onSaved" />

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />

    <!-- Image zoom lightbox -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="zoomedImage"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
          @click.self="zoomedImage = null"
          @keydown.esc="zoomedImage = null"
          tabindex="-1"
        >
          <div class="relative max-w-2xl w-full">
            <button
              @click="zoomedImage = null"
              class="absolute -top-3 -right-3 z-10 w-8 h-8 rounded-full bg-white text-gray-700 hover:text-red-600 shadow-lg flex items-center justify-center text-lg font-bold leading-none transition-colors"
            >✕</button>
            <img :src="zoomedImage" alt="Product zoom" class="w-full max-h-[80vh] object-contain rounded-xl shadow-2xl" />
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import JsBarcode from 'jsbarcode'
import ProductModal from '@/components/ProductModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

const MATERIAL_BADGES = {
  paper:     { label: 'Paper',     cls: 'bg-blue-100 text-blue-700' },
  ink:       { label: 'Ink',       cls: 'bg-purple-100 text-purple-700' },
  plate:     { label: 'Plate',     cls: 'bg-gray-200 text-gray-700' },
  chemical:  { label: 'Chemical',  cls: 'bg-yellow-100 text-yellow-700' },
  packaging: { label: 'Packaging', cls: 'bg-orange-100 text-orange-700' },
  other:     { label: 'Other',     cls: 'bg-gray-100 text-gray-500' },
}
function materialBadge(type) { return MATERIAL_BADGES[type] ?? MATERIAL_BADGES.other }

const products      = ref({ data: [] })
const categories    = ref([])
const suppliers     = ref([])
const taxes         = ref([])
const search        = ref('')
const categoryFilter = ref('')
const materialFilter = ref('')
const lowStockOnly  = ref(false)
const page          = ref(1)
const showModal     = ref(false)
const editing       = ref(null)
const loading       = ref(false)
const zoomedImage   = ref(null)
const printingId     = ref(null)
const searchInput    = ref(null)
const confirmDelete  = ref(null)
const confirmMessage = ref('')

function resetAndFetch() { page.value = 1; fetchProducts() }

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(resetAndFetch, 400)
}

async function fetchProducts() {
  loading.value = true
  try {
    const params = { page: page.value, search: search.value, category_id: categoryFilter.value, material_type: materialFilter.value }
    if (lowStockOnly.value) params.low_stock = 1
    const { data } = await axios.get('/api/products', { params })
    products.value = data
  } finally {
    loading.value = false
  }
}

async function fetchRefs() {
  const [c, s, t] = await Promise.all([axios.get('/api/categories/all'), axios.get('/api/suppliers/all'), axios.get('/api/tax-settings')])
  categories.value = c.data
  suppliers.value  = s.data
  taxes.value      = t.data.data ?? t.data
}

function openCreate() { editing.value = null; showModal.value = true }
function openEdit(p)   { editing.value = p;    showModal.value = true }

function createBarcodeSvg(value) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
  JsBarcode(svg, value, {
    format: 'CODE128',
    width: 0.8,
    height: 18,
    margin: 1,
    displayValue: false,
  })
  return svg.outerHTML
}

function buildBarcodeLabelHtml(product) {
  const barcodeValue = product.barcode?.trim() || product.sku
  const barcodeSvg   = createBarcodeSvg(barcodeValue)
  const safeName     = (product.name ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeBarcode  = barcodeValue.replace(/</g, '&lt;').replace(/>/g, '&gt;')

  return `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { size: 30mm 14mm; margin: 0; }
    * { box-sizing: border-box; margin:0; padding:0; }
    body { font-family:'Courier New',monospace; background:#fff; }
    .label { width:30mm; height:14mm; overflow:hidden; text-align:center; padding-top:2pt; }
    .name  { font-size:6pt; font-weight:900; line-height:1.2; word-break:break-word; }
    svg    { width:100%; display:block; margin-top:1pt; }
    .sku   { font-size:6pt; font-weight:700; letter-spacing:0.5px; margin-top:0; }
  </style>
</head>
<body>
  <div class="label">
    <div class="name">${safeName}</div>
    ${barcodeSvg}
    <div class="sku">SKU: ${safeBarcode}</div>
  </div>
</body>
</html>`
}

function printProductBarcode(product) {
  if (!product?.sku) return

  const html = buildBarcodeLabelHtml(product)

  // Electron: silent print directly to barcode printer via IPC
  if (window.electronAPI?.printBarcode) {
    window.electronAPI.printBarcode(html)
    return
  }

  // Browser fallback: popup with auto-print
  const popup = window.open('', '_blank', 'width=150,height=120')
  if (!popup) {
    alert('Popup blocked. Allow popups to print barcode labels.')
    return
  }
  popup.document.write(html.replace('</body>', '<script>window.onload=function(){window.print()}<\/script></body>'))
  popup.document.close()
}

function reprintBarcode(product) {
  printingId.value = product.id
  printProductBarcode(product)
  setTimeout(() => { printingId.value = null }, 3000)
}

function deleteProduct(p) {
  confirmDelete.value  = p
  confirmMessage.value = `Delete "${p.name}"? This cannot be undone.`
}

async function doDelete() {
  const p = confirmDelete.value
  confirmDelete.value = null
  await axios.delete(`/api/products/${p.id}`)
  await fetchProducts()
}

async function onSaved(payload) {
  showModal.value = false
  await fetchProducts()
}

onMounted(() => { fetchProducts(); fetchRefs() })
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.18s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
