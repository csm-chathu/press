<template>
  <div class="space-y-6">

    <!-- Header + Tab nav -->
    <div class="flex items-start justify-between gap-4 flex-wrap no-print">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Reports & Analytics</h2>
        <p class="text-sm text-gray-500 mt-0.5">{{ tabLabels[activeTab] }}</p>
      </div>
      <div class="flex flex-wrap gap-1.5 items-center">
        <button v-for="(label, key) in tabLabels" :key="key"
          @click="switchTab(key)"
          :class="activeTab === key
            ? 'bg-amber-500 text-white shadow-sm'
            : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-300 hover:text-amber-700'"
          class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
          {{ label }}
        </button>
      </div>
    </div>

    <!-- Date range filter (shared by most tabs) -->
    <div v-if="showDateFilter" class="flex gap-3 items-end flex-wrap card py-3 no-print">
      <div>
        <label class="form-label">From</label>
        <input v-model="dateFrom" type="date" class="form-input" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="dateTo" type="date" class="form-input" />
      </div>
      <button @click="loadActiveTab" class="btn-primary text-sm">Generate</button>
    </div>

    <div class="hidden print:block border-b pb-3 mb-2">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img v-if="restaurant.logo_url" :src="restaurant.logo_url" alt="Restaurant logo" class="w-14 h-14 rounded object-cover" />
          <div>
            <h1 class="text-lg font-bold text-gray-900">{{ restaurant.name || 'Restaurant' }}</h1>
            <p class="text-xs text-gray-600">{{ restaurant.address || '' }}</p>
            <p class="text-xs text-gray-600">{{ [restaurant.city, restaurant.country].filter(Boolean).join(', ') }}</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-sm font-semibold">{{ tabLabels[activeTab] }}</p>
          <p class="text-xs text-gray-600" v-if="showDateFilter">{{ dateFrom }} to {{ dateTo }}</p>
          <p class="text-xs text-gray-600" v-else>As of {{ dateTo }}</p>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-1.5 no-print">
      <button @click="exportCsv" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:border-amber-300 hover:text-amber-700">
        <i class="fas fa-file-csv mr-2"></i> Export CSV
      </button>
      <button @click="exportPdf" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:border-amber-300 hover:text-amber-700">
        <i class="fas fa-file-pdf mr-2"></i> Export PDF
      </button>
      <button @click="printReport" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:border-amber-300 hover:text-amber-700">
        Print Report
      </button>
    </div>

    <!-- ─── SALES SUMMARY ─── -->
    <div v-if="activeTab === 'summary'" class="space-y-4">
      <div v-if="summary" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Bills</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ summary.totals.count }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Revenue</p>
          <p class="text-2xl font-bold text-green-700 mt-1">LKR {{ lkr(summary.totals.total_revenue) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Discount</p>
          <p class="text-2xl font-bold text-orange-600 mt-1">LKR {{ lkr(summary.totals.total_discount) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Tax</p>
          <p class="text-2xl font-bold text-red-600 mt-1">LKR {{ lkr(summary.totals.total_tax) }}</p>
        </div>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── DAILY REVENUE ─── -->
    <div v-if="activeTab === 'daily'" class="space-y-4">
      <div v-if="daily" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Date</th>
              <th class="table-th text-right">Bills</th>
              <th class="table-th text-right">Revenue</th>
              <th class="table-th text-right">Discounts</th>
              <th class="table-th text-right">Avg Bill</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="d in daily.days" :key="d.date" class="hover:bg-amber-50">
              <td class="table-td font-semibold text-gray-700">{{ d.date }}</td>
              <td class="table-td text-right">{{ d.bill_count }}</td>
              <td class="table-td text-right font-semibold text-green-700">LKR {{ lkr(d.revenue) }}</td>
              <td class="table-td text-right text-orange-600">LKR {{ lkr(d.discount) }}</td>
              <td class="table-td text-right text-gray-500">LKR {{ lkr(d.bill_count > 0 ? d.revenue / d.bill_count : 0) }}</td>
            </tr>
            <tr v-if="!daily.days.length">
              <td colspan="5" class="table-td text-center text-gray-400 py-8">No data for this period</td>
            </tr>
          </tbody>
          <tfoot v-if="daily.days.length" class="bg-gray-50 border-t font-semibold">
            <tr>
              <td class="table-td">Total</td>
              <td class="table-td text-right">{{ daily.days.reduce((s,d)=>s+Number(d.bill_count),0) }}</td>
              <td class="table-td text-right text-green-700">LKR {{ lkr(daily.days.reduce((s,d)=>s+Number(d.revenue),0)) }}</td>
              <td class="table-td text-right text-orange-600">LKR {{ lkr(daily.days.reduce((s,d)=>s+Number(d.discount),0)) }}</td>
              <td class="table-td text-right"></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── TOP PRODUCTS ─── -->
    <div v-if="activeTab === 'products'" class="space-y-4">
      <div v-if="topProductsData" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">#</th>
              <th class="table-th">Product</th>
              <th class="table-th">Category</th>
              <th class="table-th text-right">Qty Sold</th>
              <th class="table-th text-right">Orders</th>
              <th class="table-th text-right">Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(p, i) in topProductsData.products" :key="p.id" class="hover:bg-amber-50">
              <td class="table-td text-gray-400 text-xs font-bold">{{ i + 1 }}</td>
              <td class="table-td font-semibold text-gray-800">{{ p.name }}</td>
              <td class="table-td text-gray-500 text-sm">{{ p.category ?? '—' }}</td>
              <td class="table-td text-right">{{ p.total_qty }}</td>
              <td class="table-td text-right text-gray-500">{{ p.order_count }}</td>
              <td class="table-td text-right font-semibold text-green-700">LKR {{ lkr(p.total_revenue) }}</td>
            </tr>
            <tr v-if="!topProductsData.products.length">
              <td colspan="6" class="table-td text-center text-gray-400 py-8">No sales data for this period</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── CATEGORY SALES ─── -->
    <div v-if="activeTab === 'categories'" class="space-y-4">
      <div v-if="categorySalesData" class="space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="c in categorySalesData.categories" :key="c.category" class="card text-center">
            <p class="text-xs font-semibold text-gray-500 truncate">{{ c.category }}</p>
            <p class="text-xl font-bold text-amber-700 mt-1">LKR {{ lkr(c.total_revenue) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ c.total_qty }} items · {{ c.order_count }} orders</p>
          </div>
        </div>
        <div class="card p-0 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Category</th>
                <th class="table-th text-right">Qty Sold</th>
                <th class="table-th text-right">Orders</th>
                <th class="table-th text-right">Revenue</th>
                <th class="table-th text-right">Share</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="c in categorySalesData.categories" :key="c.category" class="hover:bg-amber-50">
                <td class="table-td font-semibold">{{ c.category }}</td>
                <td class="table-td text-right">{{ c.total_qty }}</td>
                <td class="table-td text-right text-gray-500">{{ c.order_count }}</td>
                <td class="table-td text-right font-semibold text-green-700">LKR {{ lkr(c.total_revenue) }}</td>
                <td class="table-td text-right text-amber-600 font-mono text-sm">{{ categoryShare(c.total_revenue) }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── TABLE PERFORMANCE ─── -->
    <div v-if="activeTab === 'tables'" class="space-y-4">
      <div v-if="tablePerf" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Table</th>
              <th class="table-th text-right">Bills</th>
              <th class="table-th text-right">Revenue</th>
              <th class="table-th text-right">Avg Bill</th>
              <th class="table-th text-right">Discounts</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="t in tablePerf.tables" :key="t.table_label" class="hover:bg-amber-50">
              <td class="table-td font-semibold text-gray-800">{{ t.table_label }}</td>
              <td class="table-td text-right">{{ t.bill_count }}</td>
              <td class="table-td text-right font-semibold text-green-700">LKR {{ lkr(t.total_revenue) }}</td>
              <td class="table-td text-right text-gray-500">LKR {{ lkr(t.avg_bill) }}</td>
              <td class="table-td text-right text-orange-600">LKR {{ lkr(t.total_discount) }}</td>
            </tr>
            <tr v-if="!tablePerf.tables.length">
              <td colspan="5" class="table-td text-center text-gray-400 py-8">No data for this period</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── PAYMENT METHODS ─── -->
    <div v-if="activeTab === 'payments'" class="space-y-4">
      <div v-if="paymentData" class="space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="m in paymentByMethod" :key="m.method" class="card text-center">
            <p class="text-xs font-semibold text-gray-500 capitalize">{{ m.method.replace('_', ' ') }}</p>
            <p class="text-xl font-bold text-amber-700 mt-1">LKR {{ lkr(m.revenue) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ m.count }} bills</p>
          </div>
        </div>
        <div class="card p-0 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Method</th>
                <th class="table-th">Status</th>
                <th class="table-th text-right">Bills</th>
                <th class="table-th text-right">Total</th>
                <th class="table-th text-right">Collected</th>
                <th class="table-th text-right">Outstanding</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="m in paymentData.methods" :key="m.payment_method + m.payment_status" class="hover:bg-amber-50">
                <td class="table-td font-semibold capitalize">{{ m.payment_method.replace('_', ' ') }}</td>
                <td class="table-td">
                  <span :class="{
                    'bg-green-100 text-green-700': m.payment_status === 'paid',
                    'bg-yellow-100 text-yellow-700': m.payment_status === 'partial',
                    'bg-red-100 text-red-700': m.payment_status === 'pending',
                  }" class="badge capitalize">{{ m.payment_status }}</span>
                </td>
                <td class="table-td text-right">{{ m.bill_count }}</td>
                <td class="table-td text-right font-semibold">LKR {{ lkr(m.total_revenue) }}</td>
                <td class="table-td text-right text-green-700">LKR {{ lkr(m.total_collected) }}</td>
                <td class="table-td text-right text-red-600">LKR {{ lkr(m.total_revenue - m.total_collected) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── CASHIER PERFORMANCE ─── -->
    <div v-if="activeTab === 'cashier'" class="space-y-4">
      <div v-if="cashierData" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Staff</th>
              <th class="table-th">Role</th>
              <th class="table-th text-right">Bills</th>
              <th class="table-th text-right">Revenue</th>
              <th class="table-th text-right">Avg Bill</th>
              <th class="table-th text-right">Discounts</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="s in cashierData.staff" :key="s.id" class="hover:bg-amber-50">
              <td class="table-td font-semibold text-gray-800">{{ s.name }}</td>
              <td class="table-td capitalize text-gray-500 text-sm">{{ s.role }}</td>
              <td class="table-td text-right">{{ s.bill_count }}</td>
              <td class="table-td text-right font-semibold text-green-700">LKR {{ lkr(s.total_revenue) }}</td>
              <td class="table-td text-right text-gray-500">LKR {{ lkr(s.avg_bill) }}</td>
              <td class="table-td text-right text-orange-600">LKR {{ lkr(s.total_discount) }}</td>
            </tr>
            <tr v-if="!cashierData.staff.length">
              <td colspan="6" class="table-td text-center text-gray-400 py-8">No data for this period</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── PENDING BILLS ─── -->
    <div v-if="activeTab === 'pending'" class="space-y-4">
      <div v-if="pendingData" class="space-y-4">
        <div class="flex gap-4">
          <div class="card text-center flex-1">
            <p class="text-xs text-gray-500">Pending Bills</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ pendingData.bills.length }}</p>
          </div>
          <div class="card text-center flex-1">
            <p class="text-xs text-gray-500">Total Outstanding</p>
            <p class="text-2xl font-bold text-red-600 mt-1">LKR {{ lkr(pendingData.total_outstanding) }}</p>
          </div>
        </div>
        <div class="card p-0 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Invoice</th>
                <th class="table-th">Table</th>
                <th class="table-th">Customer</th>
                <th class="table-th">Status</th>
                <th class="table-th text-right">Total</th>
                <th class="table-th text-right">Paid</th>
                <th class="table-th text-right">Due</th>
                <th class="table-th">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="b in pendingData.bills" :key="b.id" class="hover:bg-red-50">
                <td class="table-td font-mono text-xs font-semibold">{{ b.invoice_number }}</td>
                <td class="table-td text-gray-500">{{ b.table_number ?? '—' }}</td>
                <td class="table-td">{{ b.customer?.name ?? 'Walk-in' }}</td>
                <td class="table-td">
                  <span :class="b.payment_status === 'partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'" class="badge capitalize">
                    {{ b.payment_status }}
                  </span>
                </td>
                <td class="table-td text-right font-semibold">LKR {{ lkr(b.total) }}</td>
                <td class="table-td text-right text-green-700">LKR {{ lkr(b.amount_paid) }}</td>
                <td class="table-td text-right font-bold text-red-600">LKR {{ lkr(b.total - b.amount_paid) }}</td>
                <td class="table-td text-xs text-gray-400">{{ b.created_at?.substring(0, 10) }}</td>
              </tr>
              <tr v-if="!pendingData.bills.length">
                <td colspan="8" class="table-td text-center text-green-600 py-8 font-semibold">✓ All bills are settled</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── STOCK LEVELS ─── -->
    <div v-if="activeTab === 'stock'" class="space-y-4">
      <div v-if="stockData" class="space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500">Total Products</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ stockData.products.length }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500">Cost Value</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">LKR {{ lkr(stockData.total_cost_value) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500">Low Stock</p>
            <p class="text-3xl font-bold text-orange-500 mt-1">{{ stockData.low_stock_count }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500">Out of Stock</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ stockData.out_of_stock }}</p>
          </div>
        </div>
        <div class="card p-0 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Product</th>
                <th class="table-th">Category</th>
                <th class="table-th">SKU</th>
                <th class="table-th text-right">Stock</th>
                <th class="table-th text-right">Min</th>
                <th class="table-th text-right">Cost</th>
                <th class="table-th text-right">Price</th>
                <th class="table-th text-right">Stock Value</th>
                <th class="table-th">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="p in stockData.products" :key="p.id"
                :class="p.stock_quantity === 0 ? 'bg-red-50' : p.stock_quantity <= p.min_stock_level ? 'bg-orange-50' : 'hover:bg-gray-50'">
                <td class="table-td font-semibold text-sm">{{ p.name }}</td>
                <td class="table-td text-gray-500 text-sm">{{ p.category?.name ?? '—' }}</td>
                <td class="table-td font-mono text-xs text-gray-400">{{ p.sku ?? '—' }}</td>
                <td class="table-td text-right font-bold">{{ p.stock_quantity }}</td>
                <td class="table-td text-right text-gray-400 text-sm">{{ p.min_stock_level }}</td>
                <td class="table-td text-right text-gray-600">LKR {{ lkr(p.purchase_price) }}</td>
                <td class="table-td text-right text-green-700">LKR {{ lkr(p.selling_price) }}</td>
                <td class="table-td text-right font-semibold">LKR {{ lkr(p.purchase_price * p.stock_quantity) }}</td>
                <td class="table-td">
                  <span v-if="p.stock_quantity === 0" class="badge bg-red-100 text-red-700">Out</span>
                  <span v-else-if="p.stock_quantity <= p.min_stock_level" class="badge bg-orange-100 text-orange-700">Low</span>
                  <span v-else class="badge bg-green-100 text-green-700">OK</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── TAX SETTINGS ─── -->
    <div v-if="activeTab === 'tax'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="font-semibold text-gray-700">VAT / Tax Settings</h3>
        <button @click="openTaxModal(null)" class="btn-primary text-sm flex items-center gap-2">
          <PlusIcon class="w-4 h-4" /> Add Tax
        </button>
      </div>
      <div class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Name</th>
              <th class="table-th">Rate (%)</th>
              <th class="table-th">Applies To</th>
              <th class="table-th">Status</th>
              <th class="table-th">Description</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="t in taxList" :key="t.id" class="hover:bg-gray-50">
              <td class="table-td font-semibold">{{ t.name }}</td>
              <td class="table-td text-blue-700 font-mono">{{ t.rate }}%</td>
              <td class="table-td capitalize text-sm">{{ t.applies_to.replace('_', ' ') }}</td>
              <td class="table-td">
                <span :class="t.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="badge">
                  {{ t.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="table-td text-gray-400 text-sm">{{ t.description ?? '—' }}</td>
              <td class="table-td text-right">
                <div class="flex justify-end gap-1.5">
                  <button @click="openTaxModal(t)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </button>
                  <button @click="deleteTax(t)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tax modal -->
      <div v-if="showTaxModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold">{{ editingTax ? 'Edit Tax' : 'Add Tax Setting' }}</h3>
            <button @click="showTaxModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <form @submit.prevent="saveTax" class="p-6 space-y-4">
            <div>
              <label class="form-label">Name *</label>
              <input v-model="taxForm.name" required class="form-input" placeholder="e.g. VAT, GST" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Rate (%) *</label>
                <input v-model.number="taxForm.rate" type="number" min="0" max="100" step="0.01" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Applies To *</label>
                <select v-model="taxForm.applies_to" required class="form-input">
                  <option value="all">All</option>
                  <option value="food_beverages">Food & Beverages</option>
                  <option value="service_charge">Service Charge</option>
                  <option value="making_charges">Making Charges</option>
                </select>
              </div>
            </div>
            <div>
              <label class="form-label">Description</label>
              <input v-model="taxForm.description" class="form-input" />
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="taxForm.is_active" class="w-4 h-4 rounded" />
              <span class="text-sm font-medium text-gray-700">Active (available at POS)</span>
            </label>
            <p v-if="taxError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ taxError }}</p>
            <div class="flex gap-3">
              <button type="button" @click="showTaxModal = false" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="taxSaving" class="btn-primary flex-1">{{ taxSaving ? 'Saving…' : 'Save' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ─── JOURNAL ENTRIES ─── -->
    <div v-if="activeTab === 'journal'" class="space-y-4">
      <div v-if="journalData" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Date</th>
              <th class="table-th">Source</th>
              <th class="table-th">Reference</th>
              <th class="table-th">Description</th>
              <th class="table-th text-right">Lines</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="entry in journalData.entries.data" :key="entry.id" class="hover:bg-amber-50">
              <td class="table-td font-semibold">{{ entry.entry_date }}</td>
              <td class="table-td text-gray-600">{{ entry.source_type }}</td>
              <td class="table-td font-mono text-xs">{{ entry.reference ?? '—' }}</td>
              <td class="table-td text-gray-600">{{ entry.description ?? '—' }}</td>
              <td class="table-td text-right text-gray-500">{{ entry.lines?.length ?? 0 }}</td>
            </tr>
            <tr v-if="!(journalData.entries.data?.length)">
              <td colspan="5" class="table-td text-center text-gray-400 py-8">No journal entries for this period</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── TRIAL BALANCE ─── -->
    <div v-if="activeTab === 'trial'" class="space-y-4">
      <div v-if="trialData" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Code</th>
              <th class="table-th">Account</th>
              <th class="table-th">Type</th>
              <th class="table-th text-right">Debit</th>
              <th class="table-th text-right">Credit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in trialData.accounts" :key="row.id" class="hover:bg-amber-50">
              <td class="table-td font-mono text-xs">{{ row.code }}</td>
              <td class="table-td font-semibold">{{ row.name }}</td>
              <td class="table-td capitalize text-gray-500">{{ row.type }}</td>
              <td class="table-td text-right text-green-700">LKR {{ lkr(row.debit) }}</td>
              <td class="table-td text-right text-red-600">LKR {{ lkr(row.credit) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50 border-t font-semibold">
            <tr>
              <td colspan="3" class="table-td">Totals</td>
              <td class="table-td text-right text-green-700">LKR {{ lkr(trialData.totals.debit) }}</td>
              <td class="table-td text-right text-red-600">LKR {{ lkr(trialData.totals.credit) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── PROFIT & LOSS ─── -->
    <div v-if="activeTab === 'pl'" class="space-y-4">
      <div v-if="plData" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Revenue</p>
          <p class="text-2xl font-bold text-green-700 mt-1">LKR {{ lkr(plData.summary.total_revenue) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Expenses</p>
          <p class="text-2xl font-bold text-red-600 mt-1">LKR {{ lkr(plData.summary.total_expense) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Net Profit</p>
          <p class="text-2xl font-bold mt-1" :class="Number(plData.summary.net_profit) >= 0 ? 'text-emerald-700' : 'text-red-700'">
            LKR {{ lkr(plData.summary.net_profit) }}
          </p>
        </div>
      </div>
      <div v-if="plData" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Code</th>
              <th class="table-th">Account</th>
              <th class="table-th">Type</th>
              <th class="table-th text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in [...plData.revenues, ...plData.expenses]" :key="row.id" class="hover:bg-amber-50">
              <td class="table-td font-mono text-xs">{{ row.code }}</td>
              <td class="table-td font-semibold">{{ row.name }}</td>
              <td class="table-td capitalize">{{ row.type }}</td>
              <td class="table-td text-right" :class="row.type === 'revenue' ? 'text-green-700' : 'text-red-600'">LKR {{ lkr(row.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!plData" class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- ─── BALANCE SHEET ─── -->
    <div v-if="activeTab === 'bs'" class="space-y-4">
      <div v-if="bsData" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Assets</p>
          <p class="text-2xl font-bold text-blue-700 mt-1">LKR {{ lkr(bsData.totals.assets) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Liabilities</p>
          <p class="text-2xl font-bold text-orange-700 mt-1">LKR {{ lkr(bsData.totals.liabilities) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Equity</p>
          <p class="text-2xl font-bold text-emerald-700 mt-1">LKR {{ lkr(bsData.totals.equity) }}</p>
        </div>
      </div>

      <div v-if="bsData" class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Code</th>
              <th class="table-th">Account</th>
              <th class="table-th">Type</th>
              <th class="table-th text-right">Balance</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in [...bsData.assets, ...bsData.liabilities, ...bsData.equity]" :key="row.id" class="hover:bg-amber-50">
              <td class="table-td font-mono text-xs">{{ row.code }}</td>
              <td class="table-td font-semibold">{{ row.name }}</td>
              <td class="table-td capitalize">{{ row.type }}</td>
              <td class="table-td text-right font-semibold">LKR {{ lkr(row.balance) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!bsData" class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import ConfirmModal from '@/components/ConfirmModal.vue'

const tabLabels = {
  summary:    'Sales Summary',
  daily:      'Daily Revenue',
  products:   'Top Products',
  categories: 'Category Sales',
  tables:     'Table Performance',
  payments:   'Payment Methods',
  cashier:    'Staff Performance',
  pending:    'Pending Bills',
  stock:      'Stock Levels',
  journal:    'Journal Entries',
  trial:      'Trial Balance',
  pl:         'Profit & Loss',
  bs:         'Balance Sheet',
  tax:        'Tax Settings',
}

const tabsWithDates = ['summary','daily','products','categories','tables','payments','cashier','journal','trial','pl','bs']
const showDateFilter = computed(() => tabsWithDates.includes(activeTab.value))

const activeTab = ref('summary')
const dateFrom  = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const dateTo    = ref(new Date().toISOString().split('T')[0])
const restaurant = ref({ name: '', address: '', city: '', country: '', logo_url: '' })

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Data refs
const summary           = ref(null)
const daily             = ref(null)
const topProductsData   = ref(null)
const categorySalesData = ref(null)
const tablePerf         = ref(null)
const paymentData       = ref(null)
const cashierData       = ref(null)
const pendingData       = ref(null)
const stockData         = ref(null)
const journalData       = ref(null)
const trialData         = ref(null)
const plData            = ref(null)
const bsData            = ref(null)
const taxList           = ref([])

const categoryShare = (revenue) => {
  const total = categorySalesData.value?.categories?.reduce((s, c) => s + Number(c.total_revenue), 0) || 1
  return ((revenue / total) * 100).toFixed(1)
}

const paymentByMethod = computed(() => {
  if (!paymentData.value) return []
  const map = {}
  for (const m of paymentData.value.methods) {
    if (!map[m.payment_method]) map[m.payment_method] = { method: m.payment_method, revenue: 0, count: 0 }
    map[m.payment_method].revenue += Number(m.total_revenue)
    map[m.payment_method].count  += Number(m.bill_count)
  }
  return Object.values(map).sort((a, b) => b.revenue - a.revenue)
})

const params = () => ({ date_from: dateFrom.value, date_to: dateTo.value })

async function loadSummary()    { const {data} = await axios.get('/api/reports/sales-summary',       {params: params()}); summary.value = data }
async function loadDaily()      { const {data} = await axios.get('/api/reports/daily-revenue',        {params: params()}); daily.value = data }
async function loadTopProducts(){ const {data} = await axios.get('/api/reports/top-products',         {params: params()}); topProductsData.value = data }
async function loadCategories() { const {data} = await axios.get('/api/reports/category-sales',       {params: params()}); categorySalesData.value = data }
async function loadTables()     { const {data} = await axios.get('/api/reports/table-performance',    {params: params()}); tablePerf.value = data }
async function loadPayments()   { const {data} = await axios.get('/api/reports/payment-methods',      {params: params()}); paymentData.value = data }
async function loadCashier()    { const {data} = await axios.get('/api/reports/cashier-performance',  {params: params()}); cashierData.value = data }
async function loadPending()    { const {data} = await axios.get('/api/reports/pending-bills');        pendingData.value = data }
async function loadStock()      { const {data} = await axios.get('/api/reports/stock-summary');        stockData.value = data }
async function loadJournal()    { const {data} = await axios.get('/api/accounting/journal-entries',    {params: params()}); journalData.value = data }
async function loadTrial()      { const {data} = await axios.get('/api/reports/trial-balance',         {params: params()}); trialData.value = data }
async function loadProfitLoss() { const {data} = await axios.get('/api/reports/profit-loss',           {params: params()}); plData.value = data }
async function loadBalanceSheet(){ const {data} = await axios.get('/api/reports/balance-sheet',        {params: { date: dateTo.value }}); bsData.value = data }
async function loadTaxes()      { const {data} = await axios.get('/api/tax-settings');                 taxList.value = data }
async function loadRestaurant() { const {data} = await axios.get('/api/settings/restaurant');           restaurant.value = data }

const loaders = {
  summary: loadSummary, daily: loadDaily, products: loadTopProducts,
  categories: loadCategories, tables: loadTables, payments: loadPayments,
  cashier: loadCashier, pending: loadPending, stock: loadStock,
  journal: loadJournal, trial: loadTrial, pl: loadProfitLoss, bs: loadBalanceSheet,
  tax: loadTaxes,
}

async function loadActiveTab() { await loaders[activeTab.value]?.() }

function switchTab(key) { activeTab.value = key; loadActiveTab() }

function printReport() {
  window.print()
}

function toFileSafe(value) {
  return String(value || 'report').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
}

function csvCell(value) {
  const str = value == null ? '' : String(value)
  return `"${str.replace(/"/g, '""')}"`
}

function tableFromActiveTab() {
  switch (activeTab.value) {
    case 'summary': {
      const totals = summary.value?.totals ?? {}
      return {
        headers: ['Metric', 'Value'],
        rows: [
          ['Bills', totals.count ?? 0],
          ['Revenue', totals.total_revenue ?? 0],
          ['Total Discount', totals.total_discount ?? 0],
          ['Total Tax', totals.total_tax ?? 0],
        ],
      }
    }
    case 'daily':
      return {
        headers: ['Date', 'Bills', 'Revenue', 'Discounts', 'Avg Bill'],
        rows: (daily.value?.days ?? []).map((d) => [d.date, d.bill_count, d.revenue, d.discount, d.bill_count > 0 ? d.revenue / d.bill_count : 0]),
      }
    case 'products':
      return {
        headers: ['Product', 'Category', 'Qty Sold', 'Orders', 'Revenue'],
        rows: (topProductsData.value?.products ?? []).map((p) => [p.name, p.category ?? '', p.total_qty, p.order_count, p.total_revenue]),
      }
    case 'categories':
      return {
        headers: ['Category', 'Qty Sold', 'Orders', 'Revenue', 'Share %'],
        rows: (categorySalesData.value?.categories ?? []).map((c) => [c.category, c.total_qty, c.order_count, c.total_revenue, categoryShare(Number(c.total_revenue))]),
      }
    case 'tables':
      return {
        headers: ['Table', 'Bills', 'Revenue', 'Avg Bill', 'Discounts'],
        rows: (tablePerf.value?.tables ?? []).map((t) => [t.table_label, t.bill_count, t.total_revenue, t.avg_bill, t.total_discount]),
      }
    case 'payments':
      return {
        headers: ['Method', 'Status', 'Bills', 'Total', 'Collected', 'Outstanding'],
        rows: (paymentData.value?.methods ?? []).map((m) => [m.payment_method, m.payment_status, m.bill_count, m.total_revenue, m.total_collected, Number(m.total_revenue) - Number(m.total_collected)]),
      }
    case 'cashier':
      return {
        headers: ['Staff', 'Role', 'Bills', 'Revenue', 'Avg Bill', 'Discounts'],
        rows: (cashierData.value?.staff ?? []).map((s) => [s.name, s.role, s.bill_count, s.total_revenue, s.avg_bill, s.total_discount]),
      }
    case 'pending':
      return {
        headers: ['Invoice', 'Table', 'Customer', 'Status', 'Total', 'Paid', 'Due', 'Date'],
        rows: (pendingData.value?.bills ?? []).map((b) => [b.invoice_number, b.table_number ?? '', b.customer?.name ?? 'Walk-in', b.payment_status, b.total, b.amount_paid, Number(b.total) - Number(b.amount_paid), (b.created_at ?? '').substring(0, 10)]),
      }
    case 'stock':
      return {
        headers: ['Product', 'Category', 'SKU', 'Stock', 'Min', 'Cost', 'Price', 'Stock Value', 'Status'],
        rows: (stockData.value?.products ?? []).map((p) => [p.name, p.category?.name ?? '', p.sku ?? '', p.stock_quantity, p.min_stock_level, p.purchase_price, p.selling_price, Number(p.purchase_price) * Number(p.stock_quantity), p.stock_quantity === 0 ? 'Out' : (p.stock_quantity <= p.min_stock_level ? 'Low' : 'OK')]),
      }
    case 'journal':
      return {
        headers: ['Date', 'Source', 'Reference', 'Description', 'Lines'],
        rows: (journalData.value?.entries?.data ?? []).map((e) => [e.entry_date, e.source_type, e.reference ?? '', e.description ?? '', e.lines?.length ?? 0]),
      }
    case 'trial':
      return {
        headers: ['Code', 'Account', 'Type', 'Debit', 'Credit'],
        rows: (trialData.value?.accounts ?? []).map((r) => [r.code, r.name, r.type, r.debit, r.credit]),
      }
    case 'pl':
      return {
        headers: ['Code', 'Account', 'Type', 'Amount'],
        rows: [...(plData.value?.revenues ?? []), ...(plData.value?.expenses ?? [])].map((r) => [r.code, r.name, r.type, r.amount]),
      }
    case 'bs':
      return {
        headers: ['Code', 'Account', 'Type', 'Balance'],
        rows: [...(bsData.value?.assets ?? []), ...(bsData.value?.liabilities ?? []), ...(bsData.value?.equity ?? [])].map((r) => [r.code, r.name, r.type, r.balance]),
      }
    case 'tax':
      return {
        headers: ['Name', 'Rate %', 'Applies To', 'Status', 'Description'],
        rows: (taxList.value ?? []).map((t) => [t.name, t.rate, t.applies_to, t.is_active ? 'Active' : 'Inactive', t.description ?? '']),
      }
    default:
      return { headers: ['Value'], rows: [] }
  }
}

function dateRangeLabel() {
  if (showDateFilter.value) {
    return `${dateFrom.value} to ${dateTo.value}`
  }
  return `As of ${dateTo.value}`
}

async function exportCsv() {
  await loadActiveTab()
  const table = tableFromActiveTab()
  const lines = [table.headers.map(csvCell).join(',')]
  for (const row of table.rows) {
    lines.push(row.map(csvCell).join(','))
  }
  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${toFileSafe(tabLabels[activeTab.value])}.csv`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

async function exportPdf() {
  await loadActiveTab()
  const table = tableFromActiveTab()

  const { data } = await axios.post('/api/reports/export/pdf', {
    title: tabLabels[activeTab.value],
    file_name: toFileSafe(tabLabels[activeTab.value]),
    date_range: dateRangeLabel(),
    restaurant: restaurant.value,
    headers: table.headers,
    rows: table.rows,
  }, { responseType: 'blob' })

  const url = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
  const a = document.createElement('a')
  a.href = url
  a.download = `${toFileSafe(tabLabels[activeTab.value])}.pdf`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

// Tax Settings
const showTaxModal   = ref(false)
const editingTax     = ref(null)
const taxSaving      = ref(false)
const taxError       = ref('')
const confirmDelete  = ref(null)
const confirmMessage = ref('')
const taxForm = reactive({ name: '', rate: 0, applies_to: 'all', is_active: true, description: '' })

function openTaxModal(t) {
  editingTax.value = t; taxError.value = ''
  Object.assign(taxForm, { name: t?.name ?? '', rate: t?.rate ?? 0, applies_to: t?.applies_to ?? 'all', is_active: t?.is_active ?? true, description: t?.description ?? '' })
  showTaxModal.value = true
}

async function saveTax() {
  taxSaving.value = true; taxError.value = ''
  try {
    if (editingTax.value) await axios.put(`/api/tax-settings/${editingTax.value.id}`, taxForm)
    else                  await axios.post('/api/tax-settings', taxForm)
    showTaxModal.value = false
    loadTaxes()
  } catch (e) {
    taxError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { taxSaving.value = false }
}

function deleteTax(t) {
  confirmDelete.value  = t
  confirmMessage.value = `Delete tax "${t.name}"? This cannot be undone.`
}

async function doDelete() {
  await axios.delete(`/api/tax-settings/${confirmDelete.value.id}`)
  confirmDelete.value = null
  loadTaxes()
}

onMounted(() => { loadSummary(); loadTaxes(); loadRestaurant() })
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }

  .card {
    box-shadow: none !important;
    border: 1px solid #e5e7eb;
    break-inside: avoid;
  }

  table {
    font-size: 12px;
  }
}
</style>
