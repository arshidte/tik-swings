# Swing & Grain — Premium Wooden Swing E-commerce

A production-ready storefront for a brand selling handcrafted wooden swings, built with
**CodeIgniter 4 · PHP 8.2 · MySQL 8+ · Tailwind CSS · vanilla JS**. Server-rendered
storefront with progressive AJAX enhancement (cart, wishlist, search, filters). No SPA.

> *Make room for moments. Make room for a swing.*

---

## Stack

| Layer | Choice |
|-------|--------|
| Framework | CodeIgniter 4.7 (MVC + Services + Filters) |
| Database | MySQL 8+/9 (normalized schema, FKs, indexes) |
| CSS | Tailwind CSS 3 (design tokens via CSS variables) |
| JS | Vanilla ES modules (Fetch, IntersectionObserver) — no framework |
| Payments | `PaymentServiceInterface` abstraction (COD built-in, Razorpay drop-in) |

## Requirements

- PHP 8.2+ with `intl`, `mysqli`, `mbstring`, `gd`, `curl`, `zip`
- MySQL 8+ (tested on 9.6)
- Composer, Node.js 18+ / npm

## Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure environment
cp env .env          # then edit DB credentials + app.baseURL
php spark key:generate

# 3. Create the database, then run migrations + seeders
mysql -uroot -e "CREATE DATABASE swing_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php spark migrate
php spark db:seed DatabaseSeeder

# 4. Build CSS (watch mode for dev)
npm run dev          # or: npm run build  (minified, for production)

# 5. Serve
php spark serve
```

Visit `http://localhost:8080`.

### Demo accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@swingandgrain.in` | `admin1234` |
| Customer | `customer@example.com` | `password` |

Admin panel: `/admin`

## Project structure

```
app/
  Controllers/{Frontend,Auth,Account,Cart,Checkout,Admin}/
  Models/            # one per table, with query methods
  Services/          # CartService, OrderService, ImageUploadService (business logic)
  Libraries/Payment/ # PaymentServiceInterface + Cod/Razorpay + PaymentManager
  Filters/           # AuthFilter, AdminFilter
  Helpers/           # format_helper (price, ratings, images), store_helper (settings, auth)
  Database/{Migrations,Seeds}/
  Views/{layouts,components,frontend,auth,account,cart,checkout,admin}/
public/assets/{css,js,images}/   # compiled CSS, ES modules, SVG placeholders
resources/css/app.css            # Tailwind source + design tokens
```

## Key design decisions

- **Server-side truth (§40):** prices, stock, coupons and totals are always re-resolved
  from the DB in `CartService` / `OrderService`; nothing money-related is trusted from the client.
- **Order snapshots (§32):** `order_items` copy product name/variant/price so historical
  orders never change when a product is later edited.
- **Payment abstraction (§31):** checkout depends only on `PaymentServiceInterface`.
  COD works out of the box; add Razorpay keys in Admin → Settings to enable online payments.
- **CSRF everywhere (§63):** enabled globally; AJAX sends the token via header and each JSON
  response returns a fresh token. Only the gateway callback is exempt (verified by signature).
- **Images (§37, §52):** uploads are validated (MIME/ext/size/dimensions), re-encoded to WebP
  and resized to large/medium/thumbnail with safe random filenames. All image paths flow through
  `product_image()` so real photography can replace the seeded SVG placeholders without touching templates.

## Payments

COD is always available. To enable Razorpay, set in **Admin → Settings** (or env):
`RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`. The checkout will then offer online payment and
verify the callback signature server-side.

## Production notes

- Set `CI_ENVIRONMENT = production` in `.env`.
- Run `npm run build` (minified CSS).
- Serve `public/` as the web root; enable gzip/Brotli and caching for `/assets`.
- `sitemap.xml` and `robots.txt` are generated dynamically.
