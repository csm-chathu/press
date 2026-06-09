<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\BottleDepositController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DamageReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\FinishingTaskController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\GoldBuybackController;
use App\Http\Controllers\Api\GoldRateController;
use App\Http\Controllers\Api\GrnController;
use App\Http\Controllers\Api\JobCardController;
use App\Http\Controllers\Api\OpenBottleController;
use App\Http\Controllers\Api\PressMachineController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PrepressTaskController;
use App\Http\Controllers\Api\PressSettingController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\RestaurantSettingController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ScrapItemController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupplierReturnController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\TaxSettingController;
use App\Http\Controllers\Api\CashierShiftController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Tenant management — protected by X-Tenant-Key header (see TENANT_MASTER_KEY in .env)
Route::post('/tenants', [\App\Http\Controllers\Api\TenantController::class, 'create']);

// Public job tracker — no auth required (for QR code scanning)
Route::get('/public/track/{jobNumber}', function (string $jobNumber) {
    $job = \App\Models\JobCard::where('job_number', $jobNumber)
        ->with('machine:id,name')
        ->first(['id', 'job_number', 'title', 'status', 'quantity_ordered', 'due_date', 'machine_id', 'customer_id']);

    if (!$job) {
        return response()->json(['message' => 'Job not found'], 404);
    }

    return response()->json([
        'job_number'       => $job->job_number,
        'title'            => $job->title,
        'status'           => $job->status,
        'status_label'     => \App\Models\JobCard::$statuses[$job->status] ?? $job->status,
        'quantity_ordered' => $job->quantity_ordered,
        'due_date'         => $job->due_date,
        'machine'          => $job->machine?->name,
    ]);
});

// Public — no auth required (used by login page)
Route::get('/public/settings', function () {
    $branch = \App\Models\Branch::first();
    return response()->json([
        'name'     => $branch?->name ?? config('app.name'),
        'logo_url' => $branch?->logo_url,
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user()->load('branch:id,name,code'));
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Opening balances
    Route::get('/opening-balance/stock',      [\App\Http\Controllers\Api\OpeningBalanceController::class, 'stockIndex']);
    Route::post('/opening-balance/stock',     [\App\Http\Controllers\Api\OpeningBalanceController::class, 'stockSave']);
    Route::get('/opening-balance/accounts',   [\App\Http\Controllers\Api\OpeningBalanceController::class, 'accountIndex']);
    Route::post('/opening-balance/accounts',  [\App\Http\Controllers\Api\OpeningBalanceController::class, 'accountSave']);

    // Reference selects (no pagination)
    Route::get('/categories/all',  [CategoryController::class, 'all']);
    Route::get('/suppliers/all',   [SupplierController::class, 'all']);
    Route::get('/customers/all',   [CustomerController::class, 'all']);
    Route::get('/tables/all',      [TableController::class, 'all']);

    // CRUD resources
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('suppliers',  SupplierController::class);
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('customers',  CustomerController::class);
    Route::apiResource('tables',     TableController::class);
    Route::get('/cashier-shifts/current',          [CashierShiftController::class, 'current']);
    Route::get('/cashier-shifts/report',           [CashierShiftController::class, 'report']);
    Route::get('/cashier-shifts/suggested-opening',[CashierShiftController::class, 'suggestedOpening']);
    Route::post('/cashier-shifts/open',            [CashierShiftController::class, 'open']);
    Route::post('/cashier-shifts/close',           [CashierShiftController::class, 'close']);
    Route::post('/cashier-shifts/cash-out',        [CashierShiftController::class, 'cashOut']);
    Route::get('/cashier-shifts/cash-outs',        [CashierShiftController::class, 'cashOuts']);

    Route::apiResource('sales',      SaleController::class)->except(['update']);
    Route::put('/sales/{sale}',      [SaleController::class, 'update']); // Draft bill updates
    Route::get('/sales/{sale}/pdf',  [SaleController::class, 'downloadPdf']);
    Route::apiResource('purchases',  PurchaseController::class)->except(['update']);
    Route::get('/purchases/{purchase}/pdf', [PurchaseController::class, 'downloadPdf']);

    // Gold rates
    Route::get('/gold-rates',           [GoldRateController::class, 'index']);
    Route::post('/gold-rates',          [GoldRateController::class, 'store']);
    Route::get('/gold-rates/today',     [GoldRateController::class, 'todayRate']);
    Route::post('/gold-rates/calculate',[GoldRateController::class, 'calculate']);

    // Tax settings
    Route::apiResource('tax-settings', TaxSettingController::class);

    // Restaurant settings
    Route::get('/settings/restaurant', [RestaurantSettingController::class, 'show']);
    Route::post('/settings/restaurant', [RestaurantSettingController::class, 'update']);

    // User management (admin)
    Route::get('/users',              [UserController::class, 'index']);
    Route::post('/users',             [UserController::class, 'store']);
    Route::put('/users/{user}',       [UserController::class, 'update']);
    Route::delete('/users/{user}',    [UserController::class, 'destroy']);
    Route::get('/branches',           [UserController::class, 'branches']);

    // Reports
    Route::get('/reports/metal-balance',       [ReportController::class, 'metalBalance']);
    Route::get('/reports/rate-pnl',            [ReportController::class, 'ratePnl']);
    Route::get('/reports/day-end',             [ReportController::class, 'dayEnd']);
    Route::post('/reports/day-end',            [ReportController::class, 'storeDayEnd']);
    Route::get('/reports/sales-summary',       [ReportController::class, 'salesSummary']);
    Route::get('/reports/top-products',        [ReportController::class, 'topProducts']);
    Route::get('/reports/category-sales',      [ReportController::class, 'categorySales']);
    Route::get('/reports/table-performance',   [ReportController::class, 'tablePerformance']);
    Route::get('/reports/payment-methods',     [ReportController::class, 'paymentMethods']);
    Route::get('/reports/cashier-performance', [ReportController::class, 'cashierPerformance']);
    Route::get('/reports/pending-bills',       [ReportController::class, 'pendingBills']);
    Route::get('/reports/stock-summary',       [ReportController::class, 'stockSummary']);
    Route::get('/reports/daily-revenue',       [ReportController::class, 'dailyRevenue']);
    Route::post('/reports/export/pdf',         [ReportExportController::class, 'pdf']);

    // Accounting / GL reports
    Route::get('/accounting/journal-entries',  [AccountingController::class, 'journalEntries']);
    Route::get('/reports/trial-balance',       [AccountingController::class, 'trialBalance']);
    Route::get('/reports/profit-loss',         [AccountingController::class, 'profitLoss']);
    Route::get('/reports/balance-sheet',       [AccountingController::class, 'balanceSheet']);

    // Audit log
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // Finance (employees, salaries, income/expense)
    Route::get('/finance/employees', [FinanceController::class, 'employees']);
    Route::post('/finance/employees', [FinanceController::class, 'storeEmployee']);
    Route::put('/finance/employees/{employee}', [FinanceController::class, 'updateEmployee']);

    Route::get('/finance/salary-payments', [FinanceController::class, 'salaryPayments']);
    Route::post('/finance/salary-payments', [FinanceController::class, 'paySalary']);

    Route::get('/finance/income-expenses', [FinanceController::class, 'incomeExpenses']);
    Route::post('/finance/income-expenses', [FinanceController::class, 'storeIncomeExpense']);

    // Stock movement ledger
    Route::get('/stock-movements', [StockMovementController::class, 'index']);

    // GRN and supplier returns
    Route::get('/grns', [GrnController::class, 'index']);
    Route::post('/grns', [GrnController::class, 'store']);
    Route::get('/supplier-returns', [SupplierReturnController::class, 'index']);
    Route::post('/supplier-returns', [SupplierReturnController::class, 'store']);

    // Damage management
    Route::get('/damage-reports', [DamageReportController::class, 'index']);
    Route::post('/damage-reports', [DamageReportController::class, 'store']);
    Route::put('/damage-reports/{damageReport}', [DamageReportController::class, 'update']);

    // Open bottle tracking
    Route::get('/open-bottles', [OpenBottleController::class, 'index']);
    Route::get('/open-bottles/available', [OpenBottleController::class, 'available']);
    Route::post('/open-bottles/open', [OpenBottleController::class, 'open']);
    Route::post('/open-bottles/{openBottle}/pour', [OpenBottleController::class, 'pour']);
    Route::post('/open-bottles/{openBottle}/close', [OpenBottleController::class, 'close']);

    // Bottle deposit management
    Route::get('/bottle-deposits', [BottleDepositController::class, 'index']);
    Route::get('/bottle-deposits/summary', [BottleDepositController::class, 'summary']);
    Route::get('/bottle-deposits/available', [BottleDepositController::class, 'available']);
    Route::post('/bottle-deposits', [BottleDepositController::class, 'store']);
    Route::post('/bottle-deposits/return', [BottleDepositController::class, 'processReturn']);
    Route::post('/bottle-deposits/supplier-return', [BottleDepositController::class, 'returnToSupplier']);

    // Buy-back (purchasing gold from customers)
    Route::get('/gold-buybacks',                [GoldBuybackController::class, 'index']);
    Route::post('/gold-buybacks',               [GoldBuybackController::class, 'store']);
    Route::put('/gold-buybacks/{goldBuyback}',  [GoldBuybackController::class, 'update']);
    Route::delete('/gold-buybacks/{goldBuyback}',[GoldBuybackController::class, 'destroy']);

    // Scrap management
    Route::get('/scrap-items',                          [ScrapItemController::class, 'index']);
    Route::post('/scrap-items/convert-product',         [ScrapItemController::class, 'convertProduct']);
    Route::put('/scrap-items/{scrapItem}',              [ScrapItemController::class, 'update']);
    Route::delete('/scrap-items/{scrapItem}',           [ScrapItemController::class, 'destroy']);

    // ── Press Machines ─────────────────────────────────────────────
    Route::get('/press-machines/all',                   [PressMachineController::class, 'all']);
    Route::apiResource('press-machines',                PressMachineController::class);

    // ── Quotations ─────────────────────────────────────────────────
    Route::get('/quotations/{quotation}/pdf',           [QuotationController::class, 'downloadPdf']);
    Route::post('/quotations/{quotation}/convert',      [QuotationController::class, 'convert']);
    Route::apiResource('quotations',                    QuotationController::class);

    // ── Client Portal ──────────────────────────────────────────────
    Route::prefix('portal')->group(function () {
        Route::get('/me',                            [PortalController::class, 'me']);
        Route::get('/quotations',                    [PortalController::class, 'quotations']);
        Route::get('/jobs',                          [PortalController::class, 'jobs']);
        Route::get('/jobs/{jobNumber}',              [PortalController::class, 'jobDetail']);
        Route::get('/orders',                        [PortalController::class, 'orders']);
        Route::post('/jobs/{jobCard}/proof-decision',[PortalController::class, 'proofDecision']);
    });

    // ── Quotation Templates ────────────────────────────────────────
    Route::get('/quotation-templates',               [\App\Http\Controllers\Api\QuotationTemplateController::class, 'index']);
    Route::post('/quotation-templates',              [\App\Http\Controllers\Api\QuotationTemplateController::class, 'store']);
    Route::delete('/quotation-templates/{quotationTemplate}', [\App\Http\Controllers\Api\QuotationTemplateController::class, 'destroy']);

    // ── Job Cards ──────────────────────────────────────────────────
    Route::get('/job-cards/queue',                   [JobCardController::class, 'queue']);
    Route::patch('/job-cards/{jobCard}/status',      [JobCardController::class, 'updateStatus']);
    Route::post('/job-cards/{jobCard}/clone',        [JobCardController::class, 'clone']);
    Route::patch('/job-cards/{jobCard}/priority',    [JobCardController::class, 'togglePriority']);
    Route::apiResource('job-cards',                  JobCardController::class);

    // ── Artwork Files ──────────────────────────────────────────────
    Route::get('/job-cards/{jobCard}/artwork',       [\App\Http\Controllers\Api\JobArtworkController::class, 'index']);
    Route::post('/job-cards/{jobCard}/artwork',      [\App\Http\Controllers\Api\JobArtworkController::class, 'store']);
    Route::delete('/job-artwork/{jobArtworkFile}',   [\App\Http\Controllers\Api\JobArtworkController::class, 'destroy']);

    // ── Job Consumables ────────────────────────────────────────────
    Route::get('/job-cards/{jobCard}/consumables',    [\App\Http\Controllers\Api\JobConsumableController::class, 'index']);
    Route::post('/job-cards/{jobCard}/consumables',   [\App\Http\Controllers\Api\JobConsumableController::class, 'store']);
    Route::delete('/job-consumables/{consumable}',    [\App\Http\Controllers\Api\JobConsumableController::class, 'destroy']);

    // ── Job Costing ─────────────────────────────────────────────────
    Route::get('/job-cards/{jobCard}/costing',        [\App\Http\Controllers\Api\JobCostingController::class, 'show']);
    Route::post('/job-cards/{jobCard}/costing',       [\App\Http\Controllers\Api\JobCostingController::class, 'upsert']);

    // ── Schedule ───────────────────────────────────────────────────
    Route::get('/schedule',                              [\App\Http\Controllers\Api\ScheduleController::class, 'index']);
    Route::get('/schedule/workload',                     [\App\Http\Controllers\Api\ScheduleController::class, 'workload']);
    Route::get('/schedule/alerts',                       [\App\Http\Controllers\Api\ScheduleController::class, 'alerts']);
    Route::patch('/job-cards/{jobCard}/reschedule',      [\App\Http\Controllers\Api\ScheduleController::class, 'reschedule']);

    // ── Production ─────────────────────────────────────────────────
    Route::get('/production/dashboard',                 [ProductionController::class, 'dashboard']);
    Route::get('/production/analytics',                 [ProductionController::class, 'analytics']);
    Route::get('/production-jobs',                      [ProductionController::class, 'index']);
    Route::post('/production-jobs',                     [ProductionController::class, 'store']);
    Route::put('/production-jobs/{productionJob}',      [ProductionController::class, 'update']);

    // ── Deliveries ─────────────────────────────────────────────────
    Route::apiResource('deliveries',                    DeliveryController::class)->except(['destroy']);

    // ── Pre-Press Tasks ────────────────────────────────────────────
    Route::get('/prepress-tasks',                       [PrepressTaskController::class, 'index']);
    Route::get('/prepress-tasks/{prepressTask}',        [PrepressTaskController::class, 'show']);
    Route::put('/prepress-tasks/{prepressTask}',        [PrepressTaskController::class, 'update']);

    // ── Finishing Tasks ────────────────────────────────────────────
    Route::get('/finishing-tasks',                      [FinishingTaskController::class, 'index']);
    Route::get('/finishing-tasks/{finishingTask}',      [FinishingTaskController::class, 'show']);
    Route::put('/finishing-tasks/{finishingTask}',      [FinishingTaskController::class, 'update']);

    // ── Press Settings ─────────────────────────────────────────────
    Route::get('/press-settings',                       [PressSettingController::class, 'show']);
    Route::post('/press-settings',                      [PressSettingController::class, 'store']);

    // ── Press Reports ──────────────────────────────────────────────
    Route::get('/reports/job-profitability',            [ReportController::class, 'jobProfitability']);
    Route::get('/reports/material-consumption',         [ReportController::class, 'materialConsumption']);
    Route::get('/reports/waste-report',                 [ReportController::class, 'wasteReport']);
    Route::get('/reports/machine-utilization',          [ReportController::class, 'machineUtilization']);
    Route::get('/reports/customer-outstanding',         [ReportController::class, 'customerOutstanding']);
    Route::get('/reports/production-status',            [ReportController::class, 'productionStatus']);
});
