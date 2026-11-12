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

## Change already made on this branch

- In `resources/ts/popups/ns-pos-customer-select-popup.vue`, customer search debounce was reduced from 500 ms to 200 ms.
- The debounce timeout is cleared when the popup unmounts to avoid a delayed search after closing.
- This is a small responsiveness improvement, not the complete quick-customer workflow.

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
