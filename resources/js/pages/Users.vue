<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">User Management</h2>
        <p class="text-sm text-gray-500 mt-0.5">Manage users, roles, permissions, and branch assignments</p>
      </div>
      <button @click="openModal(null)" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add User
      </button>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap">
      <input v-model="search" placeholder="Search name or email…" class="form-input flex-1 min-w-48" @input="load" />
      <select v-model="filterRole" class="form-input w-40" @change="load">
        <option value="">All Roles</option>
        <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
      </select>
      <select v-model="filterBranch" class="form-input w-48" @change="load">
        <option value="">All Branches</option>
        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Name</th>
            <th class="table-th">Email</th>
            <th class="table-th">Role</th>
            <th class="table-th">Branch</th>
            <th class="table-th">Permissions</th>
            <th class="table-th">Status</th>
            <th class="table-th text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50">
            <td class="table-td font-medium">{{ u.name }}</td>
            <td class="table-td text-gray-500 text-sm">{{ u.email }}</td>
            <td class="table-td">
              <span :class="roleClass(u.role)" class="badge capitalize">
                {{ roleLabel(u.role) }}
              </span>
            </td>
            <td class="table-td text-sm text-gray-600">{{ u.role === 'client' ? (u.customer?.name ?? '—') : (u.branch?.name ?? '—') }}</td>
            <td class="table-td">
              <div class="flex gap-1 flex-wrap">
                <span v-if="u.can_delete_transactions" class="badge bg-red-100 text-red-700 text-xs">Delete Txn</span>
                <span v-if="u.can_override_gold_rate" class="badge bg-amber-100 text-amber-700 text-xs">Rate Override</span>
                <span v-if="!u.can_delete_transactions && !u.can_override_gold_rate && !['admin','owner'].includes(u.role)" class="text-xs text-gray-400">Standard</span>
              </div>
            </td>
            <td class="table-td">
              <span :class="u.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="badge">
                {{ u.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="table-td text-right">
              <div class="flex justify-end gap-1.5">
                <button @click="openModal(u)"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                </button>
                <button v-if="u.id !== authUser?.id" @click="deleteUser(u)"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!users.length">
            <td colspan="7" class="table-td text-center text-gray-400 py-8">No users found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit User' : 'Add User' }}</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="save" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="form-label">Full Name *</label>
              <input v-model="form.name" required class="form-input" />
            </div>
            <div class="col-span-2">
              <label class="form-label">Email *</label>
              <input v-model="form.email" type="email" required class="form-input" />
            </div>
            <div class="col-span-2">
              <label class="form-label">{{ editing ? 'New Password (leave blank to keep)' : 'Password *' }}</label>
              <input v-model="form.password" type="password" :required="!editing" minlength="6" class="form-input" />
            </div>
            <div>
              <label class="form-label">Role *</label>
              <select v-model="form.role" required class="form-input">
                <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
              </select>
            </div>
            <div>
              <label class="form-label">Branch</label>
              <select v-model="form.branch_id" class="form-input">
                <option :value="null">— None —</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>
            <div v-if="form.role === 'client'" class="col-span-2">
              <label class="form-label">Linked Customer <span class="text-red-500">*</span></label>
              <select v-model="form.customer_id" :required="form.role === 'client'" class="form-input">
                <option :value="null">— Select Customer —</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <p class="text-xs text-gray-400 mt-1">This client user can only see data for the linked customer.</p>
            </div>
          </div>

          <!-- Permissions -->
          <div class="border-t pt-4 space-y-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Extra Permissions</p>
            <label class="flex items-center gap-3 cursor-pointer select-none">
              <input type="checkbox" v-model="form.can_override_gold_rate" class="w-4 h-4 rounded text-gold-600" />
              <div>
                <p class="text-sm font-medium text-gray-700">Can Override Price Matrix</p>
                <p class="text-xs text-gray-400">Allow price control even without owner/admin role</p>
              </div>
            </label>
            <label class="flex items-center gap-3 cursor-pointer select-none">
              <input type="checkbox" v-model="form.can_delete_transactions" class="w-4 h-4 rounded text-red-500" />
              <div>
                <p class="text-sm font-medium text-gray-700">Can Void Bills</p>
                <p class="text-xs text-gray-400">Allow deleting or voiding transactions</p>
              </div>
            </label>
            <label class="flex items-center gap-3 cursor-pointer select-none">
              <input type="checkbox" v-model="form.is_active" class="w-4 h-4 rounded text-green-500" />
              <div>
                <p class="text-sm font-medium text-gray-700">Active</p>
                <p class="text-xs text-gray-400">Inactive users cannot log in</p>
              </div>
            </label>
          </div>

          <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary flex-1">
              {{ saving ? 'Saving…' : (editing ? 'Update User' : 'Create User') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import ConfirmModal from '@/components/ConfirmModal.vue'
import axios from 'axios'

const auth     = useAuthStore()
const authUser = auth.user

const users       = ref([])
const branches    = ref([])
const showModal      = ref(false)
const editing        = ref(null)
const saving         = ref(false)
const formError      = ref('')
const confirmDelete  = ref(null)
const confirmMessage = ref('')
const search      = ref('')
const filterRole   = ref('')
const filterBranch = ref('')
const roleOptions = [
  { value: 'owner',              label: 'Owner' },
  { value: 'admin',              label: 'Admin' },
  { value: 'sales',              label: 'Sales' },
  { value: 'estimator',          label: 'Estimator' },
  { value: 'designer',           label: 'Designer' },
  { value: 'production_manager', label: 'Production Manager' },
  { value: 'machine_operator',   label: 'Machine Operator' },
  { value: 'store_keeper',       label: 'Store Keeper' },
  { value: 'accountant',         label: 'Accountant' },
  { value: 'dispatch_officer',   label: 'Dispatch Officer' },
  { value: 'client',             label: 'Client (Portal)' },
  { value: 'manager',            label: 'Manager (Legacy)' },
  { value: 'cashier',            label: 'Cashier (Legacy)' },
]

const customers = ref([])
const form = reactive({
  name: '', email: '', password: '', role: 'sales', branch_id: null, customer_id: null,
  can_override_gold_rate: false, can_delete_transactions: false, is_active: true,
})

async function load() {
  const { data } = await axios.get('/api/users', { params: {
    search: search.value, role: filterRole.value, branch_id: filterBranch.value
  }})
  users.value = data.data
}

function openModal(user) {
  editing.value   = user
  formError.value = ''
  Object.assign(form, {
    name: user?.name ?? '', email: user?.email ?? '', password: '',
    role: user?.role ?? 'sales', branch_id: user?.branch_id ?? null,
    customer_id: user?.customer_id ?? null,
    can_override_gold_rate: user?.can_override_gold_rate ?? false,
    can_delete_transactions: user?.can_delete_transactions ?? false,
    is_active: user?.is_active ?? true,
  })
  showModal.value = true
}

function roleLabel(role) {
  return roleOptions.find(r => r.value === role)?.label ?? role
}

function roleClass(role) {
  return {
    owner:              'bg-slate-200 text-slate-800',
    admin:              'bg-purple-100 text-purple-700',
    sales:              'bg-blue-100 text-blue-700',
    estimator:          'bg-indigo-100 text-indigo-700',
    designer:           'bg-pink-100 text-pink-700',
    production_manager: 'bg-orange-100 text-orange-700',
    machine_operator:   'bg-amber-100 text-amber-700',
    store_keeper:       'bg-emerald-100 text-emerald-700',
    accountant:         'bg-teal-100 text-teal-700',
    dispatch_officer:   'bg-cyan-100 text-cyan-700',
    manager:            'bg-amber-100 text-amber-700',
    cashier:            'bg-gray-100 text-gray-600',
    client:             'bg-teal-100 text-teal-700',
  }[role] ?? 'bg-gray-100 text-gray-700'
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/users/${editing.value.id}`, form)
    } else {
      await axios.post('/api/users', form)
    }
    showModal.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { saving.value = false }
}

function deleteUser(user) {
  confirmDelete.value  = user
  confirmMessage.value = `Delete user "${user.name}"? This cannot be undone.`
}

async function doDelete() {
  try {
    await axios.delete(`/api/users/${confirmDelete.value.id}`)
    confirmDelete.value = null
    load()
  } catch (e) {
    formError.value     = e.response?.data?.message ?? 'Error deleting user'
    confirmDelete.value = null
  }
}

onMounted(async () => {
  const [, b, c] = await Promise.all([load(), axios.get('/api/branches'), axios.get('/api/customers/all')])
  branches.value  = b.data
  customers.value = c.data
})
</script>
