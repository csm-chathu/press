<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <h2 class="text-lg font-semibold text-gray-800">Press Settings</h2>

    <!-- Company Info -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
      <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Company Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label">Company Name</label>
          <input v-model="settings.company_name" type="text" class="input w-full" />
        </div>
        <div>
          <label class="label">Registration No.</label>
          <input v-model="settings.registration_no" type="text" class="input w-full" />
        </div>
        <div>
          <label class="label">Phone</label>
          <input v-model="settings.phone" type="text" class="input w-full" />
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="settings.email" type="email" class="input w-full" />
        </div>
        <div class="md:col-span-2">
          <label class="label">Address</label>
          <textarea v-model="settings.address" rows="2" class="input w-full"></textarea>
        </div>
        <div>
          <label class="label">City</label>
          <input v-model="settings.city" type="text" class="input w-full" />
        </div>
        <div>
          <label class="label">Postal Code</label>
          <input v-model="settings.postal_code" type="text" class="input w-full" />
        </div>
      </div>
    </div>

    <!-- Quotation Defaults -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
      <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Quotation Defaults</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="label">Default Wastage %</label>
          <input v-model.number="settings.default_wastage_percent" type="number" step="0.5" min="0" max="50" class="input w-full" />
        </div>
        <div>
          <label class="label">Default Profit Margin %</label>
          <input v-model.number="settings.default_profit_margin" type="number" step="0.5" min="0" max="100" class="input w-full" />
        </div>
        <div>
          <label class="label">Default Tax % (VAT)</label>
          <input v-model.number="settings.default_tax_percent" type="number" step="0.5" min="0" max="30" class="input w-full" />
        </div>
        <div>
          <label class="label">Quotation Validity (days)</label>
          <input v-model.number="settings.quotation_validity_days" type="number" min="1" max="365" class="input w-full" />
        </div>
        <div>
          <label class="label">Currency</label>
          <select v-model="settings.currency" class="input w-full">
            <option value="LKR">LKR — Sri Lankan Rupee</option>
            <option value="USD">USD — US Dollar</option>
            <option value="EUR">EUR — Euro</option>
          </select>
        </div>
      </div>
      <div>
        <label class="label">Quotation Footer Note</label>
        <textarea v-model="settings.quotation_footer" rows="3" class="input w-full" placeholder="Terms and conditions, payment terms…"></textarea>
      </div>
    </div>

    <!-- Job Card Defaults -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
      <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Job Card / Production</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label">Default Lead Time (days)</label>
          <input v-model.number="settings.default_lead_time_days" type="number" min="1" class="input w-full" />
        </div>
        <div>
          <label class="label">Low Stock Alert Level (units)</label>
          <input v-model.number="settings.low_stock_threshold" type="number" min="0" class="input w-full" />
        </div>
      </div>
      <div class="flex items-center gap-3">
        <input v-model="settings.require_proof_approval" type="checkbox" id="req-proof" class="w-4 h-4 text-amber-500 rounded border-gray-300" />
        <label for="req-proof" class="text-sm text-gray-700">Require proof approval before plate-making</label>
      </div>
      <div class="flex items-center gap-3">
        <input v-model="settings.auto_create_prepress" type="checkbox" id="auto-prepress" class="w-4 h-4 text-amber-500 rounded border-gray-300" />
        <label for="auto-prepress" class="text-sm text-gray-700">Auto-create pre-press task when job card is created</label>
      </div>
    </div>

    <!-- Delivery -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
      <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Delivery</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label">Default Delivery Method</label>
          <select v-model="settings.default_delivery_method" class="input w-full">
            <option value="own_vehicle">Own Vehicle</option>
            <option value="courier">Courier</option>
            <option value="customer_pickup">Customer Pickup</option>
            <option value="third_party">Third Party Logistics</option>
          </select>
        </div>
      </div>
      <div>
        <label class="label">Delivery Note Footer</label>
        <textarea v-model="settings.delivery_footer" rows="2" class="input w-full" placeholder="Signature line, terms…"></textarea>
      </div>
    </div>

    <!-- Save -->
    <div class="flex justify-end gap-3">
      <button @click="save" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save Settings' }}</button>
    </div>

    <p v-if="saved" class="text-sm text-green-600 text-right font-medium">Settings saved successfully.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const saving  = ref(false)
const saved   = ref(false)
const settings = ref({
  company_name: 'LMUC Press',
  registration_no: '',
  phone: '',
  email: '',
  address: '',
  city: 'Colombo',
  postal_code: '',
  default_wastage_percent: 10,
  default_profit_margin: 25,
  default_tax_percent: 0,
  quotation_validity_days: 30,
  currency: 'LKR',
  quotation_footer: 'This quotation is valid for 30 days. 50% advance required on order confirmation.',
  default_lead_time_days: 5,
  low_stock_threshold: 50,
  require_proof_approval: true,
  auto_create_prepress: true,
  default_delivery_method: 'own_vehicle',
  delivery_footer: 'Please sign and return a copy to confirm receipt.',
})

async function save() {
  saving.value = true
  saved.value  = false
  try {
    await axios.post('/api/press-settings', settings.value)
    saved.value = true
    setTimeout(() => (saved.value = false), 3000)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to save settings')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/press-settings')
    if (data && typeof data === 'object') {
      settings.value = { ...settings.value, ...data }
    }
  } catch {
    // first run — use defaults
  }
})
</script>

<style scoped>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input { @apply border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none; }
.btn-primary { @apply bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50; }
</style>
