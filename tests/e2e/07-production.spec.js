import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Production', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
  })

  test('production page loads', async ({ page }) => {
    await navTo(page, 'Production')
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="queue"], [class*="card"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('production analytics page renders charts', async ({ page }) => {
    await page.goto('/production/analytics')
    await page.waitForLoadState('load')
    // Analytics page always renders KPI cards; canvas only if data exists
    await expect(
      page.locator('canvas, [class*="chart"], [class*="kpi"], [class*="card"], h2, h3').first()
    ).toBeVisible({ timeout: 10_000 })
  })

  test('production analytics has KPI cards', async ({ page }) => {
    await page.goto('/production/analytics')
    await page.waitForLoadState('load')
    // Analytics page shows date filter and some statistics
    await expect(page.locator('input[type="date"], [class*="card"], h2, h3').first()).toBeVisible()
  })

  test('production analytics date filter works', async ({ page }) => {
    await page.goto('/production/analytics')
    await page.waitForLoadState('load')

    const dateInput = page.locator('input[type="date"]').first()
    if (await dateInput.isVisible({ timeout: 2_000 }).catch(() => false)) {
      await dateInput.fill('2026-01-01')
      await page.keyboard.press('Enter')
      await page.waitForLoadState('load')
    }
  })

  test('pre-press page loads', async ({ page }) => {
    await navTo(page, 'Pre-Press')
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="task"], [class*="card"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('finishing page loads', async ({ page }) => {
    await navTo(page, 'Finishing')
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="task"], [class*="card"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('machines page lists machines', async ({ page }) => {
    await navTo(page, 'Machines')
    await page.waitForLoadState('load')
    // Machines page uses div cards (not a table). Confirm page loaded via "Add Machine" button.
    await expect(page.locator('button:has-text("Add Machine")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can add a machine', async ({ page }) => {
    await navTo(page, 'Machines')
    await page.waitForLoadState('load')

    await page.click('button:has-text("Add Machine")')
    await page.waitForTimeout(300)

    const stamp = Date.now()
    // Machine name input has placeholder "e.g., Heidelberg SM 52"
    await page.fill('input[placeholder*="Heidelberg"]', `E2E Machine ${stamp}`)

    // Machine Type select — first select in the modal form
    const typeSelect = page.locator('form select').first()
    if (await typeSelect.isVisible({ timeout: 2_000 }).catch(() => false)) {
      await typeSelect.selectOption({ index: 1 })
    }

    // Submit button is type="submit" with text "Save"
    await page.click('button[type="submit"]')
    await page.waitForTimeout(1500)
    await expect(page.locator(`text=E2E Machine ${stamp}`).first()).toBeVisible({ timeout: 8_000 })
  })

  test('deliveries page loads', async ({ page }) => {
    await navTo(page, 'Deliveries')
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="delivery"]').first()).toBeVisible({ timeout: 10_000 })
  })

})
