<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h2 class="text-lg font-semibold text-gray-800">Press Machines</h2>
      <button @click="openModal()" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors">
        <PlusIcon class="w-4 h-4" /> Add Machine
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="m in machines" :key="m.id" class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <p class="font-semibold text-gray-800">{{ m.name }}</p>
            <p class="text-xs text-gray-500 capitalize mt-0.5">{{ m.machine_type?.replace(/_/g,' ') }}</p>
          </div>
          <span :class="m.status === 'active' ? 'bg-green-100 text-green-700' : m.status === 'maintenance' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'"
            class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ m.status }}</span>
        </div>
        <div class="text-xs text-gray-500 space-y-1">
          <p v-if="m.manufacturer"><span class="text-gray-400">Manufacturer:</span> {{ m.manufacturer }}</p>
          <p v-if="m.model_number"><span class="text-gray-400">Model:</span> {{ m.model_number }}</p>
          <p v-if="m.capacity_per_hour"><span class="text-gray-400">Capacity:</span> {{ m.capacity_per_hour?.toLocaleString() }}/hr</p>
          <p><span class="text-gray-400">Active Jobs:</span> <strong class="text-purple-600">{{ m.production_jobs_count ?? 0 }}</strong></p>
          <p><span class="text-gray-400">Total Jobs:</span> {{ m.job_cards_count ?? 0 }}</p>
        </div>
        <div class="flex gap-2">
          <button @click="openModal(m)" class="text-xs text-blue-600 hover:underline">Edit</button>
          <button @click="deleteMachine(m)" class="text-xs text-red-500 hover:underline">Delete</button>
        </div>
      </div>
      <div v-if="!machines.length" class="md:col-span-3 text-center py-12 text-gray-400 text-sm">
        No machines added yet. Click "Add Machine" to get started.
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="font-semibold text-gray-800 mb-4">{{ editing ? 'Edit Machine' : 'Add Machine' }}</h3>
        <form @submit.prevent="save" class="space-y-4">
          <div>
            <label class="label">Machine Name *</label>
            <input v-model="form.name" required type="text" class="input w-full" placeholder="e.g., Heidelberg SM 52" />
          </div>
          <div>
            <label class="label">Machine Type *</label>
            <select v-model="form.machine_type" required class="input w-full">
              <option value="">Select type</option>
              <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Manufacturer</label>
              <input v-model="form.manufacturer" type="text" class="input w-full" placeholder="Heidelberg" />
            </div>
            <div>
              <label class="label">Model Number</label>
              <input v-model="form.model_number" type="text" class="input w-full" placeholder="SM52-4" />
            </div>
            <div>
              <label class="label">Capacity / hr</label>
              <input v-model.number="form.capacity_per_hour" type="number" class="input w-full" placeholder="8000" />
            </div>
            <div>
              <label class="label">Status</label>
              <select v-model="form.status" class="input w-full">
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div>
            <label class="label">Notes</label>
            <textarea v-model="form.notes" rows="2" class="input w-full"></textarea>
          </div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon } from '@heroicons/vue/24/outline'

const machines   = ref([])
const loading    = ref(false)
const showModal  = ref(false)
const saving     = ref(false)
const editing    = ref(null)

const types = [
  { value: 'printing', label: 'Printing Machine' }, { value: 'cutting', label: 'Cutting / Guillotine' },
  { value: 'binding', label: 'Binding Machine' }, { value: 'lamination', label: 'Lamination Machine' },
  { value: 'uv', label: 'UV Coating' }, { value: 'folding', label: 'Folding Machine' },
  { value: 'die_cutting', label: 'Die Cutting' }, { value: 'other', label: 'Other' },
]

const form = ref({ name: '', machine_type: '', manufacturer: '', model_number: '', capacity_per_hour: '', status: 'active', notes: '' })

function openModal(machine = null) {
  editing.value = machine
  form.value = machine
    ? { name: machine.name, machine_type: machine.machine_type, manufacturer: machine.manufacturer ?? '', model_number: machine.model_number ?? '', capacity_per_hour: machine.capacity_per_hour ?? '', status: machine.status, notes: machine.notes ?? '' }
    : { name: '', machine_type: '', manufacturer: '', model_number: '', capacity_per_hour: '', status: 'active', notes: '' }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editing.value) {
      const { data } = await axios.put(`/api/press-machines/${editing.value.id}`, form.value)
      const idx = machines.value.findIndex(m => m.id === editing.value.id)
      if (idx !== -1) machines.value[idx] = data
    } else {
      const { data } = await axios.post('/api/press-machines', form.value)
      machines.value.unshift(data)
    }
    showModal.value = false
  } catch (e) {
    alert(e.response?.data?.message ?? 'Save failed')
  } finally {
    saving.value = false
  }
}

async function deleteMachine(m) {
  if (!confirm(`Delete machine "${m.name}"?`)) return
  try {
    await axios.delete(`/api/press-machines/${m.id}`)
    machines.value = machines.value.filter(x => x.id !== m.id)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Delete failed')
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/press-machines')
    machines.value = data
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input { @apply border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none; }
.btn-primary { @apply bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50; }
.btn-secondary { @apply border border-gray-200 text-gray-600 hover:bg-gray-50 px-5 py-2 rounded-lg text-sm font-semibold transition-colors; }
</style>
