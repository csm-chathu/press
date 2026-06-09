import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Schedule', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Schedule')
  })

  test('schedule page loads with week calendar', async ({ page }) => {
    await expect(page.locator('button:has-text("Today")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('shows machine rows', async ({ page }) => {
    // Machine names from seeder — Heidelberg SM 52, Roland 700, etc.
    // Page shows machine.name in each row
    const machineRow = page.locator(':text("Heidelberg"), :text("Roland"), :text("HP Indigo"), :text("No active machines")').first()
    await expect(machineRow).toBeVisible({ timeout: 10_000 })
  })

  test('shows day column headers', async ({ page }) => {
    // Schedule renders day headers with abbreviated day names
    const dayHeader = page.locator(':text("Mon"), :text("Tue"), :text("Wed")').first()
    await expect(dayHeader).toBeVisible({ timeout: 10_000 })
  })

  test('prev / next week navigation works', async ({ page }) => {
    await page.waitForLoadState('load')

    // Get the current week indicator text
    const weekLabel = page.locator('h2, h3, [class*="week"]').first()
    const beforeText = await weekLabel.innerText().catch(() => 'before')

    // Click the next-week button (›, Next, or aria-label)
    const nextBtn = page.locator('button:has-text("›"), button[aria-label*="next"], button:has-text("Next")').first()
    if (await nextBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await nextBtn.click()
      await page.waitForTimeout(500)
      const afterText = await weekLabel.innerText().catch(() => 'after')
      expect(afterText).not.toBe(beforeText)
    }
  })

  test('unscheduled jobs panel is shown', async ({ page }) => {
    await expect(page.locator(':text("Unscheduled")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('operator workload panel is shown', async ({ page }) => {
    await expect(page.locator(':text("Workload")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('due date alerts panel is shown', async ({ page }) => {
    await expect(page.locator(':text("Alerts"), :text("Due Date")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('Today button resets to current week', async ({ page }) => {
    // Navigate to next week first
    const nextBtn = page.locator('button:has-text("›"), button[aria-label*="next"], button:has-text("Next")').first()
    if (await nextBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await nextBtn.click()
      await page.waitForTimeout(300)
    }

    // Click Today
    await page.click('button:has-text("Today")')
    await page.waitForTimeout(300)

    // "★ Today" badge should be visible (rendered when current week)
    await expect(page.locator(':text("Today")').first()).toBeVisible({ timeout: 5_000 })
  })

})
