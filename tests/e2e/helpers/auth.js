/**
 * Shared login helpers for E2E tests.
 * Uses the seeded accounts from PrintingPressSeeder.
 */

export const STAFF = {
  admin:      { email: 'admin@lmucpress.lk',     password: 'password', role: 'admin' },
  sales:      { email: 'sales@lmucpress.lk',     password: 'password', role: 'sales' },
  estimator:  { email: 'estimator@lmucpress.lk', password: 'password', role: 'estimator' },
  designer:   { email: 'designer@lmucpress.lk',  password: 'password', role: 'designer' },
  prodmgr:    { email: 'prodmgr@lmucpress.lk',   password: 'password', role: 'production_manager' },
  operator:   { email: 'operator@lmucpress.lk',  password: 'password', role: 'machine_operator' },
  accountant: { email: 'accounts@lmucpress.lk',  password: 'password', role: 'accountant' },
}

/** Route map for navTo — navigate directly instead of clicking the sidebar */
const ROUTES = {
  'Dashboard':           '/',
  'Customers':           '/customers',
  'Suppliers':           '/suppliers',
  'Quotations':          '/quotations',
  'Sales':               '/sales',
  'Job Cards':           '/job-cards',
  'Schedule':            '/schedule',
  'Production':          '/production',
  'Pre-Press':           '/prepress',
  'Finishing':           '/finishing',
  'Machines':            '/machines',
  'Products':            '/products',
  'Categories':          '/categories',
  'Purchases':           '/purchases',
  'GRN':                 '/grn',
  'Supplier Returns':    '/supplier-returns',
  'Damages':             '/damages',
  'Deliveries':          '/deliveries',
  'Reports':             '/reports',
  'Finance':             '/finance',
  'Audit Log':           '/audit-log',
  'Opening Balance':     '/opening-balance',
  'Users':               '/users',
  'Settings':            '/settings',
}

/**
 * Log in as a staff member.
 * Clears any existing auth state first, then fills the login form.
 * Tests in the same file share the browser context so localStorage persists
 * between tests — we must clear it to prevent silent redirects on /login.
 */
export async function loginAs(page, user = STAFF.admin) {
  // Clear auth state left over from previous tests in the same file
  await page.goto('/login')
  await page.evaluate(() => {
    try { localStorage.clear() } catch {}
  })

  // Navigate again so the router guard sees the cleared state
  await page.goto('/login')

  // Wait for the login form (guard may redirect if token still cached briefly)
  await page.waitForSelector('input[type="email"]', { timeout: 10_000 })

  await page.fill('input[type="email"]', user.email)
  await page.fill('input[type="password"]', user.password)
  await page.click('button[type="submit"]')

  // Wait for the dashboard to appear
  await page.waitForURL('**/', { timeout: 15_000 })
  await page.waitForSelector('aside, nav', { timeout: 10_000 })
}

/**
 * Log in as the portal (client) user.
 */
export async function loginAsPortal(page, email = 'client@lmucpress.lk', password = 'password') {
  await page.goto('/portal/login')
  await page.evaluate(() => {
    try { localStorage.clear() } catch {}
  })
  await page.goto('/portal/login')
  await page.waitForSelector('input[type="email"]', { timeout: 10_000 })
  await page.fill('input[type="email"]', email)
  await page.fill('input[type="password"]', password)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/portal**', { timeout: 15_000 })
}

/**
 * Navigate to a page by label using direct URL navigation.
 * Uses 'load' not 'networkidle' — SPAs with polling never reach networkidle.
 */
export async function navTo(page, label) {
  const route = ROUTES[label]
  if (route) {
    await page.goto(route)
  } else {
    await page.click(`aside a:has-text("${label}"), nav a:has-text("${label}")`)
    await page.waitForLoadState('load')
  }
}
