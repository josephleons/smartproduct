# SmartLabel: Digital Product Label Platform for Electronics Retailers

## 1. The idea in one line

Turn every physical electronics product in a shop into a live digital product page, reached by scanning a small printed QR sticker.

```
Company → Branch → Product → Generate QR → Print sticker → Customer scans → Product page
```

## 2. The problem

In electronics shops (Kariakoo, Posta, Mlimani City…), a customer who wants the specs of a laptop or phone has to ask staff, or switch the device on and dig through Settings. That is slow, it depends on staff being free, and specs and prices are often given inconsistently.

## 3. The solution

Each product gets a sticker with a QR code. The customer scans it with the phone camera, with no app install and no login, and immediately sees:

- price, availability and the branch it is at
- the key specifications for that category (processor, RAM, storage, battery health…)
- condition grade and warranty
- buttons to contact the store: WhatsApp, Call, View similar products

The seller manages everything from a web dashboard.

## 4. Key design decision: dynamic QR

The QR code holds **only a short URL with a token** (e.g. `smartlabel.co/p/X82KD`), never the specs themselves. So:

- price, specs, warranty and availability can change without reprinting the sticker
- every scan can be counted (analytics)
- a sold or inactive product can show a clear "Sold" state instead of stale data

## 5. Positioning

This is **not** a "QR code generator", which is a small, easily copied tool. It is **Smart Product Labels for Electronics Retailers**: the value is in tying product information, category spec templates, pricing, branches, availability, printable labels and scan analytics into one simple workflow. The QR is just the gateway.

Self-assessed startup score in the concept document: 7.5–8 / 10.

## 6. Users

| User | Where they work | Login? |
|---|---|---|
| Owner / manager | Seller dashboard: company, branches, staff, all products, analytics | Yes |
| Shop staff (agent) | Seller dashboard: register products, print labels, update price/status | Yes |
| Customer | Public mobile product page | **Never** |

## 7. MVP scope (Version 1)

In scope:

1. Merchant registration
2. Company profile (name, logo, phone, WhatsApp, address)
3. Multiple branches
4. Electronics product registration
5. Product categories
6. Specification templates per category
7. QR generation
8. Sticker templates
9. PDF / label printing (single sticker and A4 sheet)
10. Public product page (mobile-first)
11. Price editing
12. Product availability / status
13. Basic scan analytics

Out of scope for V1: POS, accounting, invoicing, stock transfers between branches, customer accounts, online payment, native mobile app.

## 8. Specification templates (starting set)

| Category | Fields |
|---|---|
| Laptop | Brand, Model, Processor, Generation, RAM, Storage, GPU, Display, Battery Health, Condition, Operating System, Warranty, Price |
| Smartphone | Brand, Model, Storage, RAM, Screen, Camera, Battery, SIM, Network, Condition, Warranty, Price |
| TV | Brand, Size, Resolution, Smart TV, HDMI, USB, Operating System, Warranty, Price |

The form shows only the fields of the chosen category.

## 9. Screens (from the wireframes)

1. **Seller login**: email + password, link to create a company account
2. **Dashboard**: cards (Products, Available, QR Scans, Branches), 7-day scans chart, top viewed products, recent products table
3. **Products**: searchable list with category and branch filters, plus a 3-step Add Product wizard (Basic info → Specs → Review / label)
4. **QR & Labels**: choose product, sticker size (e.g. 70×45 mm), template, which fields appear; live preview; A4 sheet of 8; Download PDF / Print
5. **Customer product page (mobile)**: store name and branch, status badge, model, price, spec grid, warranty, WhatsApp / Call / Similar, product ID, "last updated"
6. **Analytics**: total scans, unique products scanned, WhatsApp clicks and scan-to-contact rate, scans by branch, filters for period, branch and category

## 10. UX guardrails

- Register a known device in **under 2 minutes**.
- Keep required fields to a minimum; the category template reveals only relevant specs.
- Always show the label preview before print or download.
- The customer page puts price, top specs, condition, warranty and contact first.
- Use the same status badges everywhere: **Available, Low stock, Sold, Inactive**.
- Labels must stay scannable when printed small.

## 11. Visual identity

| Token | Hex |
|---|---|
| Primary | `#0F6680` |
| Deep Teal (sidebar) | `#174551` |
| Soft Teal | `#EAF4F7` |
| Canvas | `#F7FAFB` |
| Text | `#1F2D33` |

Cards use an 8–12 px radius, spacing follows an 8 px grid, and typography is clean and compact.

## 12. Technical direction (decided)

- **Laravel monolith** with **Blade + Vuexy HTML admin template** for the seller dashboard
- Public product page: a lightweight Blade page, not the Vuexy admin shell
- MySQL; Redis for cache and queues
- Docker Compose for local development
- Multi-tenant: one database, every tenant table scoped by `company_id`

See `CLAUDE.md` for the development rules.

## 13. Open questions (to decide later)

- Final product name and domain (`smartlabel.co` is used in the wireframes as a placeholder)
- Pricing model: per branch, per product count, or a flat monthly fee
- Public page language: Swahili, English, or a toggle
- Whether staff can only see and edit their own branch
- Thermal sticker printer support (Xprinter, Zebra) versus A4 sheets only
