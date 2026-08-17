# X Ads Mini Composer

Small PHP application for creating scheduled X Ads Website Card posts.

## Requirements

- PHP 8.1+
- PHP extensions: `curl`, `pdo_mysql`, `mbstring`
- MySQL or MariaDB
- Composer 2 for local installation
- HTTPS when deployed to hosting
- A working authenticated X Ads browser session

## Local database

The local database is `if0_42654253_x_ads`. It contains:

- `admin_user` for application login and roles.
- `user` for X Ads account/user mappings.

The seeded application login is username `admin`. Its initial password is the
private value supplied during installation. The database stores only a
`password_hash()` result, never Base64 or plaintext.

The X Ads mapping table contains:

```text
entity_id | account_id | user_id
```

The included account is:

```text
1 | 18ce55nu7l7 | 1855582736
```

To rebuild the table, select `if0_42654253_x_ads` in phpMyAdmin and import
`database.sql`.

Add another X Ads account with:

```sql
INSERT INTO `user` (`account_id`, `user_id`)
VALUES ('ACCOUNT_ID', 'USER_ID');
```

The browser sends only `entity_id`. For every media, card, or schedule request,
`api.php` resolves `account_id` and `user_id` from MySQL.

## Configuration

Edit `config.php`:

```php
'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'if0_42654253_x_ads',
    'username' => 'admin',
    'password' => 'admin',
    'charset' => 'utf8mb4',
],
```

On cPanel, replace the database username and password with the MySQL user
assigned to `if0_42654253_x_ads`. Some hosts require `localhost` instead of
`127.0.0.1`.

Keep the X `bearer` and complete Cookie request header in `config.php`.
The application extracts `ct0` directly from that Cookie.
If X returns 401 or 403, copy a fresh Cookie header from a working
`ads-api.x.com` browser request.

## Run locally

```bash
composer install --no-dev --optimize-autoloader
php -S 127.0.0.1:8888 router.php
```

Open `http://127.0.0.1:8888`.

Application pages:

```text
/login  Shared login
/       Authenticated Composer frontend
/admin  Administrator-only X Ads account management
```

## Deploy with cPanel, phpMyAdmin and FTP

1. Create `if0_42654253_x_ads` in cPanel MySQL Databases.
2. Create a MySQL user and grant it all privileges on that database.
3. Select the database in phpMyAdmin and import `database.sql`.
4. Update the database credentials in `config.php`.
5. Run Composer locally and upload the whole project, including `vendor`,
   `composer.json`, and `composer.lock`, into `htdocs` using FTP.
6. Enable HTTPS.
7. Confirm that Apache `mod_rewrite` and PHP sessions are enabled.
8. Sign in and verify both `/` and `/admin` before using the X API.

No cron is needed. X executes the scheduled post.

## Current behavior

- Frontend, admin, `api.php`, and CRUD routes all require a valid PHP session.
- Admin routes require an `admin_user` row with role `admin`.
- State-changing requests require a per-session CSRF token.
- Five failed logins lock that account for 15 minutes.
- Sessions expire after eight hours of inactivity.
- FlightPHP Core routes REST CRUD for accounts.
- `GET /api/users` lists accounts.
- `POST /api/users` creates an account.
- `GET /api/users/:id` reads one account.
- `PUT /api/users/:id` updates an account.
- `DELETE /api/users/:id` deletes an account; the last account is protected.
- Accounts are loaded from MySQL and selectable from the header.
- Media Library is loaded for the selected account.
- Website Card uses one selected media item.
- Scheduled posts use `nullcast=false`.
- Empty schedule input falls back to approximately one minute ahead.
- Custom schedule input is interpreted as Asia/Ho_Chi_Minh and converted once
  to UTC for X.
- Post text is generated as `Wataa 👅 BASE62_ID`.

## Security

`config.php` contains an authenticated X session. Do not commit it or share it.
The included `.htaccess` blocks direct web access to configuration and SQL files,
and the application enforces login on both pages and APIs. Use HTTPS in production
so credentials and session cookies are encrypted in transit.
