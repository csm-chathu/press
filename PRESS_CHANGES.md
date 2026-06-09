# Printing Press ERP — Change Log

Conversion of the existing Restaurant Management System into a full **Printing Press ERP** built on the same Laravel + Vue 3 codebase.

---

## Overview

The system was converted in-place — no rebuild from scratch. All existing infrastructure (auth, Sanctum, accounting, stock ledger, audit log, suppliers, GRN, purchases, finance, employees, Cloudinary image upload) was kept and press-specific modules were layered on top.

**Stack:** Laravel 10 · Vue 3 (Composition API `<script setup>`) · MySQL · Tailwind CSS · Vite · Pinia · vue-router · Axios

---

## 1. Database Migrations (New)

| File | Description |
|---|---|
| `2026_06_06_000001_add_press_fields_to_customers_table.php` | Adds `company_name`, `credit_limit`, `outstanding_balance` to customers |
| `2026_06_06_000002_create_press_machines_table.php` | Creates `press_machines` table (type, model, manufacturer, capacity, status) |
| `2026_06_06_000003_create_quotations_table.php` | Creates `quotations` + `quotation_items` tables with full print specs and cost breakdown |
| `2026_06_06_000004_create_job_cards_table.php` | Creates `job_cards` table with 9-status workflow, machine assignment, QR code |
| `2026_06_06_000005_create_production_jobs_table.php` | Creates `production_jobs` table (machine, operator, output/waste quantity, timing) |
| `2026_06_06_000006_create_prepress_tasks_table.php` | Creates `prepress_tasks` table (artwork upload, proof approval, plate tracking) |
| `2026_06_06_000007_create_finishing_tasks_table.php` | Creates `finishing_tasks` table (8 boolean finishing operations + status) |
| `2026_06_06_000008_create_delivery_notes_table.php` | Creates `delivery_notes` + `delivery_items` tables |
| `2026_06_06_000009_add_press_fields_to_sales_table.php` | Adds `quotation_id`, `order_type`, `artwork_status`, `advance_payment`, `delivery_date`, `order_status` to sales |
| `2026_06_06_000010_add_press_fields_to_products_table.php` | Adds `material_type`, `gsm`, `paper_size`, `reorder_level` to products |

**Fix applied:** Migration 10 originally used `->after('type')` — corrected to `->after('product_type')` (actual column name).

---

## 2. New Laravel Models (`app/Models/`)

| Model | Key Features |
|---|---|
| `PressMachine.php` | `belongsTo Branch`, `hasMany JobCards/ProductionJobs`; static `types()` array |
| `Quotation.php` | `calculateTotals()` — baseCost → +wastage% → +profit% → +tax%; `generateNumber()` → QT-YYYYMMDD-NNNN |
| `QuotationItem.php` | `belongsTo Quotation` |
| `JobCard.php` | 9-status workflow array; `generateNumber()` → JC-YYYYMMDD-NNNN; `isOverdue()`; relations to all production models |
| `ProductionJob.php` | `getDurationMinutesAttribute()` computed from start/end times |
| `PrepressTask.php` | Artwork upload, proof approval, plate tracking with timestamps |
| `FinishingTask.php` | 8 boolean op columns; `getOperationsAttribute()` returns active op names |
| `DeliveryNote.php` | `generateNumber()` → DN-YYYYMMDD-NNNN |
| `DeliveryItem.php` | `belongsTo DeliveryNote` |

---

## 3. Updated Laravel Models

### `app/Models/User.php`
- Added 8 new press roles to `ROLES` constant:
  `sales`, `estimator`, `designer`, `production_manager`, `machine_operator`, `store_keeper`, `accountant`, `dispatch_officer`
- Legacy roles kept: `manager`, `cashier`
- New helper methods: `isDesigner()`, `isOperator()`, `isDispatch()`, `isSales()`, `isEstimator()`, `canCreateQuotations()`, `canManageProduction()`

---

## 4. New API Controllers (`app/Http/Controllers/Api/`)

| Controller | Endpoints |
|---|---|
| `QuotationController` | Full CRUD + `POST /quotations/{id}/convert` (creates Sale, marks quotation converted) |
| `JobCardController` | Full CRUD + `PATCH /job-cards/{id}/status` + `GET /job-cards/queue` (priority-ordered) |
| `ProductionController` | index, store, update + `GET /production/dashboard` |
| `PressMachineController` | Full CRUD + `GET /press-machines/all` (active only, for dropdowns) |
| `DeliveryController` | index, store, show, update (no destroy); auto-updates JobCard to `delivered` |
| `PrepressTaskController` | index, show, update; auto-timestamps on status transitions |
| `FinishingTaskController` | index, show, update; sets `completed_at` on completion |
| `PressSettingController` | `GET/POST /press-settings` (stored in Laravel cache) |

All controllers implement:
- `authorizeBranch()` — branch isolation for non-admin users
- `AuditLog::record()` — audit trail on all mutations

### Updated: `DashboardController`
Completely rewritten with press KPIs:
- `pending_quotes`, `active_jobs`, `waiting_jobs`, `ready_for_dispatch`
- `customer_outstanding`, `low_stock_count` (uses `reorder_level`)
- `production_status` breakdown, `machine_utilization`, `upcoming_jobs`

---

## 5. API Routes (`routes/api.php`)

Added imports and route groups for all new controllers:

```
GET  /press-machines/all
GET|POST|PUT|DELETE  /press-machines/{id}

POST /quotations/{id}/convert
GET|POST|PUT|DELETE  /quotations/{id}

GET  /job-cards/queue
PATCH /job-cards/{id}/status
GET|POST|PUT|DELETE  /job-cards/{id}

GET  /production/dashboard
GET|POST  /production-jobs
PUT  /production-jobs/{id}

GET|POST  /deliveries
GET|PUT   /deliveries/{id}

GET|PUT   /prepress-tasks
GET|PUT   /prepress-tasks/{id}

GET|PUT   /finishing-tasks
GET|PUT   /finishing-tasks/{id}

GET|POST  /press-settings

GET  /reports/job-profitability
GET  /reports/material-consumption
GET  /reports/waste-report
GET  /reports/machine-utilization
GET  /reports/customer-outstanding
GET  /reports/production-status
```

---

## 6. Web Routes (`routes/web.php`)

Added explicit root route to fix SPA catch-all not matching `/`:

```php
Route::get('/', fn () => view('app'));
Route::get('/{any}', fn () => view('app'))->where('any', '.*');
```

---

## 7. Updated Vue — Layout & Router

### `resources/js/layouts/AppLayout.vue`
- Logo updated: 🖨️ "Press Management System" with amber-600 accent
- Sidebar navigation restructured:
  - **Main:** Dashboard, Customers, Quotations, Sales Orders, Suppliers, Purchases, Materials/Stock, Deliveries, Reports
  - **Production** (role-gated): Job Cards, Production Queue, Pre-Press, Finishing, Machines
  - **Admin:** Categories, GRN, Supplier Returns, Damages, Finance, Stock Ledger, Users & Roles, Press Settings
- Role label display updated to show press role names
- Removed cashier shift modal (restaurant-specific)
- `canSeeProduction` computed for production section visibility

### `resources/js/router.js`
Added routes:
```
/quotations          → Quotations.vue
/quotations/new      → NewQuotation.vue
/quotations/:id      → QuotationDetail.vue
/job-cards           → JobCards.vue
/job-cards/new       → NewJobCard.vue
/job-cards/:id       → JobCardDetail.vue
/production          → Production.vue
/prepress            → PrePress.vue
/finishing           → Finishing.vue
/machines            → Machines.vue
/deliveries          → Deliveries.vue
/deliveries/new      → NewDelivery.vue
/settings            → PressSettings.vue
```

Removed restaurant-specific routes: `/tables`, `/open-bottles`, `/price-matrix`, `/bottle-deposits`, `/gold-rates`, `/scrap-management`

---

## 8. New Vue Pages (`resources/js/pages/`)

| Page | Description |
|---|---|
| `Quotations.vue` | List with search + status filter, convert-to-order action, pagination |
| `NewQuotation.vue` | Full form with reactive cost calculator (live subtotal/total), print specs, finishing checklist |
| `QuotationDetail.vue` | Read-only detail view, convert button, linked order display |
| `JobCards.vue` | List with inline status `<select>` for quick updates, overdue row highlighting |
| `NewJobCard.vue` | Form with 8-op finishing checklist, operator + machine assignment |
| `JobCardDetail.vue` | Status progression bar, production history log, pre-press summary, QR code display |
| `Production.vue` | Queue table with machine filter, KPI cards, machine utilization panel |
| `Machines.vue` | Card grid with add/edit modal |
| `PrePress.vue` | Task list table with update modal (status, plate status, revision count) |
| `Finishing.vue` | Task list with inline Start / Mark Done actions |
| `Deliveries.vue` | Delivery list with status filter, Dispatch / Confirm Delivery inline actions |
| `NewDelivery.vue` | Create delivery note form with customer/job-card selectors + line items |
| `PressSettings.vue` | Company info, quotation defaults (wastage/profit/tax %), production settings |

### Updated: `Dashboard.vue`
- KPI cards updated to press context (Pending Quotes, Active Jobs, Ready for Dispatch, Low Stock)
- Quick actions: New Quotation, New Job Card, New Order
- "Fast Moving Items" replaced with "Production Queue" status breakdown
- Added `jobStatusLabel()` / `jobStatusColor()` helpers

### Updated: `Users.vue`
- `roleOptions` now lists all 10 press roles with correct badge colors
- Default new-user role changed from `cashier` → `sales`
- Role badge colors added for all press roles

---

## 9. Seeder (`database/seeders/PrintingPressSeeder.php`)

Sample data created:

| Entity | Count | Details |
|---|---|---|
| Branch | 1 | LMUC Press — Head Office, Colombo |
| Users | 9 | Admin + 8 press-role staff (all password: `password`) |
| Categories | 7 | Paper & Board, Inks & Chemicals, Plates & Films, Packaging Materials, Printing Services, Finishing Services, Consumables |
| Press Machines | 7 | Heidelberg SM 52, Roland 700, HP Indigo 12000, Polar 115, Stahlfolder, Autobond Laminator, Muller Martini Binder |
| Products/Materials | 15 | Papers (80–300gsm), inks, UV varnish, CTP plates, packaging, consumables |
| Suppliers | 5 | Ceylon Paper Mills, Sun Chemical Lanka, Agfa Materials, Express Packaging, Indo-Lanka Supplies |
| Customers | 8 | Dialog Axiata, Hemas Holdings, Lanka Hospitals, Keells, Nestlé, individual, CFW Events, University of Colombo |
| Quotations | 1 | Annual Report 2025 — QT-20260606-0001, status: sent |
| Job Cards | 4 | Business Cards (printing), A5 Brochure (proof_approval), Banner (ready), Exam Booklets (waiting) |

**Fixes applied during seeding:**
- `branches` table has no `phone` column → removed, used `city`/`country` instead
- `categories` table has no `color` column → removed; `slug` generated from name
- `products` → `'type'` key renamed to `'product_type'`
- `suppliers` table has no `contact_person` column → renamed to `company`

Registered in `database/seeders/DatabaseSeeder.php`:
```php
$this->call(PrintingPressSeeder::class);
```

---

## 10. Config Changes

### `config/tenants.php`
Added `127.0.0.1` and updated `localhost` to point to `press` database:
```php
'localhost'  => 'press',
'127.0.0.1' => 'press',
```

This was the root cause of the 404 on `http://127.0.0.1:8000` — the `ResolveTenantDatabase` middleware aborted all requests from unlisted hosts.

---

## 11. Bug Fixes Applied During Implementation

| Issue | Fix |
|---|---|
| `PressSettingController::authorize()` was `private` — conflicts with parent `Controller::authorize()` which must be `public` | Renamed to `requireAdmin()` |
| `server.php` missing from project root | Created standard Laravel router script; `php artisan serve` needs this file to route requests through `public/index.php` |
| Migration `add_press_fields_to_products` used `->after('type')` — column doesn't exist | Corrected to `->after('product_type')` |
| Root URL `/` returning 404 even with catch-all route `/{any}` | Laravel's `/{any}` requires at least one character; added explicit `Route::get('/')` |
| All requests to `127.0.0.1:8000` returning 404 | `ResolveTenantDatabase` middleware aborted unlisted hosts; added `'127.0.0.1' => 'press'` to `config/tenants.php` |

---

## 12. Default Login Credentials

| Email | Password | Role |
|---|---|---|
| `admin@lmucpress.lk` | `password` | Admin |
| `sales@lmucpress.lk` | `password` | Sales |
| `estimator@lmucpress.lk` | `password` | Estimator |
| `designer@lmucpress.lk` | `password` | Designer |
| `prodmgr@lmucpress.lk` | `password` | Production Manager |
| `operator@lmucpress.lk` | `password` | Machine Operator |
| `store@lmucpress.lk` | `password` | Store Keeper |
| `accounts@lmucpress.lk` | `password` | Accountant |
| `dispatch@lmucpress.lk` | `password` | Dispatch Officer |

---

## 13. How to Run

```bash
# Install dependencies (if not done)
composer install
npm install

# Run all migrations
php artisan migrate

# Seed press data
php artisan db:seed --class=PrintingPressSeeder

# Clear all caches
php artisan optimize:clear

# Start backend (Terminal 1)
php artisan serve

# Start frontend dev server (Terminal 2) — optional for live reload
npm run dev

# Or build for production
npm run build
del public\hot
```

Access: **http://127.0.0.1:8000**

---

## 14. Job Card Status Workflow

```
waiting → designing → proof_approval → plate_making → printing → finishing → quality_check → ready → delivered
```

## 15. Quotation Cost Formula

```
Base Cost  = plate_cost + paper_cost + ink_cost + finishing_cost + labour_cost
After Wastage  = base_cost × (1 + wastage_percent / 100)
After Profit   = after_wastage × (1 + profit_margin_percent / 100)
Total (inc tax) = after_profit × (1 + tax_rate / 100)
Unit Price     = total / quantity
```

---

*Generated: 2026-06-06 | Currency: LKR (Sri Lankan Rupees)*
