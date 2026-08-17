# X Ads Mini Composer — Multi-account Session Edition

This build supports multiple X Ads accounts while keeping the existing `OAuth2Session`/browser-cookie authentication flow.

## Authentication model

- `bearer` remains global in `config.php`.
- Every row in the `user` table stores its own X browser Cookie in `x_cookie`.
- `ct0` is extracted automatically from the selected account's Cookie.
- The frontend never receives the Cookie value.
- Switching the account selector changes the Cookie/session used for Media Library, Cards, previews and Scheduled Tweets.

## Upgrade an existing database

Run once:

```sql
ALTER TABLE `user`
    ADD COLUMN `x_cookie` TEXT NULL AFTER `user_id`;
```

The same statement is included in:

```text
migration_multi_account_cookie.sql
```

Then open **Admin** and edit each account. Paste that account's complete X Cookie header.

When editing an account later, leave the Cookie field blank to keep the existing value.

## Add another account

Open `/admin` and enter:

- Account ID
- User ID
- X Cookie

The Cookie should be copied from a currently authenticated request to `ads-api.x.com` and must contain at least `ct0` plus the X session credentials required by the browser session.

The account table only shows whether a Cookie is configured. The Cookie itself is never returned by the account CRUD API.

## Running with Apache

Example:

```apache
<VirtualHost *:8895>
    ServerName x.local
    DocumentRoot /var/www/x-ads-mini-composer

    <Directory /var/www/x-ads-mini-composer>
        Options FollowSymLinks
        AllowOverride All
        DirectoryIndex router.php index.php index.html
        Require all granted
    </Directory>
</VirtualHost>
```

Ensure Apache listens on `8895`, then visit:

```text
http://x.local:8895/
```

## Security

The `x_cookie` value is an authenticated X session credential. Do not expose it in logs, Git, frontend JSON, database dumps, screenshots, or public backups.

If an X session expires, edit only that account in Admin and paste a fresh Cookie. Other accounts remain unaffected.
