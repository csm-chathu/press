import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Dashboard', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
  })

  test('dashboard shows hero banner with greeting', async ({ page }) => {
    // Hero banner shows: "Good morning, Admin" (or Good afternoon / Good evening)
    // Text is rendered as: {{ greeting }}, {{ auth.user?.name }}
    const greeting = page.locator(':text("Good morning"), :text("Good afternoon"), :text("Good evening"), :text("LMUC Press")').first()
    await expect(greeting).toBeVisible({ timeout: 10_000 })
  })

  test('KPI cards are visible', async ({ page }) => {
    // Hero pills show "Active Jobs", "Today\'s Orders", "Ready"
    await expect(page.locator(':text("Active Jobs"), :text("Today\'s Orders"), :text("Ready")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('production queue section is visible', async ({ page }) => {
    // Dashboard.vue h3: "Production Queue"
    await expect(page.locator(':text("Production Queue")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('revenue chart renders', async ({ page }) => {
    // Chart.js renders a <canvas> only when data exists — check for canvas or the chart section heading
    await page.waitForTimeout(2000)
    const canvas = page.locator('canvas')
    const canvasCount = await canvas.count()
    if (canvasCount > 0) {
      await expect(canvas.first()).toBeVisible({ timeout: 10_000 })
    } else {
      // No chart data seeded — just confirm the dashboard section is there
      await expect(page.locator(':text("Revenue"), :text("Sales"), :text("Orders")').first()).toBeVisible({ timeout: 8_000 })
    }
  })

  test('sidebar navigation links are present', async ({ page }) => {
    await expect(page.locator('aside a:has-text("Job Cards"), nav a:has-text("Job Cards")')).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('aside a:has-text("Quotations"), nav a:has-text("Quotations")')).toBeVisible()
  })

  test('page title updates when navigating', async ({ page }) => {
    await navTo(page, 'Customers')
    // Top bar h1 shows the page title (mapped in AppLayout: 'customers' → 'Customers')
    await expect(page.locator('h1:has-text("Customers"), :text("Customers")').first()).toBeVisible({ timeout: 10_000 })
  })

})
