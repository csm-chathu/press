<template>
  <div class="max-w-4xl mx-auto space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <router-link to="/quotations" class="text-xs text-gray-500 hover:text-gray-700">← Quotations</router-link>
        <h2 class="text-lg font-semibold text-gray-800 mt-1">{{ qt?.quotation_number }}</h2>
      </div>
      <div class="flex gap-2 flex-wrap">
        <span :class="statusBadge(qt?.status)" class="px-3 py-1 rounded-full text-sm font-semibold capitalize">{{ qt?.status }}</span>
        <button v-if="qt" @click="downloadPdf" :disabled="pdfLoading"
          class="border border-gray-200 text-gray-600 hover:bg-gray-50 px-4 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-1.5 disabled:opacity-50">
          <svg v-if="!pdfLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ pdfLoading ? 'Generating…' : 'Download PDF' }}
        </button>
        <button v-if="qt?.status !== 'converted'" @click="convert" :disabled="converting"
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50">
          {{ converting ? 'Converting…' : 'Convert to Order' }}
        </button>
        <router-link v-if="qt?.order" :to="`/sales/${qt.order.id}`"
          class="border border-amber-300 text-amber-700 px-4 py-1.5 rounded-lg text-sm font-semibold">
          View Order {{ qt.order.invoice_number }}
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
    </div>

    <template v-else-if="qt">
      <!-- Customer & specs -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
          <p class="text-xs text-gray-400 font-medium">Customer</p>
          <p class="font-semibold text-gray-800">{{ qt.customer?.name ?? 'Walk-in' }}</p>
          <p class="text-xs text-gray-500">{{ qt.customer?.phone }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 font-medium">Valid Until</p>
          <p class="font-semibold text-gray-800">{{ qt.valid_until ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 font-medium">Created by</p>
          <p class="font-semibold text-gray-800">{{ qt.created_by?.name ?? '—' }}</p>
          <p class="text-xs text-gray-500">{{ fmtDate(qt.created_at) }}</p>
        </div>
      </div>

      <!-- Print specs -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Print Specifications</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div><p class="text-xs text-gray-400">Title</p><p class="font-medium">{{ qt.title }}</p></div>
          <div><p class="text-xs text-gray-400">Product Type</p><p class="font-medium capitalize">{{ qt.product_type?.replace(/_/g,' ') ?? '—' }}</p></div>
          <div><p class="text-xs text-gray-400">Paper Type</p><p class="font-medium">{{ qt.paper_type ?? '—' }}</p></div>
          <div><p class="text-xs text-gray-400">GSM</p><p class="font-medium">{{ qt.gsm ?? '—' }}</p></div>
          <div><p class="text-xs text-gray-400">Size</p><p class="font-medium">{{ qt.size ?? '—' }}</p></div>
          <div><p class="text-xs text-gray-400">Quantity</p><p class="font-medium">{{ qt.quantity?.toLocaleString() ?? '—' }}</p></div>
          <div><p class="text-xs text-gray-400">Colors</p><p class="font-medium">{{ qt.color_count }} color(s)</p></div>
          <div><p class="text-xs text-gray-400">Method</p><p class="font-medium capitalize">{{ qt.printing_method ?? '—' }}</p></div>
        </div>
      </div>

      <!-- Cost breakdown -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Cost Breakdown</h3>
        <div class="space-y-2 text-sm max-w-sm">
          <div class="flex justify-between text-gray-600"><span>Plate Cost</span><span>Rs. {{ fmt(qt.plate_cost) }}</span></div>
          <div class="flex justify-between text-gray-600"><span>Paper Cost</span><span>Rs. {{ fmt(qt.paper_cost) }}</span></div>
          <div class="flex justify-between text-gray-600"><span>Ink Cost</span><span>Rs. {{ fmt(qt.ink_cost) }}</span></div>
          <div class="flex justify-between text-gray-600"><span>Finishing Cost</span><span>Rs. {{ fmt(qt.finishing_cost) }}</span></div>
          <div class="flex justify-between text-gray-600"><span>Labour Cost</span><span>Rs. {{ fmt(qt.labour_cost) }}</span></div>
          <div class="flex justify-between text-gray-500 text-xs pt-1 border-t border-gray-100">
            <span>Wastage ({{ qt.wastage_percent }}%)</span><span>included</span>
          </div>
          <div class="flex justify-between text-gray-500 text-xs">
            <span>Profit margin ({{ qt.profit_margin_percent }}%)</span><span>included</span>
          </div>
          <div class="flex justify-between font-semibold pt-1 border-t border-gray-100"><span>Subtotal</span><span>Rs. {{ fmt(qt.subtotal) }}</span></div>
          <div class="flex justify-between text-gray-600"><span>Tax ({{ qt.tax_rate }}%)</span><span>Rs. {{ fmt(qt.tax) }}</span></div>
          <div class="flex justify-between text-lg font-bold text-amber-700 border-t border-gray-200 pt-2"><span>TOTAL</span><span>Rs. {{ fmt(qt.total) }}</span></div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="qt.notes || qt.terms" class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div v-if="qt.notes">
          <p class="text-xs text-gray-400 font-medium mb-1">Notes</p>
          <p class="text-gray-700 whitespace-pre-line">{{ qt.notes }}</p>
        </div>
        <div v-if="qt.terms">
          <p class="text-xs text-gray-400 font-medium mb-1">Terms & Conditions</p>
          <p class="text-gray-700 whitespace-pre-line">{{ qt.terms }}</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route  = useRoute()
const router = useRouter()
const qt         = ref(null)
const loading    = ref(true)
const converting = ref(false)
const pdfLoading = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/quotations/${route.params.id}`)
    qt.value = data
  } finally {
    loading.value = false
  }
}

async function downloadPdf() {
  pdfLoading.value = true
  try {
    const response = await axios.get(`/api/quotations/${qt.value.id}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const a = document.createElement('a')
    a.href = url
    a.download = `Quotation-${qt.value.quotation_number}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    alert('Failed to generate PDF')
  } finally {
    pdfLoading.value = false
  }
}

async function convert() {
  if (!confirm('Convert this quotation to a Sales Order?')) return
  converting.value = true
  try {
    const { data } = await axios.post(`/api/quotations/${qt.value.id}/convert`)
    alert(`Converted! Order: ${data.order.invoice_number}`)
    router.push('/sales')
  } catch (e) {
    alert(e.response?.data?.message ?? 'Conversion failed')
  } finally {
    converting.value = false
  }
}

const statusBadge = (s) => ({
  draft: 'bg-gray-100 text-gray-600', sent: 'bg-blue-100 text-blue-700',
  approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-600',
  converted: 'bg-amber-100 text-amber-700',
}[s] ?? 'bg-gray-100 text-gray-500')

function fmt(v) { return Number(v ?? 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-LK') : '—' }

onMounted(load)
</script>
