import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Finance', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Finance')
  })

  test('finance page loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="finance"], [class*="journal"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('finance page shows account balances', async ({ page }) => {
    // Finance page shows salary / income-expense entries with LKR amounts
    await expect(page.locator(':text("LKR"), :text("Salary"), :text("Income")').first()).toBeVisible({ timeout: 10_000 })
  })

})

test.describe('Reports', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Reports')
  })

  test('reports page loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('[class*="report"], table, [class*="card"]').first()).toBeVisible({ timeout: 10_000 })
  })

})

test.describe('Audit Log', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
  })

  test('audit log / stock ledger page loads', async ({ page }) => {
    await navTo(page, 'Audit Log')
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="audit"], [class*="ledger"]').first()).toBeVisible({ timeout: 10_000 })
  })

})
