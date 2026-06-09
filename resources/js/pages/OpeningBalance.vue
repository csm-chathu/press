<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Opening Balances</h2>
        <p class="text-sm text-gray-500 mt-1">Set initial stock quantities and account balances before going live. Run this once when starting fresh.</p>
      </div>
      <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold">
        ⚡ One-time setup
      </span>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit">
      <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
        class="px-5 py-2 rounded-lg text-sm font-semibold transition-colors"
        :class="activeTab === tab.id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
        {{ tab.label }}
      </button>
    </div>

    <!-- ── STOCK TAB ── -->
    <div v-if="activeTab === 'stock'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex gap-2">
          <input v-model="stockSearch" type="search" placeholder="Search products…" class="form-input w-56" />
          <select v-model="stockCategoryFilter" class="form-input w-44">
            <option value="">All categories</option>
            <option v-for="c in stockCategories" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-400">{{ filteredStock.length }} products</span>
          <button @click="saveStock" :disabled="savingStock"
            class="btn-primary flex items-center gap-2 disabled:opacity-50">
            <span v-if="savingStock">Saving…</span>
            <span v-else>Save Stock Balances</span>
          </button>
        </div>
      </div>

      <div v-if="loadingStock" class="flex items-center justify-center py-16 text-gray-400 gap-2">
        <svg class="w-5 h-5 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        Loading products…
      </div>

      <div v-else class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th">Product</th>
              <th class="table-th">SKU</th>
              <th class="table-th">Category</th>
              <th class="table-th">Unit</th>
              <th class="table-th text-right">Current Stock</th>
              <th class="table-th text-right">Opening Qty</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in filteredStock" :key="item.id" class="hover:bg-gray-50">
              <td class="table-td font-medium text-gray-800">{{ item.name }}</td>
              <td class="table-td font-mono text-xs text-gray-500">{{ item.sku }}</td>
              <td class="table-td text-gray-500 text-xs">{{ item.category?.name ?? '—' }}</td>
              <td class="table-td text-gray-500 text-xs">{{ item.unit_type ?? '—' }}</td>
              <td class="table-td text-right">
                <span :class="item.stock_quantity > 0 ? 'badge bg-green-100 text-green-700' : 'badge bg-gray-100 text-gray-500'">
                  {{ item.stock_quantity }}
                </span>
              </td>
              <td class="table-td text-right">
                <input v-model.number="item._opening" type="number" min="0" step="0.01"
                  class="form-input text-right w-28 text-sm"
                  :placeholder="String(item.stock_quantity)" />
              </td>
            </tr>
            <tr v-if="!filteredStock.length">
              <td colspan="6" class="table-td text-center text-gray-400 py-8">No products found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="stockSaved" class="text-sm text-green-600 font-medium flex items-center gap-1.5">
        ✓ Stock opening balances saved successfully.
      </p>
    </div>

    <!-- ── ACCOUNTS TAB ── -->
    <div v-if="activeTab === 'accounts'" class="space-y-4">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Enter the opening debit or credit balance for each account. Leave blank to skip.</p>
        <button @click="saveAccounts" :disabled="savingAccounts"
          class="btn-primary flex items-center gap-2 disabled:opacity-50">
          <span v-if="savingAccounts">Saving…</span>
          <span v-else>Save Account Balances</span>
        </button>
      </div>

      <div v-if="loadingAccounts" class="flex items-center justify-center py-16 text-gray-400 gap-2">
        <svg class="w-5 h-5 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        Loading accounts…
      </div>

      <div v-else class="space-y-3">
        <div v-for="group in accountGroups" :key="group.type">
          <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded-full" :class="group.badge">
              {{ group.label }}
            </span>
            <div class="flex-1 h-px bg-gray-100" />
          </div>
          <div class="card p-0 overflow-hidden">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th class="table-th w-20">Code</th>
                  <th class="table-th">Account</th>
                  <th class="table-th text-right w-40">Debit (LKR)</th>
                  <th class="table-th text-right w-40">Credit (LKR)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="acc in group.accounts" :key="acc.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs text-gray-500">{{ acc.code }}</td>
                  <td class="table-td font-medium text-gray-800">{{ acc.name }}</td>
                  <td class="table-td text-right">
                    <input v-model.number="acc._debit" type="number" min="0" step="0.01"
                      placeholder="0.00" class="form-input text-right w-36" />
                  </td>
                  <td class="table-td text-right">
                    <input v-model.number="acc._credit" type="number" min="0" step="0.01"
                      placeholder="0.00" class="form-input text-right w-36" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <p v-if="accountSaved" class="text-sm text-green-600 font-medium flex items-center gap-1.5">
        ✓ Account opening balances saved successfully.
      </p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('stock')
const tabs = [
  { id: 'stock',    label: 'Stock Quantities' },
  { id: 'accounts', label: 'Account Balances' },
]

// ── Stock ──────────────────────────────────────────────────────
const stockItems          = ref([])
const loadingStock        = ref(false)
const savingStock         = ref(false)
const stockSaved          = ref(false)
const stockSearch         = ref('')
const stockCategoryFilter = ref('')

const stockCategories = computed(() => {
  const cats = stockItems.value.map(i => i.category?.name).filter(Boolean)
  return [...new Set(cats)].sort()
})

const filteredStock = computed(() => {
  let list = stockItems.value
  if (stockSearch.value) {
    const q = stockSearch.value.toLowerCase()
    list = list.filter(i => i.name.toLowerCase().includes(q) || i.sku?.toLowerCase().includes(q))
  }
  if (stockCategoryFilter.value) {
    list = list.filter(i => i.category?.name === stockCategoryFilter.value)
  }
  return list
})

async function loadStock() {
  loadingStock.value = true
  try {
    const { data } = await axios.get('/api/opening-balance/stock')
    stockItems.value = data.map(p => ({ ...p, _opening: p.stock_quantity }))
  } finally {
    loadingStock.value = false
  }
}

async function saveStock() {
  savingStock.value = true
  stockSaved.value  = false
  try {
    const items = filteredStock.value
      .filter(i => i._opening !== '' && i._opening !== null && i._opening !== undefined)
      .map(i => ({ product_id: i.id, quantity: Number(i._opening) }))
    await axios.post('/api/opening-balance/stock', { items })
    stockSaved.value = true
    setTimeout(() => { stockSaved.value = false }, 4000)
    await loadStock()
  } finally {
    savingStock.value = false
  }
}

// ── Accounts ───────────────────────────────────────────────────
const accounts        = ref([])
const loadingAccounts = ref(false)
const savingAccounts  = ref(false)
const accountSaved    = ref(false)

const typeConfig = {
  asset:     { label: 'Assets',      badge: 'bg-blue-100 text-blue-800'   },
  liability: { label: 'Liabilities', badge: 'bg-red-100 text-red-800'     },
  equity:    { label: 'Equity',      badge: 'bg-purple-100 text-purple-800'},
  revenue:   { label: 'Revenue',     badge: 'bg-green-100 text-green-800' },
  expense:   { label: 'Expenses',    badge: 'bg-orange-100 text-orange-800'},
}

const accountGroups = computed(() => {
  const order = ['asset', 'liability', 'equity', 'revenue', 'expense']
  return order
    .map(type => ({
      type,
      label:    typeConfig[type]?.label  ?? type,
      badge:    typeConfig[type]?.badge  ?? 'bg-gray-100 text-gray-700',
      accounts: accounts.value.filter(a => a.type === type),
    }))
    .filter(g => g.accounts.length > 0)
})

async function loadAccounts() {
  loadingAccounts.value = true
  try {
    const { data } = await axios.get('/api/opening-balance/accounts')
    accounts.value = data.map(a => ({ ...a, _debit: null, _credit: null }))
  } finally {
    loadingAccounts.value = false
  }
}

async function saveAccounts() {
  savingAccounts.value = true
  accountSaved.value   = false
  try {
    const items = accounts.value
      .filter(a => (a._debit > 0) || (a._credit > 0))
      .map(a => ({ account_id: a.id, debit: a._debit || 0, credit: a._credit || 0 }))

    if (!items.length) {
      alert('Enter at least one debit or credit amount.')
      return
    }
    await axios.post('/api/opening-balance/accounts', { items })
    accountSaved.value = true
    setTimeout(() => { accountSaved.value = false }, 4000)
    accounts.value.forEach(a => { a._debit = null; a._credit = null })
  } finally {
    savingAccounts.value = false
  }
}

onMounted(() => {
  loadStock()
  loadAccounts()
})
</script>
