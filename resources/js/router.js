import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

NProgress.configure({ showSpinner: false, speed: 300, minimum: 0.1 })

const routes = [
    // ── Public job tracker (no auth) ───────────────────────────────
    {
        path: '/track/:number?',
        name: 'job-tracker',
        component: () => import('@/pages/JobTracker.vue'),
        meta: { public: true },
    },

    // ── Client portal ──────────────────────────────────────────────
    {
        path: '/portal/login',
        name: 'portal.login',
        component: () => import('@/pages/PortalLogin.vue'),
        meta: { portalGuest: true },
    },
    {
        path: '/portal',
        name: 'portal.dashboard',
        component: () => import('@/pages/PortalDashboard.vue'),
        meta: { requiresPortal: true },
    },

    // ── Staff login ────────────────────────────────────────────────
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            // ── Core ────────────────────────────────────────────────
            { path: '',              name: 'dashboard',       component: () => import('@/pages/Dashboard.vue') },
            { path: 'customers',     name: 'customers',       component: () => import('@/pages/Customers.vue') },
            { path: 'suppliers',     name: 'suppliers',       component: () => import('@/pages/Suppliers.vue') },

            // ── Quotations ──────────────────────────────────────────
            { path: 'quotations',        name: 'quotations',      component: () => import('@/pages/Quotations.vue') },
            { path: 'quotations/new',    name: 'quotations.new',  component: () => import('@/pages/NewQuotation.vue') },
            { path: 'quotations/:id',    name: 'quotations.show', component: () => import('@/pages/QuotationDetail.vue') },

            // ── Sales Orders ────────────────────────────────────────
            { path: 'sales',             name: 'sales',           component: () => import('@/pages/Sales.vue') },
            { path: 'sales/new',         name: 'sales.new',       component: () => import('@/pages/NewSale2.vue') },
            { path: 'sales/:id/edit',    name: 'sales.edit',      component: () => import('@/pages/EditDraft.vue') },
            { path: 'sales/:id',         name: 'sales.receipt',   component: () => import('@/pages/SaleReceipt.vue') },

            // ── Production ──────────────────────────────────────────
            { path: 'job-cards',         name: 'job-cards',       component: () => import('@/pages/JobCards.vue') },
            { path: 'job-cards/new',     name: 'job-cards.new',   component: () => import('@/pages/NewJobCard.vue') },
            { path: 'job-cards/:id',     name: 'job-cards.show',  component: () => import('@/pages/JobCardDetail.vue') },
            { path: 'schedule',          name: 'schedule',        component: () => import('@/pages/Schedule.vue') },
            { path: 'production',        name: 'production',      component: () => import('@/pages/Production.vue') },
            { path: 'production/analytics', name: 'production.analytics', component: () => import('@/pages/ProductionAnalytics.vue') },
            { path: 'prepress',          name: 'prepress',        component: () => import('@/pages/PrePress.vue') },
            { path: 'finishing',         name: 'finishing',       component: () => import('@/pages/Finishing.vue') },
            { path: 'machines',          name: 'machines',        component: () => import('@/pages/Machines.vue') },

            // ── Inventory ───────────────────────────────────────────
            { path: 'products',          name: 'products',        component: () => import('@/pages/Products.vue') },
            { path: 'categories',        name: 'categories',      component: () => import('@/pages/Categories.vue') },
            { path: 'purchases',         name: 'purchases',       component: () => import('@/pages/Purchases.vue') },
            { path: 'purchases/new',     name: 'purchases.new',   component: () => import('@/pages/NewPurchase.vue') },
            { path: 'grn',               name: 'grn',             component: () => import('@/pages/GRN.vue') },
            { path: 'supplier-returns',  name: 'supplier-returns',component: () => import('@/pages/SupplierReturns.vue') },
            { path: 'damages',           name: 'damages',         component: () => import('@/pages/Damages.vue') },

            // ── Delivery ────────────────────────────────────────────
            { path: 'deliveries',        name: 'deliveries',      component: () => import('@/pages/Deliveries.vue') },
            { path: 'deliveries/new',    name: 'deliveries.new',  component: () => import('@/pages/NewDelivery.vue') },

            // ── Finance & Admin ─────────────────────────────────────
            { path: 'reports',           name: 'reports',         component: () => import('@/pages/Reports.vue') },
            { path: 'finance',           name: 'finance',         component: () => import('@/pages/Finance.vue') },
            { path: 'audit-log',         name: 'audit-log',       component: () => import('@/pages/StockLedger.vue') },
            { path: 'opening-balance',   name: 'opening-balance', component: () => import('@/pages/OpeningBalance.vue') },
            { path: 'users',             name: 'users',           component: () => import('@/pages/Users.vue') },
            { path: 'settings',          name: 'settings',        component: () => import('@/pages/PressSettings.vue') },
            { path: 'getting-started',   name: 'getting-started', component: () => import('@/pages/GettingStarted.vue') },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to) => {
    NProgress.start()
    const auth = useAuthStore()

    // Public routes — always allow
    if (to.meta.public) return

    const isClient = auth.user?.role === 'client'

    // Portal guards
    if (to.meta.requiresPortal) {
        if (!auth.token) return '/portal/login'
        if (!isClient) return '/login'
        return
    }
    if (to.meta.portalGuest) {
        if (auth.token && isClient) return '/portal'
        return
    }

    // Staff app guards
    if (to.meta.requiresAuth) {
        if (!auth.token) return '/login'
        if (isClient) return '/portal'  // clients can't access staff app
    }
    if (to.meta.guest && auth.token) {
        return isClient ? '/portal' : '/'
    }
})

router.afterEach(() => { NProgress.done() })

export default router
