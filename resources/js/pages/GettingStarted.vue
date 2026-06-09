<template>
  <div class="space-y-6 max-w-5xl mx-auto">

    <!-- Hero Banner -->
    <div class="relative rounded-2xl overflow-hidden text-white p-8"
      style="background: linear-gradient(135deg, #1e3a8a 0%, #172554 50%, #0f172a 100%);">
      <!-- Grid overlay -->
      <div class="absolute inset-0 opacity-10 pointer-events-none">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="gs-grid" width="40" height="40" patternUnits="userSpaceOnUse">
              <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#gs-grid)" />
        </svg>
      </div>
      <!-- Glow circles -->
      <div class="absolute -top-10 -right-10 w-48 h-48 bg-amber-500 rounded-full opacity-10 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-blue-400 rounded-full opacity-10 blur-3xl pointer-events-none"></div>

      <div class="relative flex items-center gap-6">
        <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center shrink-0 backdrop-blur-sm">
          <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
          </svg>
        </div>
        <div>
          <p class="text-amber-300 text-sm font-semibold tracking-wide uppercase mb-1">LMUC Press Management System</p>
          <h1 class="text-3xl font-bold text-white">Getting Started Guide</h1>
          <p class="text-white/60 mt-1.5 text-sm max-w-xl">Everything you need to know to run your print shop — from quoting a job to delivering the finished product.</p>
        </div>
      </div>

      <!-- Quick stats row -->
      <div class="relative mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="s in quickStats" :key="s.label" class="bg-white/10 backdrop-blur-sm rounded-xl p-3 border border-white/10">
          <p class="text-2xl font-bold text-white">{{ s.value }}</p>
          <p class="text-xs text-white/60 mt-0.5">{{ s.label }}</p>
        </div>
      </div>
    </div>

    <!-- Tab navigation -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="flex overflow-x-auto scrollbar-hide border-b border-gray-100">
        <button v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'flex items-center gap-2 px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-colors border-b-2 -mb-px',
            activeTab === tab.key
              ? 'border-amber-500 text-amber-600 bg-amber-50/50'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
          ]">
          <component :is="tab.icon" class="w-4 h-4 shrink-0" />
          {{ tab.label }}
        </button>
      </div>

      <div class="p-6">

        <!-- ── Getting Started ─────────────────────────────────────── -->
        <div v-if="activeTab === 'start'" class="space-y-6">
          <SectionHeading title="How to Log In" subtitle="Two separate login portals — one for staff, one for clients." />
          <div class="grid md:grid-cols-2 gap-4">
            <LoginCard
              title="Staff Login"
              url="/login"
              color="blue"
              :steps="['Go to /login', 'Enter your email and password', 'You\'re redirected to the Dashboard based on your role']"
              note="Default password for all seeded accounts: password" />
            <LoginCard
              title="Client Portal"
              url="/portal/login"
              color="amber"
              :steps="['Go to /portal/login', 'Enter your client email and password', 'You can only see your own jobs, quotes and orders']"
              note="Client accounts are created by admin in Users & Roles" />
          </div>

          <SectionHeading title="Navigating the System" subtitle="The sidebar groups features by function." />
          <div class="grid sm:grid-cols-3 gap-3">
            <NavGroup title="Main" color="blue" :items="['Dashboard — KPIs & overview', 'Customers', 'Quotations', 'Sales Orders', 'Deliveries', 'Reports']" />
            <NavGroup title="Production" color="purple" :items="['Job Cards', 'Schedule — machine calendar', 'Production Queue', 'Pre-Press', 'Finishing', 'Machines']" />
            <NavGroup title="Admin" color="gray" :items="['Categories', 'GRN — receive stock', 'Damages & Waste', 'Finance', 'Users & Roles', 'Press Settings']" />
          </div>

          <SectionHeading title="Default Staff Accounts" subtitle="Use these to log in and explore each role." />
          <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Email</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Password</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Role</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="u in defaultUsers" :key="u.email" class="hover:bg-gray-50">
                  <td class="px-4 py-2.5 font-mono text-xs text-blue-700">{{ u.email }}</td>
                  <td class="px-4 py-2.5 font-mono text-xs text-gray-500">password</td>
                  <td class="px-4 py-2.5"><span :class="roleBadge(u.role)" class="text-xs px-2 py-0.5 rounded-full font-semibold">{{ u.role }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ── Quotations & Sales ──────────────────────────────────── -->
        <div v-if="activeTab === 'quotes'" class="space-y-6">
          <SectionHeading title="Quoting a Job" subtitle="Create a cost breakdown and send it to the client as a PDF." />
          <WorkflowSteps :steps="quotationSteps" color="amber" />

          <div class="grid md:grid-cols-2 gap-4 mt-4">
            <TipCard icon="💡" title="Quotation Templates">
              Save your common job types (business cards, brochures, banners) as templates.
              Next time, click <strong>Load Template</strong> and the cost fields pre-fill instantly.
              Click <strong>+ Save as Template</strong> from any New Quotation form.
            </TipCard>
            <TipCard icon="🔄" title="Convert to Sale">
              When the client approves, open the quotation and click <strong>Convert to Sale</strong>.
              A sales order is created pre-filled with the quotation details.
              Then download and send the invoice PDF.
            </TipCard>
          </div>

          <SectionHeading title="Cost Breakdown Formula" subtitle="How the final quote price is calculated." />
          <div class="bg-gray-50 rounded-xl border border-gray-200 p-5 font-mono text-sm space-y-2 text-gray-700">
            <div class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1.5 items-center">
              <span class="text-gray-400 text-xs">1</span><span>Base Cost = Plate + Paper + Ink + Finishing + Labour</span>
              <span class="text-gray-400 text-xs">2</span><span>With Wastage = Base × (1 + Wastage%)</span>
              <span class="text-gray-400 text-xs">3</span><span>Subtotal = With Wastage × (1 + Profit Margin%)</span>
              <span class="text-gray-400 text-xs">4</span><span class="text-amber-700 font-bold">Total = Subtotal × (1 + Tax%)</span>
            </div>
          </div>
        </div>

        <!-- ── Job Cards ───────────────────────────────────────────── -->
        <div v-if="activeTab === 'jobs'" class="space-y-6">
          <SectionHeading title="Job Card Lifecycle" subtitle="Every print job moves through these 9 stages." />

          <!-- Status pipeline -->
          <div class="flex items-center gap-0 overflow-x-auto pb-2">
            <div v-for="(s, i) in jobStatuses" :key="s.key"
              class="flex items-center shrink-0">
              <div :class="s.bg" class="flex flex-col items-center px-3 py-2 rounded-lg min-w-[90px] text-center border">
                <span class="text-lg">{{ s.icon }}</span>
                <span :class="s.text" class="text-[11px] font-semibold mt-0.5 leading-tight">{{ s.label }}</span>
              </div>
              <div v-if="i < jobStatuses.length - 1" class="text-gray-300 px-1 text-lg shrink-0">→</div>
            </div>
          </div>

          <SectionHeading title="Creating a Job Card" subtitle="Step-by-step from new job to production." />
          <WorkflowSteps :steps="jobCardSteps" color="purple" />

          <div class="grid md:grid-cols-3 gap-4">
            <TipCard icon="📋" title="Clone a Repeat Order">
              Open any completed job card → click <strong>Clone Job</strong> in the header.
              A copy is created with a new job number and status reset to Waiting.
            </TipCard>
            <TipCard icon="🚨" title="Rush / Priority Jobs">
              Click <strong>Mark Priority</strong> on any job card. A red <strong>RUSH</strong> badge
              appears in the job list so production staff can spot urgent jobs instantly.
            </TipCard>
            <TipCard icon="📎" title="Artwork File Uploads">
              In the Pre-Press sidebar of any job card, upload design files (PDF, AI, PSD, PNG).
              Files are versioned automatically — upload v2 without losing v1.
            </TipCard>
          </div>

          <SectionHeading title="Tracking Actual Production Costs" subtitle="Compare what you quoted vs what it actually cost." />
          <WorkflowSteps :steps="costingSteps" color="green" />
        </div>

        <!-- ── Schedule ────────────────────────────────────────────── -->
        <div v-if="activeTab === 'schedule'" class="space-y-6">
          <SectionHeading title="Production Schedule" subtitle="Visually plan which machine runs which job on which day." />
          <WorkflowSteps :steps="scheduleSteps" color="blue" />

          <div class="grid md:grid-cols-3 gap-4">
            <TipCard icon="📅" title="Drag & Drop">
              Drag any job chip from the <strong>Unscheduled</strong> pool at the top and drop it
              onto a machine/day cell to assign it. Drag back to unscheduled to clear.
            </TipCard>
            <TipCard icon="⚠️" title="Overbooked Warning">
              If you assign 3 or more jobs to the same machine on the same day,
              the cell turns red and shows an <strong>Overbooked</strong> warning.
            </TipCard>
            <TipCard icon="🔔" title="Due Date Alerts">
              The right sidebar automatically surfaces jobs that are overdue or due within
              2 days — without you having to search for them.
            </TipCard>
          </div>

          <SectionHeading title="Operator Workload" subtitle="See at a glance who is overloaded." />
          <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
            <div class="grid sm:grid-cols-3 gap-3">
              <div v-for="w in workloadExample" :key="w.label" class="bg-white rounded-lg border border-gray-200 p-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700 shrink-0">{{ w.initial }}</div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-semibold text-gray-800">{{ w.name }}</p>
                  <p class="text-[11px] text-gray-400">{{ w.role }}</p>
                </div>
                <span :class="w.cls" class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0">{{ w.jobs }} jobs</span>
              </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Green = available · Amber = busy · Red = overloaded</p>
          </div>
        </div>

        <!-- ── Client Portal ───────────────────────────────────────── -->
        <div v-if="activeTab === 'portal'" class="space-y-6">
          <SectionHeading title="Client Proof Approval" subtitle="Let clients approve artwork without a phone call." />
          <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <p class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold shrink-0">S</span>
                Staff side
              </p>
              <WorkflowSteps :steps="proofStaffSteps" color="blue" compact />
            </div>
            <div class="space-y-3">
              <p class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold shrink-0">C</span>
                Client side (portal)
              </p>
              <WorkflowSteps :steps="proofClientSteps" color="amber" compact />
            </div>
          </div>

          <SectionHeading title="What Clients Can See" subtitle="The client portal is separate from the staff app." />
          <div class="grid sm:grid-cols-3 gap-3">
            <FeatureCard icon="🔖" title="Job Status" desc="Track their own jobs through each production stage in real time." />
            <FeatureCard icon="📄" title="Quotations" desc="View all their quotations, amounts, and validity dates." />
            <FeatureCard icon="🧾" title="Orders & Invoices" desc="See completed orders and their payment status." />
          </div>
        </div>

        <!-- ── Inventory ───────────────────────────────────────────── -->
        <div v-if="activeTab === 'inventory'" class="space-y-6">
          <SectionHeading title="Stock Management Workflow" subtitle="From purchase order to stock on the shelf." />
          <WorkflowSteps :steps="inventorySteps" color="green" />

          <div class="grid md:grid-cols-2 gap-4">
            <TipCard icon="📦" title="Low Stock Alerts">
              On the Dashboard and Materials page, items below their reorder level
              are highlighted in red. Check these daily to avoid production stoppages.
            </TipCard>
            <TipCard icon="📋" title="Job Consumables">
              When running a job, open the job card and add materials used under
              <strong>Materials & Consumables</strong>. This gives you a per-job material cost breakdown.
            </TipCard>
          </div>
        </div>

        <!-- ── Roles ──────────────────────────────────────────────── -->
        <div v-if="activeTab === 'roles'" class="space-y-6">
          <SectionHeading title="Role Permissions" subtitle="Each role is scoped to what that staff member needs." />
          <div class="grid sm:grid-cols-2 gap-3">
            <div v-for="r in roles" :key="r.name"
              class="bg-white rounded-xl border border-gray-200 p-4 hover:border-amber-200 hover:bg-amber-50/30 transition-colors">
              <div class="flex items-start gap-3">
                <span class="text-xl shrink-0">{{ r.icon }}</span>
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <p class="text-sm font-semibold text-gray-800">{{ r.name }}</p>
                    <span :class="roleBadge(r.name.toLowerCase().replace(' ', '_'))" class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold">{{ r.name }}</span>
                  </div>
                  <p class="text-xs text-gray-500 leading-relaxed">{{ r.desc }}</p>
                  <div class="flex flex-wrap gap-1 mt-2">
                    <span v-for="tag in r.tags" :key="tag" class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full">{{ tag }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, defineComponent, h } from 'vue'
import {
  RocketLaunchIcon, DocumentTextIcon, ClipboardDocumentListIcon,
  CalendarDaysIcon, UserGroupIcon, CubeIcon, GlobeAltIcon,
} from '@heroicons/vue/24/outline'

const activeTab = ref('start')

// ── Inline sub-components ──────────────────────────────────────────
const SectionHeading = defineComponent({
  props: ['title', 'subtitle'],
  setup(props) {
    return () => h('div', { class: 'mb-1' }, [
      h('h3', { class: 'text-base font-bold text-gray-800' }, props.title),
      h('p', { class: 'text-xs text-gray-500 mt-0.5' }, props.subtitle),
    ])
  },
})

const WorkflowSteps = defineComponent({
  props: { steps: Array, color: String, compact: Boolean },
  setup(props) {
    const colors = {
      amber:  { num: 'bg-amber-500 text-white', line: 'border-amber-200' },
      blue:   { num: 'bg-blue-600 text-white',  line: 'border-blue-200' },
      purple: { num: 'bg-purple-600 text-white', line: 'border-purple-200' },
      green:  { num: 'bg-green-600 text-white',  line: 'border-green-200' },
    }
    return () => h('div', { class: 'space-y-0' },
      props.steps.map((step, i) => h('div', { key: i, class: 'flex gap-3' }, [
        h('div', { class: 'flex flex-col items-center shrink-0' }, [
          h('div', { class: `w-6 h-6 rounded-full ${colors[props.color]?.num} flex items-center justify-center text-xs font-bold shrink-0` }, String(i + 1)),
          i < props.steps.length - 1
            ? h('div', { class: `w-px flex-1 border-l-2 border-dashed ${colors[props.color]?.line} my-1 min-h-[16px]` })
            : null,
        ]),
        h('div', { class: `pb-3 ${props.compact ? '' : 'pb-4'}` }, [
          h('p', { class: 'text-sm text-gray-800 leading-snug', innerHTML: step }),
        ]),
      ]))
    )
  },
})

const TipCard = defineComponent({
  props: ['icon', 'title', 'desc'],
  setup(props, { slots }) {
    return () => h('div', { class: 'bg-amber-50 border border-amber-100 rounded-xl p-4' }, [
      h('div', { class: 'flex items-center gap-2 mb-2' }, [
        h('span', { class: 'text-lg' }, props.icon),
        h('p', { class: 'text-sm font-semibold text-gray-800' }, props.title),
      ]),
      h('p', { class: 'text-xs text-gray-600 leading-relaxed', innerHTML: slots.default ? slots.default()[0].children : props.desc }),
    ])
  },
})

const LoginCard = defineComponent({
  props: ['title', 'url', 'color', 'steps', 'note'],
  setup(props) {
    const colors = {
      blue:  { bg: 'bg-blue-600', light: 'bg-blue-50 border-blue-200 text-blue-700' },
      amber: { bg: 'bg-amber-500', light: 'bg-amber-50 border-amber-200 text-amber-700' },
    }
    const c = colors[props.color] || colors.blue
    return () => h('div', { class: 'rounded-xl border border-gray-200 overflow-hidden' }, [
      h('div', { class: `${c.bg} px-4 py-3 flex items-center justify-between` }, [
        h('p', { class: 'text-sm font-bold text-white' }, props.title),
        h('span', { class: 'text-xs bg-white/20 text-white px-2 py-0.5 rounded-full font-mono' }, props.url),
      ]),
      h('div', { class: 'p-4 space-y-2' },
        props.steps.map((s, i) => h('div', { key: i, class: 'flex items-start gap-2' }, [
          h('span', { class: `w-5 h-5 rounded-full ${c.bg} text-white text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold` }, String(i + 1)),
          h('p', { class: 'text-xs text-gray-700' }, s),
        ])).concat([
          h('p', { class: `mt-3 text-[11px] px-2.5 py-1.5 rounded-lg border ${c.light} font-medium` }, `ℹ ${props.note}`),
        ])
      ),
    ])
  },
})

const NavGroup = defineComponent({
  props: ['title', 'color', 'items'],
  setup(props) {
    const colors = {
      blue:   'bg-blue-600',
      purple: 'bg-purple-600',
      gray:   'bg-gray-600',
    }
    return () => h('div', { class: 'rounded-xl border border-gray-200 overflow-hidden' }, [
      h('div', { class: `${colors[props.color]} px-4 py-2` }, [
        h('p', { class: 'text-xs font-bold text-white uppercase tracking-wide' }, props.title),
      ]),
      h('ul', { class: 'p-3 space-y-1.5' },
        props.items.map((item, i) => h('li', { key: i, class: 'flex items-start gap-2' }, [
          h('span', { class: 'text-gray-300 mt-0.5 shrink-0 text-xs' }, '→'),
          h('span', { class: 'text-xs text-gray-700' }, item),
        ]))
      ),
    ])
  },
})

const FeatureCard = defineComponent({
  props: ['icon', 'title', 'desc'],
  setup(props) {
    return () => h('div', { class: 'bg-white rounded-xl border border-gray-200 p-4 text-center' }, [
      h('div', { class: 'text-3xl mb-2' }, props.icon),
      h('p', { class: 'text-sm font-semibold text-gray-800 mb-1' }, props.title),
      h('p', { class: 'text-xs text-gray-500 leading-relaxed' }, props.desc),
    ])
  },
})

// ── Data ───────────────────────────────────────────────────────────
const quickStats = [
  { value: '9',   label: 'Production stages' },
  { value: '13',  label: 'Staff roles' },
  { value: '12',  label: 'Modules' },
  { value: '∞',   label: 'Job cards' },
]

const tabs = [
  { key: 'start',     label: 'Getting Started',   icon: RocketLaunchIcon },
  { key: 'quotes',    label: 'Quotations & Sales', icon: DocumentTextIcon },
  { key: 'jobs',      label: 'Job Cards',          icon: ClipboardDocumentListIcon },
  { key: 'schedule',  label: 'Schedule',           icon: CalendarDaysIcon },
  { key: 'portal',    label: 'Client Portal',      icon: GlobeAltIcon },
  { key: 'inventory', label: 'Inventory',          icon: CubeIcon },
  { key: 'roles',     label: 'Roles & Access',     icon: UserGroupIcon },
]

const defaultUsers = [
  { email: 'admin@lmucpress.lk',     role: 'admin' },
  { email: 'sales@lmucpress.lk',     role: 'sales' },
  { email: 'estimator@lmucpress.lk', role: 'estimator' },
  { email: 'designer@lmucpress.lk',  role: 'designer' },
  { email: 'prodmgr@lmucpress.lk',   role: 'production_manager' },
  { email: 'operator@lmucpress.lk',  role: 'machine_operator' },
  { email: 'accounts@lmucpress.lk',  role: 'accountant' },
  { email: 'dispatch@lmucpress.lk',  role: 'dispatch_officer' },
]

const roleBadgeMap = {
  admin:              'bg-red-100 text-red-700',
  owner:              'bg-red-100 text-red-700',
  sales:              'bg-blue-100 text-blue-700',
  estimator:          'bg-indigo-100 text-indigo-700',
  designer:           'bg-purple-100 text-purple-700',
  production_manager: 'bg-orange-100 text-orange-700',
  machine_operator:   'bg-yellow-100 text-yellow-700',
  store_keeper:       'bg-green-100 text-green-700',
  accountant:         'bg-teal-100 text-teal-700',
  dispatch_officer:   'bg-cyan-100 text-cyan-700',
  client:             'bg-gray-100 text-gray-600',
}
function roleBadge(role) { return roleBadgeMap[role] ?? 'bg-gray-100 text-gray-600' }

const jobStatuses = [
  { key: 'waiting',       label: 'Waiting',       icon: '⏳', bg: 'bg-gray-50 border-gray-200',   text: 'text-gray-600' },
  { key: 'designing',     label: 'Designing',     icon: '🎨', bg: 'bg-blue-50 border-blue-200',   text: 'text-blue-700' },
  { key: 'proof',         label: 'Proof Approval',icon: '👁', bg: 'bg-yellow-50 border-yellow-200',text: 'text-yellow-700' },
  { key: 'plate_making',  label: 'Plate Making',  icon: '🔧', bg: 'bg-orange-50 border-orange-200',text: 'text-orange-700' },
  { key: 'printing',      label: 'Printing',      icon: '🖨', bg: 'bg-purple-50 border-purple-200',text: 'text-purple-700' },
  { key: 'finishing',     label: 'Finishing',     icon: '✂️', bg: 'bg-indigo-50 border-indigo-200',text: 'text-indigo-700' },
  { key: 'qc',            label: 'Quality Check', icon: '✅', bg: 'bg-teal-50 border-teal-200',    text: 'text-teal-700' },
  { key: 'ready',         label: 'Ready',         icon: '📦', bg: 'bg-green-50 border-green-200',  text: 'text-green-700' },
  { key: 'delivered',     label: 'Delivered',     icon: '🚚', bg: 'bg-gray-50 border-gray-200',    text: 'text-gray-500' },
]

const quotationSteps = [
  'Go to <strong>Quotations → New Quotation</strong>',
  'Enter the <strong>Job Title</strong> and select a <strong>Customer</strong> (or leave blank for walk-in)',
  'Choose <strong>Product Type</strong> and fill in print specs (paper, GSM, size, quantity, colours)',
  'Fill in the <strong>Cost Breakdown</strong> — Plate, Paper, Ink, Finishing, Labour costs',
  'Set <strong>Wastage %</strong> and <strong>Profit Margin %</strong> — the Final Total updates live',
  'Click <strong>Save Quotation</strong> — then <strong>Download PDF</strong> to send to client',
  'When client approves, open the quotation and click <strong>Convert to Sale</strong>',
]

const jobCardSteps = [
  'Go to <strong>Job Cards → New Job Card</strong>',
  'Fill in <strong>Title, Customer, Machine, Operator</strong> and print specifications',
  'Set the <strong>Due Date</strong> and optionally a <strong>Scheduled Date</strong>',
  'Select <strong>Finishing Options</strong> (cutting, folding, binding, lamination, etc.)',
  'Click <strong>Save</strong> — the job starts at status <strong>Waiting</strong>',
  'Open the job card and click the <strong>status pill</strong> to advance through production stages',
  'Upload artwork in the <strong>Pre-Press sidebar</strong> and track consumables in <strong>Materials & Consumables</strong>',
  'When delivered, advance to <strong>Delivered</strong> — an SMS is sent to the customer automatically',
]

const costingSteps = [
  'Open any job card → scroll to the <strong>Production Costing</strong> section',
  'Click <strong>Edit Costs</strong> and enter actual usage — paper sheets, ink colours, machine hours, etc.',
  'Totals compute live — you can see the full cost before saving',
  'Click <strong>Save Costs</strong>',
  'The comparison table shows <strong>Estimated vs Actual vs Variance</strong> for each cost bucket',
  'A <strong>Profit badge</strong> shows green (profitable) or red (loss) based on the linked sale/quotation revenue',
]

const scheduleSteps = [
  'Go to <strong>Schedule</strong> in the sidebar',
  'Use the <strong>← → arrows</strong> to navigate weeks',
  'Drag any job chip from the <strong>Unscheduled Jobs</strong> pool at the top',
  'Drop it onto a <strong>machine row / day column</strong> cell to assign it',
  'The job is saved instantly — drag back to unscheduled to clear',
  'Check the right sidebar for <strong>Due Date Alerts</strong> and <strong>Operator Workload</strong>',
]

const proofStaffSteps = [
  'Advance job status to <strong>Proof Approval</strong>',
  'An SMS is sent to the customer automatically',
  'After client decides, open the job card — the <strong>Pre-Press sidebar</strong> shows their decision and notes',
  'Advance to <strong>Plate Making</strong> if approved, or back to <strong>Designing</strong> if rejected',
]

const proofClientSteps = [
  'Client receives SMS and logs into <strong>/portal/login</strong>',
  'Goes to the <strong>Job Status</strong> tab and finds their job',
  'Clicks <strong>Review Proof</strong> (yellow button)',
  'Reads/enters optional feedback notes',
  'Clicks <strong>✓ Approve Proof</strong> or <strong>✗ Reject</strong>',
  'Staff see the decision immediately on the job card',
]

const inventorySteps = [
  'Create <strong>Suppliers</strong> (Sidebar → Suppliers → New Supplier)',
  'Create <strong>Material categories</strong> (Admin → Categories)',
  'Add <strong>Materials/Products</strong> with reorder levels and unit costs',
  'Raise a <strong>Purchase Order</strong> when stock is needed (Sidebar → Purchases → New)',
  'When goods arrive, record a <strong>GRN</strong> (Admin → GRN → New GRN) — stock increases automatically',
  'If materials are damaged, record it under <strong>Damages & Waste</strong> — stock decreases and an expense is posted',
  'Track per-job material usage in the job card\'s <strong>Materials & Consumables</strong> section',
]

const workloadExample = [
  { initial: 'C', name: 'Chaminda Ratna', role: 'Machine Operator', jobs: 2, cls: 'bg-green-100 text-green-700' },
  { initial: 'N', name: 'Nimal Jayasinghe', role: 'Prod. Manager', jobs: 4, cls: 'bg-amber-100 text-amber-700' },
  { initial: 'A', name: 'Asanka Perera', role: 'Designer', jobs: 6, cls: 'bg-red-100 text-red-700' },
]

const roles = [
  { icon: '🛡️', name: 'Admin / Owner', desc: 'Full access to everything across all branches. Can manage users, settings, and view all financial data.', tags: ['All modules', 'All branches', 'User management'] },
  { icon: '💼', name: 'Sales', desc: 'Creates quotations and sales orders, manages customers, views delivery status.', tags: ['Quotations', 'Sales Orders', 'Customers', 'Deliveries'] },
  { icon: '📐', name: 'Estimator', desc: 'Focuses on costing and creating accurate quotations. Cannot convert to sales.', tags: ['Quotations', 'Cost breakdown', 'Templates'] },
  { icon: '🎨', name: 'Designer', desc: 'Handles pre-press tasks, uploads artwork files, advances jobs from Designing to Proof Approval.', tags: ['Job Cards', 'Pre-Press', 'Artwork uploads'] },
  { icon: '🏭', name: 'Production Manager', desc: 'Oversees the full production floor — job cards, schedule, machines, operators, consumables.', tags: ['Job Cards', 'Schedule', 'Production Queue', 'Machines'] },
  { icon: '⚙️', name: 'Machine Operator', desc: 'Logs production runs, advances job status, records output and waste quantities.', tags: ['Production runs', 'Job status', 'Output tracking'] },
  { icon: '📦', name: 'Store Keeper', desc: 'Manages all inventory — receives stock via GRN, records damage and supplier returns.', tags: ['Stock', 'GRN', 'Damage reports', 'Returns'] },
  { icon: '💰', name: 'Accountant', desc: 'Accesses finance, salary, reports and all accounting journal entries.', tags: ['Finance', 'Salary', 'Reports', 'Journal'] },
  { icon: '🚚', name: 'Dispatch Officer', desc: 'Creates and manages deliveries linked to job cards.', tags: ['Deliveries', 'Job Cards'] },
  { icon: '👤', name: 'Client (Portal)', desc: 'Can only log into the client portal. Sees their own jobs, quotations, and orders. Can approve proofs.', tags: ['Portal only', 'Proof approval', 'Job tracking'] },
]
</script>
