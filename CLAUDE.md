# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install && npm install

# Start dev server (two terminals)
php artisan serve
npm run dev

# Build frontend
npm run build

# Run all migrations (fresh DB)
php artisan migrate:fresh --seed

# Run migrations only
php artisan migrate

# Run all tests
php artisan test

# Code style (Laravel Pint)
./vendor/bin/pint

# Artisan tinker (REPL)
php artisan tinker

# Clear all caches
php artisan optimize:clear
```

## Architecture

### What this is

**LMUC Press Management System** — a pure JSON REST API backed by a Vue 3 SPA. It manages a commercial print shop end-to-end:

- **Quotations** — cost-breakdown estimations (plate/paper/ink/labour/finishing/waste/margin) with PDF download
- **Sales Orders** — convert quotations to jobs, manage payments, download PDF invoices
- **Job Cards** — production workflow (waiting → designing → proof_approval → plate_making → printing → finishing → quality_check → ready → delivered)
- **Production** — machine runs, output/waste tracking, pre-press and finishing task management
- **Production Costing** — actual cost tracking per job (paper, ink, plate, machine hours, labour hours, electricity, outsourcing, waste %) compared against estimated costs from the quotation, with profit/margin analysis
- **Inventory** — products, categories, GRNs, supplier returns, damage reports
- **Purchases / Purchase Orders** — with PDF download
- **Deliveries** — linked to job cards
- **Finance** — accounting journal (double-entry), salary, income/expense
- **Client Portal** — clients log in via `/portal/login` (separate from staff `/login`) to view their own job card status
- **SMS Notifications** — Notify.lk gateway sends SMS on key job status milestones (proof_approval, ready, delivered)

All routes live under `auth:sanctum` middleware (Bearer token). Login/logout at `POST /api/login` and `POST /api/logout`.

---

### Multi-branch scoping

Every resource has a `branch_id`. This pattern appears in every controller index query:

```php
->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
```

`isAdmin()` returns `true` for both `admin` and `owner` roles. All create operations set `branch_id` from `$request->user()->branch_id`. Always call `authorizeBranch()` before accessing a resource by ID.

---

### Roles

| Role | Description |
|---|---|
| `admin` | Full access, all branches |
| `owner` | Full access, all branches |
| `manager` | Branch-scoped management |
| `cashier` | Sales and billing |
| `store_keeper` | Inventory only |
| `sales` | Quotations and sales |
| `estimator` | Quotation costing |
| `designer` | Pre-press and artwork |
| `production_manager` | Production oversight |
| `machine_operator` | Production runs |
| `accountant` | Finance and reports |
| `dispatch_officer` | Deliveries |
| `client` | Portal-only; linked to a `customer_id` |

Two extra boolean flags exist on `User` independent of role: `can_override_gold_rate` and `can_delete_transactions`.

**Client users** have a `customer_id` column linking to the `customers` table. When creating/editing a user with `role = 'client'`, the `customer_id` must be supplied. The client portal route guards prevent `client`-role users from accessing the staff app.

---

### Job Card Lifecycle

```
waiting → designing → proof_approval → plate_making → printing → finishing → quality_check → ready → delivered
```

Status changes via `PATCH /api/job-cards/{id}/status`. When status becomes `proof_approval`, `ready`, or `delivered`, `SmsService::send()` is called silently (fails safe if unconfigured).

Each job card auto-creates:
- A `prepress_tasks` row (status: pending)
- A `finishing_tasks` row with finishing options (cutting/folding/binding/lamination/uv_coating/foiling/die_cutting/packaging)

---

### Production Costing (`job_costing` table)

One `job_costing` record per job card (unique FK). All derived costs are computed server-side in `JobCostingController::upsert()`:

| Input fields | Computed |
|---|---|
| `paper_sheets × paper_rate_per_sheet` | `paper_cost` |
| `ink_colours × ink_cost_per_colour` | `ink_cost` |
| `plate_count × plate_cost_each` | `plate_cost` |
| `machine_hours × machine_rate_per_hour` | `machine_cost` |
| `labour_hours × labour_rate_per_hour` | `labour_cost` |
| `waste_percentage` of materials subtotal | `waste_cost` |
| sum of all | `total_actual_cost` |

Revenue is auto-pulled from the linked sale (`order.total`) or quotation (`quotation.total`). Profit and margin are computed from that.

`GET /api/job-cards/{id}/costing` returns `{ costing, estimated, revenue_source, revenue }` where `estimated` is the quotation's cost breakdown for comparison.

---

### Quotation Cost Fields

Quotations store the estimated breakdown used to generate the quote price:

```
plate_cost, paper_cost, ink_cost, finishing_cost, labour_cost,
wastage_percent, profit_margin_percent
→ calculateTotals() → subtotal, tax, total
```

These become the "Estimated" column in the Production Costing comparison table.

---

### PDF Generation

Uses `barryvdh/laravel-dompdf` v3.0 (already installed). Pattern:

```php
use Barryvdh\DomPDF\Facade\Pdf;
$pdf = Pdf::loadView('pdf.invoice', ['sale' => $sale])->setPaper('A4');
return $pdf->download("Invoice-{$sale->invoice_number}.pdf");
```

Blade views in `resources/views/pdf/`:
- `invoice.blade.php` — sales invoice (DejaVu Sans font, inline CSS, amber header, payment details)
- `purchase_order.blade.php` — purchase order (dark header, T&C section, signature lines)

Frontend downloads via blob:
```js
const { data } = await axios.get(`/api/sales/${id}/pdf`, { responseType: 'blob' })
const url = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
// anchor click to trigger download
```

Routes: `GET /api/sales/{sale}/pdf`, `GET /api/purchases/{purchase}/pdf`

---

### Job Consumables (`job_consumables` table)

Per-job material tracking. Types: `plate`, `ink`, `paper`, `other`.

```
job_card_id, branch_id, type, description, quantity, unit, unit_cost
→ total_cost = quantity × unit_cost (computed on store)
```

Routes:
- `GET  /api/job-cards/{jobCard}/consumables`
- `POST /api/job-cards/{jobCard}/consumables`
- `DELETE /api/job-consumables/{consumable}`

---

### SMS Notifications (`app/Support/SmsService.php`)

Static `send(string $to, string $message): void`. Uses Notify.lk HTTP API. Returns silently if credentials are not configured.

Phone normalisation: strips non-digits, converts `07X` / `947X` → `94XXXXXXXXX`.

Config keys (`.env`):
```
NOTIFY_LK_USER_ID=
NOTIFY_LK_API_KEY=
NOTIFY_LK_SERVICE_ID=
```

---

### The three side-effect services

Every significant mutation should call all three that apply:

| Service | When to call |
|---|---|
| `StockLedger::record()` | Any time `stock_quantity` changes on a `Product` |
| `AccountingService::post*()` | Sales, GRNs, supplier returns, damage reports, salary, income/expense |
| `AuditLog::record()` | Any major state change (sale created/deleted, draft updated, etc.) |

`AccountingService::post()` is **idempotent** — safe to call multiple times.

---

### Accounting Chart of Accounts

| Constant | Code | Account |
|---|---|---|
| `ACC_CASH` | 1000 | Cash |
| `ACC_AR` | 1100 | Accounts Receivable |
| `ACC_INVENTORY` | 1200 | Inventory |
| `ACC_AP` | 2000 | Accounts Payable |
| `ACC_TAX_PAYABLE` | 2100 | Tax Payable |
| `ACC_BOTTLE_DEPOSIT` | 2200 | Bottle Deposit Liability |
| `ACC_SALES` | 4000 | Sales Revenue |
| `ACC_OTHER_INCOME` | 4100 | Other Income |
| `ACC_COGS` | 5000 | Cost of Goods Sold |
| `ACC_DAMAGE_EXP` | 5100 | Damage Expense |
| `ACC_SALARY_EXP` | 5200 | Salary Expense |
| `ACC_OPERATING_EXP` | 5300 | Operating Expense |

Currency throughout is **LKR (Sri Lankan Rupees)**.

---

### Product Image Storage

`ProductController` uploads to Cloudinary via `CloudinaryService::uploadProductImage()`, storing `image` (URL) and `image_public_id`. Always call `CloudinaryService::destroyImage($product->image_public_id)` before uploading a replacement.

```
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
CLOUDINARY_FOLDER=products
CLOUDINARY_VERIFY=true
```

---

### Support classes (`app/Support/`)

- `AccountingService` — static methods for posting double-entry journals
- `StockLedger` — static `record()` helper writing `StockMovement` rows
- `CloudinaryService` — static upload/destroy wrappers around Cloudinary REST API
- `SmsService` — static `send()` wrapper around Notify.lk HTTP API

---

## Frontend SPA (`resources/js/`)

Vue 3 Composition API (`<script setup>`) with vue-router and Axios. All pages in `resources/js/pages/`, shared components in `resources/js/components/`.

### Design system

- **Color**: dark blue sidebar gradient (`#1e3a8a → #172554 → #0f172a`), amber-500 primary action, white cards
- **Sidebar** (`AppLayout.vue`): dark blue gradient background, white SVG grid overlay at 10% opacity, glassmorphism printer icon (`bg-white/10 border border-white/20`), amber-400/60 section headers, `bg-white/20` active nav items
- **Dashboard hero banner**: same dark blue gradient with white grid, shows greeting + user name + date + 3 inline KPI pills
- **KPI cards**: white `rounded-2xl` with colored icon boxes (amber-50/green-50/blue-50/etc.)
- **Forms**: `border border-gray-200 rounded-xl` inputs with `focus:ring-amber-400`
- **Primary button**: `bg-amber-500 hover:bg-amber-600 rounded-xl`
- **CSS utility classes** (scoped in many pages): `.label`, `.input`, `.btn-primary`, `.btn-secondary`, `.table-th`, `.table-td`

### Router (`resources/js/router.js`)

Three route guards:
- `meta: { public: true }` — no auth (job tracker `/track/:number`)
- `meta: { requiresPortal: true }` — client-role token required
- `meta: { requiresAuth: true }` — staff token required; clients are redirected to `/portal`

### Pages

| Page | Route | Notes |
|---|---|---|
| `Dashboard.vue` | `/` | Hero banner, 6 KPI cards, revenue trend chart, production queue, donut charts, recent orders, low stock |
| `Login.vue` | `/login` | Amber-orange gradient left panel, white form right |
| `PortalLogin.vue` | `/portal/login` | Client portal login |
| `PortalDashboard.vue` | `/portal` | Client's own jobs |
| `Customers.vue` | `/customers` | — |
| `Suppliers.vue` | `/suppliers` | — |
| `Quotations.vue` | `/quotations` | List |
| `NewQuotation.vue` | `/quotations/new` | Cost breakdown form → PDF download |
| `QuotationDetail.vue` | `/quotations/:id` | — |
| `Sales.vue` | `/sales` | — |
| `NewSale2.vue` | `/sales/new` | Current POS page |
| `SaleReceipt.vue` | `/sales/:id` | Download Invoice PDF button |
| `JobCards.vue` | `/job-cards` | — |
| `NewJobCard.vue` | `/job-cards/new` | — |
| `JobCardDetail.vue` | `/job-cards/:id` | Status bar, job info, consumables, **production costing**, production history, pre-press, finishing, QR code |
| `Production.vue` | `/production` | — |
| `ProductionAnalytics.vue` | `/production/analytics` | Date filter, KPI cards, machine efficiency table, jobs-by-status, daily CSS bar chart |
| `PrePress.vue` | `/prepress` | — |
| `Finishing.vue` | `/finishing` | — |
| `Machines.vue` | `/machines` | — |
| `Products.vue` | `/products` | Image zoom lightbox via Teleport |
| `Categories.vue` | `/categories` | — |
| `Purchases.vue` | `/purchases` | Download PO PDF button per row |
| `NewPurchase.vue` | `/purchases/new` | — |
| `GRN.vue` | `/grn` | — |
| `SupplierReturns.vue` | `/supplier-returns` | — |
| `Damages.vue` | `/damages` | — |
| `Deliveries.vue` | `/deliveries` | — |
| `NewDelivery.vue` | `/deliveries/new` | — |
| `Reports.vue` | `/reports` | — |
| `Finance.vue` | `/finance` | — |
| `StockLedger.vue` | `/audit-log` | — |
| `OpeningBalance.vue` | `/opening-balance` | — |
| `Users.vue` | `/users` | All 13 roles including `client`; client users show customer name; customer dropdown shown when role = client |
| `PressSettings.vue` | `/settings` | — |

### JobCardDetail.vue sections

1. Status progression bar (clickable pills)
2. Job Details (2-col grid)
3. Instructions (printing / finishing)
4. Materials & Consumables (add/delete line items)
5. **Production Costing** — edit form (paper/ink/plate/machine/labour/electricity/outsource/waste with live reactive totals) + view table (Estimated vs Actual vs Variance + profit badge)
6. Production History (log runs)
7. Right sidebar: Pre-Press status, Finishing task, QR code, linked order/quotation

### Sale lifecycle

Sales have `draft` and `completed` statuses. Draft sales do not decrement stock or post accounting entries. Completed sales trigger all side effects atomically in `DB::transaction`.

### `sale_items` schema addition

`serving_ml decimal(8,2) default 0` — in `SaleItem::$fillable` and `$casts`.

---

## Key API Routes

```
POST   /api/login
POST   /api/logout

GET    /api/job-cards
POST   /api/job-cards
GET    /api/job-cards/{id}
PUT    /api/job-cards/{id}
DELETE /api/job-cards/{id}
PATCH  /api/job-cards/{id}/status

GET    /api/job-cards/{id}/consumables
POST   /api/job-cards/{id}/consumables
DELETE /api/job-consumables/{id}

GET    /api/job-cards/{id}/costing
POST   /api/job-cards/{id}/costing          ← upsert (create or update)

GET    /api/production/analytics
GET    /api/production/dashboard

GET    /api/sales/{sale}/pdf
GET    /api/purchases/{purchase}/pdf

GET    /api/quotations
POST   /api/quotations
GET    /api/quotations/{id}
POST   /api/quotations/{id}/convert         ← convert to sale
```

---

## Database migrations (order / key tables)

Key press-specific tables added in session (all prefixed `2026_06_06_*`):
- `press_machines` — machines with hourly rate
- `quotations` — with full cost breakdown columns
- `job_cards` — with material specs, workflow status, QR code
- `prepress_tasks` — per job
- `finishing_tasks` — per job with boolean finishing options
- `production_jobs` — output/waste per run
- `job_consumables` — material line items (plate/ink/paper/other)
- `job_costing` — actual cost tracking with profit analysis (added `2026_06_06_220000`)
- `deliveries` + `delivery_items`
