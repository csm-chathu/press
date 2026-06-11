<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h3 class="text-lg font-semibold">{{ product ? 'Edit Product' : 'Add Product' }}</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
      </div>

      <form @submit.prevent="submit" class="overflow-y-auto p-6 space-y-5">

        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="form-label">Name *</label>
            <input v-model="form.name" required class="form-input w-full" placeholder="e.g. Art Paper 128gsm A3" />
          </div>

          <div v-if="product">
            <label class="form-label">SKU</label>
            <input :value="product.sku" readonly class="form-input w-full font-mono bg-gray-50 text-gray-400 cursor-not-allowed" />
          </div>

          <div>
            <label class="form-label">Category *</label>
            <select v-model="form.category_id" required class="form-input w-full">
              <option value="" disabled>— Select —</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div>
            <label class="form-label">Supplier</label>
            <select v-model="form.supplier_id" class="form-input w-full">
              <option value="">— None —</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
        </div>

        <!-- Material Type -->
        <div>
          <label class="form-label">Material Type *</label>
          <div class="flex flex-wrap gap-2">
            <button v-for="mt in materialTypes" :key="mt.value" type="button"
              @click="form.material_type = mt.value"
              :class="form.material_type === mt.value
                ? 'bg-amber-500 text-white border-amber-500'
                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
              class="px-4 py-1.5 text-sm rounded-lg border font-medium transition-colors">
              {{ mt.label }}
            </button>
          </div>
        </div>

        <!-- Paper-specific fields -->
        <div v-if="isPaper" class="grid grid-cols-3 gap-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
          <div>
            <label class="form-label text-blue-700">GSM</label>
            <input v-model.number="form.gsm" type="number" min="1" class="form-input w-full" placeholder="e.g. 170" />
          </div>
          <div>
            <label class="form-label text-blue-700">Paper Size</label>
            <select v-model="form.paper_size" class="form-input w-full">
              <option value="">— Select —</option>
              <option v-for="s in paperSizes" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
          <div>
            <label class="form-label text-blue-700">Bundle Size <span class="font-normal text-gray-400">(sheets/bundle)</span></label>
            <input v-model.number="form.bundle_size" type="number" min="0" class="form-input w-full" placeholder="e.g. 100, 500" />
          </div>
        </div>

        <!-- Unit & Stock -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Base Unit</label>
            <select v-model="form.base_unit" class="form-input w-full">
              <option value="">— Select —</option>
              <option v-for="u in baseUnits" :key="u" :value="u">{{ u }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Purchase Price (Rs.) *</label>
            <input v-model.number="form.purchase_price" type="number" step="0.01" min="0" required class="form-input w-full" />
          </div>
          <div>
            <label class="form-label">Selling Price (Rs.) *</label>
            <input v-model.number="form.selling_price" type="number" step="0.01" min="0" required class="form-input w-full" />
          </div>
          <div>
            <label class="form-label">Stock Quantity *</label>
            <div class="flex gap-2">
              <input v-model.number="form.stock_quantity" type="number" min="0" required class="form-input w-full" />
              <span v-if="form.bundle_size && form.stock_quantity"
                class="flex items-center text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 whitespace-nowrap">
                {{ Math.floor(form.stock_quantity / form.bundle_size) }} bndl
              </span>
            </div>
          </div>
          <div>
            <label class="form-label">Min Stock Level <span class="font-normal text-gray-400">(reorder at)</span></label>
            <input v-model.number="form.min_stock_level" type="number" min="0" class="form-input w-full" />
          </div>
          <div>
            <label class="form-label">Custom Barcode <span class="font-normal text-gray-400">(optional)</span></label>
            <input v-model="form.barcode" class="form-input w-full font-mono" placeholder="Scan or leave blank to use SKU" />
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="form-label">Description</label>
          <textarea v-model="form.description" rows="2" class="form-input w-full" placeholder="Optional notes about this material"></textarea>
        </div>

        <!-- Image -->
        <div>
          <label class="form-label">Product Image</label>
          <input type="file" accept="image/*" class="form-input w-full" @change="onImageChange" />
          <div v-if="imagePreview || form.image" class="mt-2 flex items-center gap-3">
            <img :src="imagePreview || form.image" alt="preview" class="w-16 h-16 rounded-lg border border-gray-200 object-cover" />
            <button type="button" class="text-xs text-red-500 hover:text-red-700" @click="clearImage">Remove</button>
          </div>
        </div>

        <!-- Active -->
        <div class="flex items-center gap-2">
          <input id="active" type="checkbox" v-model="form.is_active" class="rounded" />
          <label for="active" class="text-sm text-gray-700">Active</label>
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
import { reactive, ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ product: Object, categories: Array, suppliers: Array, taxes: Array })
const emit  = defineEmits(['close', 'saved'])

const materialTypes = [
  { value: 'paper',     label: 'Paper / Board' },
  { value: 'ink',       label: 'Ink' },
  { value: 'plate',     label: 'Plate / Film' },
  { value: 'chemical',  label: 'Chemical' },
  { value: 'packaging', label: 'Packaging' },
  { value: 'other',     label: 'Other' },
]

const paperSizes = ['A0','A1','A2','A3','A4','A5','A6','SRA3','SRA2','SRA1','B0','B1','B2','B3','B4','B5']

const baseUnits = ['sheet','pcs','kg','tin','can','bottle','roll','drum','ream','box','pack','set']

const isPaper = computed(() => form.material_type === 'paper')

const form = reactive({
  name: '', description: '', category_id: '', supplier_id: '',
  material_type: 'paper',
  gsm: null, paper_size: '', bundle_size: 0,
  base_unit: 'sheet',
  purchase_price: '', selling_price: '',
  stock_quantity: 0, min_stock_level: 0,
  product_type: 'product',
  is_active: true, barcode: '',
})

const saving      = ref(false)
const error       = ref('')
const imageFile   = ref(null)
const imagePreview = ref('')

onMounted(() => {
  if (props.product) {
    Object.assign(form, {
      name:           props.product.name          ?? '',
      description:    props.product.description   ?? '',
      category_id:    props.product.category_id   ?? '',
      supplier_id:    props.product.supplier_id    ?? '',
      material_type:  props.product.material_type  ?? 'other',
      gsm:            props.product.gsm            ?? null,
      paper_size:     props.product.paper_size     ?? '',
      bundle_size:    props.product.bundle_size    ?? 0,
      base_unit:      props.product.base_unit      ?? 'sheet',
      purchase_price: props.product.purchase_price ?? '',
      selling_price:  props.product.selling_price  ?? '',
      stock_quantity: props.product.stock_quantity ?? 0,
      min_stock_level:props.product.min_stock_level ?? 0,
      product_type:   props.product.product_type  ?? 'product',
      is_active:      props.product.is_active      ?? true,
      barcode:        props.product.barcode        ?? '',
    })
  }
})

async function submit() {
  saving.value = true
  error.value  = ''
  try {
    const formData = new FormData()
    const fields = [
      'name','description','category_id','supplier_id','material_type',
      'gsm','paper_size','bundle_size','base_unit',
      'purchase_price','selling_price','stock_quantity','min_stock_level',
      'product_type','barcode',
    ]
    fields.forEach(k => {
      const v = form[k]
      if (v !== null && v !== undefined && v !== '') formData.append(k, v)
    })
    formData.append('is_active', form.is_active ? '1' : '0')
    if (imageFile.value) formData.append('image', imageFile.value)

    let saved
    if (props.product) {
      formData.append('_method', 'PUT')
      const { data } = await axios.post(`/api/products/${props.product.id}`, formData)
      saved = data
    } else {
      const { data } = await axios.post('/api/products', formData)
      saved = data
    }
    emit('saved', { product: saved, isNew: !props.product })
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : (e.response?.data?.message ?? 'Error saving')
  } finally {
    saving.value = false
  }
}

function onImageChange(e) {
  const file = e.target.files?.[0]
  imageFile.value    = file || null
  imagePreview.value = file ? URL.createObjectURL(file) : ''
}

function clearImage() {
  imageFile.value    = null
  imagePreview.value = ''
}
</script>
