<template>
  <div class="min-h-screen flex">

    <!-- Left panel — branding -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-amber-600 via-orange-700 to-gray-900 flex-col items-center justify-center p-12 overflow-hidden">

      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
              <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
      </div>

      <!-- Decorative circles -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-amber-500 rounded-full opacity-20 blur-3xl"></div>
      <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-orange-900 rounded-full opacity-30 blur-3xl"></div>

      <!-- Content -->
      <div class="relative z-10 text-white w-full max-w-md">
        <!-- Press icon + title -->
        <div class="flex items-center gap-4 mb-8">
          <div class="w-16 h-16 bg-white/10 backdrop-blur rounded-2xl flex items-center justify-center border border-white/20 shadow-xl shrink-0">
            <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
          </div>
          <div>
            <h1 class="text-3xl font-extrabold tracking-tight">LMUC Press</h1>
            <p class="text-amber-200 text-sm font-medium mt-0.5">Printing Press Management ERP</p>
          </div>
        </div>

        <p class="text-white/60 text-sm leading-relaxed mb-8">
          End-to-end management for your commercial print shop — from first quote to final delivery.
        </p>

        <!-- Features list -->
        <div class="space-y-3 mb-8">
          <div v-for="f in featureList" :key="f.title"
            class="flex items-start gap-3 bg-white/5 border border-white/10 rounded-xl px-4 py-3 backdrop-blur-sm">
            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0 mt-0.5">
              <component :is="f.icon" class="w-4 h-4 text-amber-300" />
            </div>
            <div>
              <p class="text-sm font-semibold text-white">{{ f.title }}</p>
              <p class="text-xs text-white/50 mt-0.5 leading-relaxed">{{ f.desc }}</p>
            </div>
          </div>
        </div>

        <!-- Get Started button -->
        <button @click="showFeatures = true"
          class="w-full flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl px-5 py-3 text-sm font-semibold text-white transition-colors backdrop-blur-sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
          Explore All Features
        </button>
      </div>

      <!-- Bottom tag -->
      <p class="absolute bottom-6 text-white/30 text-xs">© {{ year }} LMUC Press · All rights reserved</p>
    </div>

    <!-- Features modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showFeatures"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
          @click.self="showFeatures = false">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between rounded-t-2xl">
              <div>
                <h2 class="text-lg font-bold text-gray-800">LMUC Press — All Features</h2>
                <p class="text-xs text-gray-400 mt-0.5">Complete print shop management in one system</p>
              </div>
              <button @click="showFeatures = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-for="m in allModules" :key="m.title"
                class="flex items-start gap-3 p-4 rounded-xl border border-gray-100 hover:border-amber-100 hover:bg-amber-50/30 transition-colors">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                  :class="m.bg">
                  <component :is="m.icon" class="w-5 h-5" :class="m.color" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-800">{{ m.title }}</p>
                  <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ m.desc }}</p>
                </div>
              </div>
            </div>
            <div class="px-6 pb-6">
              <button @click="showFeatures = false"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
                Sign in to get started →
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Right panel — login form -->
    <div class="flex-1 flex items-center justify-center bg-gray-50 px-6 py-12">
      <div class="w-full max-w-sm">

        <!-- Mobile logo -->
        <div class="lg:hidden text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-amber-500 rounded-2xl shadow-lg mb-4">
            <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-800">LMUC Press</h2>
          <p class="text-sm text-gray-500 mt-1">Printing Press ERP</p>
        </div>

        <!-- Form card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
          <h3 class="text-xl font-bold text-gray-800 mb-1">Welcome back</h3>
          <p class="text-sm text-gray-500 mb-6">Sign in to your account to continue</p>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email address</label>
              <input v-model="form.email" type="email" required autocomplete="email"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent outline-none transition"
                placeholder="admin@lmucpress.lk" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password</label>
              <div class="relative">
                <input v-model="form.password" :type="showPass ? 'text' : 'password'" required autocomplete="current-password"
                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent outline-none transition pr-10"
                  placeholder="••••••••" />
                <button type="button" @click="showPass = !showPass"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <svg v-if="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
              </div>
            </div>

            <div v-if="error" class="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-100 px-3 py-2.5 rounded-xl">
              <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.963-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
              {{ error }}
            </div>

            <button type="submit" :disabled="loading"
              class="w-full bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors flex items-center justify-center gap-2 mt-2">
              <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ loading ? 'Signing in…' : 'Sign in' }}
            </button>
          </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
          LMUC Press Management System · v2.0
        </p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  DocumentTextIcon, ClipboardDocumentListIcon, CogIcon,
  ArchiveBoxIcon, TruckIcon, BanknotesIcon, UsersIcon,
  ChartBarIcon, ShoppingBagIcon, CurrencyDollarIcon,
  PrinterIcon, WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'

const auth     = useAuthStore()
const router   = useRouter()
const form     = reactive({ email: '', password: '' })
const error    = ref('')
const loading  = ref(false)
const showPass = ref(false)
const showFeatures = ref(false)
const year     = computed(() => new Date().getFullYear())

// Left panel feature rows (compact, 4 items)
const featureList = [
  { icon: DocumentTextIcon,        title: 'Quotations & Costing',   desc: 'Detailed cost breakdowns with plate, paper, ink, labour & margin' },
  { icon: ClipboardDocumentListIcon, title: 'Job Cards & Workflow', desc: 'Track every job from waiting through proof, printing to delivery' },
  { icon: CogIcon,                 title: 'Production Costing',     desc: 'Actual vs estimated cost analysis with profit margin tracking' },
  { icon: TruckIcon,               title: 'Inventory & Delivery',   desc: 'Stock control, GRNs, supplier returns, and delivery management' },
]

// Full feature list for the modal
const allModules = [
  { icon: DocumentTextIcon,          title: 'Quotations',         desc: 'Cost-breakdown estimates with PDF download and quotation-to-order conversion.',      bg: 'bg-amber-50',  color: 'text-amber-600' },
  { icon: ClipboardDocumentListIcon, title: 'Job Cards',          desc: 'Full production workflow with 9-stage status tracking and QR code per job.',         bg: 'bg-purple-50', color: 'text-purple-600' },
  { icon: PrinterIcon,               title: 'Pre-Press & Proofing', desc: 'Artwork status tracking, designer assignment, and proof approval workflow.',       bg: 'bg-blue-50',   color: 'text-blue-600' },
  { icon: CogIcon,                   title: 'Production',         desc: 'Machine runs, output/waste tracking per run, and production history log.',            bg: 'bg-indigo-50', color: 'text-indigo-600' },
  { icon: WrenchScrewdriverIcon,     title: 'Finishing',          desc: 'Cutting, folding, binding, lamination, UV coating, foiling, die-cutting tasks.',     bg: 'bg-teal-50',   color: 'text-teal-600' },
  { icon: CurrencyDollarIcon,        title: 'Production Costing', desc: 'Actual cost tracking (paper/ink/plate/machine/labour) vs quotation estimate with profit analysis.', bg: 'bg-green-50',  color: 'text-green-600' },
  { icon: ArchiveBoxIcon,            title: 'Inventory',          desc: 'Products, categories, GRNs, supplier returns, and damage reporting with stock ledger.', bg: 'bg-orange-50', color: 'text-orange-600' },
  { icon: ShoppingBagIcon,           title: 'Purchases',          desc: 'Purchase orders with PDF download, supplier management, and GRN reconciliation.',    bg: 'bg-yellow-50', color: 'text-yellow-600' },
  { icon: TruckIcon,                 title: 'Deliveries',         desc: 'Delivery management linked to job cards with item-level dispatch tracking.',          bg: 'bg-sky-50',    color: 'text-sky-600' },
  { icon: BanknotesIcon,             title: 'Finance',            desc: 'Double-entry accounting journal, salary management, income & expense tracking.',      bg: 'bg-emerald-50', color: 'text-emerald-600' },
  { icon: ChartBarIcon,              title: 'Reports & Analytics', desc: 'Production analytics, revenue trends, machine efficiency, and profitability.',      bg: 'bg-rose-50',   color: 'text-rose-600' },
  { icon: UsersIcon,                 title: 'Client Portal',      desc: 'Clients log in separately to track their own job card status in real time.',          bg: 'bg-violet-50', color: 'text-violet-600' },
]

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Invalid email or password'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
