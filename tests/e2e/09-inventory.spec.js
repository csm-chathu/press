import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Inventory — Products', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Products')
  })

  test('product list loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('table tbody tr, [class*="product-card"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('product list has search', async ({ page }) => {
    const search = page.locator('input[placeholder*="Search"], input[type="search"]').first()
    await search.fill('Paper')
    await page.waitForTimeout(600)
    // Results filter
    await expect(page.locator('table tbody tr, [class*="card"]').first()).toBeVisible()
  })

  test('can create a product', async ({ page }) => {
    // Button text contains "Add" (matches "Add Product")
    await page.click('button:has-text("Add Product"), button:has-text("New Product")')
    await page.waitForTimeout(500)

    const stamp = Date.now()
    // ProductModal.vue: <label class="form-label">Name *</label><input v-model="form.name" required class="form-input" />
    await page.locator('label:has-text("Name") ~ input.form-input').first().fill(`E2E Product ${stamp}`)

    // Category is required — select first real option
    const catSelect = page.locator('label:has-text("Category") ~ select').first()
    if (await catSelect.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await catSelect.selectOption({ index: 1 })
    }

    // Selling price is required
    const priceInput = page.locator('label:has-text("Selling Price") ~ input').first()
    if (await priceInput.isVisible({ timeout: 2_000 }).catch(() => false)) {
      await priceInput.fill('150')
    }

    // Submit button in modal footer: <button @click="submit" class="btn-primary">Save</button>
    await page.locator('.btn-primary:has-text("Save"), button:has-text("Save")').last().click()
    await page.waitForTimeout(1500)
    await expect(page.locator(`text=E2E Product ${stamp}`).first()).toBeVisible({ timeout: 8_000 })
  })

  test('product image zoom lightbox works', async ({ page }) => {
    await page.waitForLoadState('load')
    const productImg = page.locator('table tbody img, [class*="product"] img').first()
    if (await productImg.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await productImg.click()
      await expect(page.locator('[class*="lightbox"], [class*="modal"], [class*="overlay"]').first()).toBeVisible({ timeout: 5_000 })
    }
  })

})

test.describe('Inventory — Categories', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Categories')
  })

  test('categories list loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('table tbody tr, [class*="category"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can create a category', async ({ page }) => {
    // Click the Add/New button
    await page.click('button:has-text("Add"), button:has-text("New"), button:has-text("Add Category")')
    await page.waitForTimeout(500)

    const stamp = Date.now()
    // Try label-based selectors first, then fallback to name/placeholder
    const nameInput = page.locator('label:has-text("Name") ~ input').first()
    if (await nameInput.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await nameInput.fill(`E2E Category ${stamp}`)
    } else {
      await page.fill('input[name="name"], input[placeholder*="Name"], input[placeholder*="name"]', `E2E Category ${stamp}`)
    }

    // Submit — try type="submit" first, then button text
    const submitted = await page.locator('button[type="submit"]').first().isVisible({ timeout: 2_000 }).catch(() => false)
    if (submitted) {
      await page.click('button[type="submit"]')
    } else {
      await page.click('button:has-text("Save"), button:has-text("Create")')
    }

    await page.waitForTimeout(1000)
    await expect(page.locator(`text=E2E Category ${stamp}`).first()).toBeVisible({ timeout: 8_000 })
  })

})

test.describe('Inventory — GRN', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'GRN')
  })

  test('GRN list loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="grn"]').first()).toBeVisible({ timeout: 10_000 })
  })

})

test.describe('Inventory — Purchases', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Purchases')
  })

  test('purchases list loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="purchase"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can download purchase order PDF', async ({ page }) => {
    await page.waitForLoadState('load')
    const pdfBtn = page.locator('button:has-text("PDF"), a:has-text("PDF"), button:has-text("Download")').first()
    if (await pdfBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      const [download] = await Promise.all([
        page.waitForEvent('download', { timeout: 15_000 }),
        pdfBtn.click(),
      ])
      expect(download.suggestedFilename()).toMatch(/\.pdf$/i)
    }
  })

})

test.describe('Inventory — Damages & Returns', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
  })

  test('damages page loads', async ({ page }) => {
    await navTo(page, 'Damages')
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="damage"]').first()).toBeVisible({ timeout: 10_000 })
  })

  test('supplier returns page loads', async ({ page }) => {
    await navTo(page, 'Supplier Returns')
    await page.waitForLoadState('load')
    await expect(page.locator('table, [class*="return"]').first()).toBeVisible({ timeout: 10_000 })
  })

})
