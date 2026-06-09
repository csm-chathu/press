<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Price Matrix</h2>
        <p class="text-sm text-gray-500">Bulk-manage buy/sell prices for menu items, liquor SKUs, and package variants.</p>
      </div>
      <button class="btn-primary" :disabled="saving || !dirtyRows.length" @click="saveAll">
        {{ saving ? 'Saving...' : `Save ${dirtyRows.length} Changes` }}
      </button>
    </div>

    <div class="card">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <input v-model="search" @input="reload" class="form-input" placeholder="Search name / SKU / barcode" />
        <select v-model="typeFilter" @change="reload" class="form-input">
          <option value="">All product types</option>
          <option v-for="type in productTypes" :key="type" :value="type">{{ type }}</option>
        </select>
        <select v-model="categoryFilter" @change="reload" class="form-input">
          <option value="">All categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="stockFilter" @change="reload" class="form-input">
          <option value="">All stock</option>
          <option value="in">In stock</option>
          <option value="low">Low stock</option>
          <option value="out">Out of stock</option>
        </select>
        <button class="btn-secondary" @click="resetFilters">Clear</button>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">SKU</th>
              <th class="table-th">Item</th>
              <th class="table-th">Type</th>
              <th class="table-th">Variant</th>
              <th class="table-th">Stock</th>
              <th class="table-th">Cost (LKR)</th>
              <th class="table-th">Sell (LKR)</th>
              <th class="table-th">Margin</th>
              <th class="table-th">Deposit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in editableRows" :key="row.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs">{{ row.sku }}</td>
              <td class="table-td">
                <p class="font-medium">{{ row.name }}</p>
                <p class="text-xs text-gray-400">{{ row.barcode || '-' }}</p>
              </td>
              <td class="table-td">{{ row.product_type || '-' }}</td>
              <td class="table-td text-gray-500">{{ [row.unit_type, row.base_unit].filter(Boolean).join(' / ') || '-' }}</td>
              <td class="table-td">
                <span :class="stockBadge(row)" class="badge">{{ row.stock_quantity }}</span>
              </td>
              <td class="table-td">
                <input v-model.number="row.purchase_price" type="number" min="0" step="0.01" class="form-input text-right" @input="markDirty(row)" />
              </td>
              <td class="table-td">
                <input v-model.number="row.selling_price" type="number" min="0" step="0.01" class="form-input text-right" @input="markDirty(row)" />
              </td>
              <td class="table-td font-semibold" :class="row.margin >= 0 ? 'text-green-700' : 'text-red-700'">
                {{ row.margin >= 0 ? '+' : '' }}{{ row.margin.toFixed(1) }}%
              </td>
              <td class="table-td">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" v-model="row.bottle_deposit_required" @change="markDirty(row)" class="rounded text-gold-600" />
                  Required
                </label>
              </td>
            </tr>
            <tr v-if="!editableRows.length">
              <td colspan="9" class="table-td text-center text-gray-400 py-10">No items found</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-600">
        <span>{{ products.from || 0 }}-{{ products.to || 0 }} of {{ products.total || 0 }}</span>
        <div class="flex gap-2">
          <button @click="prev" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="next" :disabled="page >= (products.last_page || 1)" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const products = ref({ data: [], total: 0, from: 0, to: 0, last_page: 1 })
const editableRows = ref([])
const categories = ref([])
const productTypes = ['Liquor', 'Beer', 'Soft Drinks', 'Food', 'Accessories']

const page = ref(1)
const search = ref('')
const typeFilter = ref('')
const categoryFilter = ref('')
const stockFilter = ref('')
const saving = ref(false)
const error = ref('')

const originalRows = ref(new Map())
const dirtyIds = ref(new Set())

const dirtyRows = computed(() => editableRows.value.filter(r => dirtyIds.value.has(r.id)))

function stockBadge(row) {
  if (row.stock_quantity <= 0) return 'bg-red-100 text-red-700'
  if (row.stock_quantity <= row.min_stock_level) return 'bg-yellow-100 text-yellow-700'
  return 'bg-green-100 text-green-700'
}

function withMargin(row) {
  const cost = Number(row.purchase_price || 0)
  const sell = Number(row.selling_price || 0)
  const margin = cost > 0 ? ((sell - cost) / cost) * 100 : 0
  return { ...row, margin }
}

function markDirty(row) {
  const original = originalRows.value.get(row.id)
  if (!original) return

  const changed =
    Number(row.purchase_price || 0) !== Number(original.purchase_price || 0) ||
    Number(row.selling_price || 0) !== Number(original.selling_price || 0) ||
    Boolean(row.bottle_deposit_required) !== Boolean(original.bottle_deposit_required)

  if (changed) dirtyIds.value.add(row.id)
  else dirtyIds.value.delete(row.id)

  row.margin = withMargin(row).margin
}

function resetFilters() {
  search.value = ''
  typeFilter.value = ''
  categoryFilter.value = ''
  stockFilter.value = ''
  page.value = 1
  reload()
}

async function reload() {
  error.value = ''
  const params = {
    page: page.value,
    per_page: 25,
    search: search.value,
    product_type: typeFilter.value,
    category_id: categoryFilter.value,
  }
  if (stockFilter.value === 'low') params.low_stock = 1
  if (stockFilter.value === 'out') params.out_of_stock = 1

  const { data } = await axios.get('/api/products', { params })
  let rows = data.data || []

  if (stockFilter.value === 'in') {
    rows = rows.filter(r => Number(r.stock_quantity || 0) > Number(r.min_stock_level || 0))
  }
  if (stockFilter.value === 'out') {
    rows = rows.filter(r => Number(r.stock_quantity || 0) <= 0)
  }

  products.value = data
  editableRows.value = rows.map(withMargin)
  originalRows.value = new Map(rows.map(r => [r.id, { ...r }]))
  dirtyIds.value = new Set()
}

async function saveAll() {
  if (!dirtyRows.value.length) return
  saving.value = true
  error.value = ''
  try {
    await Promise.all(dirtyRows.value.map(row => axios.put(`/api/products/${row.id}`, {
      name: row.name,
      description: row.description,
      category_id: row.category_id,
      product_type: row.product_type,
      brand: row.brand,
      unit_type: row.unit_type,
      base_unit: row.base_unit,
      selling_variants: row.selling_variants,
      purchase_price: row.purchase_price,
      selling_price: row.selling_price,
      stock_quantity: row.stock_quantity,
      min_stock_level: row.min_stock_level,
      is_active: row.is_active,
      bottle_deposit_required: row.bottle_deposit_required,
      supplier_id: row.supplier_id,
      tax_setting_id: row.tax_setting_id,
      barcode: row.barcode,
    })))

    await reload()
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Failed to update price matrix.'
  } finally {
    saving.value = false
  }
}

function prev() {
  if (page.value <= 1) return
  page.value -= 1
  reload()
}

function next() {
  if (page.value >= (products.value.last_page || 1)) return
  page.value += 1
  reload()
}

onMounted(async () => {
  const [categoryRes] = await Promise.all([
    axios.get('/api/categories/all'),
    reload(),
  ])
  categories.value = categoryRes.data
})
</script>
