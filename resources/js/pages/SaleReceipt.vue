<template>
  <div>
    <!-- Screen toolbar (hidden when printing) -->
    <div class="no-print flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <router-link to="/sales"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
          <ArrowLeftIcon class="w-4 h-4" /> Back to Bills
        </router-link>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-medium text-gray-700">{{ sale?.invoice_number }}</span>
      </div>
      <div class="flex gap-2">
        <button @click="downloadInvoice" :disabled="loading || !sale || pdfLoading"
          class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 hover:bg-gray-50 disabled:opacity-60 text-gray-700 rounded-lg font-medium text-sm transition-colors">
          <svg v-if="pdfLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
          {{ pdfLoading ? 'Generating…' : 'Download Invoice' }}
        </button>
        <button @click="printReceipt" :disabled="loading || !sale || printing"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
          <svg v-if="printing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          <PrinterIcon v-else class="w-4 h-4" />
          {{ printing ? 'Printing…' : 'Print Receipt' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20 text-gray-400">
      <ArrowPathIcon class="w-5 h-5 animate-spin mr-2" /> Loading…
    </div>

    <!-- Receipt (76mm thermal preview) -->
    <div v-else-if="sale" id="receipt-wrapper">
      <div id="receipt" class="receipt-paper">

        <!-- ── HEADER ── -->
        <div style="text-align:center; margin-bottom:6px;">
          <div v-if="restaurant.logo_url" style="margin-bottom:5px;">
            <img :src="restaurant.logo_url" alt="Logo" class="receipt-logo"
              style="max-width:120px; max-height:60px; width:auto; height:auto; display:inline-block; object-fit:contain;" />
          </div>
          <div style="font-size:15px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">
            {{ receiptCompanyName }}
          </div>
          <div v-if="receiptAddress" style="font-size:10px; margin-top:2px; line-height:1.35; white-space:pre-line; font-weight:600;">
            {{ receiptAddress }}
          </div>
          <div style="font-size:10px; margin-top:2px;">Bill Receipt</div>
        </div>

        <hr class="receipt-divider-double" />

        <!-- ── INVOICE META ── -->
        <div style="font-size:11px; font-weight:600; line-height:1.5; margin-bottom:4px; color:#000;">
          <div class="flex-row">
            <span>Invoice :</span><span style="font-weight:bold; float:right;">{{ sale.invoice_number }}</span>
          </div>
          <div class="flex-row">
            <span>Date    :</span><span style="float:right;">{{ formatDate(sale.sold_at) }}</span>
          </div>
          <div class="flex-row">
            <span>Time    :</span><span style="float:right;">{{ formatTime(sale.sold_at) }}</span>
          </div>
          <div class="flex-row">
            <span>Cashier :</span><span style="float:right;">{{ sale.user?.name ?? '—' }}</span>
          </div>
          <div class="flex-row">
            <span>Payment :</span><span style="float:right; text-transform:capitalize;">{{ paymentSummary }}</span>
          </div>
          <div class="flex-row">
            <span>Status  :</span>
            <span style="float:right; font-weight:bold; text-transform:capitalize;">
              {{ (sale.payment_status || sale.status || 'completed').toUpperCase() }}
            </span>
          </div>
        </div>

        <hr class="receipt-divider" />

        <!-- ── CUSTOMER ── -->
        <div style="font-size:10px; margin-bottom:4px;">
          <div><strong>Customer:</strong> {{ sale.customer?.name ?? 'Walk-in' }}</div>
          <div v-if="sale.customer?.phone">Phone: {{ sale.customer.phone }}</div>
        </div>

        <hr class="receipt-divider" />

        <!-- ── ITEMS ── -->
        <div style="font-size:10px;">
          <!-- Column headers -->
          <div style="display:flex; font-weight:bold; border-bottom:1px solid #333; padding-bottom:3px; margin-bottom:3px;">
            <span style="flex:1;">Item</span>
            <span style="width:28px; text-align:center;">Qty</span>
            <span style="width:54px; text-align:right;">Price</span>
            <span style="width:58px; text-align:right;">Total</span>
          </div>

          <!-- Each item -->
          <div v-for="item in sale.items" :key="item.id" style="margin-bottom:5px;">
            <div style="display:flex; align-items:baseline;">
              <span style="flex:1; font-weight:bold; word-break:break-word; padding-right:4px;">
                {{ item.product?.name ?? 'Unknown' }}
              </span>
              <span style="width:28px; text-align:center;">{{ item.quantity }}</span>
              <span style="width:54px; text-align:right;">{{ lkr(item.unit_price) }}</span>
              <span style="width:58px; text-align:right; font-weight:bold;">{{ lkr(item.total) }}</span>
            </div>
            <!-- Sub-detail line -->
            <div style="color:#222; font-size:10px; padding-left:2px; line-height:1.35;">
              <span v-if="item.open_bottle_id" style="font-style:italic;">[Opened bottle]  </span>
              <span v-if="Number(item.serving_ml) > 0">{{ item.serving_ml }}ml/shot  </span>
              <span v-if="item.product?.sku">SKU:{{ item.product.sku }}  </span>
              <span v-if="item.product?.karat">{{ item.product.karat }}</span>
              <span v-if="item.product?.weight"> {{ item.product.weight }}g</span>
            </div>
            <!-- Value breakdown -->
            <div v-if="Number(item.gold_value) > 0 || Number(item.making_charge) > 0 || Number(item.wastage_amount) > 0 || Number(item.gemstone_value) > 0"
              style="font-size:10px; color:#222; padding-left:2px;">
              <span v-if="Number(item.gold_value) > 0">Gold:{{ lkr(item.gold_value) }}  </span>
              <span v-if="Number(item.gemstone_value) > 0">Gem:{{ lkr(item.gemstone_value) }}  </span>
              <span v-if="Number(item.making_charge) > 0">MC:{{ lkr(item.making_charge) }}  </span>
              <span v-if="Number(item.wastage_amount) > 0">Wst:{{ lkr(item.wastage_amount) }}</span>
            </div>
            <div v-if="Number(item.discount) > 0" style="font-size:10px; color:#222; padding-left:2px;">
              Item Disc: -{{ lkr(item.discount) }}
            </div>
          </div>
        </div>

        <hr class="receipt-divider-solid" />

        <!-- ── TOTALS ── -->
        <div style="font-size:11px;">
          <div v-if="Number(sale.subtotal) !== Number(sale.total)" style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>Subtotal</span><span>LKR {{ lkr(sale.subtotal) }}</span>
          </div>
          <div v-if="Number(sale.discount) > 0" style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>Discount</span><span>-LKR {{ lkr(sale.discount) }}</span>
          </div>
          <div v-if="Number(sale.tax) > 0" style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>Tax ({{ sale.tax_rate }}%)</span><span>+LKR {{ lkr(sale.tax) }}</span>
          </div>
        </div>

        <hr class="receipt-divider-double" />

        <div style="display:flex; justify-content:space-between; font-size:14px; font-weight:bold; margin:4px 0;">
          <span>TOTAL</span><span>LKR {{ lkr(sale.total) }}</span>
        </div>

        <hr class="receipt-divider" />

        <div style="font-size:11px;">
          <!-- Split / multi-payment breakdown -->
          <template v-if="sale.payments && sale.payments.length > 1">
            <div v-for="(payment, index) in sale.payments" :key="payment.id ?? index"
              style="display:flex; justify-content:space-between; margin-bottom:2px; font-weight:600;">
              <span style="text-transform:capitalize;">{{ payment.payment_method?.replace(/_/g, ' ') }}</span>
              <span>LKR {{ lkr(payment.amount) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:2px; border-top:1px dashed #999; padding-top:2px; font-weight:bold;">
              <span>Total Paid</span><span>LKR {{ lkr(sale.amount_paid) }}</span>
            </div>
          </template>
          <!-- Single payment -->
          <template v-else>
            <div v-if="sale.payments && sale.payments.length === 1"
              style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <span style="text-transform:capitalize;">{{ sale.payments[0].payment_method?.replace(/_/g, ' ') }}</span>
              <span>LKR {{ lkr(sale.payments[0].amount) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
              <span>Paid</span><span>LKR {{ lkr(sale.amount_paid) }}</span>
            </div>
          </template>
          <!-- Change / balance due -->
          <div v-if="Number(sale.amount_paid) > Number(sale.total)"
            style="display:flex; justify-content:space-between; font-weight:bold;">
            <span>Change</span><span>LKR {{ lkr(Number(sale.amount_paid) - Number(sale.total)) }}</span>
          </div>
          <div v-if="Number(sale.amount_paid) < Number(sale.total)"
            style="display:flex; justify-content:space-between; font-weight:bold;">
            <span>Balance Due</span><span>LKR {{ lkr(Number(sale.total) - Number(sale.amount_paid)) }}</span>
          </div>
        </div>

        <!-- ── NOTES ── -->
        <div v-if="sale.notes" style="margin-top:6px; font-size:10px; color:#222;">
          <hr class="receipt-divider" />
          Note: {{ sale.notes }}
        </div>

        <hr class="receipt-divider" />

        <!-- ── FOOTER ── -->
        <div style="text-align:center; font-size:10px; line-height:1.6;">
          <div style="font-weight:bold;">*** Thank You! Come Again ***</div>
          <div style="font-size:10px; color:#222;">{{ formatDate(sale.sold_at) }}</div>
          <div style="font-size:10px; font-weight:600; margin-top:3px; letter-spacing:0.5px;">www.lumac.lk</div>
        </div>

      </div>
    </div>

    <!-- Error -->
    <div v-else class="text-center py-20 text-gray-400">
      <p>Bill not found.</p>
      <router-link to="/sales" class="text-amber-600 hover:underline text-sm mt-2 inline-block">← Back to Bills</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ArrowLeftIcon, PrinterIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const route          = useRoute()
const router         = useRouter()
const sale           = ref(null)
const loading        = ref(true)
const printing       = ref(false)
const pdfLoading     = ref(false)
const appName        = import.meta.env.VITE_APP_NAME ?? 'Liquor Shop POS'
const restaurant     = ref({ name: '', address: '', city: '', country: '' })

const receiptCompanyName = computed(() => (restaurant.value.name || appName))
const receiptAddress = computed(() => {
  const lines = [restaurant.value.address, [restaurant.value.city, restaurant.value.country].filter(Boolean).join(', ')]
    .map(v => (v || '').trim())
    .filter(Boolean)
  return lines.join('\n')
})
const paymentSummary = computed(() => {
  const payments = sale.value?.payments ?? []
  const methods = [...new Set(payments.map(payment => payment.payment_method).filter(Boolean))]

  if (methods.length > 1) {
    return methods.map(method => method.replace('_', ' ')).join(' + ')
  }

  if (methods.length === 1) {
    return methods[0].replace('_', ' ')
  }

  return sale.value?.payment_method?.replace('_', ' ') ?? 'Unpaid'
})

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatDate(d) {
  return new Date(d).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
}
function formatTime(d) {
  return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}


async function downloadInvoice() {
  pdfLoading.value = true
  try {
    const response = await axios.get(`/api/sales/${route.params.id}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const a = document.createElement('a')
    a.href = url; a.download = `Invoice-${sale.value.invoice_number}.pdf`; a.click()
    URL.revokeObjectURL(url)
  } catch { alert('Failed to generate invoice PDF') }
  finally { pdfLoading.value = false }
}

async function printReceipt(autoRedirect = false) {
  printing.value = true
  try {
    // Use window.print() only — electronAPI.printReceipt + kiosk-printing causes double print
    window.print()
    await new Promise(r => setTimeout(r, 600))
  } finally {
    printing.value = false
  }
  if (autoRedirect) router.push('/sales/new')
}


onMounted(async () => {
  try {
    const [saleRes, settingsRes] = await Promise.all([
      axios.get(`/api/sales/${route.params.id}`),
      axios.get('/api/settings/restaurant').catch(() => ({ data: {} })),
    ])

    sale.value = saleRes.data
    restaurant.value = settingsRes.data || {}
    await nextTick()

    if (route.query.print === '1') {
      setTimeout(() => printReceipt(true), 400)
    }
  } catch {
    sale.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style>
/* ── Screen preview ─────────────────────────────────────── */
.receipt-paper {
  width: 287px;           /* 76mm ≈ 287px at 96dpi */
  padding: 16px 22px 16px 14px;
  margin: 0 auto 32px;
  background: #fff;
  box-shadow: 0 0 0 1px #e5e7eb, 0 4px 24px rgba(0,0,0,0.08);
  border-radius: 4px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  line-height: 1.45;
  font-weight: 500;
  color: #111;
}

.receipt-divider        { border: none; border-top: 1px dashed #666; margin: 6px 0; }
.receipt-divider-solid  { border: none; border-top: 1px solid #555; margin: 6px 0; }
.receipt-divider-double { border: none; border-top: 3px double #333; margin: 6px 0; }

/* ── 76mm Thermal print ──────────────────────────────────── */
@media print {
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    overflow: visible !important;
    background: #fff !important;
  }

  /* Reset application layout wrappers to avoid clipping and extra whitespace */
  #app,
  #app > div,
  #app > div > div,
  #app > div > div > div,
  #app main {
    display: block !important;
    width: auto !important;
    min-width: 0 !important;
    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    padding: 0 !important;
    margin: 0 !important;
    background: #fff !important;
    flex: none !important;
  }

  aside, header, .no-print { display: none !important; }

  #receipt-wrapper {
    position: static !important;
    width: 75mm !important;
    padding: 0 !important;
    margin: 0 auto !important;
    overflow: visible !important;
    page-break-before: auto;
    page-break-after: auto;
  }

  .receipt-paper {
    width: 75mm !important;
    max-width: 75mm !important;
    margin: 0 !important;
    padding: 4mm 7mm 4mm 3mm !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    font-size: 11pt !important;
    font-weight: 500 !important;
    font-family: 'Courier New', Courier, monospace !important;
    color: #000 !important;
    background: #fff !important;
    break-inside: auto;
    page-break-inside: auto;
  }

  /* Force black — thermal has no colour ink */
  #receipt-wrapper,
  #receipt-wrapper * {
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    background: transparent !important;
  }

  /* Keep logo visible when printing */
  #receipt-wrapper img.receipt-logo {
    display: inline-block !important;
    max-width: 120px !important;
    max-height: 60px !important;
  }


  @page {
    size: 76mm auto;
    margin: 0;
  }
}
</style>
