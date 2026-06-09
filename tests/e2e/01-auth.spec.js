import { test, expect } from '@playwright/test'
import { loginAs, STAFF } from './helpers/auth.js'

test.describe('Authentication', () => {

  test('staff login page loads', async ({ page }) => {
    await page.goto('/login')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('admin can log in and see dashboard', async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await expect(page).toHaveURL(/\/$/)
    await expect(page.locator('aside, nav').first()).toBeVisible()
  })

  test('wrong password shows error', async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 5_000 })
    await page.fill('input[type="email"]', STAFF.admin.email)
    await page.fill('input[type="password"]', 'wrong-password')
    await page.click('button[type="submit"]')
    await expect(
      page.locator(':text("Invalid"), :text("incorrect"), :text("wrong"), :text("credentials"), :text("Unauthorized")').first()
    ).toBeVisible({ timeout: 8_000 })
  })

  test('empty fields show validation', async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 5_000 })
    await page.click('button[type="submit"]')
    // HTML5 required validation
    const emailInput = page.locator('input[type="email"]')
    const validity = await emailInput.evaluate(el => el.validity.valid)
    expect(validity).toBe(false)
  })

  test('admin can log out', async ({ page }) => {
    await loginAs(page, STAFF.admin)
    // Logout button is icon-only with title="Logout"
    await page.click('button[title="Logout"]')
    await expect(page).toHaveURL(/\/login/, { timeout: 10_000 })
  })

  test('client portal login page is separate from staff login', async ({ page }) => {
    await page.goto('/portal/login')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page).toHaveURL(/\/portal\/login/)
  })

  test('unauthenticated redirect to login', async ({ page }) => {
    // Navigate to login first so we have a page to evaluate on
    await page.goto('/login')
    await page.evaluate(() => {
      try { localStorage.clear() } catch {}
    })
    // Full navigation to root with no auth — router guard should redirect
    await page.goto('/')
    await expect(page).toHaveURL(/\/login/, { timeout: 10_000 })
  })

  test('sales role can log in', async ({ page }) => {
    await loginAs(page, STAFF.sales)
    await expect(page.locator('aside, nav').first()).toBeVisible()
  })

})
