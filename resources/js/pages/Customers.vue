<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <input v-model="search" type="search" placeholder="Search customers…" class="form-input w-64" @input="debouncedFetch" />
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Customer
      </button>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr>
          <th class="table-th">Name</th><th class="table-th">Email</th>
          <th class="table-th">Phone</th><th class="table-th">City</th>
          <th class="table-th">KYC</th><th class="table-th">Sales</th><th class="table-th">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="c in customers.data" :key="c.id" class="hover:bg-gray-50">
            <td class="table-td font-medium">{{ c.name }}</td>
            <td class="table-td text-gray-500">{{ c.email }}</td>
            <td class="table-td">{{ c.phone }}</td>
            <td class="table-td">{{ c.city }}</td>
            <td class="table-td">
              <span v-if="c.kyc_verified" class="badge bg-green-100 text-green-700 text-xs">✓ Verified</span>
              <span v-else-if="c.id_number" class="badge bg-yellow-100 text-yellow-700 text-xs">ID on file</span>
              <span v-else class="text-gray-300 text-xs">—</span>
            </td>
            <td class="table-td">{{ c.sales_count }}</td>
            <td class="table-td"><div class="flex gap-2">
            <button @click="openEdit(c)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
              <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
            </button>
            <button @click="del(c)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
              <TrashIcon class="w-3.5 h-3.5" /> Delete
            </button>
            </div></td>
          </tr>
          <tr v-if="!customers.data?.length"><td colspan="6" class="table-td text-center text-gray-400 py-8">No customers</td></tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ customers.total ?? 0 }} customers</span>
        <div class="flex gap-2">
          <button @click="page--; fetch()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetch()" :disabled="page>=customers.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold mb-4">{{ editing ? 'Edit' : 'Add' }} Customer</h3>
        <div class="grid grid-cols-2 gap-3">
          <div class="col-span-2"><label class="form-label">Name *</label><input v-model="form.name" required class="form-input" /></div>
          <div><label class="form-label">Email</label><input v-model="form.email" type="email" class="form-input" /></div>
          <div><label class="form-label">Phone</label><input v-model="form.phone" class="form-input" /></div>
          <div><label class="form-label">City</label><input v-model="form.city" class="form-input" /></div>
          <div><label class="form-label">Country</label><input v-model="form.country" class="form-input" /></div>
          <div><label class="form-label">Date of Birth</label><input v-model="form.date_of_birth" type="date" class="form-input" /></div>
          <div><label class="form-label">Gender</label>
            <select v-model="form.gender" class="form-input">
              <option value="">—</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>
            </select>
          </div>
          <div class="col-span-2"><label class="form-label">Address</label><textarea v-model="form.address" rows="2" class="form-input"></textarea></div>
          <div class="col-span-2"><label class="form-label">Notes</label><textarea v-model="form.notes" rows="2" class="form-input"></textarea></div>

          <!-- KYC / ID Section -->
          <div class="col-span-2 border-t pt-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">KYC / ID Verification</p>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">ID Type</label>
                <select v-model="form.id_type" class="form-input">
                  <option value="">None / Not provided</option>
                  <option value="nic">NIC (National ID)</option>
                  <option value="passport">Passport</option>
                  <option value="driving_license">Driving License</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div>
                <label class="form-label">ID Number</label>
                <input v-model="form.id_number" class="form-input" placeholder="e.g. 199012345678" />
              </div>
              <div>
                <label class="form-label">ID Expiry Date</label>
                <input v-model="form.id_expiry" type="date" class="form-input" />
              </div>
              <div class="flex flex-col justify-end">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                  <input type="checkbox" v-model="form.kyc_verified" class="rounded text-gold-600" />
                  <span class="font-medium">KYC Verified</span>
                </label>
                <p class="text-xs text-gray-400 mt-1">Check once ID has been physically sighted</p>
              </div>
              <div class="col-span-2">
                <label class="form-label">KYC Notes</label>
                <textarea v-model="form.kyc_notes" rows="2" class="form-input" placeholder="e.g. ID sighted and photocopied on 2026-05-07…"></textarea>
              </div>
            </div>
          </div>
        </div>
        <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
        <div class="flex justify-end gap-3 mt-5">
          <button @click="showModal=false" class="btn-secondary">Cancel</button>
          <button @click="save" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
        </div>
      </div>
    </div>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/components/ConfirmModal.vue'

const customers     = ref({ data: [] })
const search        = ref(''); const page = ref(1)
const showModal     = ref(false); const editing = ref(null)
const saving        = ref(false); const error   = ref('')
const confirmDelete  = ref(null)
const confirmMessage = ref('')
const form      = reactive({ name:'',email:'',phone:'',address:'',city:'',country:'',date_of_birth:'',gender:'',notes:'', id_type:'',id_number:'',id_expiry:'',kyc_verified:false,kyc_notes:'' })

let debounceTimer = null
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { page.value=1; fetch() }, 400) }

async function fetch() {
  const { data } = await axios.get('/api/customers', { params: { page: page.value, search: search.value } })
  customers.value = data
}

function openCreate() { editing.value=null; Object.assign(form,{name:'',email:'',phone:'',address:'',city:'',country:'',date_of_birth:'',gender:'',notes:'',id_type:'',id_number:'',id_expiry:'',kyc_verified:false,kyc_notes:''}); showModal.value=true }
function openEdit(c)  { editing.value=c; Object.assign(form,c); showModal.value=true }

async function save() {
  saving.value=true; error.value=''
  try {
    if (editing.value) await axios.put(`/api/customers/${editing.value.id}`, form)
    else               await axios.post('/api/customers', form)
    showModal.value=false; fetch()
  } catch(e) { error.value = Object.values(e.response?.data?.errors??{}).flat().join(', ')||'Error' }
  finally { saving.value=false }
}

function del(c) {
  confirmDelete.value  = c
  confirmMessage.value = `Delete "${c.name}"? This cannot be undone.`
}

async function doDelete() {
  await axios.delete(`/api/customers/${confirmDelete.value.id}`)
  confirmDelete.value = null
  fetch()
}

onMounted(fetch)
</script>
