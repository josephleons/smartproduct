# SmartLabel

Smart product labels for electronics retailers in Tanzania. Sellers register products and print QR stickers. Customers scan a sticker and get a fast mobile page with the specs, price, condition, warranty and the shop's contacts.

- Business context and MVP scope: [`docs/PROJECT_BRIEF.md`](docs/PROJECT_BRIEF.md)
- Development rules: [`CLAUDE.md`](CLAUDE.md)

## Stack

Laravel 13 (PHP 8.4), Blade + Vuexy, Laravel Fortify, MySQL 8, Redis (cache, sessions, queue), dompdf, bacon-qr-code, Docker Compose.

## Getting started

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --seed
docker compose exec app sh -c "npm install && npm run build"   # or: npm run dev
```

| Service    | URL / port              |
|------------|-------------------------|
| App        | http://localhost:8095   |
| phpMyAdmin | http://localhost:8096   |
| MySQL      | localhost:3315          |
| Redis      | localhost:6385          |

These ports were picked to avoid the other projects on this machine. Change them in `.env` with `APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT` and `FORWARD_PMA_PORT`.

The `queue` container runs `queue:work redis` (scan events). The `scheduler` container runs `schedule:work`, which triggers the hourly billing sweep. Run it by hand with `php artisan billing:check`.

### Billing

Subscriptions are monthly. As superadmin:

1. **Admin → Plans**: set each plan's price, limits (products, branches, users) and features. The seeded Normal/Premium plans start at TZS 0 and unlimited, so set real values before onboarding paying companies.
2. **Admin → Billing settings**: trial days, grace days, when bills are issued, and the payment instructions companies see.
3. **Admin → Invoices**: confirm or reject payments companies submit, or record cash/bank payments yourself.

Companies that stay unpaid after the grace period become read-only. Their customer QR pages keep working.

### Onboarding companies

Companies cannot sign up themselves. The platform superadmin creates each company, its first branch and its owner account at **Admin → Companies** (`/admin/companies`). In production, create the first superadmin with:

```bash
docker compose exec app php artisan smartlabel:create-superadmin you@example.com --name="Your Name"
```

### Demo accounts (local seed)

| Role    | Email                    | Password |
|---------|--------------------------|----------|
| Superadmin (platform) | admin@smartlabel.test | password |
| Owner   | owner@smartlabel.test    | password |
| Manager | manager@smartlabel.test  | password |
| Staff (Mlimani City branch only) | staff@smartlabel.test | password |

## Tests

```bash
docker compose exec app php artisan test      # uses the smartlabel_testing database
docker compose exec app ./vendor/bin/pint
```

## Important: the QR URL

Every sticker encodes `APP_PUBLIC_URL/p/{TOKEN}`. Set `APP_PUBLIC_URL` to the final public domain **before printing real stickers**. Changing it later breaks every sticker already printed.
