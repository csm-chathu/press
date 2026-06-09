<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <input v-model="search" type="search" placeholder="Search suppliers…" class="form-input w-64" @input="debouncedFetch" />
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Supplier
      </button>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr>
          <th class="table-th">Name</th><th class="table-th">Company</th>
          <th class="table-th">Email</th><th class="table-th">Phone</th>
          <th class="table-th">Products</th><th class="table-th">Status</th>
          <th class="table-th">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-gray-50">
            <td class="table-td font-medium">{{ s.name }}</td>
            <td class="table-td text-gray-500">{{ s.company }}</td>
            <td class="table-td">{{ s.email }}</td>
            <td class="table-td">{{ s.phone }}</td>
            <td class="table-td">{{ s.products_count }}</td>
            <td class="table-td">
              <span :class="s.is_active ? 'badge bg-green-100 text-green-700' : 'badge bg-gray-100 text-gray-500'">
                {{ s.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="table-td"><div class="flex gap-2">
            <button @click="openEdit(s)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
              <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
            </button>
            <button @click="del(s)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
              <TrashIcon class="w-3.5 h-3.5" /> Delete
            </button>
            </div></td>
          </tr>
          <tr v-if="!suppliers.data?.length"><td colspan="7" class="table-td text-center text-gray-400 py-8">No suppliers</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold mb-4">{{ editing ? 'Edit' : 'Add' }} Supplier</h3>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="form-label">Name *</label><input v-model="form.name" required class="form-input" /></div>
          <div><label class="form-label">Company</label><input v-model="form.company" class="form-input" /></div>
          <div><label class="form-label">Email</label><input v-model="form.email" type="email" class="form-input" /></div>
          <div><label class="form-label">Phone</label><input v-model="form.phone" class="form-input" /></div>
          <div><label class="form-label">City</label><input v-model="form.city" class="form-input" /></div>
          <div><label class="form-label">Country</label><input v-model="form.country" class="form-input" /></div>
          <div class="col-span-2"><label class="form-label">Address</label><textarea v-model="form.address" rows="2" class="form-input"></textarea></div>
          <div class="col-span-2"><label class="form-label">Notes</label><textarea v-model="form.notes" rows="2" class="form-input"></textarea></div>
          <div class="col-span-2"><label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded text-gold-600" /> Active</label></div>
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

const suppliers     = ref({ data: [] })
const search        = ref(''); const page = ref(1)
const showModal     = ref(false); const editing = ref(null)
const saving        = ref(false); const error   = ref('')
const confirmDelete  = ref(null)
const confirmMessage = ref('')
const form      = reactive({ name:'',company:'',email:'',phone:'',address:'',city:'',country:'',is_active:true,notes:'' })

let debounceTimer = null
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { page.value=1; fetch() }, 400) }

async function fetch() {
  const { data } = await axios.get('/api/suppliers', { params: { page: page.value, search: search.value } })
  suppliers.value = data
}

function openCreate() { editing.value=null; Object.assign(form,{name:'',company:'',email:'',phone:'',address:'',city:'',country:'',is_active:true,notes:''}); showModal.value=true }
function openEdit(s)  { editing.value=s; Object.assign(form,s); showModal.value=true }

async function save() {
  saving.value=true; error.value=''
  try {
    if (editing.value) await axios.put(`/api/suppliers/${editing.value.id}`, form)
    else               await axios.post('/api/suppliers', form)
    showModal.value=false; fetch()
  } catch(e) { error.value = Object.values(e.response?.data?.errors??{}).flat().join(', ')||'Error' }
  finally { saving.value=false }
}

function del(s) {
  confirmDelete.value  = s
  confirmMessage.value = `Delete "${s.name}"? This cannot be undone.`
}

async function doDelete() {
  await axios.delete(`/api/suppliers/${confirmDelete.value.id}`)
  confirmDelete.value = null
  fetch()
}

onMounted(fetch)
</script>
