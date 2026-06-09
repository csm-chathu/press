<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50">
        <div class="w-full h-full bg-white flex flex-col overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 bg-gray-900 text-white shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-sm font-bold">?</div>
              <div>
                <p class="font-bold text-sm leading-tight">Getting Started</p>
                <p class="text-xs text-gray-400">LMUC POS &amp; Business Management</p>
              </div>
            </div>
            <button @click="$emit('update:modelValue', false)"
              class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-gray-700 transition-colors text-lg font-bold">
              ✕
            </button>
          </div>

          <!-- Progress bar -->
          <div class="h-1 bg-gray-100 shrink-0">
            <div class="h-full bg-amber-500 transition-all duration-300"
              :style="{ width: `${((currentStep + 1) / steps.length) * 100}%` }" />
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto flex flex-col">
            <div class="flex-1 max-w-2xl mx-auto w-full px-8 py-10">

              <!-- Step indicator -->
              <div class="flex items-center gap-2 mb-6">
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
                  Step {{ currentStep + 1 }} of {{ steps.length }}
                </span>
                <div class="flex gap-1 ml-1">
                  <div v-for="(s, i) in steps" :key="i"
                    class="h-1.5 rounded-full transition-all duration-200"
                    :class="[
                      i === currentStep ? 'w-6 bg-amber-500' :
                      i < currentStep ? 'w-3 bg-amber-300' : 'w-3 bg-gray-200'
                    ]" />
                </div>
              </div>

              <!-- Step content -->
              <div class="flex gap-5 mb-8">
                <div class="w-12 h-12 rounded-xl bg-gray-900 text-white text-lg font-bold flex items-center justify-center shrink-0">
                  {{ steps[currentStep].icon }}
                </div>
                <div>
                  <h2 class="text-xl font-bold text-gray-900 mb-2">{{ steps[currentStep].title }}</h2>
                  <p class="text-sm text-gray-500 leading-relaxed">{{ steps[currentStep].desc }}</p>
                </div>
              </div>

              <!-- Detail items -->
              <div v-if="steps[currentStep].items" class="space-y-2 mb-8">
                <div v-for="item in steps[currentStep].items" :key="item"
                  class="flex items-start gap-3 bg-gray-50 rounded-lg px-4 py-3">
                  <span class="text-amber-500 font-bold text-sm mt-0.5">→</span>
                  <span class="text-sm text-gray-700">{{ item }}</span>
                </div>
              </div>

              <!-- Code block -->
              <div v-if="steps[currentStep].code"
                class="bg-gray-900 rounded-xl px-5 py-4 mb-8 font-mono text-xs text-green-400 leading-relaxed whitespace-pre">{{ steps[currentStep].code }}</div>

              <!-- Warning -->
              <div v-if="steps[currentStep].warning"
                class="flex gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-8">
                <span class="text-amber-500 text-base shrink-0">⚠️</span>
                <p class="text-xs text-amber-800 leading-relaxed">{{ steps[currentStep].warning }}</p>
              </div>

              <!-- Navigate to page button -->
              <div v-if="steps[currentStep].path" class="mb-8">
                <button @click="navigateTo(steps[currentStep].path)"
                  class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 transition-colors">
                  <span>Go to {{ steps[currentStep].pathLabel }}</span>
                  <span class="text-gray-400">→</span>
                </button>
              </div>

            </div>
          </div>

          <!-- Footer nav -->
          <div class="px-8 py-4 border-t border-gray-100 bg-gray-50 shrink-0 flex items-center justify-between">
            <button v-if="currentStep > 0" @click="currentStep--"
              class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-200 transition-colors">
              ← Back
            </button>
            <div v-else />

            <div class="flex items-center gap-3">
              <button @click="$emit('update:modelValue', false)"
                class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                Skip guide
              </button>
              <button v-if="currentStep < steps.length - 1" @click="currentStep++"
                class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition-colors">
                Next →
              </button>
              <button v-else @click="$emit('update:modelValue', false)"
                class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold transition-colors">
                ✓ Done — Start using the system
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

defineProps({ modelValue: Boolean })
const emit = defineEmits(['update:modelValue'])
const router = useRouter()

const currentStep = ref(0)

function navigateTo(path) {
  emit('update:modelValue', false)
  router.push(path)
}

const steps = [
  {
    icon: '🔐',
    title: 'Log in and secure all accounts',
    desc: 'Sign in with the credentials provided by your administrator. Then immediately change the default passwords for all staff accounts — they are all set to "password" by default.',
    items: [
      'Use the email and password provided when the system was set up',
      'Navigate to Settings → Users to update every account password',
      'Assign each staff member the correct role and branch',
    ],
    warning: 'Default passwords are "password". Change every account immediately to prevent unauthorised access.',
    path: '/users',
    pathLabel: 'Users & Roles',
  },
  {
    icon: '🏪',
    title: 'Configure your business settings',
    desc: 'Set your business name, address, and upload your logo. The logo appears on the login screen and browser tab, giving your system a professional branded feel.',
    items: [
      'Enter your business name and address',
      'Upload your logo (PNG or JPG recommended)',
      'Changes reflect immediately on the login page and favicon',
    ],
    path: '/settings',
    pathLabel: 'Restaurant Settings',
  },
  {
    icon: '🏷️',
    title: 'Add product categories',
    desc: 'Create the categories that match your product range. Categories are used to filter products at the POS, making it faster for cashiers to find items.',
    items: [
      'Examples: Liquor, Beer, Soft Drinks, Food, Tobacco',
      'Each product must belong to a category',
      'Categories appear as filter tabs on the POS billing screen',
    ],
    path: '/categories',
    pathLabel: 'Menu Categories',
  },
  {
    icon: '📦',
    title: 'Add your products',
    desc: 'Add every product you sell with its details. SKU is used for barcode scanning at the POS. Selling price is what customers pay.',
    items: [
      'Enter name, SKU, category, selling price, and unit type',
      'Optionally upload a product image (shown as thumbnail at POS)',
      'Set the product type (e.g. liquor, beer, food) for open-bottle tracking',
      'Leave stock quantity at 0 — you will set opening stock in the next step',
    ],
    path: '/products',
    pathLabel: 'Products',
  },
  {
    icon: '📊',
    title: 'Set opening stock & account balances',
    desc: 'Before going live, enter the actual quantities you have in stock right now, and optionally set opening balances for your chart of accounts. This is a one-time setup step.',
    items: [
      'Stock Quantities tab — enter the current on-hand qty for each product',
      'Account Balances tab — enter debit/credit opening balances for asset, liability, and equity accounts',
      'This creates the correct starting point for all future reports',
    ],
    path: '/opening-balance',
    pathLabel: 'Opening Balance',
  },
  {
    icon: '🚚',
    title: 'Add your suppliers',
    desc: 'Add the companies or individuals you purchase stock from. Suppliers are linked to purchase orders, GRNs, and supplier returns for full traceability.',
    items: [
      'Enter supplier name, contact person, phone, and email',
      'Suppliers are selected when raising purchase orders',
    ],
    path: '/suppliers',
    pathLabel: 'Suppliers',
  },
  {
    icon: '🪑',
    title: 'Set up tables (dine-in)',
    desc: 'If you run a dine-in operation, add your table layout. Cashiers assign a table when opening a bill, and the table appears on the receipt.',
    items: [
      'Add each table with a name or number (e.g. Table 1, VIP Room)',
      'Tables are available at the POS when creating a new bill',
      'Skip this step if you are take-away or counter service only',
    ],
    path: '/tables',
    pathLabel: 'Tables',
  },
  {
    icon: '🛒',
    title: 'You\'re ready — start taking orders',
    desc: 'The system is set up. Click "New Bill" in the sidebar to open the POS. Select products, assign a table or customer, choose a payment method, and complete the sale.',
    items: [
      'New Bill — opens the full POS billing screen',
      'Sales → view all completed and draft invoices',
      'Reports → daily, weekly, and monthly sales analytics',
      'Stock Ledger → full audit trail of every stock movement',
    ],
    path: '/sales/new',
    pathLabel: 'New Bill (POS)',
  },
]
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
