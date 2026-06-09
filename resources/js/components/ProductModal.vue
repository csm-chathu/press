<template>
  <!-- Modal backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h3 class="text-lg font-semibold">{{ product ? 'Edit Product' : 'Add Product' }}</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <form @submit.prevent="submit" class="overflow-y-auto p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="form-label">Name *</label>
            <input v-model="form.name" required class="form-input" />
          </div>
          <div v-if="product">
            <label class="form-label">SKU</label>
            <input :value="product.sku" readonly class="form-input font-mono bg-gray-50 text-gray-500 cursor-not-allowed" />
          </div>
          <div>
            <label class="form-label">Custom Barcode <span class="text-gray-400 font-normal">(optional override)</span></label>
            <input v-model="form.barcode" class="form-input font-mono" placeholder="Scan or type barcode — leave blank to use SKU" />
          </div>
          <div class="col-span-2">
            <label class="form-label mb-1">Type *</label>
            <div class="flex rounded-lg border border-gray-200 overflow-hidden w-fit">
              <button type="button"
                @click="setTab('food')"
                :class="activeTab === 'food'
                  ? 'bg-amber-500 text-white font-semibold'
                  : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="px-6 py-2 text-sm transition-colors">
                Food
              </button>
              <button type="button"
                @click="setTab('other')"
                :class="activeTab === 'other'
                  ? 'bg-amber-500 text-white font-semibold'
                  : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="px-6 py-2 text-sm border-l border-gray-200 transition-colors">
                Other
              </button>
            </div>
          </div>
          <div>
            <label class="form-label">Category *</label>
            <select v-model="form.category_id" required class="form-input">
              <option value="" disabled>— Select —</option>
              <option v-for="c in filteredCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div v-if="!isFood">
            <label class="form-label">Supplier</label>
            <select v-model="form.supplier_id" class="form-input">
              <option value="">— None —</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div v-if="!isFood">
            <label class="form-label">Brand</label>
            <input v-model="form.brand" class="form-input" placeholder="e.g. Heineken, Glenfiddich" />
          </div>
          <div v-if="!isFood">
            <label class="form-label">Unit Type</label>
            <select v-model="form.unit_type" class="form-input">
              <option value="">— Select —</option>
              <option v-for="u in unitTypes" :key="u" :value="u">{{ u }}</option>
            </select>
          </div>
          <div v-if="!isFood">
            <label class="form-label">Base Unit</label>
            <input v-model="form.base_unit" class="form-input" placeholder="e.g. 750ml, 24 bottles, 1 kg" />
          </div>
          <div v-if="!isFood">
            <label class="form-label">Selling Variants</label>
            <input v-model="form.selling_variants" class="form-input" placeholder="e.g. 30ml, 50ml, 750ml" />
          </div>
          <!-- Shot variants — for liquor categories or products that already have variants -->
          <div v-if="isLiquor || form.shot_variants.length" class="col-span-2">
            <div class="flex items-center justify-between mb-2">
              <label class="form-label mb-0">Shot Variants</label>
              <button type="button" @click="addShotVariant"
                class="text-xs px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 font-semibold transition-colors">
                + Add Variant
              </button>
            </div>
            <div v-if="form.shot_variants.length" class="space-y-2">
              <div v-for="(v, idx) in form.shot_variants" :key="idx" class="flex items-center gap-2">
                <input v-model="v.name" class="form-input flex-1" placeholder="e.g. 30ml, 60ml, Single" />
                <input v-model.number="v.price" type="number" step="0.01" min="0" class="form-input w-32" placeholder="LKR price" />
                <button type="button" @click="form.shot_variants.splice(idx, 1)"
                  class="w-7 h-7 flex items-center justify-center text-gray-300 hover:text-red-500 rounded-lg transition-colors shrink-0">✕</button>
              </div>
            </div>
            <p v-else class="text-xs text-gray-400 italic">No shot variants — add one above.</p>
          </div>
          <div>
            <label class="form-label">Tax Setting</label>
            <select v-model="form.tax_setting_id" class="form-input">
              <option value="">— None —</option>
              <option v-for="tax in taxes" :key="tax.id" :value="tax.id">{{ tax.name }} ({{ tax.rate }}%)</option>
            </select>
          </div>
          <div v-if="!isFood">
            <label class="form-label">Purchase Price (LKR) *</label>
            <input v-model="form.purchase_price" type="number" step="0.01" min="0" :required="!isFood" class="form-input" />
          </div>
          <div>
            <label class="form-label">Selling Price (LKR) *</label>
            <input v-model="form.selling_price" type="number" step="0.01" min="0" required class="form-input" />
          </div>
          <div v-if="!isFood">
            <label class="form-label">Stock Quantity *</label>
            <input v-model="form.stock_quantity" type="number" min="0" :required="!isFood" class="form-input" />
          </div>
          <div>
            <label class="form-label">Min Stock Level</label>
            <input v-model="form.min_stock_level" type="number" min="0" class="form-input" />
          </div>
          <div class="col-span-2">
            <label class="form-label">Description</label>
            <textarea v-model="form.description" rows="2" class="form-input"></textarea>
          </div>
          <div class="col-span-2">
            <label class="form-label">Product Image</label>
            <input type="file" accept="image/*" class="form-input" @change="onImageChange" />
            <div v-if="imagePreview || form.image" class="mt-2 flex items-center gap-3">
              <img :src="imagePreview || form.image" alt="Product image" class="w-16 h-16 rounded-lg border border-gray-200 object-cover" />
              <button type="button" class="btn-secondary py-1 px-3 text-xs" @click="clearImage">Remove</button>
            </div>
          </div>
          <div class="col-span-2 flex items-center gap-2">
            <input id="active" type="checkbox" v-model="form.is_active" class="rounded text-gold-600" />
            <label for="active" class="text-sm text-gray-700">Active</label>
          </div>
          <div v-if="!isFood" class="col-span-2 flex items-center gap-2">
            <input id="deposit" type="checkbox" v-model="form.bottle_deposit_required" class="rounded text-gold-600" />
            <label for="deposit" class="text-sm text-gray-700">Bottle deposit required</label>
          </div>
          <div v-if="!isFood && form.bottle_deposit_required">
            <label class="form-label">Empty Bottle Deposit Amount (LKR) *</label>
            <input v-model.number="form.bottle_deposit_amount" type="number" step="0.01" min="0" required class="form-input" placeholder="0.00" />
          </div>
        </div>

        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
      </form>

      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
        <button @click="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ product: Object, categories: Array, suppliers: Array, taxes: Array })
const emit  = defineEmits(['close', 'saved'])

const unitTypes = ['Bottle', 'Can', 'Pack', 'Glass', 'Case', 'Plate', 'Serving']

const FOOD_CATEGORY_NAMES = ['food', 'snacks']

const activeTab = ref('other')

const isFood = computed(() => activeTab.value === 'food')

const filteredCategories = computed(() => props.categories ?? [])

function setTab(tab) {
  if (activeTab.value === tab) return
  activeTab.value = tab
  form.category_id = ''
}

const isLiquor = computed(() => {
  const cat = (props.categories ?? []).find(c => c.id === form.category_id)
  return !!cat?.enable_variants
})

const CATEGORY_TYPE_MAP = {
  'liquor': 'Liquor', 'hard liquor': 'Liquor', 'foreign liquor': 'Liquor',
  'hard-liquor': 'Liquor', 'foreign-liquor': 'Liquor',
  'beer': 'Beer', 'soft drinks': 'Soft Drinks',
  'soft-drinks': 'Soft Drinks', 'food': 'Food', 'snacks': 'Food', 'accessories': 'Accessories',
}

const form = reactive({
  name: '', description: '', category_id: '', supplier_id: '', tax_setting_id: '',
  product_type: 'Liquor', brand: '', unit_type: '', base_unit: '', selling_variants: '',
  shot_variants: [],
  purchase_price: '', selling_price: '', stock_quantity: 0, min_stock_level: 5,
  bottle_deposit_required: false, bottle_deposit_amount: 0, is_active: true, barcode: '',
})

const saving = ref(false)
const error  = ref('')
const imageFile = ref(null)
const imagePreview = ref('')

watch(isFood, (val) => {
  if (val) {
    form.stock_quantity = 1000
    form.purchase_price = form.selling_price
  }
})

watch(() => form.selling_price, (val) => {
  if (isFood.value) form.purchase_price = val
})

onMounted(() => {
  if (props.product) {
    const cat = (props.categories ?? []).find(c => c.id === props.product.category_id)
    activeTab.value = FOOD_CATEGORY_NAMES.includes((cat?.name ?? '').toLowerCase()) ? 'food' : 'other'
    Object.assign(form, props.product)
    form.selling_variants = Array.isArray(props.product.selling_variants)
      ? props.product.selling_variants.join(', ')
      : (props.product.selling_variants ?? '')
    let sv = props.product.shot_variants
    if (typeof sv === 'string') { try { sv = JSON.parse(sv) } catch { sv = [] } }
    form.shot_variants = Array.isArray(sv)
      ? sv.map(v => ({ name: v.name ?? '', price: v.price ?? '' }))
      : []
  }
})

async function submit() {
  saving.value = true
  error.value  = ''
  try {
    const cat = (props.categories ?? []).find(c => c.id === form.category_id)
    const derivedType = CATEGORY_TYPE_MAP[(cat?.name ?? '').toLowerCase()] ?? 'Accessories'
    const payload = {
      ...form,
      product_type: derivedType,
      selling_variants: form.selling_variants ? form.selling_variants.split(',').map(v => v.trim()).filter(Boolean).join(', ') : '',
      shot_variants: JSON.stringify(form.shot_variants.filter(v => v.name)),
    }
    const formData = new FormData()
    Object.entries(payload).forEach(([key, value]) => {
      if (value === null || value === undefined) return
      if (key === 'image') return  // only send image when a new file is chosen
      if (key === 'is_active' || key === 'bottle_deposit_required') {
        formData.append(key, value ? '1' : '0')
        return
      }
      formData.append(key, value)
    })
    if (imageFile.value) {
      formData.append('image', imageFile.value)
    }

    let savedProduct
    if (props.product) {
      formData.append('_method', 'PUT')
      const { data } = await axios.post(`/api/products/${props.product.id}`, formData)
      savedProduct = data
    } else {
      const { data } = await axios.post('/api/products', formData)
      savedProduct = data
    }
    emit('saved', { product: savedProduct, isNew: !props.product })
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : (e.response?.data?.message ?? 'Error saving')
  } finally {
    saving.value = false
  }
}

function addShotVariant() {
  form.shot_variants.push({ name: '', price: '' })
}

function onImageChange(event) {
  const file = event.target.files?.[0]
  imageFile.value = file || null
  imagePreview.value = file ? URL.createObjectURL(file) : ''
}

function clearImage() {
  imageFile.value = null
  imagePreview.value = ''
}
</script>
