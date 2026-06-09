# System Training Script

## 1. System Overview
This system is an all-in-one restaurant and POS platform.
It manages sales, inventory, customers, tables, purchasing, staff finance, and accounting.
The main goal is simple: enter data once during operations and get accurate reports automatically.

## 2. User Login And Roles
Each user logs in with a role.
Common roles are owner, admin, manager, cashier, and store keeper.
Roles control access so sensitive features like finance and user management stay secure.

## 3. Dashboard
Dashboard gives a quick business snapshot.
It summarizes key performance so management can monitor operations quickly.
Dashboard values come from sales, inventory, and finance activity.

## 4. Categories And Products
Categories organize your menu and inventory items.
Products store SKU/barcode, stock, purchase price, selling price, and type.
Liquor items support serving size pricing logic, which affects line totals in billing.

## 5. Suppliers, Purchases, And GRN
Suppliers are used for procurement.
Purchases and GRN increase stock quantities when goods are received.
These entries connect directly to inventory valuation and accounting payables.

## 6. Customers And Tables
Customers can be selected or quickly created during a sale.
Tables can be managed and assigned while billing.
This connects front-of-house order flow to transaction tracking.

## 7. POS Billing (New Sale)
Cashier adds products manually or by barcode scan.
The bill supports quantity, item discount, tax, bill discount, split payment, and notes.
Liquor serving ml and bottle deposit logic are calculated automatically in line totals.

## 8. Draft Bills
If service is not complete, cashier can save a draft bill.
Draft can be reopened later with restored item data and pricing context.
This helps manage busy service periods without losing progress.

## 9. Split Billing And Payments
A bill can be paid by multiple methods or people.
System tracks collected amount and remaining balance.
This keeps payment tracking accurate for partial and mixed-method settlements.

## 10. Sales Completion And Receipt
When sale is completed, it becomes part of sales history.
Receipt data is generated from final sale details.
Completed transactions feed reports and accounting.

## 11. Inventory Control Features
Damage reports reduce stock and track loss reasons.
Open bottle tracking manages bottle usage in serving operations.
Bottle deposits and supplier returns manage liabilities and stock corrections.
All of these keep inventory quantities and values accurate.

## 12. Restaurant Settings
Restaurant details and logo can be managed in settings.
Logo and details appear in key UI/report contexts for branding.
This ensures consistent business identity in operational documents.

## 13. Finance Module - Employee Tab
Employee tab maintains staff records.
It stores employee code, role, contact, base salary, and active status.
This is the master data used by salary posting.

## 14. Finance Module - Salary Tab
Salary tab records salary payments by employee and month.
Posting salary creates accounting impact immediately.
Standard effect: salary expense increases and cash decreases.

## 15. Finance Module - Income Expense Tab
This tab records manual income and expense transactions.
Each entry supports date, category, amount, payment method, and optional account code.
Posting creates immediate journal impact for financial statements.

## 16. Accounting Connection
System auto-posts journal entries for major business events.
Examples include sales, stock movements, damages, salaries, and income/expense entries.
Trial Balance, Profit and Loss, and Balance Sheet read from these journal entries.

## 17. Reports And Decision Support
Reports provide sales, payment method, top product, stock, and accounting views.
Users can filter by date and export where supported.
This turns daily operations into management insights.

## 18. End-To-End Connection Story
Sale created -> stock reduced -> payment recorded -> accounting posted -> reports updated.
Purchase or GRN posted -> stock increased -> payable/inventory reflected -> margins become accurate.
Salary and expense posted -> costs visible in Profit and Loss -> management sees true profitability.

## 19. Practical Training Flow
Step 1: Create category, product, supplier, and table.
Step 2: Create a purchase or GRN and confirm stock increase.
Step 3: Create a sale with split payment and complete it.
Step 4: Save a draft, reopen it, and complete it.
Step 5: Add employee, post salary, then post one expense.
Step 6: Open reports and confirm all entries are reflected.

## 20. Trainer Closing Message
Correct source entry is the key to system accuracy.
If billing, stock, and finance are entered correctly, reports and accounts stay reliable.
Use each module for its intended task and avoid manual side tracking outside the system.
