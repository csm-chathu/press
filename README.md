# LMUC — POS & Business Management System

A multi-tenant Laravel 10 + Vue 3 POS platform for liquor bars, restaurants, and gold/jewelry shops. Each client runs on their own domain with a fully isolated database.

---

## Table of Contents

1. [Tech Stack](#tech-stack)
2. [First-Time Setup](#first-time-setup)
3. [Environment Variables](#environment-variables)
4. [Multi-Tenant Architecture](#multi-tenant-architecture)
5. [Creating a New Tenant](#creating-a-new-tenant)
6. [Default Credentials](#default-credentials)
7. [Useful Commands](#useful-commands)
8. [Frontend Development](#frontend-development)
9. [Deployment — Nginx](#deployment--nginx)

---

## Tech Stack

| Layer     | Technology                                        |
|-----------|---------------------------------------------------|
| Backend   | Laravel 10, Sanctum (Bearer token auth)           |
| Frontend  | Vue 3 Composition API, Vite, Tailwind CSS         |
| Database  | MySQL 8+ (one database per tenant)                |
| Images    | Cloudinary (products), local storage (logos)      |
| Charts    | vue-chartjs + Chart.js v3                         |
| Barcodes  | JsBarcode (CODE128, printed via popup)            |

---

## First-Time Setup

```bash
# 1. Clone
git clone https://github.com/csm-chathu/restaurant.git
cd restaurant

# 2. PHP dependencies
composer install

# 3. JS dependencies
npm install

# 4. Environment
cp .env.example .env
php artisan key:generate
# Edit .env — set DB_*, CLOUDINARY_*, TENANT_MASTER_KEY

# 5. Create the default database in MySQL, then migrate + seed demo data
php artisan migrate --seed

# 6. Link storage for uploaded logos
php artisan storage:link

# 7. Build frontend
npm run build

# 8. Start server
php artisan serve
```

Open `http://localhost` and log in with `admin@store.local` / `password`.

---

## Environment Variables

```dotenv
APP_NAME="My Shop Name"
APP_URL=http://localhost

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurant          # default (localhost) tenant database
DB_USERNAME=root
DB_PASSWORD=secret

# Cloudinary — product image uploads
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
CLOUDINARY_FOLDER=products
CLOUDINARY_VERIFY=true          # set false to skip SSL in local dev

# Multi-tenant — keep this secret, used to protect POST /api/tenants
TENANT_MASTER_KEY=change-this-secret-key
```

---

## Multi-Tenant Architecture

Every tenant has their own domain and their own MySQL database. The schema is identical across all databases — they all run the same migrations.

### How domain routing works

1. `ResolveTenantDatabase` middleware runs on **every request** (registered globally in `Kernel.php`).
2. It reads `$request->getHost()` and looks it up in `config/tenants.php`.
3. If found → switches the MySQL connection to that tenant's database.
4. If not found → returns `404` so no data leaks between tenants.

### `config/tenants.php`

```php
return [
    'localhost'           => 'restaurant',     // local dev
    'shop1.example.com'  => 'lmuc_shop1',
    'shop2.example.com'  => 'lmuc_shop2',
];
```

Add one entry per client. Always run `php artisan config:clear` after editing this file.

---

## Creating a New Tenant

### Option A — Artisan command (requires server SSH access)

```bash
php artisan tenant:create {domain} {database} [--password=secret]

# Example
php artisan tenant:create shop1.example.com lmuc_shop1 --password=SecurePass123

# Always clear config cache after
php artisan config:clear
```

The command:
1. Creates the MySQL database
2. Runs all migrations against it
3. Seeds chart of accounts + one admin user
4. Writes the domain entry into `config/tenants.php`
5. Prints the login credentials

---

### Option B — REST API endpoint

**`POST /api/tenants`**

Protected by the `X-Tenant-Key` header (must match `TENANT_MASTER_KEY` in `.env`).

#### Request

```http
POST /api/tenants
Content-Type: application/json
X-Tenant-Key: change-this-secret-key

{
    "domain":   "shop1.example.com",
    "database": "lmuc_shop1",
    "password": "SecurePass123"
}
```

| Field      | Required | Notes                                           |
|------------|----------|-------------------------------------------------|
| `domain`   | Yes      | Bare hostname — no `https://` or trailing slash |
| `database` | Yes      | MySQL db name — alphanumeric + underscores only |
| `password` | No       | Admin password, defaults to `password`          |

#### Response `201 Created`

```json
{
    "message":            "Tenant created successfully.",
    "domain":             "shop1.example.com",
    "database":           "lmuc_shop1",
    "login_email":        "admin@store.local",
    "login_password":     "SecurePass123",
    "already_registered": false
}
```

#### cURL example

```bash
curl -X POST https://yourserver.com/api/tenants \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: change-this-secret-key" \
  -d '{
    "domain":   "shop1.example.com",
    "database": "lmuc_shop1",
    "password": "SecurePass123"
  }'
```

> The API automatically updates `config/tenants.php` and calls `config:clear`. No manual step needed.

---

## Default Credentials

Seeded by `TenantSeeder` (fresh tenant — no demo data):

| Email                   | Password   | Role          | Can Delete |
|-------------------------|------------|---------------|:----------:|
| `owner@store.local`     | `password` | Owner         | ✓          |
| `admin@store.local`     | `password` | Admin         | ✓          |
| `manager@store.local`   | `password` | Manager       | ✗          |
| `cashier@store.local`   | `password` | Cashier       | ✗          |
| `keeper@store.local`    | `password` | Store Keeper  | ✗          |

> Change all passwords immediately after first login via Settings → Users.

`DatabaseSeeder` (used with `--seed`) additionally creates demo branches, users, products, suppliers, and customers — for development only.

---

## Useful Commands

```bash
# Run pending migrations
php artisan migrate

# Fresh schema + demo data (DESTROYS ALL DATA — dev only)
php artisan migrate:fresh --seed

# Fresh schema + login only (no demo data)
php artisan migrate:fresh
php artisan db:seed --class=TenantSeeder

# Create a production tenant
php artisan tenant:create shop1.example.com lmuc_shop1 --password=Secret123

# Clear config cache (required after editing config/tenants.php)
php artisan config:clear

# Clear everything
php artisan optimize:clear

# Run tests
php artisan test

# Code style (Laravel Pint)
./vendor/bin/pint
```

---

## Frontend Development

```bash
# Hot-reload dev server
npm run dev

# Production build
npm run build
```

- Pages: `resources/js/pages/`
- Shared components: `resources/js/components/`
- Auth state: Pinia store at `resources/js/stores/auth.js`
- Router: `resources/js/router.js`

All API calls use Axios with a Bearer token. All routes except `POST /api/login`,
`GET /api/public/settings`, and `POST /api/tenants` require `auth:sanctum`.

---

## Deployment — Nginx

One server block handles all tenant domains:

```nginx
server {
    listen 80;
    server_name ~^(.+)$;

    root /var/www/restaurant/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Post-deploy checklist:**
- [ ] Point all tenant domains DNS to the server
- [ ] Add each domain to `config/tenants.php` (or use `POST /api/tenants`)
- [ ] `php artisan storage:link`
- [ ] `php artisan config:clear && php artisan optimize`
- [ ] `storage/` and `bootstrap/cache/` are writable by the web server
