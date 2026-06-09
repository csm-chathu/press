<template>
  <div class="space-y-5">

    <!-- Header + actions -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
          <input v-model="search" type="search" placeholder="Search table…"
            class="form-input pl-8 w-52" @input="debouncedFetch" />
        </div>
        <select v-model="statusFilter" class="form-input w-32" @change="fetchData">
          <option value="">All status</option>
          <option value="available">Available</option>
          <option value="occupied">Occupied</option>
          <option value="reserved">Reserved</option>
          <option value="maintenance">Maintenance</option>
        </select>
        <button v-if="search || statusFilter" @click="clearFilters"
          class="text-xs text-gray-400 hover:text-gray-600 underline">Clear</button>
      </div>
      <button @click="showNewForm = !showNewForm" type="button"
        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-sm shadow-sm transition-colors shrink-0">
        <PlusIcon class="w-4 h-4" /> {{ showNewForm ? 'Cancel' : 'Add Table' }}
      </button>
    </div>

    <!-- New table form -->
    <div v-if="showNewForm" class="card bg-blue-50 border border-blue-200 space-y-4">
      <h3 class="font-semibold text-gray-800 flex items-center gap-2">
        <UserPlusIcon class="w-4 h-4 text-blue-600" /> Add New Table
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
          <label class="form-label">Table Number <span class="text-red-500">*</span></label>
          <input v-model="newTable.table_number" type="text" placeholder="e.g., T1, A5, Counter"
            class="form-input" />
        </div>
        <div>
          <label class="form-label">Capacity <span class="text-red-500">*</span></label>
          <input v-model.number="newTable.capacity" type="number" min="1" placeholder="e.g., 4"
            class="form-input" />
        </div>
        <div>
          <label class="form-label">Status</label>
          <select v-model="newTable.status" class="form-input">
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="reserved">Reserved</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>
        <div>
          <label class="form-label">Notes</label>
          <input v-model="newTable.notes" type="text" placeholder="Optional notes"
            class="form-input" />
        </div>
        <div class="flex items-end gap-2">
          <button @click="saveNewTable" :disabled="savingNew || !newTable.table_number || !newTable.capacity"
            class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg font-semibold text-sm transition-colors">
            <ArrowPathIcon v-if="savingNew" class="w-4 h-4 animate-spin" />
            <CheckCircleIcon v-else class="w-4 h-4" />
            Create
          </button>
        </div>
      </div>
      <p v-if="newTableError" class="text-sm text-red-600">{{ newTableError }}</p>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
          <span class="text-lg font-bold text-blue-600">{{ tables.total ?? 0 }}</span>
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Tables</p>
          <p class="text-2xl font-bold text-gray-800">{{ tables.total ?? 0 }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
          <CheckCircleIcon class="w-5 h-5 text-green-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Available</p>
          <p class="text-2xl font-bold text-green-700">{{ availableCount }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
          <UserIcon class="w-5 h-5 text-orange-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Occupied</p>
          <p class="text-2xl font-bold text-orange-700">{{ occupiedCount }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
          <CalendarDaysIcon class="w-5 h-5 text-purple-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Reserved</p>
          <p class="text-2xl font-bold text-purple-700">{{ reservedCount }}</p>
        </div>
      </div>
    </div>

    <!-- Table list -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th w-32">Table Number</th>
              <th class="table-th w-20">Capacity</th>
              <th class="table-th w-32">Status</th>
              <th class="table-th">Notes</th>
              <th class="table-th w-28 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading">
              <td colspan="5" class="table-td text-center py-10 text-gray-400">
                <div class="flex items-center justify-center gap-2">
                  <ArrowPathIcon class="w-4 h-4 animate-spin" /> Loading…
                </div>
              </td>
            </tr>
            <template v-else>
              <tr v-for="t in tables.data" :key="t.id"
                class="hover:bg-amber-50/40 transition-colors cursor-default group"
                @click="editingId === t.id || (editingId = null)">
                <td class="table-td">
                  <div v-if="editingId === t.id" class="flex items-center gap-2">
                    <input v-model="editForm.table_number" type="text" class="form-input text-sm flex-1" />
                  </div>
                  <span v-else class="font-bold text-gray-800">{{ t.table_number }}</span>
                </td>
                <td class="table-td">
                  <div v-if="editingId === t.id" class="flex items-center gap-2">
                    <input v-model.number="editForm.capacity" type="number" min="1" class="form-input text-sm w-20" />
                  </div>
                  <span v-else class="font-medium text-gray-700">{{ t.capacity }} seats</span>
                </td>
                <td class="table-td">
                  <div v-if="editingId === t.id">
                    <select v-model="editForm.status" class="form-input text-sm">
                      <option value="available">Available</option>
                      <option value="occupied">Occupied</option>
                      <option value="reserved">Reserved</option>
                      <option value="maintenance">Maintenance</option>
                    </select>
                  </div>
                  <span v-else :class="statusBadge(t.status)" class="badge capitalize">{{ t.status }}</span>
                </td>
                <td class="table-td text-sm text-gray-600">
                  <div v-if="editingId === t.id" class="flex items-center gap-2">
                    <input v-model="editForm.notes" type="text" class="form-input text-sm flex-1" />
                  </div>
                  <span v-else>{{ t.notes || '—' }}</span>
                </td>
                <td class="table-td text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button v-if="editingId === t.id" @click.stop="saveEdit(t)"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 hover:bg-green-200">
                      <CheckCircleIcon class="w-3.5 h-3.5" />
                    </button>
                    <button v-else @click.stop="startEdit(t)"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                      <PencilSquareIcon class="w-3.5 h-3.5" />
                    </button>
                    <button @click.stop="deleteTable(t)"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!tables.data?.length">
                <td colspan="5" class="table-td text-center py-12">
                  <div class="flex flex-col items-center gap-2 text-gray-400">
                    <TableCellsIcon class="w-10 h-10 opacity-30" />
                    <span>No tables found</span>
                    <button @click="showNewForm = true" class="text-amber-600 hover:underline text-sm font-medium">Create your first table →</button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-5 py-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-sm text-gray-500 bg-gray-50/50">
        <span class="text-xs">
          Showing <strong class="text-gray-700">{{ tables.from ?? 0 }}–{{ tables.to ?? 0 }}</strong>
          of <strong class="text-gray-700">{{ tables.total ?? 0 }}</strong> records
        </span>
        <div class="flex items-center gap-1">
          <button @click="page--; fetchData()" :disabled="page<=1"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border border-gray-200 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            <ChevronLeftIcon class="w-3.5 h-3.5" /> Prev
          </button>
          <span class="px-3 py-1.5 text-xs font-semibold text-gray-700">Page {{ page }} / {{ tables.last_page ?? 1 }}</span>
          <button @click="page++; fetchData()" :disabled="page>=(tables.last_page ?? 1)"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border border-gray-200 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            Next <ChevronRightIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import axios from 'axios'
import {
  PlusIcon, TrashIcon, MagnifyingGlassIcon,
  CheckCircleIcon, ArrowPathIcon, ChevronLeftIcon, ChevronRightIcon,
  UserIcon, UserPlusIcon, CalendarDaysIcon, PencilSquareIcon,
  TableCellsIcon,
} from '@heroicons/vue/24/outline'
import ConfirmModal from '@/components/ConfirmModal.vue'

const tables          = ref({ data: [] })
const search          = ref('')
const page            = ref(1)
const statusFilter    = ref('')
const loading         = ref(false)
const confirmDelete   = ref(null)
const confirmMessage  = ref('')
const showNewForm     = ref(false)
const savingNew       = ref(false)
const newTableError   = ref('')
const editingId       = ref(null)
const editForm        = reactive({})

const newTable = reactive({
  table_number: '',
  capacity: 4,
  status: 'available',
  notes: '',
})

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value = 1; fetchData() }, 400) }

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/tables', {
      params: {
        page:    page.value,
        search:  search.value,
        status:  statusFilter.value,
      },
    })
    tables.value = data
  } finally { loading.value = false }
}

function clearFilters() {
  search.value = ''; statusFilter.value = ''
  page.value = 1; fetchData()
}

async function saveNewTable() {
  savingNew.value = true; newTableError.value = ''
  try {
    await axios.post('/api/tables', {
      table_number: newTable.table_number,
      capacity: newTable.capacity,
      status: newTable.status,
      notes: newTable.notes || null,
    })
    newTable.table_number = ''
    newTable.capacity = 4
    newTable.status = 'available'
    newTable.notes = ''
    showNewForm.value = false
    fetchData()
  } catch (e) {
    newTableError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Could not save table'
  } finally { savingNew.value = false }
}

function startEdit(table) {
  editingId.value = table.id
  Object.assign(editForm, {
    table_number: table.table_number,
    capacity: table.capacity,
    status: table.status,
    notes: table.notes,
  })
}

async function saveEdit(table) {
  try {
    await axios.put(`/api/tables/${table.id}`, editForm)
    editingId.value = null
    fetchData()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not update table')
  }
}

function deleteTable(table) {
  confirmDelete.value  = table
  confirmMessage.value = `Delete table ${table.table_number}? This cannot be undone.`
}

async function doDelete() {
  try {
    await axios.delete(`/api/tables/${confirmDelete.value.id}`)
    confirmDelete.value = null
    fetchData()
  } catch (e) {
    confirmDelete.value = null
  }
}

const availableCount = computed(() => (tables.value.data ?? []).filter(t => t.status === 'available').length)
const occupiedCount = computed(() => (tables.value.data ?? []).filter(t => t.status === 'occupied').length)
const reservedCount = computed(() => (tables.value.data ?? []).filter(t => t.status === 'reserved').length)

function statusBadge(status) {
  return {
    available:   'bg-green-100 text-green-700',
    occupied:    'bg-orange-100 text-orange-700',
    reserved:    'bg-purple-100 text-purple-700',
    maintenance: 'bg-red-100 text-red-700',
  }[status] ?? 'bg-gray-100 text-gray-700'
}

onMounted(fetchData)
</script>
