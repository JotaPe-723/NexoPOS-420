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
