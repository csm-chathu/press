<template>
  <div class="space-y-6">
    <div class="flex items-center gap-4">
      <router-link to="/purchases" class="text-gray-500 hover:text-gray-700 text-sm">← Back</router-link>
      <h2 class="text-xl font-semibold text-gray-800">New Purchase Order</h2>
    </div>

    <div class="grid grid-cols-3 gap-6">
      <div class="col-span-2 space-y-4">
        <div class="card">
          <h3 class="font-semibold mb-3 text-gray-700">Items</h3>
          <div v-for="(item, i) in form.items" :key="i" class="mb-3 rounded-lg border border-gray-100 p-2">
            <div class="grid grid-cols-12 gap-2 mb-1">
              <label class="form-label col-span-7 mb-0">Product</label>
              <label class="form-label col-span-2 mb-0">Qty</label>
              <label class="form-label col-span-2 mb-0">Price</label>
              <span class="col-span-1"></span>
            </div>
            <div class="grid grid-cols-12 gap-2 items-start">
              <!-- Combobox -->
              <div class="col-span-7 relative" @keydown.escape="closeDropdown(i)">
                <input
                  v-model="productSearches[i]"
                  type="text"
                  placeholder="Search product…"
                  class="form-input w-full"
                  autocomplete="off"
                  @input="onSearchInput(i)"
                  @focus="openDropdown(i)"
                  @blur="onBlur(i)"
                />
                <ul
                  v-if="showDropdown[i] && filteredProducts(i).length"
                  class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-52 overflow-y-auto text-sm"
                >
                  <li
                    v-for="p in filteredProducts(i)"
                    :key="p.id"
                    class="px-3 py-2 cursor-pointer hover:bg-amber-50 flex justify-between items-center"
                    @mousedown.prevent="selectProduct(i, p)"
                  >
                    <span class="font-medium text-gray-800">{{ p.name }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ p.sku }}</span>
                  </li>
                </ul>
                <p v-if="showDropdown[i] && productSearches[i] && !filteredProducts(i).length"
                   class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow mt-1 px-3 py-2 text-sm text-gray-400">
                  No products found
                </p>
              </div>

              <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="form-input col-span-2" @input="recalc" />
              <input v-model.number="item.unit_cost" type="number" min="0" placeholder="Cost" class="form-input col-span-2" @input="recalc" />
              <button @click="removeItem(i)" class="col-span-1 text-red-400 hover:text-red-600 text-lg mt-1">✕</button>
            </div>
          </div>
          <button @click="addItem" class="btn-secondary text-sm mt-2">+ Add Item</button>
        </div>
      </div>

      <div class="space-y-4">
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700">Supplier *</h3>
          <select v-model="form.supplier_id" required class="form-input">
            <option value="">Select supplier…</option>
            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700">Details</h3>
          <div><label class="form-label">Status</label>
            <select v-model="form.status" class="form-input">
              <option value="draft">Draft</option>
              <option value="approved">Approved</option>
              <option value="sent">Sent to Supplier</option>
              <option value="received">Received</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div><label class="form-label">Tax (LKR)</label>
            <input v-model.number="form.tax" type="number" min="0" class="form-input" @input="recalc" />
          </div>
          <div class="border-t pt-3 space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>LKR {{ subtotal.toFixed(2) }}</span></div>
            <div class="flex justify-between font-bold text-gray-800 text-base"><span>Total</span><span>LKR {{ total.toFixed(2) }}</span></div>
          </div>
        </div>

        <div class="card"><label class="form-label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="form-input"></textarea>
        </div>

        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
        <button @click="submit" :disabled="saving || !form.supplier_id || !form.items.length" class="btn-primary w-full">
          {{ saving ? 'Saving…' : 'Create Purchase Order' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router    = useRouter()
const products  = ref([])
const suppliers = ref([])
const saving    = ref(false)
const error     = ref('')

const productSearches = ref([''])
const showDropdown    = ref([false])

const form = reactive({ supplier_id:'', status:'draft', tax:0, notes:'', items:[{product_id:'',quantity:1,unit_cost:0}] })

function filteredProducts(i) {
  const q = (productSearches.value[i] ?? '').toLowerCase().trim()
  if (!q) return products.value.slice(0, 30)
  return products.value.filter(p => p.name.toLowerCase().includes(q) || (p.sku ?? '').toLowerCase().includes(q))
}

function onSearchInput(i) {
  showDropdown.value[i] = true
  form.items[i].product_id = ''
}

function openDropdown(i) {
  showDropdown.value[i] = true
}

function closeDropdown(i) {
  showDropdown.value[i] = false
}

function onBlur(i) {
  setTimeout(() => { showDropdown.value[i] = false }, 150)
}

function selectProduct(i, p) {
  form.items[i].product_id = p.id
  form.items[i].unit_cost  = p.purchase_price ?? 0
  productSearches.value[i] = p.name
  showDropdown.value[i]    = false
  recalc()
}

function addItem() {
  form.items.push({ product_id:'', quantity:1, unit_cost:0 })
  productSearches.value.push('')
  showDropdown.value.push(false)
}

function removeItem(i) {
  form.items.splice(i, 1)
  productSearches.value.splice(i, 1)
  showDropdown.value.splice(i, 1)
  recalc()
}

const subtotal = computed(() => form.items.reduce((a, i) => a + (i.unit_cost * i.quantity), 0))
const total    = computed(() => subtotal.value + (form.tax || 0))
function recalc() {}

async function submit() {
  saving.value = true; error.value = ''
  try {
    await axios.post('/api/purchases', form)
    router.push('/purchases')
  } catch(e) {
    error.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors??{}).flat().join(', ') ?? 'Error'
  } finally { saving.value = false }
}

onMounted(async () => {
  const [p, s] = await Promise.all([axios.get('/api/products',{params:{per_page:200}}), axios.get('/api/suppliers/all')])
  products.value  = p.data.data
  suppliers.value = s.data
})
</script>
