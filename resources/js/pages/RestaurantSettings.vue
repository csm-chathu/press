<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Restaurant Settings</h2>
      <p class="text-sm text-gray-500 mt-0.5">Update restaurant name, logo, and address shown in reports and printouts.</p>
    </div>

    <!-- POS Preferences (localStorage, per device) -->
    <div class="card space-y-4">
      <div>
        <h3 class="font-semibold text-gray-800">POS Preferences</h3>
        <p class="text-sm text-gray-500 mt-0.5">These settings are saved on this device only.</p>
      </div>
      <!-- Bill layout picker -->
      <div class="flex items-center justify-between py-3 border-t border-gray-100">
        <div>
          <p class="text-sm font-medium text-gray-800">POS Bill Layout</p>
          <p class="text-xs text-gray-500 mt-0.5">
            Choose which New Bill page to use on this device.<br />
            <span class="font-medium text-gray-600">Classic</span> — standard compact layout &nbsp;·&nbsp;
            <span class="font-medium text-gray-600">Enhanced</span> — larger cards, visual table picker, kitchen notes
          </p>
        </div>
        <div class="flex shrink-0 rounded-lg border border-gray-200 overflow-hidden text-xs font-semibold ml-4">
          <button
            type="button"
            @click="setBillLayout('1')"
            class="px-4 py-2 transition-colors"
            :class="billLayout === '1' ? 'bg-amber-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
          >Classic</button>
          <button
            type="button"
            @click="setBillLayout('2')"
            class="px-4 py-2 border-l border-gray-200 transition-colors"
            :class="billLayout === '2' ? 'bg-amber-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
          >Enhanced</button>
        </div>
      </div>

      <div class="flex items-center justify-between py-3 border-t border-gray-100">
        <div>
          <p class="text-sm font-medium text-gray-800">Keyboard Shortcuts</p>
          <p class="text-xs text-gray-500 mt-0.5">
            Enable F-keys and hotkeys on the New Bill page (F1 search, F2 barcode, F3 amount, F9 draft, F10 complete, +/− qty, Alt combos).
          </p>
        </div>
        <button
          type="button"
          @click="toggleKbShortcuts"
          class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none"
          :class="kbShortcutsEnabled ? 'bg-amber-500' : 'bg-gray-300'"
          :aria-checked="kbShortcutsEnabled"
          role="switch"
        >
          <span
            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
            :class="kbShortcutsEnabled ? 'translate-x-6' : 'translate-x-1'"
          />
        </button>
      </div>
    </div>

    <div class="card space-y-5">
      <div v-if="canSelectBranch" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Branch</label>
          <select v-model="selectedBranchId" @change="load" class="form-input">
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }} ({{ branch.code }})
            </option>
          </select>
        </div>
        <div>
          <label class="form-label">Branch Code</label>
          <input :value="branchCode" class="form-input bg-gray-100" readonly />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Restaurant Name *</label>
          <input v-model="form.name" class="form-input" maxlength="120" required />
        </div>
        <div>
          <label class="form-label">Logo</label>
          <input type="file" accept="image/*" @change="onLogoChange" class="form-input" />
        </div>

        <div class="md:col-span-2">
          <label class="form-label">Address</label>
          <textarea v-model="form.address" rows="2" class="form-input"></textarea>
        </div>

        <div>
          <label class="form-label">City</label>
          <input v-model="form.city" class="form-input" maxlength="100" />
        </div>
        <div>
          <label class="form-label">Country</label>
          <input v-model="form.country" class="form-input" maxlength="100" />
        </div>
      </div>

      <div v-if="previewLogo || currentLogo" class="pt-2">
        <p class="text-xs text-gray-500 mb-2 uppercase tracking-wide">Logo Preview</p>
        <img :src="previewLogo || currentLogo" alt="Restaurant logo" class="w-24 h-24 object-cover rounded-lg border border-gray-200" />
      </div>

      <p v-if="message" class="text-sm text-emerald-700 bg-emerald-50 px-3 py-2 rounded-lg">{{ message }}</p>
      <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>

      <div class="flex gap-3">
        <button @click="load" type="button" class="btn-secondary">Reset</button>
        <button @click="save" type="button" :disabled="saving" class="btn-primary">
          {{ saving ? 'Saving…' : 'Save Settings' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const canSelectBranch = computed(() => ['admin', 'owner'].includes(auth.user?.role))

const form = reactive({
  name: '',
  address: '',
  city: '',
  country: '',
})

const branches = ref([])
const selectedBranchId = ref(auth.user?.branch_id ?? null)
const branchCode = ref('')
const currentLogo = ref('')
const previewLogo = ref('')
const logoFile = ref(null)
const saving = ref(false)
const message = ref('')
const error = ref('')

// POS preferences — stored in localStorage (per device)
const kbShortcutsEnabled = ref(localStorage.getItem('pos_keyboard_shortcuts') !== 'false')
const billLayout = ref(localStorage.getItem('pos_bill_layout') || '1')

function toggleKbShortcuts() {
  kbShortcutsEnabled.value = !kbShortcutsEnabled.value
  localStorage.setItem('pos_keyboard_shortcuts', kbShortcutsEnabled.value ? 'true' : 'false')
}

function setBillLayout(v) {
  billLayout.value = v
  localStorage.setItem('pos_bill_layout', v)
}

function onLogoChange(event) {
  const file = event.target.files?.[0]
  logoFile.value = file ?? null
  previewLogo.value = file ? URL.createObjectURL(file) : ''
}

async function load() {
  message.value = ''
  error.value = ''
  const requestConfig = canSelectBranch.value && selectedBranchId.value
    ? { params: { branch_id: selectedBranchId.value } }
    : {}
  const { data } = await axios.get('/api/settings/restaurant', requestConfig)
  form.name = data.name ?? ''
  form.address = data.address ?? ''
  form.city = data.city ?? ''
  form.country = data.country ?? ''
  branchCode.value = data.code ?? ''
  currentLogo.value = data.logo_url ?? ''
  previewLogo.value = ''
  logoFile.value = null
}

async function loadBranches() {
  if (!canSelectBranch.value) return
  const { data } = await axios.get('/api/branches')
  branches.value = data
  if (!selectedBranchId.value && branches.value.length) {
    selectedBranchId.value = branches.value[0].id
  }
}

async function save() {
  saving.value = true
  message.value = ''
  error.value = ''

  try {
    const payload = new FormData()
    payload.append('name', form.name || '')
    payload.append('address', form.address || '')
    payload.append('city', form.city || '')
    payload.append('country', form.country || '')
    if (canSelectBranch.value && selectedBranchId.value) payload.append('branch_id', selectedBranchId.value)
    if (logoFile.value) payload.append('logo', logoFile.value)

    const { data } = await axios.post('/api/settings/restaurant', payload)
    message.value = data.message || 'Settings saved'
    currentLogo.value = data.settings?.logo_url || ''
    previewLogo.value = ''
    logoFile.value = null
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Failed to save settings'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadBranches()
  await load()
})
</script>
