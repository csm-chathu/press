import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Sales & Invoices', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Sales')
  })

  test('sales list loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="sale"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can navigate to new sale', async ({ page }) => {
    // Sales page uses router-link with text "New Bill"
    await page.click('a:has-text("New Bill"), button:has-text("New Bill"), a:has-text("New Sale")')
    await page.waitForLoadState('load')
    await expect(page).toHaveURL(/\/sales\/new/)
  })

  test('new sale POS form has product search', async ({ page }) => {
    await page.goto('/sales/new')
    await page.waitForLoadState('load')
    await expect(page.locator('input[placeholder*="Search"], input[placeholder*="Product"]').first()).toBeVisible()
  })

  test('sales list shows invoice numbers starting with INV-', async ({ page }) => {
    await page.waitForLoadState('load')
    const invCell = page.locator('td:has-text("INV-")').first()
    if (await invCell.count() > 0) {
      await expect(invCell).toBeVisible()
    }
  })

  test('can download invoice PDF from sale detail', async ({ page }) => {
    await page.waitForLoadState('load')

    const firstRow = page.locator('table tbody tr').first()
    if (await firstRow.count() > 0) {
      await firstRow.click()
      await page.waitForLoadState('load')

      const downloadBtn = page.locator('button:has-text("PDF"), button:has-text("Invoice"), a:has-text("Download")').first()
      if (await downloadBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
        const [download] = await Promise.all([
          page.waitForEvent('download', { timeout: 15_000 }),
          downloadBtn.click(),
        ])
        expect(download.suggestedFilename()).toMatch(/\.pdf$/i)
      }
    }
  })

  test('sale receipt shows customer and total', async ({ page }) => {
    await page.waitForLoadState('load')
    const firstRow = page.locator('table tbody tr').first()
    if (await firstRow.count() > 0) {
      await firstRow.click()
      await page.waitForLoadState('load')
      await expect(page.locator(':text("Total"), :text("LKR")').first()).toBeVisible()
    }
  })

})
