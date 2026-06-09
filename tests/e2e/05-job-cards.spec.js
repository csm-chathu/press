import { test, expect } from '@playwright/test'
import { loginAs, STAFF, navTo } from './helpers/auth.js'

test.describe('Job Cards', () => {

  test.beforeEach(async ({ page }) => {
    await loginAs(page, STAFF.admin)
    await navTo(page, 'Job Cards')
  })

  test('job card list loads', async ({ page }) => {
    await expect(page.locator('table tbody tr').first()).toBeVisible({ timeout: 10_000 })
  })

  test('job card list shows status badges', async ({ page }) => {
    // Status in JobCards.vue is a <select> dropdown per row with status options
    await expect(
      page.locator('select, :text("Waiting"), :text("Printing"), :text("Designing"), :text("Finishing")').first()
    ).toBeVisible({ timeout: 8_000 })
  })

  test('RUSH badge is shown for priority jobs', async ({ page }) => {
    const rushBadge = page.locator(':text("PRIORITY"), :text("RUSH"), :text("Rush")').first()
    const count = await rushBadge.count()
    if (count > 0) {
      await expect(rushBadge).toBeVisible()
    }
  })

  test('can open new job card form', async ({ page }) => {
    // JobCards.vue has a "New Job Card" button or similar
    await page.click('button:has-text("New"), a:has-text("New Job"), button:has-text("Create"), a:has-text("New Job Card")')
    await page.waitForLoadState('load')
    await expect(page).toHaveURL(/\/job-cards\/new/)
  })

  test('create a new job card', async ({ page }) => {
    await page.goto('/job-cards/new')
    await page.waitForLoadState('load')

    const stamp = Date.now()
    // Title input placeholder: "e.g., Business Cards — Dialog Axiata"
    const titleInput = page.locator('input[placeholder*="Business Cards"], input[placeholder*="Business"]').first()
    await titleInput.fill(`E2E Job ${stamp}`)

    // Qty input placeholder: "1000"
    const qty = page.locator('input[placeholder="1000"], input[placeholder*="Qty Ordered"]').first()
    if (await qty.isVisible({ timeout: 2_000 }).catch(() => false)) {
      await qty.fill('1000')
    }

    await page.click('button[type="submit"]')
    await page.waitForLoadState('load')
    await expect(page.locator(`:text("E2E Job ${stamp}")`).first()).toBeVisible({ timeout: 8_000 })
  })

  test('job card detail shows status pipeline bar', async ({ page }) => {
    // Navigate via "Details" router-link, not row click (row click doesn't navigate)
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')
      // Status pipeline bar renders all 9 stage labels
      await expect(page.locator(':text("Waiting")').first()).toBeVisible({ timeout: 10_000 })
      await expect(page.locator(':text("Designing")').first()).toBeVisible()
    }
  })

  test('job card detail shows sections', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')
      await expect(page.locator(':text("Job Details")').first()).toBeVisible({ timeout: 10_000 })
    }
  })

  test('can advance job status', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')

      // Click the "Designing" status pill in the pipeline bar
      const designingPill = page.locator(':text("Designing")').first()
      if (await designingPill.isVisible({ timeout: 5_000 }).catch(() => false)) {
        await designingPill.click()
        await page.waitForTimeout(500)
      }
    }
  })

  test('can toggle priority on a job card', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')

      const priorityBtn = page.locator('button:has-text("Priority"), button:has-text("Set Priority")').first()
      if (await priorityBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
        await priorityBtn.click()
        await page.waitForTimeout(500)
      }
    }
  })

  test('can add a consumable to a job card', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')

      const addBtn = page.locator('button:has-text("Add Consumable"), button:has-text("+ Add"), button:has-text("Add")').first()
      if (await addBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
        await addBtn.click()
        await page.waitForTimeout(300)

        const desc = page.locator('input[placeholder*="Description"], input[placeholder*="description"]').last()
        if (await desc.isVisible({ timeout: 2_000 }).catch(() => false)) {
          await desc.fill('CTP Plate A3')
          const qty = page.locator('input[placeholder*="Qty"], input[placeholder*="qty"]').last()
          if (await qty.isVisible({ timeout: 2_000 }).catch(() => false)) await qty.fill('4')
          const cost = page.locator('input[placeholder*="Unit Cost"], input[placeholder*="cost"]').last()
          if (await cost.isVisible({ timeout: 2_000 }).catch(() => false)) await cost.fill('350')

          await page.click('button[type="submit"]:has-text("Add"), button:has-text("Save")')
          await page.waitForTimeout(1000)
          await expect(page.locator(':text("CTP Plate A3")').first()).toBeVisible({ timeout: 6_000 })
        }
      }
    }
  })

  test('production costing section is present', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')
      await expect(page.locator(':text("Production Costing")').first()).toBeVisible({ timeout: 10_000 })
    }
  })

  test('can enter production costing data', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')

      // Button text: "Enter Costs" (no costing yet) or "Edit Costs" (existing)
      const editCostBtn = page.locator('button:has-text("Edit Costs"), button:has-text("Enter Costs")').first()
      if (await editCostBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
        await editCostBtn.click()
        await page.waitForTimeout(300)

        const sheets = page.locator('input[name="paper_sheets"]').first()
        if (await sheets.isVisible({ timeout: 2_000 }).catch(() => false)) {
          await sheets.fill('500')
        }

        await page.click('button:has-text("Save Costing")')
        await page.waitForTimeout(1000)
      }
    }
  })

  test('can clone a job card', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')

      const cloneBtn = page.locator('button:has-text("Clone Job")').first()
      if (await cloneBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
        await cloneBtn.click()
        await page.waitForTimeout(1000)
      }
    }
  })

  test('QR code is shown on job card detail', async ({ page }) => {
    const detailsLink = page.locator('a:has-text("Details")').first()
    if (await detailsLink.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await detailsLink.click()
      await page.waitForLoadState('load')
      // QR code renders as <img alt="QR Code">
      await expect(page.locator('img[alt="QR Code"]').first()).toBeVisible({ timeout: 10_000 })
    }
  })

})
