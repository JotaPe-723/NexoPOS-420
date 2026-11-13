# NexoPOS-420 — Quick Flow implementation brief

## Goal

Make the in-store workflow fast for a small smoke/vape shop. The POS must prioritize speed at the counter. Optional customer/product metadata must never block a normal sale.

## Repository and branch

- Repository: https://github.com/JotaPe-723/NexoPOS-420
- Working branch: `nexo-quick-flow`
- Base branch: `master`
- Do not push changes to `master` directly.
- Keep changes small, reviewable, and testable. Open a draft PR when a coherent slice is ready.

## Business priorities (in order)

1. Fast checkout / sale.
2. Fast product creation that saves a real inventory product (not a temporary cart-only item).
3. Fast customer creation with only essential information.
4. Preserve inventory accuracy, tax calculations, permissions, cash-register rules, and auditability.

## UX requirements

### Sales
- Search by name, SKU, or barcode; barcode scanning should add the matching product immediately.
- Cart quantity edits should be quick and obvious.
- Checkout should have the fewest possible steps.
- Customer selection must be optional for walk-in sales.
- Do not remove required cash-register/payment/accounting safeguards without verifying the backend behavior.
- Do not create duplicate sales on double-click or repeated Enter.

### Quick customer
- Minimum required input: customer display/name (the current model uses `first_name`).
- Phone is optional unless the store's existing unique-phone setting requires validation.
- Last name, email, birth date, credit limit, gender, billing/shipping addresses, and other profile fields should be optional/deferred.
- Customer group is currently marked required in `app/Crud/CustomerCrud.php`. Before relaxing it, inspect customer creation validation, model/database constraints, and default group behavior. Prefer assigning the configured/default customer group server-side over asking cashiers to choose one.
- After successful creation from the POS, select the new customer on the current order and return to checkout.

### Quick product
- Must create a persistent product in inventory. The existing `resources/ts/popups/ns-pos-quick-product-popup.vue` creates a temporary cart product with `POS.addToCart`; it does NOT save a catalog/inventory record.
- Minimum useful fields for a real stock item: name, sale price, cost, initial stock quantity, unit, and category/default category.
- Barcode and supplier/brand/image/tax details should be optional or receive safe store defaults.
- Respect current NexoPOS product variations/unit-quantity schema, tax rules, and stock management. Do not bypass the official product creation service/CRUD validation.
- On success, make the newly created product available in the POS catalog and add it to the active cart only if the cashier explicitly chooses that behavior.

## Confirmed code observations

- `resources/ts/pages/dashboard/pos/ns-pos-product-entry-toolbar.vue` already supports barcode input, auto-focus, product search, and adding scanned products to cart.
- `resources/ts/pages/dashboard/pos/ns-pos-cart.vue` owns the cart UI and payment interaction.
- `resources/ts/popups/ns-pos-customer-select-popup.vue` searches customers and opens the customer creation popup when no result matches.
- `resources/ts/popups/ns-pos-customers.vue` currently renders the full `ns-crud-form` using `/api/crud/ns.customers/form-config`.
- `app/Crud/CustomerCrud.php` currently requires `first_name` and `group_id`; billing/shipping tabs and several profile fields are present.
- `resources/ts/popups/ns-pos-quick-product-popup.vue` builds a temporary product object and adds it directly to the cart; it does not persist it.
- `app/Crud/ProductCrud.php` uses a product + variation/unit-quantity structure. Product creation must respect that structure.

## Additional change made on this branch

- In `app/Crud/CustomerCrud.php`, the customer group selector is no longer marked as required in the form. Existing `filterPostInputs()` logic fills an empty group using `ns_customers_default_group` and rejects creation if that configured group does not exist. This removes the need for cashiers to choose a group manually while preserving server-side validation.
- The customer form still contains optional profile and address tabs; they do not need to be completed for a basic customer record. A dedicated compact popup has not yet been implemented.
- Fixed the POS customer popup save path: `ns-crud-form` now supports `redirectOnSave=false` for embedded workflows, and the POS popup listens to the component's actual `saved` event. This allows the newly created customer to be selected on the active POS order instead of redirecting the cashier to the dashboard edit page. This behavior has not yet been verified in a running app.

## Change already made on this branch

- In `resources/ts/popups/ns-pos-customer-select-popup.vue`, customer search debounce was reduced from 500 ms to 200 ms.
- The debounce timeout is cleared when the popup unmounts to avoid a delayed search after closing.
- This is a small responsiveness improvement, not the complete quick-customer workflow.

## Product creation backend path inspected

- `app/Http/Controllers/Dashboard/ProductsController.php::saveProduct()` expects the structured product form payload, including a primary variation under `variations`, then flattens its identification fields and passes the result to `ProductService::create()`.
- `app/Services/ProductService.php::create()` validates that the category exists and delegates simple/variable product creation. `createSimpleProduct()` checks barcode uniqueness, generates a SKU when omitted, persists the product, and computes unit quantities.
- `app/Crud/ProductCrud.php` currently requires a category, unit group, assigned unit, sale price, and wholesale price. It models units and prices inside the product variation's selling group.
- `ProductUnitQuantity` stores the sale price, wholesale price, barcode, and stock quantity. Initial stock must be written through the product's supported unit-quantity/stock workflow, not by inventing a top-level product quantity.
- The existing quick POS popup is not a safe base for persistent creation because it explicitly sets stock management to disabled and adds only a cart object. A future quick-product flow should reuse the official product creation path and its structured payload, or a dedicated backend action that delegates to the same service with validated defaults.

## Guardrails

- Never modify `master` directly.
- Do not rewrite the POS or replace the Laravel/Vue architecture.
- Do not invent API endpoints. Trace existing routes/controllers/services before using them.
- Do not weaken permissions, tax calculations, inventory tracking, cash register requirements, or payment validation to make the UI appear faster.
- Avoid migrations unless strictly necessary; the deployment deadline is short.
- Preserve existing behavior outside the POS quick workflows.
- Keep Spanish UI copy clear and concise if adding translations; follow the project's translation conventions.
- Do not store secrets, credentials, customer personal data, or production database dumps in Git.

## Required verification before proposing merge/deployment

1. Existing automated checks relevant to changed files.
2. Create a customer with only the minimum supported data; verify it is saved and selected in the order.
3. Sell without selecting a customer.
4. Create a real stock product; verify it appears in catalog/search and its stock is correct.
5. Add a product by barcode and by text search.
6. Complete a cash sale and verify payment, receipt, stock decrement, and register totals.
7. Test duplicate-click / repeated Enter behavior.
8. Confirm desktop and phone/tablet layout.

If a test cannot be run in the available environment, say so explicitly. Do not claim it passed.


## Verification status

- Changes were committed to `nexo-quick-flow` and the branch comparison against `master` confirms four commits ahead, zero behind.
- The repository was inspected remotely through GitHub. A local PHP/Node environment and application database were not available in this work session, so PHP lint, frontend build, automated tests, and end-to-end POS flows have **not** been run.
- Do not deploy or merge based only on these commits. Run the verification checklist in a configured staging copy first.


## Expense register analysis (2026-11-12)

The supplied workbook `Gastos 420_FOUR-TWENTY.xlsx` defines a payment-level expense ledger with these fields: date, category, description, COP amount, payment method, responsible person, fixed/variable type, paid/pending status, receipt/reference, notes, and derived month/year/classification. Categories include inventory purchase, rent, services, personal, deliveries/transport, advertising, operating supplies, technology, financial expenses, loan repayment, taxes/procedures, and other. Classification is derived: inventory purchase => Inventory; loan repayment => Financing; all other categories => Operation. The workbook's summary separates paid operating expenses, paid inventory purchases, loan repayments, and pending payments by month/category.

NexoPOS's existing Accounting > Expenses page is backed by `TransactionCrud`, which stores transaction definitions (including recurring/scheduled/entity transactions). Actual postings are stored in `TransactionHistory` and may trigger accounting reflection/report jobs. That model is not a one-row-per-payment register and does not have all workbook metadata (payment method, responsible person, receipt/reference, notes, fixed/variable type, paid/pending status). Reusing it naively would risk changing existing accounting behavior or misrepresenting scheduled transaction definitions as individual payments.

Recommended implementation: add a dedicated, permission-protected expense-register resource/table for manual payment-level entries and link it from the existing Accounting > Expenses area, while leaving the existing accounting transaction engine intact. Do not import the workbook's example rows. Do not automatically change inventory stock or post accounting journals until an explicit, tested mapping to NexoPOS's accounting accounts/payment methods is implemented. The month summary should distinguish inventory purchases and loan principal from operating expenses, as the workbook does.


## Dedicated expense register implementation (2026-11-12)

A separate payment-level expense register has been added without replacing NexoPOS's existing transaction engine:
- Table/model: `nexopos_expense_register` / `App\\Models\\ExpenseRegister`.
- CRUD: `App\\Crud\\ExpenseRegisterCrud`, with server-side validation for date, category, positive COP amount, payment method, fixed/variable type, status, and optional metadata.
- Fields follow the workbook: date, category, description, amount, payment method, responsible person, fixed/variable type, paid/pending status, receipt/reference, and notes. Classification is assigned server-side: inventory purchase => inventory; loan repayment => financing; other categories => operation.
- Dashboard paths: `/dashboard/accounting/expenses`, `/dashboard/accounting/expenses/create`, and `/dashboard/accounting/expenses/summary`. Existing transaction definitions remain available as Scheduled Transactions, and transaction history/accounting settings are preserved.
- Monthly summary includes paid outflows, operating expenses, inventory purchases, loan repayments, pending payments, movement count, category paid/pending totals and paid percentage, plus annual monthly evolution.
- Dedicated permissions are created and assigned to administrator/store administrator roles; store cashier can read, create, and update entries so a pending expense can be marked paid. Cashier delete permission is not granted.
- Workbook example rows are not imported. The register does not automatically modify inventory stock or create accounting journal postings. Inventory purchases must still be received through Procurement when stock needs to increase; the register is a manual cash-outflow record. Avoid recording the same payment in both places if you are using Procurement accounting automation.

Verification status: source files and GitHub changes have been reviewed, but PHP lint, Laravel migrations, browser rendering, CRUD create/edit/delete, permission behavior, and summary calculations have not been executed against a running NexoPOS database. Do not deploy until these checks are run in the store's staging/local installation.
