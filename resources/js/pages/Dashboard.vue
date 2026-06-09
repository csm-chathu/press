<template>
  <div class="space-y-5">

    <!-- Hero banner -->
    <div class="relative overflow-hidden rounded-2xl text-white"
      style="background: linear-gradient(135deg, #1e3a8a 0%, #172554 50%, #0f172a 100%);">
      <!-- Grid pattern -->
      <div class="absolute inset-0 opacity-10 pointer-events-none">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="dash-grid" width="40" height="40" patternUnits="userSpaceOnUse">
              <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#dash-grid)" />
        </svg>
      </div>
      <!-- Decorative blurs -->
      <div class="absolute -top-10 -right-10 w-56 h-56 bg-blue-400 rounded-full opacity-20 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-8 -left-8 w-40 h-40 bg-blue-600 rounded-full opacity-15 blur-2xl pointer-events-none"></div>

      <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-6 py-5">
        <div class="flex items-center gap-4">
          <!-- Printer icon in glassmorphism pill -->
          <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-sm flex items-center justify-center shrink-0 shadow-lg">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
          </div>
          <div>
            <p class="text-xs font-medium text-amber-200">{{ greeting }}, {{ auth.user?.name }}</p>
            <h2 class="text-xl font-bold tracking-tight">LMUC Press</h2>
            <p class="text-xs text-white/50 mt-0.5">{{ todayLabel }}</p>
          </div>
        </div>
        <!-- Inline KPI pills -->
        <div class="flex items-center gap-3 flex-wrap">
          <div class="bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-center min-w-[80px]">
            <p class="text-base font-bold leading-tight">{{ (data.totals?.active_jobs ?? '—') }}</p>
            <p class="text-[10px] text-white/60 mt-0.5">Active Jobs</p>
          </div>
          <div class="bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-center min-w-[80px]">
            <p class="text-base font-bold leading-tight">{{ data.totals?.orders_today ?? '—' }}</p>
            <p class="text-[10px] text-white/60 mt-0.5">Today's Orders</p>
          </div>
          <div class="bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-center min-w-[80px]">
            <p class="text-base font-bold leading-tight">{{ data.totals?.ready_for_dispatch ?? '—' }}</p>
            <p class="text-[10px] text-white/60 mt-0.5">Ready</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="flex gap-3 justify-end flex-wrap">
      <router-link to="/quotations/new"
        class="flex items-center gap-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl px-4 py-2.5 shadow-sm transition-colors">
        <DocumentTextIcon class="w-5 h-5 shrink-0" />
        <div>
          <p class="text-sm font-semibold leading-tight">New Quotation</p>
          <p class="text-xs opacity-75">Create estimate</p>
        </div>
      </router-link>
      <router-link to="/job-cards/new"
        class="flex items-center gap-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl px-4 py-2.5 shadow-sm transition-colors">
        <ClipboardDocumentListIcon class="w-5 h-5 shrink-0" />
        <div>
          <p class="text-sm font-semibold leading-tight">New Job Card</p>
          <p class="text-xs opacity-75">Start production</p>
        </div>
      </router-link>
      <router-link to="/sales/new"
        class="flex items-center gap-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2.5 shadow-sm transition-colors">
        <ShoppingCartIcon class="w-5 h-5 shrink-0" />
        <div>
          <p class="text-sm font-semibold leading-tight">New Order</p>
          <p class="text-xs opacity-75">Create sales order</p>
        </div>
      </router-link>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
      <div v-for="card in kpiCards" :key="card.label"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3 flex flex-col gap-1 hover:border-amber-100 hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-gray-400 uppercase tracking-wide leading-tight">{{ card.label }}</span>
          <span class="text-base w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
            :class="card.iconBg ?? 'bg-gray-50'">{{ card.icon }}</span>
        </div>
        <p class="text-xl font-bold" :class="card.color">{{ card.value }}</p>
        <p v-if="card.sub" class="text-xs text-gray-400">{{ card.sub }}</p>
      </div>
    </div>

    <!-- Row 2: Revenue trend + Production Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Revenue trend (30 days) -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="font-semibold text-gray-700 text-sm">Revenue Trend</h3>
            <p class="text-xs text-gray-400">Last 30 days — revenue & bill count</p>
          </div>
          <span class="text-xs bg-amber-50 text-amber-600 font-semibold px-2 py-1 rounded-full border border-amber-100">30 days</span>
        </div>
        <div class="h-56">
          <Line v-if="revenueTrendData" :data="revenueTrendData" :options="trendOptions" />
          <ChartEmpty v-else :loaded="loaded" />
        </div>
      </div>

      <!-- Production Status -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col">
        <div class="mb-3">
          <h3 class="font-semibold text-gray-700 text-sm">Production Queue</h3>
          <p class="text-xs text-gray-400">Active jobs by status</p>
        </div>
        <div v-if="!loaded" class="flex-1 flex items-center justify-center">
          <div class="w-5 h-5 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
        </div>
        <div v-else-if="!data.production_status?.length" class="flex-1 flex flex-col items-center justify-center text-gray-300 gap-2">
          <span class="text-3xl">🖨️</span>
          <span class="text-xs">No active jobs</span>
        </div>
        <div v-else class="space-y-2 flex-1">
          <div v-for="s in data.production_status" :key="s.status" class="flex items-center gap-3">
            <span :class="jobStatusColor(s.status)" class="inline-block w-2.5 h-2.5 rounded-full shrink-0"></span>
            <span class="text-xs text-gray-700 flex-1 capitalize">{{ jobStatusLabel(s.status) }}</span>
            <span class="text-sm font-bold" :class="Number(s.count) > 0 ? 'text-gray-800' : 'text-gray-300'">{{ s.count }}</span>
          </div>
          <router-link to="/job-cards" class="mt-3 block text-xs text-amber-600 hover:underline text-center">View all job cards →</router-link>
        </div>
      </div>
    </div>

    <!-- Row 3: Payment methods + Category breakdown + Hourly pattern -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

      <!-- Payment methods donut -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <h3 class="font-semibold text-gray-700 text-sm mb-0.5">Payment Methods</h3>
        <p class="text-xs text-gray-400 mb-3">This month by revenue</p>
        <div class="h-48">
          <Doughnut v-if="paymentMethodData" :data="paymentMethodData" :options="doughnutOptions" />
          <ChartEmpty v-else :loaded="loaded" message="No sales this month" />
        </div>
        <!-- Legend totals -->
        <div v-if="data.payment_methods?.length" class="mt-3 space-y-1">
          <div v-for="(m, i) in data.payment_methods" :key="m.payment_method" class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: donutColors[i % donutColors.length] }"></span>
              <span class="text-gray-600 capitalize">{{ m.payment_method.replace('_', ' ') }}</span>
            </div>
            <span class="font-semibold text-gray-700">{{ m.count }} bills</span>
          </div>
        </div>
      </div>

      <!-- Category breakdown donut -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <h3 class="font-semibold text-gray-700 text-sm mb-0.5">Category Sales</h3>
        <p class="text-xs text-gray-400 mb-3">This month by revenue</p>
        <div class="h-48">
          <Doughnut v-if="categorySalesData" :data="categorySalesData" :options="doughnutOptions" />
          <ChartEmpty v-else :loaded="loaded" message="No sales this month" />
        </div>
        <div v-if="data.category_sales?.length" class="mt-3 space-y-1">
          <div v-for="(c, i) in data.category_sales.slice(0, 4)" :key="c.category" class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: categoryColors[i % categoryColors.length] }"></span>
              <span class="text-gray-600 truncate max-w-[100px]">{{ c.category }}</span>
            </div>
            <span class="font-semibold text-gray-700">LKR {{ shortNum(c.revenue) }}</span>
          </div>
        </div>
      </div>

      <!-- Hourly pattern bar -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <h3 class="font-semibold text-gray-700 text-sm mb-0.5">Busiest Hours</h3>
        <p class="text-xs text-gray-400 mb-3">Bill count by hour — last 7 days</p>
        <div class="h-48">
          <Bar v-if="hourlyPatternData" :data="hourlyPatternData" :options="hourlyOptions" />
          <ChartEmpty v-else :loaded="loaded" message="No data for last 7 days" />
        </div>
        <div v-if="peakHour !== null" class="mt-3 flex items-center gap-1.5 text-xs text-gray-500">
          <span class="w-2 h-2 rounded-full bg-amber-400"></span>
          Peak: <span class="font-semibold text-gray-700">{{ formatHour(peakHour) }}</span>
        </div>
      </div>
    </div>

    <!-- Row 4: Recent orders (full width) -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
      <h3 class="font-semibold text-gray-700 text-sm mb-3">Recent Orders</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
              <th class="text-left pb-2 pr-4 font-medium">Invoice</th>
              <th class="text-left pb-2 pr-4 font-medium">Customer</th>
              <th class="text-left pb-2 pr-4 font-medium">Status</th>
              <th class="text-right pb-2 font-medium">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="sale in data.recent_sales" :key="sale.id" class="hover:bg-gray-50 transition-colors">
              <td class="py-2 pr-4 font-mono text-xs font-semibold text-gray-800">{{ sale.invoice_number }}</td>
              <td class="py-2 pr-4 text-xs text-gray-500">{{ sale.customer?.name ?? 'Walk-in' }}</td>
              <td class="py-2 pr-4">
                <span class="text-xs px-2 py-0.5 rounded-full font-semibold" :class="statusClass(sale.payment_status)">
                  {{ sale.payment_status }}
                </span>
              </td>
              <td class="py-2 text-right text-xs font-bold text-gray-800">LKR {{ shortNum(sale.total) }}</td>
            </tr>
            <tr v-if="!data.recent_sales?.length">
              <td colspan="4" class="py-6 text-center text-sm text-gray-400">No sales yet</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Row 5: Low stock -->
    <div v-if="data.low_stock?.length" class="bg-white rounded-2xl border border-red-100 shadow-sm p-4">
      <h3 class="font-semibold text-gray-700 text-sm mb-3 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
        Low Stock Alerts ({{ data.low_stock.length }})
      </h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-xs text-gray-400 uppercase tracking-wide">
              <th class="text-left pb-2 pr-4">SKU</th>
              <th class="text-left pb-2 pr-4">Product</th>
              <th class="text-left pb-2 pr-4">Category</th>
              <th class="text-right pb-2 pr-4">Stock</th>
              <th class="text-right pb-2">Min Level</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="p in data.low_stock" :key="p.id" class="hover:bg-red-50 transition-colors">
              <td class="py-1.5 pr-4 font-mono text-xs text-gray-500">{{ p.sku }}</td>
              <td class="py-1.5 pr-4 font-medium text-gray-800">{{ p.name }}</td>
              <td class="py-1.5 pr-4 text-xs text-gray-400">{{ p.category?.name ?? '—' }}</td>
              <td class="py-1.5 pr-4 text-right">
                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold">{{ p.stock_quantity }}</span>
              </td>
              <td class="py-1.5 text-right text-xs text-gray-400">{{ p.min_stock_level }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue'
import axios from 'axios'
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { ShoppingCartIcon, DocumentTextIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale, LinearScale,
  PointElement, LineElement,
  BarElement, ArcElement,
  Title, Tooltip, Legend, Filler,
} from 'chart.js'

ChartJS.register(
  CategoryScale, LinearScale,
  PointElement, LineElement,
  BarElement, ArcElement,
  Title, Tooltip, Legend, Filler,
)

// ── Inline empty-state component ───────────────────────
const ChartEmpty = defineComponent({
  props: { loaded: Boolean, message: { type: String, default: 'No data available' } },
  setup(props) {
    return () => h('div', { class: 'h-full flex flex-col items-center justify-center text-gray-300 gap-2' }, [
      props.loaded
        ? [h('svg', { class: 'w-8 h-8 opacity-30', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' },
            [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M3 17l6-6 4 4 8-8' })]),
          h('span', { class: 'text-xs' }, props.message)]
        : [h('div', { class: 'w-5 h-5 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin' })],
    ])
  },
})

// ── Auth ──────────────────────────────────────────────
const auth = useAuthStore()

// ── Hero greeting ─────────────────────────────────────
const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})
const todayLabel = computed(() =>
  new Date().toLocaleDateString('en-LK', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

// ── Data ──────────────────────────────────────────────
const data   = ref({})
const loaded = ref(false)

// ── Color palettes ────────────────────────────────────
const donutColors    = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4']
const categoryColors = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899']

// ── Press KPI cards ────────────────────────────────────
const kpiCards = computed(() => {
  const t = data.value.totals ?? {}
  return [
    { label: "Today's Revenue",   value: 'LKR ' + shortNum(t.revenue_today   ?? 0), icon: '💰', iconBg: 'bg-amber-50',  color: 'text-amber-600',  sub: `${t.orders_today ?? 0} orders` },
    { label: 'Month Revenue',     value: 'LKR ' + shortNum(t.revenue_month   ?? 0), icon: '📈', iconBg: 'bg-green-50',  color: 'text-green-600'   },
    { label: 'Pending Quotes',    value: t.pending_quotes  ?? '—',                   icon: '📋', iconBg: 'bg-blue-50',   color: 'text-blue-600'    },
    { label: 'Active Jobs',       value: t.active_jobs     ?? '—',                   icon: '🖨️', iconBg: 'bg-purple-50', color: 'text-purple-600', sub: `${t.waiting_jobs ?? 0} waiting` },
    { label: 'Ready for Dispatch',value: t.ready_for_dispatch ?? '—',                icon: '📦', iconBg: 'bg-teal-50',   color: 'text-teal-600'    },
    { label: 'Low Stock',         value: t.low_stock_count ?? '—',                   icon: '⚠️', iconBg: 'bg-red-50',    color: 'text-red-500'     },
  ]
})

// ── Production status helpers ──────────────────────────
const jobStatusLabels = {
  waiting: 'Waiting', designing: 'Designing', proof_approval: 'Proof Approval',
  plate_making: 'Plate Making', printing: 'Printing', finishing: 'Finishing',
  quality_check: 'Quality Check', ready: 'Ready', delivered: 'Delivered',
}
const jobStatusColors = {
  waiting: 'bg-gray-400', designing: 'bg-blue-400', proof_approval: 'bg-yellow-400',
  plate_making: 'bg-orange-400', printing: 'bg-purple-500', finishing: 'bg-indigo-400',
  quality_check: 'bg-teal-400', ready: 'bg-green-500', delivered: 'bg-gray-300',
}
function jobStatusLabel(s) { return jobStatusLabels[s] ?? s }
function jobStatusColor(s)  { return jobStatusColors[s] ?? 'bg-gray-400' }

// ── Chart data ────────────────────────────────────────
const revenueTrendData = computed(() => {
  const sales = data.value.sales_chart
  if (!sales?.length) return null
  return {
    labels: sales.map(s => {
      const d = new Date(s.date)
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    }),
    datasets: [
      {
        label: 'Revenue (LKR)',
        data: sales.map(s => Number(s.revenue)),
        borderColor: '#d97706',
        backgroundColor: 'rgba(217,119,6,0.12)',
        fill: true,
        tension: 0.4,
        pointRadius: 2,
        pointHoverRadius: 5,
        yAxisID: 'y',
      },
      {
        label: 'Bills',
        data: sales.map(s => Number(s.count)),
        borderColor: '#3b82f6',
        backgroundColor: 'transparent',
        fill: false,
        tension: 0.4,
        pointRadius: 2,
        pointHoverRadius: 5,
        borderDash: [4, 3],
        yAxisID: 'y1',
      },
    ],
  }
})

const maxTopSold = computed(() => {
  const products = data.value.top_products ?? []
  return Math.max(...products.map(p => Number(p.total_sold)), 1)
})

const paymentMethodData = computed(() => {
  const methods = data.value.payment_methods ?? []
  if (!methods.length) return null
  return {
    labels: methods.map(m => m.payment_method.replace('_', ' ')),
    datasets: [{
      data: methods.map(m => Number(m.revenue)),
      backgroundColor: donutColors.slice(0, methods.length),
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 6,
    }],
  }
})

const categorySalesData = computed(() => {
  const cats = data.value.category_sales ?? []
  if (!cats.length) return null
  return {
    labels: cats.map(c => c.category),
    datasets: [{
      data: cats.map(c => Number(c.revenue)),
      backgroundColor: categoryColors.slice(0, cats.length),
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 6,
    }],
  }
})

const hourlyPatternData = computed(() => {
  const pattern = data.value.hourly_pattern ?? []
  if (!pattern.length) return null
  const map = Object.fromEntries(pattern.map(h => [h.hour, Number(h.count)]))
  const maxCount = Math.max(...Object.values(map), 1)
  const counts = Array.from({ length: 24 }, (_, i) => map[i] ?? 0)
  return {
    labels: Array.from({ length: 24 }, (_, i) => {
      if (i === 0) return '12am'
      if (i === 12) return '12pm'
      return i < 12 ? `${i}am` : `${i - 12}pm`
    }),
    datasets: [{
      label: 'Bills',
      data: counts,
      backgroundColor: counts.map(v => {
        const opacity = v === 0 ? 0.08 : 0.25 + (v / maxCount) * 0.75
        return `rgba(217,119,6,${opacity.toFixed(2)})`
      }),
      borderRadius: 3,
      borderSkipped: false,
    }],
  }
})

// ── Peak hour helper ──────────────────────────────────
const peakHour = computed(() => {
  const pattern = data.value.hourly_pattern ?? []
  if (!pattern.length) return null
  return pattern.reduce((a, b) => Number(a.count) >= Number(b.count) ? a : b).hour
})

function formatHour(h) {
  if (h === 0) return '12:00 am'
  if (h === 12) return '12:00 pm'
  return h < 12 ? `${h}:00 am` : `${h - 12}:00 pm`
}

// ── Chart options ─────────────────────────────────────
const trendOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: true, position: 'top', labels: { boxWidth: 10, font: { size: 11 }, usePointStyle: true } },
    tooltip: {
      callbacks: {
        label: ctx => ctx.datasetIndex === 0
          ? ` LKR ${Number(ctx.raw).toLocaleString('en-LK', { maximumFractionDigits: 0 })}`
          : ` ${ctx.raw} bills`,
      },
    },
  },
  scales: {
    y:  { beginAtZero: true, position: 'left',  grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'LKR ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v), font: { size: 10 } } },
    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false },    ticks: { font: { size: 10 } } },
    x:  { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 } },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => ` LKR ${Number(ctx.raw).toLocaleString('en-LK', { maximumFractionDigits: 0 })}` } },
  },
}

const hourlyOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.raw} bills` } } },
  scales: {
    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 9 }, stepSize: 1 } },
    x: { grid: { display: false }, ticks: { font: { size: 8 }, maxRotation: 0, callback: (_, i) => i % 3 === 0 ? hourlyPatternData.value?.labels[i] ?? '' : '' } },
  },
}

// ── Utilities ─────────────────────────────────────────
function shortNum(v) {
  const n = Number(v || 0)
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return n.toLocaleString('en-LK', { maximumFractionDigits: 0 })
}

function statusClass(s) {
  return {
    paid:     'bg-green-100 text-green-700',
    pending:  'bg-yellow-100 text-yellow-700',
    partial:  'bg-blue-100 text-blue-700',
    refunded: 'bg-red-100 text-red-700',
    draft:    'bg-gray-100 text-gray-500',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

onMounted(async () => {
  try {
    const { data: d } = await axios.get('/api/dashboard')
    data.value = d
  } finally {
    loaded.value = true
  }
})
</script>
