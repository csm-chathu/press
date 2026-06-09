<template>
  <div class="space-y-0">

    <!-- Top bar -->
    <div class="flex items-center justify-between mb-5 gap-4">
      <div class="flex items-center gap-3">
        <router-link to="/sales"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
          <ArrowLeftIcon class="w-4 h-4" /> Back to Bills
        </router-link>
        <span class="text-gray-300">/</span>
        <h2 class="text-lg font-semibold text-gray-800">Edit Draft Bill</h2>
      </div>

      <!-- Draft Bill Compact Info (Top Right) -->
      <div class="rounded-lg border border-amber-200 overflow-hidden bg-white min-w-[260px]">
        <table class="w-full text-xs">
          <tbody>
            <tr class="border-b border-amber-100">
              <th class="px-2 py-1.5 text-left font-semibold text-amber-700 bg-amber-50 w-24">Invoice</th>
              <td class="px-2 py-1.5 font-mono text-gray-700">{{ sale.invoice_number }}</td>
            </tr>
            <tr>
              <th class="px-2 py-1.5 text-left font-semibold text-amber-700 bg-amber-50">Table</th>
              <td class="px-2 py-1.5 text-gray-600">{{ form.table_number || 'Not assigned' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- POS layout: Cart left | Summary right -->
    <div class="flex gap-5 items-start">

      <!-- ───── LEFT: Cart ───── -->
      <div class="flex-1 min-w-0 space-y-4">

        <!-- Cart header -->
        <div class="card">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
              <ShoppingCartIcon class="w-4 h-4 text-amber-500" /> Bill Items
              <span v-if="form.items.length" class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ form.items.length }}
              </span>
            </h3>
            <button @click="addItem"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition-colors">
              <PlusIcon class="w-4 h-4" /> Add Line
            </button>
          </div>

          <!-- Barcode scanner input -->
          <div class="mb-3">
            <div class="relative">
              <QrCodeIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
              <input
                v-model="barcodeInput"
                type="text"
                placeholder="Scan barcode (or type barcode/SKU) and press Enter…"
                class="form-input pl-9 text-sm font-mono"
                @keyup.enter="scanBarcode"
                @keyup.tab.prevent="scanBarcode"
              />
            </div>
            <div class="mt-2 flex items-center justify-between gap-3">
              <p class="text-xs text-gray-500">
                Use a handheld scanner here, or open the camera scanner on supported browsers.
              </p>
              <button type="button" @click="openScanner"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100">
                <QrCodeIcon class="w-4 h-4" /> Camera Scanner
              </button>
            </div>
            <p v-if="barcodeError" class="mt-1 text-xs text-red-600 flex items-center gap-1">
              <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ barcodeError }}
            </p>
          </div>

          <!-- Cart item rows -->
          <div class="space-y-3">
            <div v-for="(item, i) in form.items" :key="i" class="border border-gray-200 rounded-xl overflow-hidden">

              <!-- Item header row -->
              <div class="bg-gray-50 px-4 py-2.5 flex items-center gap-3 border-b border-gray-100">
                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 text-xs font-bold flex items-center justify-center shrink-0">{{ i + 1 }}</span>
                <img
                  v-if="item.product_ref?.image"
                  :src="item.product_ref.image"
                  alt="Product"
                  class="w-9 h-9 rounded-md object-cover border border-gray-200 shrink-0"
                />
                <div v-else class="w-9 h-9 rounded-md bg-gray-100 border border-gray-200 shrink-0"></div>
                <div class="flex-1 space-y-1">
                  <input
                    v-model="item.product_search"
                    type="text"
                    placeholder="Search product by name / SKU / barcode"
                    class="form-input text-xs"
                    @keydown.enter.prevent="openProductDropdown(i)"
                  />
                  <select :id="`product-select-${i}`" v-model="item.product_id" class="form-input font-medium" @change="fillProduct(item)">
                    <option value="">— Select product —</option>
                    <optgroup v-for="cat in getGroupedProductsForItem(item)" :key="cat.label" :label="cat.label">
                      <option v-for="p in cat.items" :key="p.id" :value="p.id">
                        {{ p.name }} · Stock: {{ p.stock_quantity }}
                      </option>
                    </optgroup>
                  </select>
                </div>
                <button @click="removeItem(i)"
                  class="w-7 h-7 flex items-center justify-center rounded-full text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors shrink-0">
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>

              <!-- Item fields -->
              <div class="px-4 py-3 space-y-3">
                <div class="grid grid-cols-3 gap-3">
                  <div>
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Quantity</label>
                    <input v-model.number="item.quantity" type="number" min="1" class="form-input text-center font-semibold" @input="recalcItem(item)" />
                  </div>
                  <div>
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Unit Price (LKR)</label>
                    <input v-model.number="item.unit_price" type="number" min="0" class="form-input" @input="recalcItem(item)" />
                  </div>
                  <div>
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Item Discount (LKR)</label>
                    <input v-model.number="item.discount" type="number" min="0" class="form-input" @input="recalcItem(item)" />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3" v-if="item.product_ref?.bottle_deposit_required || item.product_ref?.product_type === 'Liquor'">
                  <div v-if="item.product_ref?.bottle_deposit_required" class="bg-amber-50 border border-amber-100 rounded-lg p-2.5">
                    <label class="text-xs font-medium text-gray-600 flex items-center gap-2">
                      <input type="checkbox" v-model="item.empty_bottle_returned" @change="recalcItem(item)" class="rounded text-amber-600" />
                      Empty bottle returned now?
                    </label>
                  </div>
                  <div v-if="item.product_ref?.bottle_deposit_required">
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Deposit/Bottle (LKR)</label>
                    <input v-model.number="item.bottle_deposit_amount" type="number" min="0" step="0.01" class="form-input" @input="recalcItem(item)" />
                  </div>
                  <div v-if="item.product_ref?.product_type === 'Liquor'">
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Serving ml (shots)</label>
                    <div class="flex gap-2">
                      <input v-model.number="item.serving_ml" type="number" min="0" step="1" class="form-input flex-1" placeholder="30 / 50 / 60" @input="recalcItem(item)" />
                    </div>
                    <div class="flex gap-1.5 mt-1.5">
                      <button v-for="size in [30, 50, 60, 75]" :key="size" @click="item.serving_ml = size; recalcItem(item)" type="button"
                        class="px-2.5 py-1 text-xs font-semibold rounded-md transition-colors"
                        :class="item.serving_ml === size ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-200'">
                        {{ size }}ml
                      </button>
                    </div>
                  </div>
                </div>

                <div v-if="item.unit_price > 0" class="text-right text-sm text-gray-500">
                  Line Total: <strong class="text-amber-700">LKR {{ lkr(item._lineTotal) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="card">
          <label class="form-label flex items-center gap-1.5 mb-2">
            <ChatBubbleLeftIcon class="w-4 h-4 text-gray-400" /> Order Notes (optional)
          </label>
          <textarea v-model="form.notes" rows="2" placeholder="Any special instructions or remarks…" class="form-input resize-none"></textarea>
        </div>
      </div>

      <!-- ───── RIGHT: Order summary ───── -->
      <div class="w-80 xl:w-96 shrink-0 space-y-4 sticky top-4">

        <!-- Customer -->
        <div class="card space-y-2">
          <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
              <UserIcon class="w-4 h-4 text-blue-500" /> Guest / Table
            </h3>
            <button @click="showNewCustomer = !showNewCustomer" type="button"
              class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-md transition-colors"
              :class="showNewCustomer ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
              <UserPlusIcon class="w-3.5 h-3.5" />
              {{ showNewCustomer ? 'Cancel' : 'New Guest' }}
            </button>
          </div>

          <!-- Quick new-customer form -->
          <div v-if="showNewCustomer" class="bg-blue-50 border border-blue-200 rounded-lg p-3 space-y-2">
            <p class="text-xs font-semibold text-blue-700 mb-1 flex items-center gap-1.5">
              <UserPlusIcon class="w-3.5 h-3.5" /> Quick Add Guest
            </p>
            <div>
              <label class="text-xs text-gray-500 block mb-1">Name <span class="text-red-400">*</span></label>
              <input v-model="newCustomer.name" type="text" placeholder="Full name"
                class="form-input text-sm" @keyup.enter="saveNewCustomer" />
            </div>
            <div>
              <label class="text-xs text-gray-500 block mb-1">Phone</label>
              <input v-model="newCustomer.phone" type="tel" placeholder="Phone number"
                class="form-input text-sm" @keyup.enter="saveNewCustomer" />
            </div>
            <div>
              <label class="text-xs text-gray-500 block mb-1">Email</label>
              <input v-model="newCustomer.email" type="email" placeholder="Email (optional)"
                class="form-input text-sm" />
            </div>
            <p v-if="newCustomerError" class="text-xs text-red-600">{{ newCustomerError }}</p>
            <button @click="saveNewCustomer" :disabled="savingCustomer || !newCustomer.name.trim()" type="button"
              class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors">
              <ArrowPathIcon v-if="savingCustomer" class="w-3.5 h-3.5 animate-spin" />
              <CheckCircleIcon v-else class="w-3.5 h-3.5" />
              {{ savingCustomer ? 'Saving…' : 'Save & Select' }}
            </button>
          </div>

          <!-- Existing customer selector -->
          <select v-if="!showNewCustomer" v-model="form.customer_id" class="form-input">
            <option value="">Walk-in / No customer</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">
              {{ c.name }}{{ c.phone ? ` · ${c.phone}` : '' }}
            </option>
          </select>

          <!-- Selected customer chip -->
          <div v-if="!showNewCustomer && selectedCustomer" class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-2.5 py-1.5">
            <div class="w-6 h-6 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 text-xs font-bold shrink-0">
              {{ selectedCustomer.name[0].toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-blue-800 truncate">{{ selectedCustomer.name }}</p>
              <p v-if="selectedCustomer.phone" class="text-xs text-blue-500">{{ selectedCustomer.phone }}</p>
            </div>
          </div>

          <!-- Table number (for restaurant orders) -->
          <div>
            <label class="text-xs font-medium text-gray-500 mb-1 block flex items-center justify-between">
              <span>Table Number (optional)</span>
              <router-link to="/tables" class="text-xs text-amber-600 hover:underline font-semibold">Manage Tables →</router-link>
            </label>
            <div class="flex gap-2 mb-2">
              <select v-model="form.table_number" class="form-input flex-1">
                <option value="">Select table or type</option>
                <option v-for="t in availableTables" :key="t.id" :value="t.table_number">
                  {{ t.table_number }} ({{ t.capacity }} seats)
                </option>
              </select>
            </div>
            <input v-model="form.table_number" type="text" placeholder="Or type table number" class="form-input" />
          </div>

          <div v-if="selectedCustomer?.id_number && !selectedCustomer?.kyc_verified"
            class="text-xs bg-yellow-50 border border-yellow-200 text-yellow-700 rounded px-2 py-1.5 flex items-center gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> KYC not verified for this customer
          </div>
        </div>

        <!-- Payment -->
        <div class="card space-y-3">
          <div class="flex items-center justify-between gap-3 mb-1">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
              <CreditCardIcon class="w-4 h-4 text-green-500" /> Payment
            </h3>
            <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
              <input v-model="useSplitPayments" type="checkbox" class="rounded text-amber-600" @change="handleSplitToggle" />
              Split Bill
            </label>
          </div>
          <div v-if="!useSplitPayments">
            <label class="form-label">Method</label>
            <select v-model="form.payment_method" class="form-input">
              <option v-for="option in paymentOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </div>
          <div v-else class="space-y-3 rounded-xl border border-amber-200 bg-amber-50/60 p-3">
            <div class="flex items-center justify-between gap-2">
              <p class="text-xs text-gray-600">Split this bill across multiple payment methods or payers.</p>
              <div class="flex gap-2">
                <button type="button" @click="splitPaymentsEvenly"
                  class="px-2.5 py-1 rounded-md border border-amber-200 bg-white text-xs font-semibold text-amber-700 hover:bg-amber-100">
                  Split Evenly
                </button>
                <button type="button" @click="addPaymentRow()"
                  class="px-2.5 py-1 rounded-md bg-amber-500 text-xs font-semibold text-white hover:bg-amber-600">
                  Add Payment
                </button>
              </div>
            </div>
            <div v-for="(payment, index) in paymentRows" :key="index" class="grid grid-cols-12 gap-2 items-end">
              <div class="col-span-4">
                <label class="text-xs font-medium text-gray-500 mb-1 block">Method</label>
                <select v-model="payment.payment_method" class="form-input">
                  <option v-for="option in paymentOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>
              <div class="col-span-4">
                <label class="text-xs font-medium text-gray-500 mb-1 block">Amount (LKR)</label>
                <input v-model.number="payment.amount" type="number" min="0" step="0.01" class="form-input" />
              </div>
              <div class="col-span-3">
                <label class="text-xs font-medium text-gray-500 mb-1 block">Note</label>
                <input v-model="payment.notes" type="text" class="form-input" placeholder="optional" />
              </div>
              <div class="col-span-1">
                <button type="button" @click="removePaymentRow(index)"
                  class="w-10 h-10 rounded-lg border border-red-200 text-red-500 hover:bg-red-50">
                  ×
                </button>
              </div>
            </div>
            <div class="flex items-center justify-between text-xs font-medium text-gray-600">
              <span>Collected: LKR {{ lkr(totalPaid) }}</span>
              <span>Remaining: LKR {{ lkr(balanceDue) }}</span>
            </div>
          </div>
          <div>
            <label class="form-label">Bill Discount (LKR)</label>
            <input v-model.number="form.discount" type="number" min="0" class="form-input" @input="recalc" />
          </div>

          <!-- Tax -->
          <div class="border-t pt-3">
            <div class="flex items-center justify-between mb-2">
              <label class="form-label mb-0 flex items-center gap-1.5">
                <CalculatorIcon class="w-3.5 h-3.5 text-gray-400" /> Tax
              </label>
              <select v-model="selectedTaxId" class="form-input text-xs py-1 w-32" @change="applyTax">
                <option value="">No Tax</option>
                <option v-for="t in taxes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.rate }}%)</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-xs text-gray-400">Rate (%)</label>
                <input v-model.number="form.tax_rate" type="number" min="0" step="0.01" class="form-input mt-1" @input="recalc" />
              </div>
              <div>
                <label class="text-xs text-gray-400">Tax Amount (LKR)</label>
                <input v-model.number="form.tax" type="number" class="form-input mt-1 bg-gray-50 text-gray-500" readonly />
              </div>
            </div>
          </div>
        </div>

        <!-- Order total card (dark) -->
        <div class="card bg-gray-800 text-white">
          <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-1.5">
            <ReceiptPercentIcon class="w-3.5 h-3.5" /> Bill Summary
          </h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-400">Subtotal</span>
              <span class="font-medium">LKR {{ lkr(subtotal) }}</span>
            </div>
            <div v-if="form.discount > 0" class="flex justify-between">
              <span class="text-gray-400">Discount</span>
              <span class="text-red-400">-LKR {{ lkr(form.discount) }}</span>
            </div>
            <div v-if="form.tax > 0" class="flex justify-between">
              <span class="text-gray-400">Tax ({{ form.tax_rate }}%)</span>
              <span class="text-blue-300">+LKR {{ lkr(form.tax) }}</span>
            </div>
            <div class="border-t border-gray-600 pt-2 flex justify-between text-lg font-bold">
              <span>Total</span>
              <span class="text-amber-400">LKR {{ lkr(total) }}</span>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-600">
            <template v-if="!useSplitPayments">
              <label class="text-xs text-gray-400 mb-1 block">Amount Paid (LKR)</label>
              <input v-model.number="form.amount_paid" type="number" min="0"
                class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </template>
            <div v-else class="rounded-lg border border-gray-600 bg-gray-700/60 px-3 py-2 text-sm space-y-1">
              <div class="flex justify-between">
                <span class="text-gray-300">Total Paid</span>
                <span class="font-semibold text-white">LKR {{ lkr(totalPaid) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-300">Balance Due</span>
                <span class="font-semibold text-yellow-300">LKR {{ lkr(balanceDue) }}</span>
              </div>
            </div>
          </div>
          <div v-if="balanceDue > 0"
            class="mt-2 text-xs text-yellow-300 bg-yellow-900/40 rounded px-2 py-1.5 flex items-center gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
            Balance due: LKR {{ lkr(balanceDue) }}
          </div>
          <div v-if="changeDue > 0"
            class="mt-2 text-xs text-green-300 bg-green-900/40 rounded px-2 py-1.5 flex items-center gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
            Change to return: LKR {{ lkr(changeDue) }}
          </div>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg flex items-center gap-2">
          <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ error }}
        </p>

        <!-- Submit buttons -->
        <div class="flex gap-2">
          <button @click="saveDraft" :disabled="saving || !form.items.length"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-500 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg font-semibold text-sm shadow transition-colors">
            <CheckCircleIcon v-if="!saving" class="w-4 h-4" />
            <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
            Save Changes
          </button>
          <button @click="completeSale" :disabled="saving || !form.items.length"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg font-bold text-sm shadow-md transition-colors">
            <CheckCircleIcon v-if="!saving" class="w-5 h-5" />
            <ArrowPathIcon v-else class="w-5 h-5 animate-spin" />
            {{ saving ? 'Processing…' : 'Complete Sale' }}
          </button>
        </div>
        <p class="text-center text-xs text-gray-400">
          {{ form.items.length }} item{{ form.items.length !== 1 ? 's' : '' }} · Total: LKR {{ lkr(total) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import {
  ArrowLeftIcon, PlusIcon, XMarkIcon,
  ShoppingCartIcon, UserIcon, UserPlusIcon, CreditCardIcon, CalculatorIcon,
  ReceiptPercentIcon, CheckCircleIcon, ArrowPathIcon,
  ExclamationTriangleIcon, ChatBubbleLeftIcon, QrCodeIcon,
} from '@heroicons/vue/24/outline'

const router        = useRouter()
const route         = useRoute()
const products      = ref([])
const customers     = ref([])
const availableTables = ref([])
const taxes         = ref([])
const saving        = ref(false)
const error         = ref('')
const selectedTaxId    = ref('')
const showNewCustomer  = ref(false)
const savingCustomer   = ref(false)
const newCustomerError = ref('')
const newCustomer      = reactive({ name: '', phone: '', email: '' })
const sale             = reactive({})
const paymentOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Card' },
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'other', label: 'Other' },
]

// Barcode scanner
const barcodeInput = ref('')
const barcodeError = ref('')
let barcodeClearTimer = null

function processBarcode(code) {
  const normalized = (code ?? '').trim()
  barcodeInput.value = ''
  if (!normalized) return

  const product = products.value.find(p =>
    p.barcode?.toLowerCase() === normalized.toLowerCase() ||
    p.sku?.toLowerCase() === normalized.toLowerCase()
  )
  if (!product) {
    barcodeError.value = `Barcode/SKU "${normalized}" not found`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    return
  }

  const existing = form.items.find(i => i.product_id == product.id)
  if (existing) {
    existing.quantity++
    recalcItem(existing)
  } else {
    const item = newItem()
    item.product_id = product.id
    form.items.push(item)
    fillProduct(item)
  }
  barcodeError.value = ''
}

function scanBarcode() {
  processBarcode(barcodeInput.value)
}

const form = reactive({
  customer_id: '', payment_method: 'cash', amount_paid: 0, discount: 0, tax: 0, tax_rate: 0, notes: '',
  table_number: '',
  items: [],
})
const useSplitPayments = ref(false)
const paymentRows = ref([newPaymentRow()])

// Group products by category for optgroup display
const groupedProducts = computed(() => {
  const map = {}
  for (const p of products.value) {
    const cat = p.category?.name ?? 'Uncategorized'
    if (!map[cat]) map[cat] = { label: cat, items: [] }
    map[cat].items.push(p)
  }
  return Object.values(map)
})

const selectedCustomer = computed(() =>
  customers.value.find(c => c.id == form.customer_id) ?? null
)

async function saveNewCustomer() {
  if (!newCustomer.name.trim()) return
  savingCustomer.value = true; newCustomerError.value = ''
  try {
    const { data } = await axios.post('/api/customers', {
      name:  newCustomer.name.trim(),
      phone: newCustomer.phone.trim() || null,
      email: newCustomer.email.trim() || null,
    })
    customers.value.unshift(data)
    form.customer_id = data.id
    showNewCustomer.value = false
    newCustomer.name = ''; newCustomer.phone = ''; newCustomer.email = ''
  } catch (e) {
    newCustomerError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Could not save customer'
  } finally { savingCustomer.value = false }
}

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function newPaymentRow(paymentMethod = 'cash', amount = 0, notes = '') {
  return {
    payment_method: paymentMethod,
    amount,
    notes,
  }
}

function addPaymentRow() {
  paymentRows.value.push(newPaymentRow(form.payment_method || 'cash', 0, ''))
}

function removePaymentRow(index) {
  if (paymentRows.value.length === 1) {
    paymentRows.value = [newPaymentRow(form.payment_method || 'cash', 0, '')]
    return
  }
  paymentRows.value.splice(index, 1)
}

function handleSplitToggle() {
  if (useSplitPayments.value) {
    const startingAmount = Number(form.amount_paid || 0) > 0 ? Number(form.amount_paid || 0) : total.value
    paymentRows.value = [newPaymentRow(form.payment_method || 'cash', startingAmount, '')]
    return
  }

  form.amount_paid = totalPaid.value > 0 ? totalPaid.value : total.value
  paymentRows.value = [newPaymentRow(form.payment_method || 'cash', 0, '')]
}

function splitPaymentsEvenly() {
  if (!paymentRows.value.length) {
    paymentRows.value = [newPaymentRow(form.payment_method || 'cash', total.value, '')]
    return
  }

  const rowCount = paymentRows.value.length
  const evenShare = rowCount > 0 ? Math.floor((total.value / rowCount) * 100) / 100 : total.value
  let remaining = Math.round(total.value * 100) / 100

  paymentRows.value = paymentRows.value.map((payment, index) => {
    const amount = index === rowCount - 1 ? remaining : evenShare
    remaining = Math.round((remaining - amount) * 100) / 100
    return {
      ...payment,
      amount,
    }
  })
}

function newItem() {
  return {
    product_id: '', product_search: '', quantity: 1, unit_price: 0, discount: 0,
    empty_bottle_returned: false, bottle_deposit_amount: 100, serving_ml: 0,
    product_ref: null, _lineTotal: 0,
  }
}

function addItem()     { form.items.push(newItem()) }
function removeItem(i) { form.items.splice(i, 1); recalc() }

function fillProduct(item) {
  const p = products.value.find(x => x.id == item.product_id)
  if (!p) { item.product_ref = null; return }
  item.product_ref = p
  item.product_search = p.name || ''
  item.unit_price  = p.selling_price
  item.empty_bottle_returned = false
  item.bottle_deposit_amount = 100
  item.serving_ml = 0

  recalcItem(item)
}

function openProductDropdown(index) {
  const el = document.getElementById(`product-select-${index}`)
  if (!el) return

  el.focus()
  if (typeof el.showPicker === 'function') {
    try {
      el.showPicker()
      return
    } catch {
      // Fallback below for browsers that block showPicker.
    }
  }

  el.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
}

function getGroupedProductsForItem(item) {
  const search = String(item?.product_search || '').trim().toLowerCase()
  const map = {}

  for (const p of products.value) {
    if (search) {
      const haystack = `${p.name || ''} ${p.sku || ''} ${p.barcode || ''} ${p.brand || ''}`.toLowerCase()
      if (!haystack.includes(search)) continue
    }

    const cat = p.category?.name ?? 'Uncategorized'
    if (!map[cat]) map[cat] = { label: cat, items: [] }
    map[cat].items.push(p)
  }

  return Object.values(map)
}

function recalcItem(item) {
  const effectiveUnitPrice = getEffectiveUnitPrice(item)
  const depositAdd = item.product_ref?.bottle_deposit_required && !item.empty_bottle_returned
    ? (Number(item.bottle_deposit_amount || 0) * Number(item.quantity || 0))
    : 0
  item._lineTotal = (effectiveUnitPrice * item.quantity) - (item.discount || 0) + depositAdd
  recalc()
}

function getEffectiveUnitPrice(item) {
  const baseUnitPrice = Number(item.unit_price || 0)
  const servingMl = Number(item.serving_ml || 0)
  const isLiquor = String(item.product_ref?.product_type || '').toLowerCase() === 'liquor'

  if (!isLiquor || servingMl <= 0) {
    return baseUnitPrice
  }

  const baseMl = parseBaseUnitMl(item.product_ref?.base_unit)
  if (baseMl <= 0) {
    return baseUnitPrice
  }

  return Math.round(((baseUnitPrice / baseMl) * servingMl) * 100) / 100
}

function parseBaseUnitMl(baseUnit) {
  const text = String(baseUnit || '').trim().toLowerCase()
  if (!text) return 0

  const mlMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*ml/)
  if (mlMatch) return Number(mlMatch[1]) || 0

  const lMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*l\b/)
  if (lMatch) return (Number(lMatch[1]) || 0) * 1000

  return 0
}

const subtotal   = computed(() => form.items.reduce((s, i) => s + (i._lineTotal || 0), 0))
const total      = computed(() => Math.max(0, subtotal.value - (form.discount || 0) + (form.tax || 0)))
const totalPaid  = computed(() => useSplitPayments.value
  ? paymentRows.value.reduce((sum, payment) => sum + Number(payment.amount || 0), 0)
  : Number(form.amount_paid || 0))
const balanceDue = computed(() => Math.max(0, total.value - totalPaid.value))
const changeDue  = computed(() => Math.max(0, totalPaid.value - total.value))

function recalc() {
  if (form.tax_rate > 0) {
    form.tax = Math.round(subtotal.value * (form.tax_rate / 100) * 100) / 100
  }
  if (!useSplitPayments.value) {
    form.amount_paid = total.value
  }
}

function applyTax() {
  const t = taxes.value.find(x => x.id == selectedTaxId.value)
  if (t) { form.tax_rate = t.rate; recalc() }
  else   { form.tax_rate = 0; form.tax = 0 }
}

async function saveDraft() {
  saving.value = true; error.value = ''
  try {
    await axios.put(`/api/sales/${route.params.id}`, {
      customer_id:    form.customer_id || null,
      discount:       form.discount,
      tax:            form.tax,
      tax_rate:       form.tax_rate,
      table_number:   form.table_number || null,
      notes:          form.notes,
      items: form.items.map(i => ({
        product_id:     i.product_id,
        quantity:       i.quantity,
        unit_price:     getEffectiveUnitPrice(i),
        discount:       i.discount,
        empty_bottle_returned: i.empty_bottle_returned,
        bottle_deposit_amount: i.bottle_deposit_amount,
        serving_ml: i.serving_ml,
      })),
    })
    router.push('/sales')
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'An error occurred. Please try again.'
  } finally { saving.value = false }
}

async function completeSale() {
  saving.value = true; error.value = ''
  try {
    const payments = !useSplitPayments.value
      ? []
      : paymentRows.value
          .map(payment => ({
            payment_method: payment.payment_method,
            amount: Number(payment.amount || 0),
            notes: payment.notes?.trim() || null,
          }))
          .filter(payment => payment.amount > 0)

    if (useSplitPayments.value && !payments.length) {
      throw new Error('Add at least one split payment amount before completing the sale.')
    }

    await axios.post('/api/sales', {
      customer_id:    form.customer_id || null,
      payment_method: useSplitPayments.value ? (payments[0]?.payment_method ?? form.payment_method) : form.payment_method,
      payment_status: 'paid',
      discount:       form.discount,
      tax:            form.tax,
      tax_rate:       form.tax_rate,
      amount_paid:    totalPaid.value,
      status:         'completed',
      table_number:   form.table_number || null,
      notes:          form.notes,
      total:          total.value,
      subtotal:       subtotal.value,
      ...(payments.length ? { payments } : {}),
      items: form.items.map(i => ({
        product_id:     i.product_id,
        quantity:       i.quantity,
        unit_price:     getEffectiveUnitPrice(i),
        discount:       i.discount,
        empty_bottle_returned: i.empty_bottle_returned,
        bottle_deposit_amount: i.bottle_deposit_amount,
        serving_ml: i.serving_ml,
      })),
    })
    // Delete the draft
    await axios.delete(`/api/sales/${route.params.id}`)
    router.push('/sales')
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'An error occurred. Please try again.'
  } finally { saving.value = false }
}

onMounted(async () => {
  try {
    // Load draft bill
    const { data: saleData } = await axios.get(`/api/sales/${route.params.id}`)
    Object.assign(sale, saleData)
    form.customer_id = saleData.customer_id || ''
    form.payment_method = saleData.payment_method || 'cash'
    form.discount = saleData.discount || 0
    form.tax = saleData.tax || 0
    form.tax_rate = saleData.tax_rate || 0
    form.table_number = saleData.table_number || ''
    form.notes = saleData.notes || ''
    paymentRows.value = [newPaymentRow(form.payment_method, 0, '')]
    
    // Load items
    if (saleData.items?.length) {
      form.items = saleData.items.map(si => ({
        product_id: si.product_id,
        product_search: si.product?.name || '',
        quantity: Number(si.quantity || 0),
        unit_price: Number(si.product?.selling_price ?? si.unit_price ?? 0),
        discount: Number(si.discount || 0),
        empty_bottle_returned: false,
        bottle_deposit_amount: 100,
        serving_ml: Number(si.serving_ml || 0),
        product_ref: si.product,
        _lineTotal: 0,
      }))
      form.items.forEach(recalcItem)
    }
    recalc()
    paymentRows.value = [newPaymentRow(form.payment_method, form.amount_paid, '')]

    // Load shared data
    const [p, c, t, tb] = await Promise.all([
      axios.get('/api/products', { params: { per_page: 200 } }),
      axios.get('/api/customers/all'),
      axios.get('/api/tax-settings'),
      axios.get('/api/tables/all'),
    ])
    products.value  = p.data.data
    customers.value = c.data
    taxes.value     = t.data.filter(x => x.is_active)
    availableTables.value = tb.data
  } catch (e) {
    error.value = 'Failed to load draft bill'
  }
})
</script>
