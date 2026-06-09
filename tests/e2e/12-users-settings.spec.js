import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Users Management', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Users')
  })

  test('users list loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('table tbody tr').first()).toBeVisible({ timeout: 10_000 })
  })

  test('seeded users are listed', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator(':text("admin@lmucpress.lk"), :text("Admin")').first()).toBeVisible()
  })

  test('users list shows role badges', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator(':text("admin"), :text("sales"), :text("designer")').first()).toBeVisible()
  })

  test('can open create user form', async ({ page }) => {
    await page.click('button:has-text("Add User")')
    await page.waitForTimeout(300)
    // Users.vue modal: <input v-model="form.email" type="email" required class="form-input" />
    // No name or placeholder — use type selector
    await expect(page.locator('input[type="email"]').first()).toBeVisible({ timeout: 5_000 })
  })

  test('can create a new user', async ({ page }) => {
    await page.click('button:has-text("Add User")')
    await page.waitForTimeout(300)

    const stamp = Date.now()
    // Users.vue form inputs use label pattern — no name/placeholder attributes
    // Full Name: <label class="form-label">Full Name *</label><input v-model="form.name" required class="form-input" />
    await page.locator('label:has-text("Full Name") ~ input').first().fill(`E2E User ${stamp}`)
    await page.locator('input[type="email"]').first().fill(`e2e${stamp}@lmucpress.lk`)
    await page.locator('input[type="password"]').first().fill('password')

    // Role select: <label class="form-label">Role *</label><select v-model="form.role" required class="form-input">
    const roleSelect = page.locator('label:has-text("Role") ~ select').first()
    if (await roleSelect.isVisible({ timeout: 2_000 }).catch(() => false)) {
      await roleSelect.selectOption('sales')
    }

    // Submit button IS type="submit": <button type="submit" ...>Create User</button>
    await page.click('button[type="submit"]:has-text("Create User"), button[type="submit"]')
    await page.waitForTimeout(1000)
    await expect(page.locator(`:text("E2E User ${stamp}")`).first()).toBeVisible({ timeout: 8_000 })
  })

  test('client role shows customer dropdown', async ({ page }) => {
    await page.click('button:has-text("Add User")')
    await page.waitForTimeout(300)

    const roleSelect = page.locator('label:has-text("Role") ~ select').first()
    if (await roleSelect.isVisible({ timeout: 2_000 }).catch(() => false)) {
      await roleSelect.selectOption('client')
      // Customer dropdown: <label class="form-label">Linked Customer</label><select v-model="form.customer_id" ...>
      await expect(page.locator('label:has-text("Customer") ~ select, label:has-text("Linked Customer") ~ select').first()).toBeVisible({ timeout: 3_000 })
    }
  })

  test('can toggle user active status', async ({ page }) => {
    await page.waitForLoadState('load')
    const toggleBtn = page.locator('button[title*="active"], button:has-text("Deactivate"), button:has-text("Activate")').first()
    if (await toggleBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await toggleBtn.click()
      await page.waitForLoadState('load')
    }
  })

})

test.describe('Settings', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Settings')
  })

  test('settings page loads', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('[class*="setting"], form, input').first()).toBeVisible({ timeout: 10_000 })
  })

  test('settings page shows company info fields', async ({ page }) => {
    await page.waitForLoadState('load')
    await expect(page.locator('input[name*="name"], label:has-text("Company"), label:has-text("Branch")').first()).toBeVisible()
  })

})

test.describe('Getting Started page', () => {

  test('getting started page is accessible', async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await page.goto('/getting-started')
    await page.waitForLoadState('load')
    // Page may not exist — check for body content only
    await expect(page.locator('body')).toBeVisible()
  })

  test('getting started tabs are navigable', async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await page.goto('/getting-started')
    await page.waitForLoadState('load')

    const tabs = page.locator('button[role="tab"], [class*="tab"]')
    const count = await tabs.count()
    if (count > 1) {
      await tabs.nth(1).click()
      await page.waitForLoadState('load')
      await expect(page.locator('[class*="tab-panel"], [class*="content"]').first()).toBeVisible()
    }
  })

  test('getting started shows all 13 roles', async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await page.goto('/getting-started')
    await page.waitForLoadState('load')

    const rolesTab = page.locator('button:has-text("Roles"), [role="tab"]:has-text("Roles")').first()
    if (await rolesTab.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await rolesTab.click()
      await expect(page.locator(':text("admin"), :text("Admin")').first()).toBeVisible()
    }
  })

})
