# AI Sales Girl — PHP + Hostinger MySQL

PHP onboarding and SaaS backend using **Hostinger MySQL**, **Google OAuth**, and **passwordless email OTP** (Resend). No Supabase.

## Requirements

- PHP 8.2+ with `pdo_mysql`, `curl`, `json`
- Hostinger (or any) MySQL database
- Google Cloud OAuth client
- Resend account with verified domain

## Setup

### 1. Database (phpMyAdmin)

1. hPanel → **Databases** → **MySQL Databases** → create database, user, password.
2. Open **phpMyAdmin** → select your database → **SQL**.
3. Paste and run the full script: [`database/mysql_schema.sql`](database/mysql_schema.sql)

This creates all tables: `users`, `email_otps`, `oauth_states`, `businesses`, `business_settings`, `products`, `offerings`, `connected_accounts`, `customers`, `conversations`, `messages`, `orders`, `order_items`, `payments`, `owner_alerts`, `handoffs`.

### 2. Environment

```bash
cp .env.example .env
```

Fill in `APP_URL`, MySQL credentials, Google OAuth, and Resend.

On Hostinger, `DB_HOST` is usually `localhost`. Use the exact database name and username from hPanel (often prefixed, e.g. `u123456789_ai_salesgirl`).

### 3. Google OAuth

1. [Google Cloud Console](https://console.cloud.google.com/) → Credentials → OAuth 2.0 Client ID (Web application).
2. **Authorized redirect URI:** `https://yourdomain.com/auth/callback.php`
3. Copy Client ID and Secret into `.env`.

### 4. Deploy

Upload all files to `public_html` (or your web root). Ensure `.env` is **not** publicly downloadable (keep it outside `public_html` if possible, or protect via `.htaccess`).

### 5. Local development

```bash
php -S localhost:8080 -t .
```

Set `APP_URL=http://localhost:8080` in `.env`.

## Routes

| Path | Purpose |
|------|---------|
| `/index.php` | Marketing homepage |
| `/login.php` | Sign in (Google or email OTP) |
| `/auth/google.php` | Start Google OAuth |
| `/auth/callback.php` | Google OAuth callback |
| `/auth/send-otp.php` | Send OTP email (Resend) |
| `/auth/verify-otp.php` | Verify OTP → session |
| `/auth/logout.php` | Sign out |
| `/app.php` | Onboarding router (authenticated) |

## Architecture

- **PDO → MySQL** via `lib/Database.php`
- **UUIDs** generated in PHP (`lib/Uuid.php`) for all primary keys
- **Sessions** store `users.id` (no external auth provider)
- **Repositories** in `lib/repositories/` — one class per table, wired through `RepositoryFactory`

## IDs in PHP

```php
$id = Uuid::v4(); // e.g. 550e8400-e29b-41d4-a716-446655440000
```

Use this on every `INSERT` into tables with `CHAR(36)` primary keys.
