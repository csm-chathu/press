<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside :class="sidebarHidden ? 'w-0 overflow-hidden' : collapsed ? 'w-16' : 'w-64'"
      class="relative text-white flex flex-col shrink-0 transition-all duration-200 overflow-hidden"
      style="background: linear-gradient(to bottom, #1e3a8a, #172554, #0f172a);">

      <!-- Background grid pattern (matches login) -->
      <div class="absolute inset-0 opacity-10 pointer-events-none">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="sidebar-grid" width="40" height="40" patternUnits="userSpaceOnUse">
              <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#sidebar-grid)" />
        </svg>
      </div>
      <!-- Decorative glow -->
      <div class="absolute -top-16 -left-16 w-48 h-48 bg-amber-500 rounded-full opacity-15 blur-3xl pointer-events-none"></div>

      <!-- Logo -->
      <div class="relative flex items-center gap-3 px-3 py-5 border-b border-white/10 min-h-[72px]">
        <div class="w-9 h-9 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center shrink-0">
          <img v-if="press.logo_url" :src="press.logo_url" alt="logo" class="w-9 h-9 rounded-xl object-cover" />
          <svg v-else class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
          </svg>
        </div>
        <div v-if="!collapsed" class="overflow-hidden">
          <p class="font-bold text-white text-sm leading-tight truncate">{{ press.name }}</p>
          <p class="text-xs text-amber-300/60">Press Management System</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="relative flex-1 py-4 overflow-y-auto overflow-x-hidden">
        <!-- Main section -->
        <div v-if="!collapsed" class="px-4 mb-2 text-xs font-semibold text-amber-400/60 uppercase tracking-wider">Main</div>
        <router-link v-for="item in navItems" :key="item.to" :to="item.to"
          :title="collapsed ? item.label : ''"
          :class="[
            'flex items-center py-2.5 mx-2 rounded-lg text-sm transition-colors',
            collapsed ? 'justify-center px-0' : 'gap-3 px-4',
            isNavActive(item.to)
              ? 'bg-white/20 text-white shadow-sm backdrop-blur-sm'
              : 'text-white/70 hover:bg-white/10 hover:text-white'
          ]">
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          <span v-if="!collapsed">{{ item.label }}</span>
        </router-link>

        <!-- Production section -->
        <template v-if="canSeeProduction">
          <div v-if="!collapsed" class="px-4 mt-4 mb-2 text-xs font-semibold text-amber-400/60 uppercase tracking-wider">Production</div>
          <div v-else class="my-3 mx-3 border-t border-white/10"></div>
          <router-link v-for="item in productionNavItems" :key="item.to" :to="item.to"
            :title="collapsed ? item.label : ''"
            :class="[
              'flex items-center py-2.5 mx-2 rounded-lg text-sm transition-colors',
              collapsed ? 'justify-center px-0' : 'gap-3 px-4',
              isNavActive(item.to)
                ? 'bg-white/20 text-white shadow-sm backdrop-blur-sm'
                : 'text-white/70 hover:bg-white/10 hover:text-white'
            ]">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </router-link>
        </template>

        <!-- Admin section -->
        <template v-if="['admin', 'owner'].includes(auth.user?.role)">
          <div v-if="!collapsed" class="px-4 mt-4 mb-2 text-xs font-semibold text-amber-400/60 uppercase tracking-wider">Admin</div>
          <div v-else class="my-3 mx-3 border-t border-white/10"></div>
          <router-link v-for="item in adminNavItems" :key="item.to" :to="item.to"
            :title="collapsed ? item.label : ''"
            :class="[
              'flex items-center py-2.5 mx-2 rounded-lg text-sm transition-colors',
              collapsed ? 'justify-center px-0' : 'gap-3 px-4',
              isNavActive(item.to)
                ? 'bg-white/20 text-white shadow-sm backdrop-blur-sm'
                : 'text-white/70 hover:bg-white/10 hover:text-white'
            ]">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </router-link>
        </template>
      </nav>

      <!-- User info + collapse toggle -->
      <div class="relative px-2 py-4 border-t border-white/10 space-y-2">
        <button @click="toggleCollapse"
          :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          class="w-full flex items-center justify-center gap-2 py-1.5 rounded-lg text-white/40 hover:text-white hover:bg-white/10 transition-colors text-xs">
          <ChevronDoubleLeftIcon v-if="!collapsed" class="w-4 h-4" />
          <ChevronDoubleRightIcon v-else class="w-4 h-4" />
          <span v-if="!collapsed">Collapse</span>
        </button>

        <div :class="collapsed ? 'justify-center' : 'gap-3'" class="flex items-center">
          <div class="w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center text-sm font-bold shrink-0 border border-amber-400/40">
            {{ auth.user?.name?.charAt(0) }}
          </div>
          <div v-if="!collapsed" class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-white/50 truncate capitalize">{{ roleLabel }}</p>
          </div>
          <button v-if="!collapsed" @click="doLogout" title="Logout"
            class="p-1 rounded text-white/40 hover:text-white hover:bg-white/10 transition-colors">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
          <button v-else @click="doLogout" title="Logout"
            class="p-1 rounded text-white/40 hover:text-white hover:bg-white/10 transition-colors">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main area -->
    <div class="flex-1 flex flex-col min-h-0 min-w-0">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <router-link v-if="sidebarHidden" to="/" title="Dashboard"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold transition-colors">
            <HomeIcon class="w-4 h-4" />
            Home
          </router-link>
          <h1 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h1>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-500">
          <button @click="toggleSidebarHidden"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
            <ArrowsPointingOutIcon v-if="!sidebarHidden" class="w-3.5 h-3.5" />
            <ArrowsPointingInIcon v-else class="w-3.5 h-3.5" />
            {{ sidebarHidden ? 'Exit Full Screen' : 'Full Screen' }}
          </button>
          <span>{{ currentDate }}</span>
          <button v-if="sidebarHidden" @click="doLogout" title="Logout"
            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
          </button>
        </div>
      </header>

      <!-- Page -->
      <main class="flex-1 overflow-auto p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import {
  HomeIcon, CubeIcon, TagIcon, UsersIcon,
  TruckIcon, ShoppingCartIcon, ArchiveBoxIcon,
  ArrowRightOnRectangleIcon,
  UserGroupIcon, ClipboardDocumentCheckIcon,
  ClipboardDocumentListIcon, CurrencyDollarIcon,
  ChartBarIcon, Cog6ToothIcon, BanknotesIcon,
  ChevronDoubleLeftIcon, ChevronDoubleRightIcon,
  ArrowsPointingOutIcon, ArrowsPointingInIcon,
  DocumentTextIcon, WrenchScrewdriverIcon,
  PrinterIcon, TruckIcon as DeliveryTruckIcon,
  QueueListIcon, BeakerIcon, ScissorsIcon,
  BuildingOfficeIcon, ReceiptPercentIcon,
  CalendarDaysIcon, RocketLaunchIcon,
} from '@heroicons/vue/24/outline'

const auth   = useAuthStore()
const route  = useRoute()
const press  = ref({ name: 'LMUC Press', logo_url: '', address: '' })

const collapsed     = ref(localStorage.getItem('sidebar_collapsed') === 'true')
const sidebarHidden = ref(false)

function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('sidebar_collapsed', collapsed.value)
}
function toggleSidebarHidden() {
  sidebarHidden.value = !sidebarHidden.value
}

const roleLabels = {
  admin: 'Admin', owner: 'Owner', sales: 'Sales',
  estimator: 'Estimator', designer: 'Designer',
  production_manager: 'Production Manager', machine_operator: 'Machine Operator',
  store_keeper: 'Store Keeper', accountant: 'Accountant',
  dispatch_officer: 'Dispatch Officer', manager: 'Manager', cashier: 'Cashier',
}
const roleLabel = computed(() => roleLabels[auth.user?.role] ?? auth.user?.role)

const canSeeProduction = computed(() => {
  const role = auth.user?.role
  return ['admin', 'owner', 'production_manager', 'machine_operator', 'designer'].includes(role)
})

// ── Main nav (visible to most roles) ──────────────────────────────
const allNavItems = [
  { to: '/',           label: 'Dashboard',        icon: HomeIcon,              roles: null },
  { to: '/customers',  label: 'Customers',         icon: UsersIcon,             roles: ['admin', 'owner', 'sales', 'manager', 'estimator', 'accountant'] },
  { to: '/quotations', label: 'Quotations',        icon: DocumentTextIcon,      roles: ['admin', 'owner', 'sales', 'estimator', 'manager'] },
  { to: '/sales',      label: 'Sales Orders',      icon: ShoppingCartIcon,      roles: ['admin', 'owner', 'sales', 'manager', 'accountant', 'cashier'] },
  { to: '/suppliers',  label: 'Suppliers',         icon: TruckIcon,             roles: ['admin', 'owner', 'manager', 'store_keeper', 'accountant'] },
  { to: '/purchases',  label: 'Purchases',         icon: ArchiveBoxIcon,        roles: ['admin', 'owner', 'manager', 'store_keeper', 'accountant'] },
  { to: '/products',   label: 'Materials / Stock', icon: CubeIcon,              roles: ['admin', 'owner', 'manager', 'store_keeper', 'production_manager'] },
  { to: '/deliveries', label: 'Deliveries',        icon: DeliveryTruckIcon,     roles: ['admin', 'owner', 'manager', 'dispatch_officer', 'sales'] },
  { to: '/reports',    label: 'Reports',           icon: ChartBarIcon,          roles: ['admin', 'owner', 'manager', 'accountant'] },
]

const navItems = computed(() => {
  const role = auth.user?.role
  return allNavItems.filter(item => !item.roles || item.roles.includes(role))
})

// ── Production nav ──────────────────────────────────────────────────
const productionNavItems = [
  { to: '/job-cards',   label: 'Job Cards',         icon: ClipboardDocumentListIcon },
  { to: '/schedule',    label: 'Schedule',          icon: CalendarDaysIcon },
  { to: '/production',  label: 'Production Queue',  icon: QueueListIcon },
  { to: '/prepress',    label: 'Pre-Press',         icon: BeakerIcon },
  { to: '/finishing',   label: 'Finishing',         icon: ScissorsIcon },
  { to: '/machines',    label: 'Machines',          icon: PrinterIcon },
]

// ── Admin nav ────────────────────────────────────────────────────────
const adminNavItems = [
  { to: '/getting-started', label: 'Getting Started', icon: RocketLaunchIcon },
  { to: '/categories',      label: 'Categories',      icon: TagIcon },
  { to: '/grn',             label: 'GRN',             icon: ClipboardDocumentCheckIcon },
  { to: '/supplier-returns',label: 'Supplier Returns',icon: ArchiveBoxIcon },
  { to: '/damages',         label: 'Damages / Waste', icon: WrenchScrewdriverIcon },
  { to: '/finance',         label: 'Finance',         icon: BanknotesIcon },
  { to: '/audit-log',       label: 'Stock Ledger',    icon: ClipboardDocumentListIcon },
  { to: '/users',           label: 'Users & Roles',   icon: UserGroupIcon },
  { to: '/settings',        label: 'Press Settings',  icon: Cog6ToothIcon },
]

const pageTitles = {
  dashboard:        'Dashboard',
  customers:        'Customer Management',
  quotations:       'Quotations',
  'quotations.new': 'New Quotation',
  'quotations.show':'Quotation Details',
  sales:            'Sales Orders',
  'sales.new':      'New Sales Order',
  'sales.receipt':  'Order Invoice',
  suppliers:        'Suppliers',
  purchases:        'Purchase Orders',
  'purchases.new':  'New Purchase Order',
  products:         'Materials & Stock',
  categories:       'Material Categories',
  'job-cards':      'Job Cards',
  'job-cards.new':  'New Job Card',
  'job-cards.show': 'Job Card Details',
  schedule:         'Production Schedule',
  production:       'Production Queue',
  prepress:         'Pre-Press Management',
  finishing:        'Finishing',
  machines:         'Press Machines',
  deliveries:       'Delivery Management',
  reports:          'Reports & Analytics',
  finance:          'Finance Management',
  grn:              'Goods Received Notes',
  'supplier-returns':'Supplier Returns',
  damages:          'Damages & Waste',
  'audit-log':      'Stock Ledger',
  users:            'Users & Roles',
  settings:         'Press Settings',
  'getting-started':'Getting Started',
}

const pageTitle  = computed(() => pageTitles[route.name] ?? 'LMUC Press')
const currentDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

function isNavActive(targetPath) {
  if (targetPath === '/') return route.path === '/'
  return route.path === targetPath || route.path.startsWith(`${targetPath}/`)
}

async function doLogout() {
  await auth.logout()
  window.location.href = '/login'
}

async function loadPressSettings() {
  try {
    const { data } = await axios.get('/api/settings/restaurant')
    press.value = {
      name:    data.name || 'LMUC Press',
      logo_url:data.logo_url || '',
      address: data.address || '',
    }
  } catch { /* keep fallbacks */ }
}

onMounted(() => {
  loadPressSettings()
})
</script>
