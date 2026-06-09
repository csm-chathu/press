<template>
  <div class="max-w-5xl mx-auto space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <router-link to="/job-cards" class="text-xs text-gray-500 hover:text-gray-700">← Job Cards</router-link>
        <div class="flex items-center gap-3 mt-1">
          <h2 class="text-lg font-semibold text-gray-800">{{ job?.job_number }}</h2>
          <span :class="statusBadge(job?.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold">{{ statusLabel(job?.status) }}</span>
          <span v-if="job?.is_priority" class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-600 border border-red-200">
            🔴 PRIORITY
          </span>
        </div>
      </div>
      <div class="flex gap-2 flex-wrap">
        <button @click="togglePriority"
          :class="job?.is_priority ? 'bg-red-50 border-red-200 text-red-600 hover:bg-red-100' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
          class="border px-3 py-1.5 rounded-lg text-sm transition-colors">
          {{ job?.is_priority ? '🔴 Priority On' : '⚪ Set Priority' }}
        </button>
        <button @click="cloneJob" :disabled="cloning" class="border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50">
          {{ cloning ? 'Cloning…' : '⧉ Clone Job' }}
        </button>
        <button @click="printCard" class="border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50">
          🖨 Print
        </button>
        <router-link :to="`/job-cards/${id}/edit`" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">
          Edit
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="w-6 h-6 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
    </div>

    <template v-else-if="job">
      <!-- Status progression -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-1 flex-wrap">
          <div v-for="(label, val) in statuses" :key="val"
            :class="[
              'flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-full font-medium cursor-pointer transition-colors',
              job.status === val ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
            ]"
            @click="updateStatus(val)">
            {{ label }}
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Left: details -->
        <div class="lg:col-span-2 space-y-5">
          <!-- Job info -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">Job Details</h3>
            <div class="grid grid-cols-2 gap-y-3 text-sm">
              <div><p class="text-xs text-gray-400">Title</p><p class="font-medium text-gray-800">{{ job.title }}</p></div>
              <div><p class="text-xs text-gray-400">Customer</p><p class="font-medium">{{ job.customer?.name ?? '—' }}</p></div>
              <div><p class="text-xs text-gray-400">Quantity</p><p class="font-medium">{{ job.quantity_ordered?.toLocaleString() ?? '—' }}</p></div>
              <div><p class="text-xs text-gray-400">Paper Type</p><p class="font-medium">{{ job.paper_type ?? '—' }} {{ job.gsm ? job.gsm + 'gsm' : '' }}</p></div>
              <div><p class="text-xs text-gray-400">Size</p><p class="font-medium">{{ job.size ?? '—' }}</p></div>
              <div><p class="text-xs text-gray-400">Colors</p><p class="font-medium">{{ job.color_count ?? '—' }}</p></div>
              <div><p class="text-xs text-gray-400">Method</p><p class="font-medium capitalize">{{ job.printing_method ?? '—' }}</p></div>
              <div><p class="text-xs text-gray-400">Machine</p><p class="font-medium">{{ job.machine?.name ?? 'Not assigned' }}</p></div>
              <div><p class="text-xs text-gray-400">Operator</p><p class="font-medium">{{ job.operator?.name ?? 'Not assigned' }}</p></div>
              <div><p class="text-xs text-gray-400">Artwork Status</p><p class="font-medium capitalize">{{ job.artwork_status }}</p></div>
              <div><p class="text-xs text-gray-400">Due Date</p><p class="font-medium" :class="isOverdue ? 'text-red-600' : ''">{{ job.due_date ?? '—' }}</p></div>
              <div><p class="text-xs text-gray-400">Order Ref</p><p class="font-medium">{{ job.order?.invoice_number ?? '—' }}</p></div>
            </div>
            <div v-if="job.product_description" class="mt-4 pt-4 border-t border-gray-100">
              <p class="text-xs text-gray-400 mb-1">Description</p>
              <p class="text-sm text-gray-700">{{ job.product_description }}</p>
            </div>
          </div>

          <!-- Instructions -->
          <div v-if="job.printing_instructions || job.finishing_instructions" class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="job.printing_instructions">
              <p class="text-xs text-gray-400 font-medium mb-1">Printing Instructions</p>
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ job.printing_instructions }}</p>
            </div>
            <div v-if="job.finishing_instructions">
              <p class="text-xs text-gray-400 font-medium mb-1">Finishing Instructions</p>
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ job.finishing_instructions }}</p>
            </div>
          </div>

          <!-- Consumables -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-700 text-sm">Materials & Consumables</h3>
              <button @click="showAddConsumable = !showAddConsumable" class="text-xs text-amber-600 hover:underline">
                + Add Item
              </button>
            </div>
            <form v-if="showAddConsumable" @submit.prevent="addConsumable"
              class="mb-4 p-3 bg-amber-50 rounded-lg border border-amber-100 grid grid-cols-2 gap-3 text-sm">
              <div>
                <label class="label">Type</label>
                <select v-model="consumableForm.type" class="input w-full">
                  <option value="plate">Plate</option>
                  <option value="ink">Ink</option>
                  <option value="paper">Paper</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div>
                <label class="label">Description</label>
                <input v-model="consumableForm.description" type="text" required class="input w-full" placeholder="e.g. CMYK Ink Set" />
              </div>
              <div>
                <label class="label">Quantity</label>
                <input v-model.number="consumableForm.quantity" type="number" step="0.001" min="0" required class="input w-full" />
              </div>
              <div>
                <label class="label">Unit</label>
                <input v-model="consumableForm.unit" type="text" class="input w-full" placeholder="pcs / kg / L" />
              </div>
              <div>
                <label class="label">Unit Cost (LKR)</label>
                <input v-model.number="consumableForm.unit_cost" type="number" step="0.01" min="0" class="input w-full" />
              </div>
              <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary text-xs py-1 flex-1">Save</button>
                <button type="button" @click="showAddConsumable = false" class="btn-secondary text-xs py-1 flex-1">Cancel</button>
              </div>
            </form>
            <div v-if="consumables.length" class="space-y-1">
              <div v-for="c in consumables" :key="c.id"
                class="flex items-center gap-3 text-xs text-gray-600 py-2 border-b border-gray-100 last:border-0">
                <span :class="{plate:'bg-blue-100 text-blue-700',ink:'bg-purple-100 text-purple-700',paper:'bg-green-100 text-green-700',other:'bg-gray-100 text-gray-600'}[c.type]"
                  class="px-1.5 py-0.5 rounded text-xs font-medium capitalize">{{ c.type }}</span>
                <span class="flex-1 font-medium text-gray-700">{{ c.description }}</span>
                <span class="text-gray-500">{{ c.quantity }} {{ c.unit }}</span>
                <span v-if="c.total_cost > 0" class="font-semibold text-gray-700">LKR {{ Number(c.total_cost).toLocaleString() }}</span>
                <button @click="deleteConsumable(c.id)" class="text-red-400 hover:text-red-600 ml-1">✕</button>
              </div>
            </div>
            <div v-else class="text-xs text-gray-400 text-center py-3">No materials logged yet</div>
            <div v-if="consumables.length" class="mt-2 pt-2 border-t border-gray-100 flex justify-end text-xs font-semibold text-amber-700">
              Total: LKR {{ consumables.reduce((s,c) => s + Number(c.total_cost), 0).toLocaleString() }}
            </div>
          </div>

          <!-- Production Costing -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-1">
              <div>
                <h3 class="font-semibold text-gray-700 text-sm">Production Costing</h3>
                <p class="text-xs text-gray-400 mt-0.5">Actual vs estimated cost &amp; profitability</p>
              </div>
              <button @click="toggleCostingEdit" class="text-xs text-amber-600 hover:underline">
                {{ showCostingEdit ? 'Cancel' : (costing ? 'Edit Costs' : 'Enter Costs') }}
              </button>
            </div>

            <!-- Edit form -->
            <form v-if="showCostingEdit" @submit.prevent="saveCosting" class="mt-4 space-y-4 text-sm">
              <!-- Paper -->
              <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Paper</p>
                <div class="grid grid-cols-3 gap-2">
                  <div><label class="label">Sheets</label><input v-model.number="costingForm.paper_sheets" type="number" min="0" class="input w-full" /></div>
                  <div><label class="label">Rate / Sheet (LKR)</label><input v-model.number="costingForm.paper_rate_per_sheet" type="number" step="0.0001" min="0" class="input w-full" /></div>
                  <div><label class="label">Paper Cost</label><p class="input bg-gray-50 text-gray-500 w-full">LKR {{ fmt(calcPaper) }}</p></div>
                </div>
              </div>
              <!-- Ink -->
              <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Ink</p>
                <div class="grid grid-cols-3 gap-2">
                  <div><label class="label">Colours</label><input v-model.number="costingForm.ink_colours" type="number" min="0" class="input w-full" /></div>
                  <div><label class="label">Cost / Colour (LKR)</label><input v-model.number="costingForm.ink_cost_per_colour" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div><label class="label">Ink Cost</label><p class="input bg-gray-50 text-gray-500 w-full">LKR {{ fmt(calcInk) }}</p></div>
                </div>
              </div>
              <!-- Plate -->
              <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Plates</p>
                <div class="grid grid-cols-3 gap-2">
                  <div><label class="label">Plate Count</label><input v-model.number="costingForm.plate_count" type="number" min="0" class="input w-full" /></div>
                  <div><label class="label">Cost Each (LKR)</label><input v-model.number="costingForm.plate_cost_each" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div><label class="label">Plate Cost</label><p class="input bg-gray-50 text-gray-500 w-full">LKR {{ fmt(calcPlate) }}</p></div>
                </div>
              </div>
              <!-- Machine -->
              <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Machine</p>
                <div class="grid grid-cols-3 gap-2">
                  <div><label class="label">Hours</label><input v-model.number="costingForm.machine_hours" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div><label class="label">Rate / Hour (LKR)</label><input v-model.number="costingForm.machine_rate_per_hour" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div><label class="label">Machine Cost</label><p class="input bg-gray-50 text-gray-500 w-full">LKR {{ fmt(calcMachine) }}</p></div>
                </div>
              </div>
              <!-- Labour -->
              <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Labour</p>
                <div class="grid grid-cols-3 gap-2">
                  <div><label class="label">Hours</label><input v-model.number="costingForm.labour_hours" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div><label class="label">Rate / Hour (LKR)</label><input v-model.number="costingForm.labour_rate_per_hour" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div><label class="label">Labour Cost</label><p class="input bg-gray-50 text-gray-500 w-full">LKR {{ fmt(calcLabour) }}</p></div>
                </div>
              </div>
              <!-- Fixed costs -->
              <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Other Costs</p>
                <div class="grid grid-cols-2 gap-2">
                  <div><label class="label">Electricity (LKR)</label><input v-model.number="costingForm.electricity_cost" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div><label class="label">Outsourced (LKR)</label><input v-model.number="costingForm.outsource_cost" type="number" step="0.01" min="0" class="input w-full" /></div>
                  <div class="col-span-2"><label class="label">Outsource Description</label><input v-model="costingForm.outsource_description" type="text" class="input w-full" placeholder="e.g. Lamination at XYZ Press" /></div>
                </div>
              </div>
              <!-- Waste -->
              <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Waste</p>
                <div class="grid grid-cols-3 gap-2">
                  <div><label class="label">Waste %</label><input v-model.number="costingForm.waste_percentage" type="number" step="0.01" min="0" max="100" class="input w-full" /></div>
                  <div><label class="label">Waste Cost</label><p class="input bg-gray-50 text-gray-500 w-full">LKR {{ fmt(calcWaste) }}</p></div>
                  <div><label class="label">Total Actual Cost</label><p class="input bg-amber-50 text-amber-700 font-semibold w-full">LKR {{ fmt(calcTotal) }}</p></div>
                </div>
              </div>
              <!-- Notes -->
              <div><label class="label">Notes</label><textarea v-model="costingForm.notes" rows="2" class="input w-full"></textarea></div>
              <!-- Live profit preview -->
              <div class="p-3 rounded-xl border" :class="calcProfit >= 0 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100'">
                <div class="flex items-center justify-between text-sm">
                  <span class="font-medium" :class="calcProfit >= 0 ? 'text-green-700' : 'text-red-700'">
                    {{ calcProfit >= 0 ? 'Estimated Profit' : 'Estimated Loss' }}
                  </span>
                  <span class="font-bold text-base" :class="calcProfit >= 0 ? 'text-green-700' : 'text-red-700'">
                    LKR {{ fmt(Math.abs(calcProfit)) }}
                    <span class="text-xs font-normal">({{ fmt(Math.abs(calcMargin)) }}%)</span>
                  </span>
                </div>
                <p class="text-xs mt-1" :class="calcProfit >= 0 ? 'text-green-500' : 'text-red-400'">
                  Revenue: LKR {{ fmt(costingRevenue) }} — Cost: LKR {{ fmt(calcTotal) }}
                </p>
              </div>
              <div class="flex gap-2 pt-1">
                <button type="submit" :disabled="costingSaving" class="btn-primary text-xs py-1.5">
                  {{ costingSaving ? 'Saving…' : 'Save Costing' }}
                </button>
                <button type="button" @click="showCostingEdit = false" class="btn-secondary text-xs py-1.5">Cancel</button>
              </div>
            </form>

            <!-- View mode: comparison table -->
            <div v-else-if="costing" class="mt-4">
              <table class="w-full text-xs">
                <thead>
                  <tr class="text-gray-400 border-b border-gray-100">
                    <th class="text-left pb-1.5 font-medium">Cost Item</th>
                    <th class="text-right pb-1.5 font-medium">Estimated</th>
                    <th class="text-right pb-1.5 font-medium">Actual</th>
                    <th class="text-right pb-1.5 font-medium">Variance</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="row in costComparison" :key="row.label" class="py-1">
                    <td class="py-1.5 text-gray-600">{{ row.label }}</td>
                    <td class="py-1.5 text-right text-gray-400">{{ row.est !== null ? 'LKR ' + fmt(row.est) : '—' }}</td>
                    <td class="py-1.5 text-right font-medium text-gray-700">LKR {{ fmt(row.act) }}</td>
                    <td class="py-1.5 text-right font-semibold"
                      :class="row.est !== null ? (row.act <= row.est ? 'text-green-600' : 'text-red-500') : 'text-gray-300'">
                      {{ row.est !== null ? (row.act <= row.est ? '▼' : '▲') + ' LKR ' + fmt(Math.abs(row.act - row.est)) : '—' }}
                    </td>
                  </tr>
                  <tr class="border-t border-gray-200 font-semibold text-sm">
                    <td class="pt-2 text-gray-700">Total Cost</td>
                    <td class="pt-2 text-right text-gray-500">{{ costingEstimated ? 'LKR ' + fmt(costingEstimated.total) : '—' }}</td>
                    <td class="pt-2 text-right text-amber-700">LKR {{ fmt(costing.total_actual_cost) }}</td>
                    <td class="pt-2 text-right">
                      <span v-if="costingEstimated"
                        :class="costing.total_actual_cost <= costingEstimated.total ? 'text-green-600' : 'text-red-500'">
                        {{ costing.total_actual_cost <= costingEstimated.total ? '▼' : '▲' }}
                        LKR {{ fmt(Math.abs(costing.total_actual_cost - costingEstimated.total)) }}
                      </span>
                      <span v-else class="text-gray-300">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Profit summary -->
              <div class="mt-4 p-3 rounded-xl border flex items-center justify-between"
                :class="costing.profit >= 0 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100'">
                <div>
                  <p class="text-xs font-semibold" :class="costing.profit >= 0 ? 'text-green-700' : 'text-red-700'">
                    {{ costing.profit >= 0 ? 'Profitable' : 'Loss-making' }}
                  </p>
                  <p class="text-xs mt-0.5" :class="costing.profit >= 0 ? 'text-green-500' : 'text-red-400'">
                    Revenue: LKR {{ fmt(costing.revenue) }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-base font-bold" :class="costing.profit >= 0 ? 'text-green-700' : 'text-red-600'">
                    {{ costing.profit >= 0 ? '+' : '' }}LKR {{ fmt(costing.profit) }}
                  </p>
                  <p class="text-xs" :class="costing.profit >= 0 ? 'text-green-500' : 'text-red-400'">{{ fmt(costing.profit_margin) }}% margin</p>
                </div>
              </div>
            </div>

            <p v-else class="mt-4 text-xs text-gray-400 text-center py-3">No costing entered yet — click "Enter Costs" to begin</p>
          </div>

          <!-- Production history -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-700 text-sm">Production History</h3>
              <button @click="showAddProd = !showAddProd" class="text-xs text-amber-600 hover:underline">
                + Log Production Run
              </button>
            </div>
            <!-- Add production form -->
            <form v-if="showAddProd" @submit.prevent="addProduction" class="mb-4 p-3 bg-amber-50 rounded-lg border border-amber-100 grid grid-cols-2 gap-3 text-sm">
              <div><label class="label">Start Time</label><input v-model="prodForm.start_time" type="datetime-local" class="input w-full" /></div>
              <div><label class="label">End Time</label><input v-model="prodForm.end_time" type="datetime-local" class="input w-full" /></div>
              <div><label class="label">Output Qty</label><input v-model.number="prodForm.output_quantity" type="number" class="input w-full" /></div>
              <div><label class="label">Waste Qty</label><input v-model.number="prodForm.waste_quantity" type="number" class="input w-full" /></div>
              <div class="col-span-2 flex gap-2">
                <button type="submit" class="btn-primary text-xs py-1">Save</button>
                <button type="button" @click="showAddProd = false" class="btn-secondary text-xs py-1">Cancel</button>
              </div>
            </form>
            <!-- History list -->
            <div v-if="job.production_jobs?.length" class="space-y-2">
              <div v-for="pj in job.production_jobs" :key="pj.id" class="flex items-center gap-3 text-xs text-gray-600 py-2 border-b border-gray-100">
                <span :class="pj.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="px-1.5 py-0.5 rounded text-xs font-medium capitalize">{{ pj.status }}</span>
                <span>{{ pj.machine?.name ?? 'No machine' }}</span>
                <span>Output: <strong>{{ pj.output_quantity }}</strong></span>
                <span>Waste: <strong>{{ pj.waste_quantity }}</strong></span>
                <span class="text-gray-400">{{ fmtDate(pj.start_time) }}</span>
              </div>
            </div>
            <p v-else class="text-xs text-gray-400 text-center py-3">No production runs logged yet</p>
          </div>
        </div>

        <!-- Right sidebar -->
        <div class="space-y-5">
          <!-- Pre-press -->
          <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Pre-Press Status</h3>
            <div v-if="job.prepress_task" class="space-y-2 text-sm">
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">Status</span>
                <span class="font-medium capitalize">{{ job.prepress_task.status?.replace(/_/g, ' ') }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">Revisions</span>
                <span class="font-medium">{{ job.prepress_task.revision_count }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">Plate Status</span>
                <span class="font-medium capitalize">{{ job.prepress_task.plate_status?.replace(/_/g, ' ') }}</span>
              </div>
              <!-- Client proof decision badge -->
              <div v-if="job.prepress_task.client_decision" class="flex justify-between text-xs mt-1">
                <span class="text-gray-500">Client Decision</span>
                <span class="font-bold" :class="job.prepress_task.client_decision === 'approved' ? 'text-green-600' : 'text-red-600'">
                  {{ job.prepress_task.client_decision === 'approved' ? '✓ Approved' : '✗ Rejected' }}
                </span>
              </div>
              <p v-if="job.prepress_task.client_notes" class="text-xs text-gray-400 italic mt-1 truncate" :title="job.prepress_task.client_notes">
                "{{ job.prepress_task.client_notes }}"
              </p>
            </div>
            <router-link to="/prepress" class="text-xs text-amber-600 hover:underline mt-2 block">Manage Pre-Press →</router-link>
          </div>

          <!-- Artwork Files -->
          <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-gray-700 text-sm">Artwork Files</h3>
              <label class="text-xs text-amber-600 hover:underline cursor-pointer">
                + Upload
                <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.ai,.psd,.eps,.svg,.tiff,.tif" @change="uploadArtwork" :disabled="artworkUploading" />
              </label>
            </div>
            <div v-if="artworkUploading" class="text-xs text-gray-400 text-center py-2">Uploading…</div>
            <div v-else-if="artworkFiles.length" class="space-y-1.5">
              <div v-for="af in artworkFiles" :key="af.id" class="flex items-center gap-2 text-xs">
                <span class="w-5 h-5 rounded bg-gray-100 flex items-center justify-center text-gray-500 shrink-0 text-[10px] font-bold">v{{ af.version }}</span>
                <a :href="af.url" target="_blank" class="flex-1 text-amber-600 hover:underline truncate" :title="af.original_name">{{ af.original_name }}</a>
                <span class="text-gray-400 shrink-0">{{ (af.file_size / 1024).toFixed(0) }}KB</span>
                <button @click="deleteArtwork(af.id)" class="text-red-400 hover:text-red-600 shrink-0">✕</button>
              </div>
            </div>
            <p v-else class="text-xs text-gray-400 text-center py-2">No artwork uploaded yet</p>
          </div>

          <!-- Finishing task -->
          <div v-if="job.finishing_task" class="bg-white rounded-xl border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">Finishing</h3>
            <div class="flex flex-wrap gap-1">
              <span v-for="op in finishingOps(job.finishing_task)" :key="op"
                class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">{{ op }}</span>
              <span v-if="!finishingOps(job.finishing_task).length" class="text-xs text-gray-400">None specified</span>
            </div>
            <p class="text-xs mt-2">
              <span :class="job.finishing_task.status === 'completed' ? 'text-green-600' : 'text-gray-500'" class="font-medium capitalize">{{ job.finishing_task.status }}</span>
            </p>
          </div>

          <!-- QR Code -->
          <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 font-medium mb-3">Job Tracking QR</p>
            <div class="flex justify-center">
              <img v-if="qrDataUrl" :src="qrDataUrl" class="w-36 h-36 rounded" alt="QR Code" />
              <div v-else class="w-36 h-36 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400">Generating…</div>
            </div>
            <p class="text-xs text-gray-400 mt-2 text-center">Scan to track job status</p>
            <a :href="job.qr_code" target="_blank" class="block text-center text-xs text-amber-600 hover:underline mt-1 truncate">{{ job.job_number }}</a>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import QRCode from 'qrcode'

const route  = useRoute()
const router = useRouter()
const id    = computed(() => route.params.id)
const job   = ref(null)
const loading = ref(true)
const showAddProd = ref(false)
const showAddConsumable = ref(false)
const consumables = ref([])
const qrDataUrl = ref('')

watch(job, async (j) => {
  if (j?.qr_code) {
    qrDataUrl.value = await QRCode.toDataURL(j.qr_code, { width: 144, margin: 1, color: { dark: '#1f2937', light: '#ffffff' } })
  }
})

const prodForm = ref({ start_time: '', end_time: '', output_quantity: 0, waste_quantity: 0 })

// ── Priority & Clone ──────────────────────────────────
const cloning = ref(false)

async function togglePriority() {
  try {
    const { data } = await axios.patch(`/api/job-cards/${id.value}/priority`)
    job.value.is_priority = data.is_priority
  } catch { alert('Failed to update priority') }
}

async function cloneJob() {
  if (!confirm('Clone this job card? A new job card will be created with status "Waiting".')) return
  cloning.value = true
  try {
    const { data } = await axios.post(`/api/job-cards/${id.value}/clone`)
    router.push(`/job-cards/${data.id}`)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Clone failed')
  } finally {
    cloning.value = false
  }
}

// ── Artwork Files ─────────────────────────────────────
const artworkFiles    = ref([])
const artworkUploading = ref(false)

async function loadArtwork() {
  try {
    const { data } = await axios.get(`/api/job-cards/${id.value}/artwork`)
    artworkFiles.value = data
  } catch {}
}

async function uploadArtwork(event) {
  const file = event.target.files[0]
  if (!file) return
  artworkUploading.value = true
  const form = new FormData()
  form.append('file', file)
  try {
    const { data } = await axios.post(`/api/job-cards/${id.value}/artwork`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    artworkFiles.value.unshift(data)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Upload failed')
  } finally {
    artworkUploading.value = false
    event.target.value = ''
  }
}

async function deleteArtwork(fileId) {
  if (!confirm('Delete this artwork file?')) return
  try {
    await axios.delete(`/api/job-artwork/${fileId}`)
    artworkFiles.value = artworkFiles.value.filter(f => f.id !== fileId)
  } catch { alert('Delete failed') }
}
const consumableForm = ref({ type: 'plate', description: '', quantity: 1, unit: 'pcs', unit_cost: 0 })

// ── Costing ──────────────────────────────────────────
const costing          = ref(null)
const costingEstimated = ref(null)
const costingRevenue   = ref(0)
const showCostingEdit  = ref(false)
const costingSaving    = ref(false)

const costingForm = ref({
  paper_sheets: 0, paper_rate_per_sheet: 0,
  ink_colours: 0, ink_cost_per_colour: 0,
  plate_count: 0, plate_cost_each: 0,
  machine_hours: 0, machine_rate_per_hour: 0,
  labour_hours: 0, labour_rate_per_hour: 0,
  electricity_cost: 0, outsource_cost: 0, outsource_description: '',
  waste_percentage: 0, notes: '',
})

const calcPaper        = computed(() => (costingForm.value.paper_sheets   || 0) * (costingForm.value.paper_rate_per_sheet  || 0))
const calcInk          = computed(() => (costingForm.value.ink_colours     || 0) * (costingForm.value.ink_cost_per_colour   || 0))
const calcPlate        = computed(() => (costingForm.value.plate_count     || 0) * (costingForm.value.plate_cost_each       || 0))
const calcMachine      = computed(() => (costingForm.value.machine_hours   || 0) * (costingForm.value.machine_rate_per_hour || 0))
const calcLabour       = computed(() => (costingForm.value.labour_hours    || 0) * (costingForm.value.labour_rate_per_hour  || 0))
const calcMaterialsSub = computed(() => calcPaper.value + calcInk.value + calcPlate.value + calcMachine.value + calcLabour.value)
const calcWaste        = computed(() => calcMaterialsSub.value * (costingForm.value.waste_percentage || 0) / 100)
const calcTotal        = computed(() =>
  calcMaterialsSub.value + calcWaste.value +
  (Number(costingForm.value.electricity_cost) || 0) +
  (Number(costingForm.value.outsource_cost)   || 0)
)
const calcProfit  = computed(() => (costingRevenue.value || 0) - calcTotal.value)
const calcMargin  = computed(() => costingRevenue.value > 0 ? (calcProfit.value / costingRevenue.value * 100) : 0)

const costComparison = computed(() => {
  const e = costingEstimated.value
  const a = costing.value
  if (!a) return []
  return [
    { label: 'Paper',      est: e?.paper_cost      ?? null, act: a.paper_cost   },
    { label: 'Ink',        est: e?.ink_cost         ?? null, act: a.ink_cost     },
    { label: 'Plate',      est: e?.plate_cost       ?? null, act: a.plate_cost   },
    { label: 'Machine',    est: null,                        act: a.machine_cost },
    { label: 'Labour',     est: e?.labour_cost      ?? null, act: a.labour_cost  },
    { label: 'Finishing',  est: e?.finishing_cost   ?? null, act: a.outsource_cost },
    { label: 'Electricity',est: null,                        act: a.electricity_cost },
    { label: 'Waste',      est: null,                        act: a.waste_cost   },
  ]
})

function fmt(n) {
  return Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function toggleCostingEdit() {
  if (!showCostingEdit.value && costing.value) {
    const c = costing.value
    Object.keys(costingForm.value).forEach(k => {
      if (c[k] !== undefined && c[k] !== null) costingForm.value[k] = c[k]
    })
  }
  showCostingEdit.value = !showCostingEdit.value
}

async function loadCosting() {
  try {
    const { data } = await axios.get(`/api/job-cards/${id.value}/costing`)
    costing.value          = data.costing
    costingEstimated.value = data.estimated
    costingRevenue.value   = data.revenue || 0
  } catch {}
}

async function saveCosting() {
  costingSaving.value = true
  try {
    const { data } = await axios.post(`/api/job-cards/${id.value}/costing`, costingForm.value)
    costing.value        = data
    costingRevenue.value = data.revenue
    showCostingEdit.value = false
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to save costing')
  } finally {
    costingSaving.value = false
  }
}

const statuses = {
  waiting: 'Waiting', designing: 'Designing', proof_approval: 'Proof Approval',
  plate_making: 'Plate Making', printing: 'Printing', finishing: 'Finishing',
  quality_check: 'Quality Check', ready: 'Ready', delivered: 'Delivered',
}
const statusBadgeMap = {
  waiting: 'bg-gray-100 text-gray-600', designing: 'bg-blue-100 text-blue-700',
  proof_approval: 'bg-yellow-100 text-yellow-700', plate_making: 'bg-orange-100 text-orange-700',
  printing: 'bg-purple-100 text-purple-700', finishing: 'bg-indigo-100 text-indigo-700',
  quality_check: 'bg-teal-100 text-teal-700', ready: 'bg-green-100 text-green-700',
  delivered: 'bg-gray-100 text-gray-500',
}

function statusBadge(s) { return statusBadgeMap[s] ?? 'bg-gray-100 text-gray-600' }
function statusLabel(s)  { return statuses[s] ?? s }
function fmtDate(d)      { return d ? new Date(d).toLocaleString('en-LK', { dateStyle: 'short', timeStyle: 'short' }) : '—' }
const isOverdue = computed(() => job.value?.due_date && new Date(job.value.due_date) < new Date() && !['ready','delivered'].includes(job.value?.status))

function finishingOps(ft) {
  if (!ft) return []
  return ['cutting','folding','binding','lamination','uv_coating','foiling','die_cutting','packaging']
    .filter(k => ft[k])
    .map(k => k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))
}

async function load() {
  loading.value = true
  try {
    const [jobRes, conRes] = await Promise.all([
      axios.get(`/api/job-cards/${id.value}`),
      axios.get(`/api/job-cards/${id.value}/consumables`),
    ])
    job.value         = jobRes.data
    consumables.value = conRes.data
    loadCosting()
    loadArtwork()
  } finally {
    loading.value = false
  }
}

async function addConsumable() {
  try {
    const { data } = await axios.post(`/api/job-cards/${id.value}/consumables`, consumableForm.value)
    consumables.value.push(data)
    showAddConsumable.value = false
    consumableForm.value = { type: 'plate', description: '', quantity: 1, unit: 'pcs', unit_cost: 0 }
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to add consumable')
  }
}

async function deleteConsumable(cid) {
  if (!confirm('Remove this item?')) return
  try {
    await axios.delete(`/api/job-consumables/${cid}`)
    consumables.value = consumables.value.filter(c => c.id !== cid)
  } catch { alert('Failed to delete') }
}

async function updateStatus(status) {
  if (status === job.value.status) return
  if (!confirm(`Change status to "${statuses[status]}"?`)) return
  try {
    const { data } = await axios.patch(`/api/job-cards/${id.value}/status`, { status })
    job.value.status = data.status
  } catch (e) {
    alert(e.response?.data?.message ?? 'Update failed')
  }
}

async function addProduction() {
  try {
    await axios.post('/api/production-jobs', {
      ...prodForm.value,
      job_card_id: id.value,
    })
    await load()
    showAddProd.value = false
    prodForm.value = { start_time: '', end_time: '', output_quantity: 0, waste_quantity: 0 }
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to log production')
  }
}

function printCard() { window.print() }

onMounted(load)
</script>

<style scoped>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input { @apply border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none; }
.btn-primary { @apply bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50; }
.btn-secondary { @apply border border-gray-200 text-gray-600 hover:bg-gray-50 px-5 py-2 rounded-lg text-sm font-semibold transition-colors; }
</style>
