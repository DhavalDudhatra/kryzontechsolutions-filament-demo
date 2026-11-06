# Filament + React Demo

Laravel 13 admin & storefront demo — **Filament v5** for the admin panel, **React 19 + Inertia v3** for the public site, backed by **MySQL 8.4** via Laravel Sail.

## Features

- Public storefront (Inertia/React): home (`/`) with latest 4 active products, product listing (`/products`) paginated (24/page).
- Admin panel (Filament): Products, Categories, Orders (+ Order Items), Users, Roles, Permissions, Email Templates, Settings.
- Orders: `OrderService` with transactions, stock commit on `processing`/`completed`, status transitions, totals & PDF download, order confirmation email from DB template.
- Products: image upload, category relation, stock + low-stock threshold, soft deletes, Excel import (`ProductsImport`) / export (`ProductExporter`).
- Auth & security: Spatie Permissions (`super_admin`, `user` roles, `App\Enums\Permission`), Google 2FA (`pragmarx/google2fa`) + email 2FA, passkeys, profile & settings pages.
- Email: DB-driven templates via `EmailService` (`sendTemplate` / `queueTemplate`), queue + cache on database driver.
- Dashboard widgets: stats overview, product status pie chart, product chart, date-range filters.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP ^8.3 (Sail runtime 8.5), Laravel ^13.17 |
| Admin | Filament ^5.0, Spatie Laravel Permission ^8.3 |
| Frontend | React ^19.3, Inertia React ^3.9, Vite ^8.0, Tailwind CSS ^4.3, Radix UI, Lucide |
| Typed routes | Laravel Wayfinder ^0.1.21 |
| Data | MySQL 8.4, database queue/cache/session |
| Quality | Pest ^5.3, Larastan ^3.13, Laravel Pint ^1.27 |

## Prerequisites

- Docker + Docker Compose (for Sail), or PHP 8.3+, Composer 2, Node 20+, MySQL 8 locally.
- Ports: `80` (app, `APP_PORT`), `5173` (Vite), `3306` (MySQL), `8025` (Mailpit), `8081` (phpMyAdmin).

## Installation

```bash
# 1. Clone and enter project
git clone <repo-url> filament_react_demo && cd filament_react_demo

# 2. Environment
cp .env.example .env

# 3. Start Sail containers (MySQL, Mailpit, phpMyAdmin)
vendor/bin/sail up -d

# 4. Install dependencies, migrate & seed, link storage, build assets
vendor/bin/sail composer setup
# — or step-by-step:
# vendor/bin/sail composer install
# vendor/bin/sail php artisan key:generate
# vendor/bin/sail php artisan migrate --seed
# vendor/bin/sail php artisan storage:link
# vendor/bin/sail npm install && vendor/bin/sail npm run build

# 5. Run dev servers (Laravel + Vite + queue + logs)
vendor/bin/sail composer dev
# — equivalent: vendor/bin/sail php artisan dev
# Manual: vendor/bin/sail php artisan serve / npm run dev / queue:listen / pail
```

No Sail? Run the same commands without the `vendor/bin/sail` prefix against a local MySQL configured in `.env` (`DB_CONNECTION=mysql`, `DB_DATABASE=filament_react_demo` by default).

Open http://127.0.0.1:8000 — verify with `vendor/bin/sail ps` and `vendor/bin/sail logs -f`.

## Configuration

Key `.env` values (see `.env.example`):

```ini
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=filament_react_demo
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
MAIL_MAILER=log
```

- Change `APP_NAME` to brand the app; `APP_DATE_FORMAT` / `APP_DATE_TIME_FORMAT` control displayed dates.
- For real mail, set `MAIL_MAILER=smtp` + host/credentials — or view intercepted mail at http://127.0.0.1:8025 (Mailpit).
- Admin DB UI: http://127.0.0.1:8081 (phpMyAdmin, host `mysql`).

## Default Accounts (from seeders)

Seeded by `DatabaseSeeder` (`RolePermissionSeeder`, `UserSeeder`, `SettingsSeeder`, `TwoFactorSettingsSeeder`, `EmailTemplateSeeder`) on first `migrate --seed`:

| Role | Email | Password |
|---|---|---|
| Super Admin (`super_admin`) | `admin@gmail.com` | `12345678` |
| User (`user`) | `user@gmail.com` | `12345678` |

> Change these immediately after first login. Do not commit real credentials.

## Usage

| URL | Description |
|---|---|
| `GET /` | Home — latest active products (`HomeController@index`) |
| `GET /products` | Product catalog, paginated (`ProductController@index`) |
| `/admin` | Filament admin — login with admin account above |
| `/admin/dashboard`, `/admin/profile`, `/admin/settings` | Dashboard widgets, 2FA setup, app settings |
| `/admin/products`, `/admin/categories`, `/admin/orders`, `/admin/users`, `/admin/roles`, `/admin/permissions`, `/admin/email-templates` | CRUD resources with tables, forms, infolists |

Order statuses: `pending → processing → completed`, any → `cancelled` (see `App\Enums\OrderStatus`). Only `processing`/`completed` commit stock. Payment: `unpaid | paid | refunded`.

## Project Structure

```
app/
├── Filament/          # Resources (Products, Categories, Orders, Users, Roles, Permissions, EmailTemplates),
│                      # Pages (Dashboard, Profile, Settings), Widgets, Auth (2FA), Actions, Exports
├── Http/Controllers/  # HomeController, ProductController
├── Models/            # User, Product, Category, Order, OrderItem, Setting, EmailTemplate
├── Services/          # OrderService (stock/totals), EmailService (DB templates)
├── Enums/             # OrderStatus, PaymentStatus, Permission, CustomIcon
├── Imports/           # ProductsImport (Excel)
└── Providers/         # App / Filament / Icon service providers
resources/
├── js/                # React pages, layouts, components, hooks, Wayfinder actions/routes
└── css/               # app.css, filament/admin/theme.css
routes/web.php         # `/` (home), `/products` (products.index)
database/              # migrations, seeders, factories
tests/                 # Pest tests (Feature/, Unit/)
docs/implementation-plan.md
compose.yaml           # Sail: app (PHP 8.5), mysql 8.4, mailpit, phpmyadmin
```

## Scripts & Checks

```bash
vendor/bin/sail npm run dev          # Vite dev server
vendor/bin/sail npm run build        # Production build
vendor/bin/sail npm run build:ssr    # Build + SSR
vendor/bin/sail composer lint        # Pint (format)
vendor/bin/sail composer types:check # PHPStan
vendor/bin/sail composer test        # Pint + PHPStan + Pest
vendor/bin/sail artisan test --compact  # Tests only
vendor/bin/sail bin pint --dirty --format agent  # Fix formatting after PHP changes
```

## Data Model

- `users` (2FA fields, avatar, `created_by`), `products` (category FK, stock, images, soft deletes), `categories`, `orders` (`order_number`, totals, statuses), `order_items` (snapshot price/name, committed qty), `settings`, `email_templates` (`template_key`, cc/bcc), plus Spatie permission, import/export, notification, cache, job, passkey tables.

## Troubleshooting

- **Vite manifest missing** → `vendor/bin/sail npm run build` or keep `npm run dev` running; check `vendor/bin/sail logs`.
- **DB connection refused** → `vendor/bin/sail up -d`, confirm `.env` DB vars, check `vendor/bin/sail ps`.
- **Queue/cache not updating** → `vendor/bin/sail php artisan queue:listen` / `php artisan cache:clear`.

## License

MIT — based on the [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) skeleton.
