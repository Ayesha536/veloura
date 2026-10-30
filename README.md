# VELOURA — Premium Women's Accessories E-Commerce

A complete, luxurious e-commerce website built with **Core PHP (no framework), MySQL, PDO, HTML, CSS, JavaScript and Bootstrap 5**. Soft lavender & silver theme, white cards, elegant typography and subtle animations — fully responsive on mobile, tablet and desktop.

---

## ✨ Features

### Storefront (customer side)
- **Homepage** — DB-driven hero banner carousel, USP strip, category grid, featured products, promo band, new arrivals, testimonials, working newsletter subscription
- **Shop page** — search, category filter, price-range filter, on-sale & in-stock filters, 5 sort orders, pagination
- **Product details** — image gallery, stock status, quantity selector, related products, description tabs, wishlist & buy-now
- **Shopping cart** — AJAX add/update/remove with stock clamping, free-shipping threshold, works for guests (session-based)
- **Wishlist** — database-backed, requires login, AJAX toggle from any product card
- **Checkout** — login required, server-side validation, **atomic order placement in a DB transaction** with row-locked stock checks and automatic stock decrement
- **Order history & details** — status timeline, addresses, payment info
- **About, Contact (stores messages in DB), FAQ** pages — all content editable from the admin panel
- **Authentication** — signup, login, logout with bcrypt password hashing, session hardening, login throttling

### Admin panel (`/admin`) — separate secure login, no public registration
- **Dashboard** — revenue/orders/products/customers stat cards, 7-day revenue chart (Chart.js), order status breakdown, recent orders, low-stock alerts
- **Products** — full CRUD with image upload, featured/visibility toggles, search, category filter, pagination
- **Categories** — full CRUD with image upload, guarded deletion when products exist
- **Orders** — search, status filters, order view with **status & payment-status management**
- **Customers** — list with order counts & lifetime spend, search, block/unblock accounts
- **Banners** — CRUD for homepage hero slides with image upload, ordering, active toggles
- **FAQs** — CRUD with publish toggles
- **Site Content** — edit announcement bar, footer text, about text, contact info, shipping fees, social links
- **Messages** — contact-form inbox with read/unread, modal view, delete

### Security (implemented, not decorative)
- **PDO prepared statements everywhere** (no string-interpolated SQL)
- **`password_hash()` / `password_verify()`** bcrypt hashing
- **CSRF tokens** on every POST form and AJAX call
- **Server-side validation** on all forms (never trusting client input)
- **XSS escaping** via `e()` (htmlspecialchars) on all output
- **Session hardening** — httponly cookies, `session_regenerate_id()` on login, per-action throttling
- **Role-based authorization** — `require_admin()` guards every admin page; customers can never reach admin functions; **no public admin registration** (admin accounts only via the database)
- **Stock integrity** — `SELECT ... FOR UPDATE` row locks + conditional checks inside transactions
- **File upload validation** — MIME/size checks, randomized filenames

---

## 📋 Requirements

| Software | Version |
|---|---|
| XAMPP (Apache + MySQL) | any recent (PHP 7.4+ / 8.x) |
| Browser | Chrome, Edge, Firefox, Safari |

---

## 🚀 Installation with XAMPP (step by step)

### 1. Install & start XAMPP
1. Install [XAMPP](https://www.apachefriends.org/) in the default location (`C:\xampp` on Windows).
2. Open the **XAMPP Control Panel**.
3. Click **Start** next to **Apache** and **Start** next to **MySQL**. Both should turn green.

### 2. Copy the project
1. Copy the whole **`veloura`** folder into XAMPP's web root:
   - **Windows:** `C:\xampp\htdocs\veloura`
   - **macOS:** `/Applications/XAMPP/htdocs/veloura`
   - **Linux:** `/opt/lampp/htdocs/veloura`
2. Final path should look like: `C:\xampp\htdocs\veloura\index.php` ✔

### 3. Create the database
**Option A — phpMyAdmin (easiest)**
1. Open `http://localhost/phpmyadmin` in your browser.
2. Click **Import** in the top menu.
3. Choose the file `veloura/database/veloura.sql` from the project.
4. Click **Import** (leave all defaults). You should see *"Import has been successfully finished"* and a `veloura` database with **11 tables** filled with sample data.

**Option B — command line**
```bash
cd C:\xampp\mysql\bin
mysql -u root < C:\xampp\htdocs\veloura\database\veloura.sql
```

### 4. Check configuration (usually nothing to change)
Open `config/config.php` and confirm the credentials match your XAMPP:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'veloura');
define('DB_USER', 'root');
define('DB_PASS', '');        // XAMPP default is empty
```

### 5. Open the website
| What | URL |
|---|---|
| **Storefront** | http://localhost/veloura/ |
| **Admin panel** | http://localhost/veloura/admin/ |

> 💡 Internet is required on first load for CDN assets (Bootstrap, fonts, Chart.js). Everything else is local.

---

## 🔑 Default accounts

| Role | Email | Password | Notes |
|---|---|---|---|
| **Administrator** | `admin@veloura.com` | `Admin@123` | Change after first login |
| **Demo customer** | `emma@example.com` | `User@123` | Has 4 sample orders, wishlist, history |

> Admin accounts **cannot** be created from the public site — signup always creates a `customer` role. Create additional admins directly in the `users` table via phpMyAdmin (set `role` to `admin`).

---

## 🗂 Project structure

```
veloura/
├── config/
│   └── config.php          # DB credentials, session & app settings
├── includes/
│   ├── db.php              # PDO singleton connection
│   ├── functions.php       # helpers: escaping, CSRF, cart, settings, uploads…
│   ├── auth.php            # login/session/role management & throttling
│   ├── header.php          # storefront header + navbar
│   ├── footer.php          # storefront footer + newsletter
│   ├── product_card.php    # reusable product card component
│   ├── admin_header.php    # admin layout chrome
│   └── admin_footer.php
├── index.php               # homepage
├── shop.php                # shop with filters/search/sort/pagination
├── product.php             # product details
├── cart.php                # shopping cart
├── wishlist.php            # wishlist (login required)
├── checkout.php            # checkout (login required, transactional)
├── orders.php              # customer order history
├── order_details.php       # single order with status timeline
├── about.php / contact.php / faq.php
├── login.php / signup.php / logout.php
├── ajax/
│   ├── cart.php            # cart operations (JSON)
│   ├── wishlist.php        # wishlist toggle (JSON)
│   └── subscribe.php       # newsletter (JSON)
├── admin/
│   ├── login.php           # separate admin login (role-checked)
│   ├── index.php           # dashboard + Chart.js
│   ├── products.php        # list + delete
│   ├── product_form.php    # create/edit with image upload
│   ├── categories.php      # CRUD
│   ├── orders.php          # list + filters
│   ├── order_view.php      # status/payment management
│   ├── customers.php       # list + block/unblock
│   ├── banners.php         # homepage hero CRUD
│   ├── faqs.php            # FAQ CRUD
│   ├── content.php         # site settings editor
│   └── messages.php        # contact inbox
├── assets/
│   ├── css/style.css       # lavender & silver theme
│   ├── css/admin.css       # admin theme
│   ├── js/main.js          # storefront JS (AJAX cart/wishlist…)
│   ├── js/admin.js         # admin JS
│   └── images/             # product, banner & misc images
└── database/
    └── veloura.sql         # full schema + sample data (import this)
```

---

## 🧪 Quick test checklist for demos

1. Browse the homepage — carousel, categories, featured products.
2. Shop → filter by category, search "pearl", sort by price.
3. Open a product → add to cart (badge updates), add to wishlist (prompts login).
4. Sign in as `emma@example.com / User@123`.
5. Wishlist the item again → succeeds; go to cart → change quantity (AJAX updates).
6. Checkout → fill the form → place order (COD or demo card `4242 4242 4242 4242`, any future `MM/YY`, any CVV).
7. See the order confirmation → check **My Orders** → status timeline.
8. Sign in to **admin** as `admin@veloura.com / Admin@123`:
   - Dashboard shows your new revenue.
   - **Orders** → open the new order → set status *Processing* → customer sees the timeline advance.
   - **Products** → add a product with an image → appears in the shop.
   - **Banners / FAQs / Site Content** → edit anything → refresh the storefront.
9. Try the contact form on the storefront → message appears in **admin → Messages**.

---

## 🔧 Troubleshooting

| Problem | Solution |
|---|---|
| *"Database connection failed"* page | MySQL not started in XAMPP Control Panel, or `veloura` DB not imported yet (step 3). |
| `Access denied for user 'root'` | Set the correct `DB_PASS` in `config/config.php`. |
| Pages look unstyled | You opened the file directly from disk (`file://`). Always browse via `http://localhost/veloura/`. |
| Images missing after uploading | Ensure `assets/images/products/` is writable (usually automatic on XAMPP). |
| Admin login rejected | Use exactly `admin@veloura.com` / `Admin@123`; after 5 failed attempts the form locks for 5 minutes. |
| Port 80 already in use | In XAMPP Config → `httpd.conf`, change `Listen 80` to `Listen 8080`, then use `http://localhost:8080/veloura/`. |

---

## 📝 Notes

- Payments are a **demo**: COD and a simulated card form (validated, never stored or charged) — no real gateway is configured.
- The storefront runs on **session carts** for guests; the wishlist and orders are database-backed.
- To reset all demo data, re-import `database/veloura.sql` (it drops and recreates all tables).
