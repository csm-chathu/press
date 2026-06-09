# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: 09-inventory.spec.js >> Inventory — Categories >> can create a category
- Location: tests\e2e\09-inventory.spec.js:62:3

# Error details

```
Test timeout of 30000ms exceeded.
```

```
Error: page.fill: Test timeout of 30000ms exceeded.
Call log:
  - waiting for locator('input[name="name"], input[placeholder*="Name"]')

```

# Page snapshot

```yaml
- generic [ref=e3]:
  - complementary [ref=e4]:
    - generic:
      - img
    - generic [ref=e5]:
      - img [ref=e7]
      - generic [ref=e9]:
        - paragraph [ref=e10]: LMUC Press — Head Office
        - paragraph [ref=e11]: Press Management System
    - navigation [ref=e12]:
      - generic [ref=e13]: Main
      - link "Dashboard" [ref=e14] [cursor=pointer]:
        - /url: /
        - img [ref=e15]
        - generic [ref=e17]: Dashboard
      - link "Customers" [ref=e18] [cursor=pointer]:
        - /url: /customers
        - img [ref=e19]
        - generic [ref=e21]: Customers
      - link "Quotations" [ref=e22] [cursor=pointer]:
        - /url: /quotations
        - img [ref=e23]
        - generic [ref=e25]: Quotations
      - link "Sales Orders" [ref=e26] [cursor=pointer]:
        - /url: /sales
        - img [ref=e27]
        - generic [ref=e29]: Sales Orders
      - link "Suppliers" [ref=e30] [cursor=pointer]:
        - /url: /suppliers
        - img [ref=e31]
        - generic [ref=e33]: Suppliers
      - link "Purchases" [ref=e34] [cursor=pointer]:
        - /url: /purchases
        - img [ref=e35]
        - generic [ref=e37]: Purchases
      - link "Materials / Stock" [ref=e38] [cursor=pointer]:
        - /url: /products
        - img [ref=e39]
        - generic [ref=e41]: Materials / Stock
      - link "Deliveries" [ref=e42] [cursor=pointer]:
        - /url: /deliveries
        - img [ref=e43]
        - generic [ref=e45]: Deliveries
      - link "Reports" [ref=e46] [cursor=pointer]:
        - /url: /reports
        - img [ref=e47]
        - generic [ref=e49]: Reports
      - generic [ref=e50]: Production
      - link "Job Cards" [ref=e51] [cursor=pointer]:
        - /url: /job-cards
        - img [ref=e52]
        - generic [ref=e54]: Job Cards
      - link "Schedule" [ref=e55] [cursor=pointer]:
        - /url: /schedule
        - img [ref=e56]
        - generic [ref=e58]: Schedule
      - link "Production Queue" [ref=e59] [cursor=pointer]:
        - /url: /production
        - img [ref=e60]
        - generic [ref=e62]: Production Queue
      - link "Pre-Press" [ref=e63] [cursor=pointer]:
        - /url: /prepress
        - img [ref=e64]
        - generic [ref=e66]: Pre-Press
      - link "Finishing" [ref=e67] [cursor=pointer]:
        - /url: /finishing
        - img [ref=e68]
        - generic [ref=e70]: Finishing
      - link "Machines" [ref=e71] [cursor=pointer]:
        - /url: /machines
        - img [ref=e72]
        - generic [ref=e74]: Machines
      - generic [ref=e75]: Admin
      - link "Getting Started" [ref=e76] [cursor=pointer]:
        - /url: /getting-started
        - img [ref=e77]
        - generic [ref=e79]: Getting Started
      - link "Categories" [ref=e80] [cursor=pointer]:
        - /url: /categories
        - img [ref=e81]
        - generic [ref=e84]: Categories
      - link "GRN" [ref=e85] [cursor=pointer]:
        - /url: /grn
        - img [ref=e86]
        - generic [ref=e88]: GRN
      - link "Supplier Returns" [ref=e89] [cursor=pointer]:
        - /url: /supplier-returns
        - img [ref=e90]
        - generic [ref=e92]: Supplier Returns
      - link "Damages / Waste" [ref=e93] [cursor=pointer]:
        - /url: /damages
        - img [ref=e94]
        - generic [ref=e96]: Damages / Waste
      - link "Finance" [ref=e97] [cursor=pointer]:
        - /url: /finance
        - img [ref=e98]
        - generic [ref=e100]: Finance
      - link "Stock Ledger" [ref=e101] [cursor=pointer]:
        - /url: /audit-log
        - img [ref=e102]
        - generic [ref=e104]: Stock Ledger
      - link "Users & Roles" [ref=e105] [cursor=pointer]:
        - /url: /users
        - img [ref=e106]
        - generic [ref=e108]: Users & Roles
      - link "Press Settings" [ref=e109] [cursor=pointer]:
        - /url: /settings
        - img [ref=e110]
        - generic [ref=e113]: Press Settings
    - generic [ref=e114]:
      - button "Collapse" [ref=e115] [cursor=pointer]:
        - img [ref=e116]
        - generic [ref=e118]: Collapse
      - generic [ref=e119]:
        - generic [ref=e120]: A
        - generic [ref=e121]:
          - paragraph [ref=e122]: Admin
          - paragraph [ref=e123]: Admin
        - button "Logout" [ref=e124] [cursor=pointer]:
          - img [ref=e125]
  - generic [ref=e127]:
    - banner [ref=e128]:
      - heading "Material Categories" [level=1] [ref=e130]
      - generic [ref=e131]:
        - button "Full Screen" [ref=e132] [cursor=pointer]:
          - img [ref=e133]
          - text: Full Screen
        - generic [ref=e135]: Monday, June 8, 2026
    - main [ref=e136]:
      - generic [ref=e137]:
        - button "Add Category" [active] [ref=e139] [cursor=pointer]:
          - img [ref=e140]
          - text: Add Category
        - table [ref=e143]:
          - rowgroup [ref=e144]:
            - row "Name Slug Products Variants Status Actions" [ref=e145]:
              - columnheader "Name" [ref=e146]
              - columnheader "Slug" [ref=e147]
              - columnheader "Products" [ref=e148]
              - columnheader "Variants" [ref=e149]
              - columnheader "Status" [ref=e150]
              - columnheader "Actions" [ref=e151]
          - rowgroup [ref=e152]:
            - row "Liquor liquor 2 — Active Edit Delete" [ref=e153]:
              - cell "Liquor" [ref=e154]
              - cell "liquor" [ref=e155]
              - cell "2" [ref=e156]
              - cell "—" [ref=e157]
              - cell "Active" [ref=e158]:
                - generic [ref=e159]: Active
              - cell "Edit Delete" [ref=e160]:
                - generic [ref=e161]:
                  - button "Edit" [ref=e162] [cursor=pointer]:
                    - img [ref=e163]
                    - text: Edit
                  - button "Delete" [ref=e165] [cursor=pointer]:
                    - img [ref=e166]
                    - text: Delete
            - row "Beer beer 2 — Active Edit Delete" [ref=e168]:
              - cell "Beer" [ref=e169]
              - cell "beer" [ref=e170]
              - cell "2" [ref=e171]
              - cell "—" [ref=e172]
              - cell "Active" [ref=e173]:
                - generic [ref=e174]: Active
              - cell "Edit Delete" [ref=e175]:
                - generic [ref=e176]:
                  - button "Edit" [ref=e177] [cursor=pointer]:
                    - img [ref=e178]
                    - text: Edit
                  - button "Delete" [ref=e180] [cursor=pointer]:
                    - img [ref=e181]
                    - text: Delete
            - row "Soft Drinks soft-drinks 2 — Active Edit Delete" [ref=e183]:
              - cell "Soft Drinks" [ref=e184]
              - cell "soft-drinks" [ref=e185]
              - cell "2" [ref=e186]
              - cell "—" [ref=e187]
              - cell "Active" [ref=e188]:
                - generic [ref=e189]: Active
              - cell "Edit Delete" [ref=e190]:
                - generic [ref=e191]:
                  - button "Edit" [ref=e192] [cursor=pointer]:
                    - img [ref=e193]
                    - text: Edit
                  - button "Delete" [ref=e195] [cursor=pointer]:
                    - img [ref=e196]
                    - text: Delete
            - row "Food food 2 — Active Edit Delete" [ref=e198]:
              - cell "Food" [ref=e199]
              - cell "food" [ref=e200]
              - cell "2" [ref=e201]
              - cell "—" [ref=e202]
              - cell "Active" [ref=e203]:
                - generic [ref=e204]: Active
              - cell "Edit Delete" [ref=e205]:
                - generic [ref=e206]:
                  - button "Edit" [ref=e207] [cursor=pointer]:
                    - img [ref=e208]
                    - text: Edit
                  - button "Delete" [ref=e210] [cursor=pointer]:
                    - img [ref=e211]
                    - text: Delete
            - row "Snacks snacks 1 — Active Edit Delete" [ref=e213]:
              - cell "Snacks" [ref=e214]
              - cell "snacks" [ref=e215]
              - cell "1" [ref=e216]
              - cell "—" [ref=e217]
              - cell "Active" [ref=e218]:
                - generic [ref=e219]: Active
              - cell "Edit Delete" [ref=e220]:
                - generic [ref=e221]:
                  - button "Edit" [ref=e222] [cursor=pointer]:
                    - img [ref=e223]
                    - text: Edit
                  - button "Delete" [ref=e225] [cursor=pointer]:
                    - img [ref=e226]
                    - text: Delete
            - row "Accessories accessories 1 — Active Edit Delete" [ref=e228]:
              - cell "Accessories" [ref=e229]
              - cell "accessories" [ref=e230]
              - cell "1" [ref=e231]
              - cell "—" [ref=e232]
              - cell "Active" [ref=e233]:
                - generic [ref=e234]: Active
              - cell "Edit Delete" [ref=e235]:
                - generic [ref=e236]:
                  - button "Edit" [ref=e237] [cursor=pointer]:
                    - img [ref=e238]
                    - text: Edit
                  - button "Delete" [ref=e240] [cursor=pointer]:
                    - img [ref=e241]
                    - text: Delete
            - row "Paper & Board paper-board 5 — Active Edit Delete" [ref=e243]:
              - cell "Paper & Board" [ref=e244]
              - cell "paper-board" [ref=e245]
              - cell "5" [ref=e246]
              - cell "—" [ref=e247]
              - cell "Active" [ref=e248]:
                - generic [ref=e249]: Active
              - cell "Edit Delete" [ref=e250]:
                - generic [ref=e251]:
                  - button "Edit" [ref=e252] [cursor=pointer]:
                    - img [ref=e253]
                    - text: Edit
                  - button "Delete" [ref=e255] [cursor=pointer]:
                    - img [ref=e256]
                    - text: Delete
            - row "Inks & Chemicals inks-chemicals 4 — Active Edit Delete" [ref=e258]:
              - cell "Inks & Chemicals" [ref=e259]
              - cell "inks-chemicals" [ref=e260]
              - cell "4" [ref=e261]
              - cell "—" [ref=e262]
              - cell "Active" [ref=e263]:
                - generic [ref=e264]: Active
              - cell "Edit Delete" [ref=e265]:
                - generic [ref=e266]:
                  - button "Edit" [ref=e267] [cursor=pointer]:
                    - img [ref=e268]
                    - text: Edit
                  - button "Delete" [ref=e270] [cursor=pointer]:
                    - img [ref=e271]
                    - text: Delete
            - row "Plates & Films plates-films 2 — Active Edit Delete" [ref=e273]:
              - cell "Plates & Films" [ref=e274]
              - cell "plates-films" [ref=e275]
              - cell "2" [ref=e276]
              - cell "—" [ref=e277]
              - cell "Active" [ref=e278]:
                - generic [ref=e279]: Active
              - cell "Edit Delete" [ref=e280]:
                - generic [ref=e281]:
                  - button "Edit" [ref=e282] [cursor=pointer]:
                    - img [ref=e283]
                    - text: Edit
                  - button "Delete" [ref=e285] [cursor=pointer]:
                    - img [ref=e286]
                    - text: Delete
            - row "Packaging Materials packaging-materials 2 — Active Edit Delete" [ref=e288]:
              - cell "Packaging Materials" [ref=e289]
              - cell "packaging-materials" [ref=e290]
              - cell "2" [ref=e291]
              - cell "—" [ref=e292]
              - cell "Active" [ref=e293]:
                - generic [ref=e294]: Active
              - cell "Edit Delete" [ref=e295]:
                - generic [ref=e296]:
                  - button "Edit" [ref=e297] [cursor=pointer]:
                    - img [ref=e298]
                    - text: Edit
                  - button "Delete" [ref=e300] [cursor=pointer]:
                    - img [ref=e301]
                    - text: Delete
            - row "Printing Services printing-services 0 — Active Edit Delete" [ref=e303]:
              - cell "Printing Services" [ref=e304]
              - cell "printing-services" [ref=e305]
              - cell "0" [ref=e306]
              - cell "—" [ref=e307]
              - cell "Active" [ref=e308]:
                - generic [ref=e309]: Active
              - cell "Edit Delete" [ref=e310]:
                - generic [ref=e311]:
                  - button "Edit" [ref=e312] [cursor=pointer]:
                    - img [ref=e313]
                    - text: Edit
                  - button "Delete" [ref=e315] [cursor=pointer]:
                    - img [ref=e316]
                    - text: Delete
            - row "Finishing Services finishing-services 0 — Active Edit Delete" [ref=e318]:
              - cell "Finishing Services" [ref=e319]
              - cell "finishing-services" [ref=e320]
              - cell "0" [ref=e321]
              - cell "—" [ref=e322]
              - cell "Active" [ref=e323]:
                - generic [ref=e324]: Active
              - cell "Edit Delete" [ref=e325]:
                - generic [ref=e326]:
                  - button "Edit" [ref=e327] [cursor=pointer]:
                    - img [ref=e328]
                    - text: Edit
                  - button "Delete" [ref=e330] [cursor=pointer]:
                    - img [ref=e331]
                    - text: Delete
            - row "Consumables consumables 2 — Active Edit Delete" [ref=e333]:
              - cell "Consumables" [ref=e334]
              - cell "consumables" [ref=e335]
              - cell "2" [ref=e336]
              - cell "—" [ref=e337]
              - cell "Active" [ref=e338]:
                - generic [ref=e339]: Active
              - cell "Edit Delete" [ref=e340]:
                - generic [ref=e341]:
                  - button "Edit" [ref=e342] [cursor=pointer]:
                    - img [ref=e343]
                    - text: Edit
                  - button "Delete" [ref=e345] [cursor=pointer]:
                    - img [ref=e346]
                    - text: Delete
        - generic [ref=e349]:
          - heading "Add Category" [level=3] [ref=e350]
          - generic [ref=e351]:
            - generic [ref=e352]:
              - generic [ref=e353]: Name *
              - textbox [ref=e354]
            - generic [ref=e355]:
              - generic [ref=e356]: Description
              - textbox [ref=e357]
            - generic [ref=e358]:
              - checkbox "Active" [checked] [ref=e359]
              - text: Active
            - generic [ref=e360]:
              - checkbox "Enable shot/serving variants for products in this category" [ref=e361]
              - text: Enable shot/serving variants for products in this category
          - generic [ref=e362]:
            - button "Cancel" [ref=e363] [cursor=pointer]
            - button "Save" [ref=e364] [cursor=pointer]
```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test'
  2   | import { loginAs, STAFF, navTo } from './helpers/auth.js'
  3   | 
  4   | test.describe('Inventory — Products', () => {
  5   | 
  6   |   test.beforeEach(async ({ page }) => {
  7   |     await loginAs(page, STAFF.admin)
  8   |     await navTo(page, 'Products')
  9   |   })
  10  | 
  11  |   test('product list loads', async ({ page }) => {
  12  |     await page.waitForLoadState('load')
  13  |     await expect(page.locator('table tbody tr, [class*="product-card"]').first()).toBeVisible({ timeout: 10_000 })
  14  |   })
  15  | 
  16  |   test('product list has search', async ({ page }) => {
  17  |     const search = page.locator('input[placeholder*="Search"], input[type="search"]').first()
  18  |     await search.fill('Paper')
  19  |     await page.waitForLoadState('load')
  20  |     // Results filter
  21  |     await expect(page.locator('table tbody tr, [class*="card"]').first()).toBeVisible()
  22  |   })
  23  | 
  24  |   test('can create a product', async ({ page }) => {
  25  |     await page.click('button:has-text("Add"), button:has-text("New Product")')
  26  |     const stamp = Date.now()
  27  |     await page.fill('input[name="name"], input[placeholder*="Name"]', `E2E Product ${stamp}`)
  28  | 
  29  |     const price = page.locator('input[name="price"], input[placeholder*="Price"], input[name="selling_price"]').first()
  30  |     if (await price.isVisible({ timeout: 2_000 }).catch(() => false)) {
  31  |       await price.fill('150')
  32  |     }
  33  | 
  34  |     await page.click('button[type="submit"]:has-text("Save"), button[type="submit"]:has-text("Create")')
  35  |     await page.waitForLoadState('load')
  36  |     await expect(page.locator(`text=E2E Product ${stamp}`).first()).toBeVisible({ timeout: 8_000 })
  37  |   })
  38  | 
  39  |   test('product image zoom lightbox works', async ({ page }) => {
  40  |     await page.waitForLoadState('load')
  41  |     const productImg = page.locator('table tbody img, [class*="product"] img').first()
  42  |     if (await productImg.isVisible({ timeout: 3_000 }).catch(() => false)) {
  43  |       await productImg.click()
  44  |       await expect(page.locator('[class*="lightbox"], [class*="modal"], [class*="overlay"]').first()).toBeVisible({ timeout: 5_000 })
  45  |     }
  46  |   })
  47  | 
  48  | })
  49  | 
  50  | test.describe('Inventory — Categories', () => {
  51  | 
  52  |   test.beforeEach(async ({ page }) => {
  53  |     await loginAs(page, STAFF.admin)
  54  |     await navTo(page, 'Categories')
  55  |   })
  56  | 
  57  |   test('categories list loads', async ({ page }) => {
  58  |     await page.waitForLoadState('load')
  59  |     await expect(page.locator('table tbody tr, [class*="category"]').first()).toBeVisible({ timeout: 10_000 })
  60  |   })
  61  | 
  62  |   test('can create a category', async ({ page }) => {
  63  |     await page.click('button:has-text("Add"), button:has-text("New")')
  64  |     const stamp = Date.now()
> 65  |     await page.fill('input[name="name"], input[placeholder*="Name"]', `E2E Category ${stamp}`)
      |                ^ Error: page.fill: Test timeout of 30000ms exceeded.
  66  |     await page.click('button[type="submit"]:has-text("Save"), button[type="submit"]:has-text("Create")')
  67  |     await page.waitForLoadState('load')
  68  |     await expect(page.locator(`text=E2E Category ${stamp}`).first()).toBeVisible({ timeout: 8_000 })
  69  |   })
  70  | 
  71  | })
  72  | 
  73  | test.describe('Inventory — GRN', () => {
  74  | 
  75  |   test.beforeEach(async ({ page }) => {
  76  |     await loginAs(page, STAFF.admin)
  77  |     await navTo(page, 'GRN')
  78  |   })
  79  | 
  80  |   test('GRN list loads', async ({ page }) => {
  81  |     await page.waitForLoadState('load')
  82  |     await expect(page.locator('table, [class*="grn"]').first()).toBeVisible({ timeout: 10_000 })
  83  |   })
  84  | 
  85  | })
  86  | 
  87  | test.describe('Inventory — Purchases', () => {
  88  | 
  89  |   test.beforeEach(async ({ page }) => {
  90  |     await loginAs(page, STAFF.admin)
  91  |     await navTo(page, 'Purchases')
  92  |   })
  93  | 
  94  |   test('purchases list loads', async ({ page }) => {
  95  |     await page.waitForLoadState('load')
  96  |     await expect(page.locator('table, [class*="purchase"]').first()).toBeVisible({ timeout: 10_000 })
  97  |   })
  98  | 
  99  |   test('can download purchase order PDF', async ({ page }) => {
  100 |     await page.waitForLoadState('load')
  101 |     const pdfBtn = page.locator('button:has-text("PDF"), a:has-text("PDF"), button:has-text("Download")').first()
  102 |     if (await pdfBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
  103 |       const [download] = await Promise.all([
  104 |         page.waitForEvent('download', { timeout: 15_000 }),
  105 |         pdfBtn.click(),
  106 |       ])
  107 |       expect(download.suggestedFilename()).toMatch(/\.pdf$/i)
  108 |     }
  109 |   })
  110 | 
  111 | })
  112 | 
  113 | test.describe('Inventory — Damages & Returns', () => {
  114 | 
  115 |   test.beforeEach(async ({ page }) => {
  116 |     await loginAs(page, STAFF.admin)
  117 |   })
  118 | 
  119 |   test('damages page loads', async ({ page }) => {
  120 |     await navTo(page, 'Damages')
  121 |     await page.waitForLoadState('load')
  122 |     await expect(page.locator('table, [class*="damage"]').first()).toBeVisible({ timeout: 10_000 })
  123 |   })
  124 | 
  125 |   test('supplier returns page loads', async ({ page }) => {
  126 |     await navTo(page, 'Supplier Returns')
  127 |     await page.waitForLoadState('load')
  128 |     await expect(page.locator('table, [class*="return"]').first()).toBeVisible({ timeout: 10_000 })
  129 |   })
  130 | 
  131 | })
  132 | 
```