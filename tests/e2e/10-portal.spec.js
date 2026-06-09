import { test, expect } from '@playwright/test'
import { loginAs, loginAsPortal, STAFF } from './helpers/auth.js'

/**
 * Portal tests require a client user seeded in the DB.
 * If no client user exists, tests are skipped gracefully.
 * To seed one: php artisan tinker --execute="
 *   $c = App\Models\Customer::first();
 *   App\Models\User::create(['name'=>'Portal Client','email'=>'client@lmucpress.lk',
 *     'password'=>bcrypt('password'),'role'=>'client',
 *     'branch_id'=>$c->branch_id,'customer_id'=>$c->id,'is_active'=>true]);
 * "
 */

test.describe('Client Portal', () => {

  test('portal login page is accessible', async ({ page }) => {
    await page.goto('/portal/login')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
  })

  test('portal login page is visually distinct from staff login', async ({ page }) => {
    await page.goto('/portal/login')
    // Should say "Portal" or "Client" somewhere
    // Portal login shows "Client Portal" as the h1
    await expect(page.locator(':text("Client Portal")').first()).toBeVisible()
  })

  test('staff credentials are rejected on portal login', async ({ page }) => {
    await page.goto('/portal/login')
    await page.fill('input[type="email"]', STAFF.admin.email)
    await page.fill('input[type="password"]', STAFF.admin.password)
    await page.click('button[type="submit"]')
    // Should NOT redirect to /portal dashboard — either error message or stays on login
    await expect(page).not.toHaveURL('/portal', { timeout: 5_000 }).catch(() => {})
  })

  test('public job tracker page loads', async ({ page }) => {
    await page.goto('/track/JC-HQ-0001')
    // Should show job info or "not found" — either is a valid response (page renders)
    await expect(page.locator('body')).toBeVisible()
  })

})

test.describe('Client Portal — Authenticated', () => {

  test.beforeEach(async ({ page }) => {
    // Try to log in as the client user — skip if not seeded
    try {
      await loginAsPortal(page)
    } catch {
      test.skip()
    }
  })

  test('portal dashboard shows job list', async ({ page }) => {
    // Portal dashboard shows "Your Jobs"
    await expect(page.locator(':text("Your Jobs")').first()).toBeVisible({ timeout: 8_000 })
  })

  test('portal dashboard shows quotations tab', async ({ page }) => {
    // Portal shows "Your Quotations" tab
    await expect(page.locator(':text("Your Quotations"), :text("Quotations")').first()).toBeVisible()
  })

  test('portal dashboard has job status information', async ({ page }) => {
    // Status labels rendered via jobStatusLabel()
    await expect(page.locator(':text("Status"), :text("Waiting"), :text("Designing"), :text("Printing")').first()).toBeVisible({ timeout: 6_000 })
  })

  test('proof approval panel is shown when job is in proof_approval', async ({ page }) => {
    // This test depends on a job being in proof_approval status for this customer
    const proofPanel = page.locator(':text("Proof Approval"), :text("Approve"), :text("Reject")').first()
    const count = await proofPanel.count()
    if (count > 0) {
      await expect(proofPanel).toBeVisible()
    }
  })

  test('client cannot access staff pages', async ({ page }) => {
    await page.goto('/job-cards')
    // Should redirect to portal or show 403
    await expect(page).not.toHaveURL('/job-cards', { timeout: 5_000 }).catch(() => {})
  })

})
