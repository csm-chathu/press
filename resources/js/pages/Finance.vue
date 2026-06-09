<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Finance Management</h2>
      <p class="text-sm text-gray-500">Manage employees, post salary payments, and record manual income/expense entries with accounting impact.</p>
    </div>

    <div class="card py-2 px-2">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          type="button"
          @click="activeTab = tab.value"
          class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors"
          :class="activeTab === tab.value
            ? 'bg-amber-500 text-white shadow-sm'
            : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-300 hover:text-amber-700'"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ error }}</p>
    <p v-if="message" class="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-2 rounded-lg">{{ message }}</p>

    <template v-if="activeTab === 'employee'">
      <div class="card space-y-3">
        <h3 class="font-semibold text-gray-700">Add Employee</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <input v-model="employeeForm.employee_code" class="form-input" placeholder="Employee code" />
          <input v-model="employeeForm.name" class="form-input" placeholder="Employee name *" />
          <input v-model="employeeForm.role" class="form-input" placeholder="Role" />
          <input v-model="employeeForm.phone" class="form-input" placeholder="Phone" />
          <input v-model.number="employeeForm.base_salary" type="number" min="0" step="0.01" class="form-input" placeholder="Base salary" />
          <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input v-model="employeeForm.is_active" type="checkbox" class="rounded text-amber-600" /> Active employee
          </label>
        </div>
        <button class="btn-primary" :disabled="savingEmployee" @click="createEmployee">
          {{ savingEmployee ? 'Saving...' : 'Save Employee' }}
        </button>
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">Employees</h3>
          <button class="btn-secondary py-1 px-3 text-xs" @click="loadEmployees">Refresh</button>
        </div>
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Code</th>
              <th class="table-th">Name</th>
              <th class="table-th">Role</th>
              <th class="table-th text-right">Salary</th>
              <th class="table-th">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="employee in employees.data" :key="employee.id">
              <td class="table-td font-mono text-xs">{{ employee.employee_code || '-' }}</td>
              <td class="table-td">{{ employee.name }}</td>
              <td class="table-td">{{ employee.role || '-' }}</td>
              <td class="table-td text-right">LKR {{ lkr(employee.base_salary) }}</td>
              <td class="table-td">
                <span :class="employee.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="badge">{{ employee.is_active ? 'Active' : 'Inactive' }}</span>
              </td>
            </tr>
            <tr v-if="!employees.data?.length"><td colspan="5" class="table-td text-center text-gray-400">No employees found</td></tr>
          </tbody>
        </table>
      </div>
    </template>

    <template v-else-if="activeTab === 'salary'">
      <div class="card space-y-3">
        <h3 class="font-semibold text-gray-700">Pay Salary</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <select v-model="salaryForm.employee_id" class="form-input">
            <option value="">Select employee *</option>
            <option v-for="e in allEmployees" :key="e.id" :value="e.id">{{ e.name }}{{ e.employee_code ? ` (${e.employee_code})` : '' }}</option>
          </select>
          <input v-model="salaryForm.period_month" type="month" class="form-input" />
          <input v-model="salaryForm.paid_at" type="date" class="form-input" />
          <input v-model.number="salaryForm.amount" type="number" min="0" step="0.01" class="form-input" placeholder="Amount *" />
          <select v-model="salaryForm.payment_method" class="form-input">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="card">Card</option>
            <option value="other">Other</option>
          </select>
          <input v-model="salaryForm.reference" class="form-input" placeholder="Reference" />
        </div>
        <textarea v-model="salaryForm.notes" rows="2" class="form-input" placeholder="Notes"></textarea>
        <button class="btn-primary" :disabled="savingSalary" @click="paySalary">
          {{ savingSalary ? 'Posting...' : 'Post Salary Payment' }}
        </button>
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">Recent Salary Payments</h3>
          <button class="btn-secondary py-1 px-3 text-xs" @click="loadSalaryPayments">Refresh</button>
        </div>
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Date</th>
              <th class="table-th">Employee</th>
              <th class="table-th">Period</th>
              <th class="table-th text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="payment in salaryPayments.data" :key="payment.id">
              <td class="table-td">{{ payment.paid_at ? formatDate(payment.paid_at) : '-' }}</td>
              <td class="table-td">{{ payment.employee?.name || '-' }}</td>
              <td class="table-td">{{ payment.period_month ? formatMonth(payment.period_month) : '-' }}</td>
              <td class="table-td text-right">LKR {{ lkr(payment.amount) }}</td>
            </tr>
            <tr v-if="!salaryPayments.data?.length"><td colspan="4" class="table-td text-center text-gray-400">No salary payments yet</td></tr>
          </tbody>
        </table>
      </div>
    </template>

    <template v-else>
      <div class="card space-y-3">
        <h3 class="font-semibold text-gray-700">Record Income / Expense</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <select v-model="incomeExpenseForm.type" class="form-input">
            <option value="income">Income</option>
            <option value="expense">Expense</option>
          </select>
          <input v-model="incomeExpenseForm.entry_date" type="date" class="form-input" />
          <input v-model.number="incomeExpenseForm.amount" type="number" min="0" step="0.01" class="form-input" placeholder="Amount *" />
          <input v-model="incomeExpenseForm.category" class="form-input" placeholder="Category" />
          <input v-model="incomeExpenseForm.description" class="form-input" placeholder="Description *" />
          <input v-model="incomeExpenseForm.reference" class="form-input" placeholder="Reference" />
          <select v-model="incomeExpenseForm.payment_method" class="form-input">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="card">Card</option>
            <option value="other">Other</option>
          </select>
          <input v-model="incomeExpenseForm.account_code" class="form-input" placeholder="Account code (optional)" />
        </div>
        <textarea v-model="incomeExpenseForm.notes" rows="2" class="form-input" placeholder="Notes"></textarea>
        <button class="btn-primary" :disabled="savingIncomeExpense" @click="saveIncomeExpense">
          {{ savingIncomeExpense ? 'Posting...' : 'Post Transaction' }}
        </button>
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">Recent Income / Expense Entries</h3>
          <button class="btn-secondary py-1 px-3 text-xs" @click="loadIncomeExpenses">Refresh</button>
        </div>
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Date</th>
              <th class="table-th">Type</th>
              <th class="table-th">Description</th>
              <th class="table-th">Category</th>
              <th class="table-th text-right">Amount</th>
              <th class="table-th">By</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="entry in incomeExpenses.data" :key="entry.id">
              <td class="table-td">{{ formatDate(entry.entry_date) }}</td>
              <td class="table-td">
                <span :class="entry.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="badge capitalize">{{ entry.type }}</span>
              </td>
              <td class="table-td">{{ entry.description }}</td>
              <td class="table-td">{{ entry.category || '-' }}</td>
              <td class="table-td text-right">LKR {{ lkr(entry.amount) }}</td>
              <td class="table-td">{{ entry.user?.name || '-' }}</td>
            </tr>
            <tr v-if="!incomeExpenses.data?.length"><td colspan="6" class="table-td text-center text-gray-400">No entries found</td></tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const employees = ref({ data: [] })
const allEmployees = ref([])
const salaryPayments = ref({ data: [] })
const incomeExpenses = ref({ data: [] })
const activeTab = ref('employee')
const tabs = [
  { value: 'employee', label: 'Employee' },
  { value: 'salary', label: 'Salary' },
  { value: 'income-expense', label: 'Income / Expense' },
]

const savingEmployee = ref(false)
const savingSalary = ref(false)
const savingIncomeExpense = ref(false)
const error = ref('')
const message = ref('')

const today = new Date()
const monthText = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
const todayText = today.toISOString().slice(0, 10)

const employeeForm = reactive({
  employee_code: '',
  name: '',
  role: '',
  phone: '',
  base_salary: 0,
  is_active: true,
})

const salaryForm = reactive({
  employee_id: '',
  period_month: monthText,
  paid_at: todayText,
  amount: 0,
  payment_method: 'cash',
  reference: '',
  notes: '',
})

const incomeExpenseForm = reactive({
  type: 'expense',
  entry_date: todayText,
  category: '',
  description: '',
  amount: 0,
  payment_method: 'cash',
  reference: '',
  account_code: '',
  notes: '',
})

function lkr(value) {
  return Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('en-LK')
}

function formatMonth(value) {
  const d = new Date(value)
  return d.toLocaleDateString('en-LK', { month: 'short', year: 'numeric' })
}

function clearMessages() {
  error.value = ''
  message.value = ''
}

async function loadEmployees() {
  const { data } = await axios.get('/api/finance/employees', { params: { per_page: 10 } })
  employees.value = data
}

async function loadEmployeesForSelect() {
  const { data } = await axios.get('/api/finance/employees', { params: { per_page: 100, is_active: true } })
  allEmployees.value = data.data || []
}

async function loadSalaryPayments() {
  const { data } = await axios.get('/api/finance/salary-payments', { params: { per_page: 10 } })
  salaryPayments.value = data
}

async function loadIncomeExpenses() {
  const { data } = await axios.get('/api/finance/income-expenses', { params: { per_page: 15 } })
  incomeExpenses.value = data
}

async function createEmployee() {
  clearMessages()
  savingEmployee.value = true
  try {
    await axios.post('/api/finance/employees', employeeForm)
    employeeForm.employee_code = ''
    employeeForm.name = ''
    employeeForm.role = ''
    employeeForm.phone = ''
    employeeForm.base_salary = 0
    employeeForm.is_active = true
    message.value = 'Employee saved successfully.'
    await Promise.all([loadEmployees(), loadEmployeesForSelect()])
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Failed to save employee.'
  } finally {
    savingEmployee.value = false
  }
}

async function paySalary() {
  clearMessages()
  if (!salaryForm.employee_id) {
    error.value = 'Select an employee before posting salary.'
    return
  }
  savingSalary.value = true
  try {
    const payload = {
      ...salaryForm,
      period_month: `${salaryForm.period_month}-01`,
    }
    await axios.post('/api/finance/salary-payments', payload)
    salaryForm.amount = 0
    salaryForm.reference = ''
    salaryForm.notes = ''
    message.value = 'Salary payment posted and journalized.'
    await Promise.all([loadSalaryPayments(), loadIncomeExpenses()])
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Failed to post salary payment.'
  } finally {
    savingSalary.value = false
  }
}

async function saveIncomeExpense() {
  clearMessages()
  savingIncomeExpense.value = true
  try {
    await axios.post('/api/finance/income-expenses', incomeExpenseForm)
    incomeExpenseForm.description = ''
    incomeExpenseForm.category = ''
    incomeExpenseForm.amount = 0
    incomeExpenseForm.reference = ''
    incomeExpenseForm.account_code = ''
    incomeExpenseForm.notes = ''
    message.value = 'Income/expense entry posted to accounts.'
    await loadIncomeExpenses()
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Failed to save income/expense.'
  } finally {
    savingIncomeExpense.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    loadEmployees(),
    loadEmployeesForSelect(),
    loadSalaryPayments(),
    loadIncomeExpenses(),
  ])
})
</script>
