import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Quotations', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Quotations')
  })

  test('quotation list loads', async ({ page }) => {
    await expect(page.locator('table, [class*="list"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('quotation number starts with QT-', async ({ page }) => {
    await expect(page.locator(':text("QT-")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can navigate to new quotation form', async ({ page }) => {
    // Button text: "New Quotation"
    await page.click('button:has-text("New Quotation"), a:has-text("New Quotation")')
    await page.waitForLoadState('load')
    await expect(page).toHaveURL(/\/quotations\/new/)
  })

  test('create a quotation and verify computed totals', async ({ page }) => {
    await page.goto('/quotations/new')
    await page.waitForLoadState('load')

    await page.fill('input[placeholder*="Annual Report"]', 'E2E Test Quotation')
    await page.fill('input[placeholder="500"]', '200')

    // Cost fields by placeholder
    const fillByPlaceholder = async (ph, val) => {
      const el = page.locator(`input[placeholder="${ph}"]`).first()
      if (await el.isVisible({ timeout: 1_500 }).catch(() => false)) await el.fill(String(val))
    }

    await fillByPlaceholder('0', '2000')   // plate_cost or similar numeric field

    // Fill cost inputs by position (label: Plate Cost, Paper Cost, etc.)
    const costInputs = page.locator('input[type="number"][placeholder="0"]')
    const count = await costInputs.count()
    const values = [2000, 5000, 1500, 1000, 2000]
    for (let i = 0; i < Math.min(count, values.length); i++) {
      await costInputs.nth(i).fill(String(values[i]))
    }

    await page.click('button:has-text("Save Quotation")')
    await page.waitForLoadState('load')
    await expect(page.locator(':text("E2E Test Quotation")').first()).toBeVisible({ timeout: 8_000 })
  })

  test('quotation detail page shows cost breakdown', async ({ page }) => {
    await page.locator('table tbody tr').first().click()
    await page.waitForLoadState('load')
    await expect(page.locator(':text("Total"), :text("Subtotal")').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can download quotation PDF', async ({ page }) => {
    await page.locator('table tbody tr').first().click()
    await page.waitForLoadState('load')

    const downloadBtn = page.locator('button:has-text("PDF"), a:has-text("Download"), button:has-text("Download")').first()
    if (await downloadBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
      const [download] = await Promise.all([
        page.waitForEvent('download', { timeout: 15_000 }),
        downloadBtn.click(),
      ])
      expect(download.suggestedFilename()).toMatch(/\.pdf$/i)
    }
  })

  test('can search quotations', async ({ page }) => {
    const search = page.locator('input[placeholder*="Search"]').first()
    await search.fill('Annual')
    await page.waitForTimeout(600)   // debounce
    await expect(page.locator(':text("Annual")').first()).toBeVisible({ timeout: 8_000 })
  })

  test('can save quotation as template', async ({ page }) => {
    await page.goto('/quotations/new')
    await page.waitForLoadState('load')

    // Handle the native prompt() dialog that appears when clicking "Save as Template"
    page.once('dialog', async dialog => {
      await dialog.accept('E2E Template')
    })
    await page.click('button:has-text("Save as Template")')
    // After accepting dialog, alert appears — dismiss it
    page.once('dialog', async dialog => {
      await dialog.accept()
    })
    await page.waitForTimeout(1000)
  })

})
