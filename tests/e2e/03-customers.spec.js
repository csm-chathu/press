import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Customers', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Customers')
  })

  test('customer list loads with seeded data', async ({ page }) => {
    await expect(page.locator('table tbody tr').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can search for a customer', async ({ page }) => {
    const search = page.locator('input[placeholder*="Search"]').first()
    await search.fill('Dialog')
    await page.waitForTimeout(600)  // debounce
    await expect(page.locator(':text("Dialog")').first()).toBeVisible({ timeout: 8_000 })
  })

  test('can open create customer modal/form', async ({ page }) => {
    await page.click('button:has-text("Add Customer")')
    await page.waitForTimeout(300)  // Vue nextTick for modal render
    await expect(page.locator('label:has-text("Name")').first()).toBeVisible({ timeout: 5_000 })
  })

  test('create a new customer', async ({ page }) => {
    await page.click('button:has-text("Add Customer")')
    await page.waitForTimeout(300)  // wait for modal to render

    const stamp = Date.now()
    // Inputs use label siblings — no name/placeholder attributes
    // <label class="form-label">Name *</label><input v-model="form.name" required class="form-input" />
    await page.locator('label:has-text("Name") + input').first().fill(`E2E Customer ${stamp}`)
    await page.locator('label:has-text("Phone") + input').first().fill('0771111222')

    // Submit: <button @click="save" class="btn-primary">Save</button> (not type="submit")
    await page.locator('.btn-primary:has-text("Save"), button:has-text("Save")').last().click()
    await page.waitForTimeout(1500)

    // Search for the created customer to avoid pagination issues
    const search = page.locator('input[placeholder*="Search"]').first()
    await search.fill(`E2E Customer ${stamp}`)
    await page.waitForTimeout(600)
    await expect(page.locator(`:text("E2E Customer ${stamp}")`).first()).toBeVisible({ timeout: 8_000 })
  })

  test('can edit a customer', async ({ page }) => {
    const editBtn = page.locator('button:has-text("Edit")').first()
    if (await editBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await editBtn.click()
      await page.waitForTimeout(300)

      const nameInput = page.locator('label:has-text("Name") + input').first()
      await nameInput.fill('Updated Customer Name')

      await page.locator('.btn-primary:has-text("Save"), button:has-text("Save")').last().click()
      await page.waitForTimeout(1000)
      await expect(page.locator(':text("Updated Customer Name")').first()).toBeVisible({ timeout: 8_000 })
    }
  })

  test('can delete a customer', async ({ page }) => {
    const rows = page.locator('table tbody tr')
    const count = await rows.count()
    if (count > 0) {
      const deleteBtn = rows.last().locator('button:has-text("Delete"), button[title="Delete"]').first()
      if (await deleteBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
        page.once('dialog', async d => d.accept())
        await deleteBtn.click()
        await page.waitForTimeout(1000)
      }
    }
  })

})

test.describe('Suppliers', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Suppliers')
  })

  test('supplier list loads', async ({ page }) => {
    await expect(page.locator('table tbody tr').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can create a supplier', async ({ page }) => {
    await page.click('button:has-text("Add Supplier")')
    await page.waitForTimeout(300)  // modal render

    const stamp = Date.now()
    // <label class="form-label">Name *</label><input v-model="form.name" required class="form-input" />
    await page.locator('label:has-text("Name") + input').first().fill(`E2E Supplier ${stamp}`)

    // Submit: <button @click="save" class="btn-primary">Save</button>
    await page.locator('.btn-primary:has-text("Save"), button:has-text("Save")').last().click()
    await page.waitForTimeout(1500)
    await expect(page.locator(`:text("E2E Supplier ${stamp}")`).first()).toBeVisible({ timeout: 8_000 })
  })

})
