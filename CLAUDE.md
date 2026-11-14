# CLAUDE.md

Guidance for Claude Code when working in this repository.

## Project

**SmartLabel** is a Digital Product Label Platform for electronics retailers in Tanzania. Sellers register products, and the system generates QR stickers. Customers scan a sticker and see a public mobile product page: specs, price, condition, warranty and store contacts.

Full business context, MVP scope and screen list: `docs/PROJECT_BRIEF.md`. Read it before starting a new feature.

## Stack

- PHP 8.3+, Laravel (latest stable)
- Blade views + **Vuexy HTML (Laravel version)** admin template for the seller dashboard
- Vite for assets
- MySQL 8, Redis (cache, queues, sessions)
- Docker Compose services: `app` (php-fpm), `nginx`, `mysql`, `redis`, `queue`, `scheduler`
- QR: `bacon/bacon-qr-code` v3 (SVG output) via `App\Services\QrCodeRenderer`. `simplesoftwareio/simple-qrcode` cannot be used: it pins bacon v2, which conflicts with Fortify.
- PDF labels: `barryvdh/laravel-dompdf`
- Auth: **Laravel Fortify** with session auth (login, logout, password reset). Fortify registration is **disabled**: this is a SaaS and companies are onboarded by the platform superadmin (`/admin/companies` → `App\Actions\CreateCompany`). No SPA, no token auth.

This is a **monolith**. Do not introduce Vue/React SPAs, Inertia or Livewire unless explicitly asked. Small interactivity is handled with the JS that ships with Vuexy, plus vanilla JS / Alpine-style snippets in page-level scripts.

## Commands

Run everything inside Docker:

```bash
docker compose up -d                              # app on http://localhost:8095, phpMyAdmin :8096, MySQL :3315, Redis :6385
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=ProductTest
docker compose exec app ./vendor/bin/pint        # format before committing
npm install && npm run dev                        # Vite (on host or in a node container)
```

## Architecture

### Two surfaces, two layouts

| Surface | Routes | Layout | Auth |
|---|---|---|---|
| Seller dashboard | `routes/web.php`, prefix `/dashboard` and related | Vuexy layout (`resources/views/layouts/`) | `auth` + tenant |
| Public product page | `GET /p/{token}` (+ `/p/{token}/pay`) | `resources/views/public/layout.blade.php`, lightweight and **not** Vuexy | none |

The public page must stay fast on cheap Android phones on 3G: no Vuexy bundle, minimal CSS, no heavy JS, server-rendered, at most ~100 KB per page.

### Multi-tenancy (single database)

- Every tenant-owned table has a `company_id` column.
- Tenant models use the `App\Models\Concerns\BelongsToCompany` trait. It adds a global scope filtering by the authenticated user's `company_id` and sets `company_id` automatically on create.
- **Never** query tenant data without this scope in the dashboard. Use `withoutGlobalScope` only in public token lookup, jobs, and admin code, with a comment explaining why.
- Every feature test must include a check that company A cannot see or modify company B's records.
- Authorization goes through Policies (`ProductPolicy`, `BranchPolicy`…), never inline checks in controllers.

### Roles

`users.role`: `superadmin`, `owner`, `manager`, `staff`. `users.branch_id` is nullable (null means all branches).

- superadmin: platform operator, `company_id` is null. Only uses `/admin` (create companies with their first branch and owner, activate/deactivate). Is redirected away from tenant pages. Create one with `php artisan smartlabel:create-superadmin {email}`.
- owner: everything in their company, including company settings, users and billing
- manager: products, labels, branches, analytics
- staff: create and edit products, print labels; limited to their branch if `branch_id` is set

Companies have `is_active`. Users of an inactive company are logged out of the dashboard; their public product pages keep working.

### Subscriptions (monthly billing)

- Superadmin configures **plans** (`/admin/plans`: monthly price in TZS, scope of usage = `max_products` / `max_branches` / `max_users` (null = unlimited) and `features`, see `App\Enums\PlanFeature`) and **billing settings** (`/admin/billing/settings`: trial days, grace days, days-before to issue a bill, payment gateway, payment instructions; stored in `platform_settings`, defaults in `config/billing.php`).
- One `subscriptions` row per company. Access derives from `paid_until`: Trial/Active → PastDue (inside `grace_days`, full access + banner) → ReadOnly (dashboard view-only, no writes, no label printing; billing page still works). Customer QR pages are never blocked.
- `App\Jobs\CheckSubscriptions` runs **hourly** (`routes/console.php`, `scheduler` container; manual run `php artisan billing:check`): asks each pending payment's gateway, settles fully paid bills, issues the next monthly bill `invoice_days_before` days ahead (one open bill at a time), marks overdue bills, recalculates statuses. Logic lives in `App\Services\Billing\SubscriptionManager` and `PaymentProcessor`.
- Paying a bill extends `paid_until` by one month from the later of the current `paid_until` and today (lapsed companies don't pay for read-only months). Zero-price bills settle automatically.
- Payments: the owner submits "I have paid" with an M-Pesa/bank reference (`/settings/billing`) → `pending`; the gateway driver confirms it. The `manual` driver (`App\Services\Billing\Gateways\ManualGateway`) leaves it for the superadmin to confirm/reject at `/admin/invoices`. A mobile money provider is added by implementing `App\Contracts\PaymentGateway` and registering it in `config/billing.php`.
- Enforcement: `subscription` middleware (read-only), `feature:<name>` middleware (plan features), `Controller::planLimitResponse()` (plan limits).
- Premium features **SMS to customers** and **WhatsApp orders** exist only as plan feature flags ("coming soon"); do not build them until asked.

### Dynamic QR

- A QR encodes **only** `config('app.public_url') . '/p/' . $token`, never specs or prices.
- Token: 7 characters, uppercase, alphabet without ambiguous characters (`23456789ABCDEFGHJKLMNPQRSTUVWXYZ`), unique and indexed. Generate it in `App\Services\QrTokenGenerator`.
- A token is **permanent** for a product. Editing a product never changes its token, because printed stickers must keep working.
- An unknown token returns a friendly 404 page. A sold or inactive product still shows its page, with the status badge and contact buttons, but **no price**.

### Scan tracking

- `GET /p/{token}` dispatches `RecordProductEvent` to the queue and must not write synchronously.
- Store event type (`scan`, `whatsapp_click`, `call_click`, `similar_click`), product, branch, company, hashed IP (`sha256(ip . app.key)`, never a raw IP), device type parsed from the user agent, and timestamp.
- Contact buttons go through `GET /p/{token}/go/{whatsapp|call}`, which records the event and then redirects (`https://wa.me/255…?text=…` or `tel:`).
- Ignore bots and crawlers by user agent. De-duplicate repeat scans from the same hashed IP and product within 30 minutes for the "unique" metric.
- Rate-limit public routes (`throttle:60,1`).

### Customer payments from the QR page (approved 2026-11-14)

SmartLabel **records** payments; it never moves money and has no payment gateway for customers.

- The QR page shows **Pay for this item** (only while the price is shown). `/p/{token}/pay` lets the customer choose **Cash** (enter the amount actually paid, which may be a negotiated discount) or **Mobile money** (network cards with the shop's Lipa Namba, plus the SMS transaction code), with name and phone. CSS-only toggles, no JS. Throttled per IP (`sale-requests` limiter).
- Networks are listed in `config/payments.php`. Each company enters its Lipa Namba per network in Settings > Company profile; a branch may override them (`companies/branches.payment_numbers`, resolved by `App\Services\LipaNamba`).
- A confirmation creates a `sales` row (`pending`) with a 6-character reference. The customer's status page (`/p/{token}/pay/{reference}`) shows no personal data.
- Staff review under `/sales` (sidebar **Payments**), installable as a PWA (`public/manifest.webmanifest`, `public/sw.js`, which never caches dashboard data). They scan the sticker with the camera (`smartlabel-scanner.js`: BarcodeDetector, else `jsqr`) or type the SKU, check the device, enter the **amount received**, and approve or reject. Any role may review sales of the branches they can access (`SalePolicy`).
- Approving (`App\Actions\ReviewSale`) takes one unit off `quantity`; at 0 the product becomes **Sold**. Several pending confirmations per product are allowed. Unreviewed ones expire after `pending_expiry_hours` (24) via `sales:expire`, run hourly.

## Data model

```
companies          id, name, slug, logo_path, phone, whatsapp, email, address, payment_numbers (json)?, timestamps
users              id, company_id, branch_id?, name, email, password, role, timestamps
branches           id, company_id, name, location, phone?, whatsapp?, payment_numbers (json)?, is_active, timestamps
categories         id, company_id? (null = system category), name, slug, icon?, timestamps
category_spec_fields
                   id, category_id, key, label, type (text|number|select|boolean),
                   options (json)?, unit?, is_required, show_on_label, sort_order
products           id, company_id, branch_id, category_id, brand, model, description?,
                   condition (new|grade_a|grade_b|grade_c|refurbished), price (unsigned int, TZS),
                   quantity (default 1), serial_number?, warranty_months?,
                   status (available|low_stock|sold|inactive), sku (e.g. TZ-LAT5420-024),
                   timestamps, soft deletes
product_specs      id, product_id, spec_field_id?, key, label, value
qr_codes           id, product_id (unique), token (unique), scans_count, last_scanned_at?, timestamps
product_events     id, company_id, branch_id, product_id, type, ip_hash, device_type,
                   user_agent?, created_at       (index: company_id, created_at)
label_templates    defined in config/labels.php for the MVP (not a DB table)
plans              id, name, slug, description?, monthly_price (TZS), max_branches?, max_users?, max_products?,
                   features (json), is_active, sort_order
subscriptions      id, company_id (unique), plan_id, status, paid_until, trial_ends_at?, grace_ends_at?
invoices           id, company_id, subscription_id, plan_id, number, plan_name, amount, period_start, period_end,
                   due_date, status (pending|overdue|paid|cancelled), paid_at?
payments           id, company_id, invoice_id, amount, gateway, method, reference, status (pending|confirmed|failed),
                   submitted_by?, reviewed_by?, confirmed_at?, note?, meta?
platform_settings  key, value (json)              -- billing settings
sales              id, company_id, branch_id, product_id, reference (unique), method (cash|mobile), network?,
                   payment_number?, transaction_code?, customer_name, customer_phone, listed_price, amount,
                   received_amount?, status (pending|approved|rejected|expired), reviewed_by?, reviewed_at?,
                   note?, ip_hash, timestamps
```

Rules:

- **Money:** TZS is stored as an unsigned integer with no decimals. Display it as `TZS 850,000` using the `money()` helper.
- `product_specs` copies `key` and `label` from the template at save time, so later template edits don't corrupt old products.
- Brand and model live on `products`; all other category fields live in `product_specs`.
- System categories (Laptop, Smartphone, TV) and their spec fields are seeded by `CategorySeeder`. The starting fields are in `docs/PROJECT_BRIEF.md` §8.

## Labels and printing

- Templates are defined in `config/labels.php`: size in mm (e.g. 70×45), which fields to show, and sheet layout (e.g. A4, 2×4 = 8 labels).
- Render with Blade into dompdf. Use mm units in the CSS. QR is inline SVG, at least **20 mm** wide, with error correction level `M` and a quiet zone.
- Always provide an HTML preview identical to the PDF before download or print.
- The label shows: company name, model, a short spec line (fields with `show_on_label`), price, branch, the QR, and the hint "Scan specs".

## UI conventions

- Use Vuexy components (cards, tables, form layouts, badges, wizard / bs-stepper for Add Product). Do not hand-roll components Vuexy already provides.
- **Do not edit Vuexy core/vendor files.** Theme overrides go in `resources/assets/` custom SCSS and the template's theme config.
- Brand colors: primary `#0F6680`, sidebar `#174551`, soft `#EAF4F7`, canvas `#F7FAFB`, text `#1F2D33`.
- Sidebar navigation: Dashboard, Products, Payments, QR & Labels, Branches, Analytics, Settings.
- Status badges come from one Blade component, `<x-status-badge :status="$product->status" />`, used everywhere.
- Add Product is a 3-step wizard (Basic info → Specs → Review & label). Category selection loads its spec fields dynamically. The target is under 2 minutes per product.
- Put user-facing strings in `lang/` (`en` first, `sw` planned), not hardcoded.

## Code conventions

- Thin controllers. Use Form Requests for validation and Actions/Services in `app/Actions` or `app/Services` for business logic (e.g. `CreateProduct`, `GenerateLabelPdf`).
- Use PHP enums for `role`, `condition`, `status` and the event `type` (`app/Enums`).
- Use resource controllers and named routes. Route names follow `products.index`, `labels.preview`, etc.
- Eager-load relations on list pages. Paginate every list.
- Cache the public product page per token (Redis, 5 min). Bust the cache on product update via an observer.
- Feature tests (PHPUnit/Pest) for every endpoint. Use factories for every model.
- Run Pint before finishing a task.

## MVP boundaries

Build only what is in `docs/PROJECT_BRIEF.md` §7, plus the approved additions above (bulk import, customer payment confirmations with the staff PWA). **Do not add** POS, invoicing, accounting, stock transfers, customer accounts, a payment gateway that moves customer money, or a native mobile app. If a task seems to need one of these, stop and ask.

## When unsure

Ask before deciding on: pricing/billing logic, the public page language, branch-restricted access edge cases, printer-specific formats, or anything that changes the QR URL format. Existing printed stickers depend on that format.
