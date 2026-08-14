# X Ads Mini Composer

A tiny local UI for publishing X Ads Tweets directly, with Media Library selection.

## Requirements

- PHP 8+
- PHP cURL extension
- An authenticated X / X Ads browser session that can already call the Ads API

Check:

```bash
php -v
php -m | grep curl
```

If cURL is missing on Ubuntu:

```bash
sudo apt install php-curl
```

## 1. Configure your X Ads values

Open `config.php`.

`account_id`, `user_id`, `bearer`, and `ct0` are filled from the browser-console script values.

The optional `cookie` field can stay empty:

```php
'cookie' => '',
```

The app sends X Ads requests using:

```text
Authorization: Bearer ...
X-CSRF-Token: ct0
X-Twitter-Auth-Type: OAuth2Session
```

If X returns 401 / 403, paste the full `Cookie` request header as a fallback:

```php
'cookie' => 'auth_token=...; ct0=...; twid=...; ...',
```

To get it:

1. Log in to `https://ads.x.com/`.
2. Open DevTools → Network.
3. Trigger any request to `ads-api.x.com`.
4. Click the request.
5. Request Headers → `Cookie`.
6. Copy the **entire Cookie header value**.
7. Paste it into `config.php`.

The server can extract `ct0` from that cookie automatically, but the hard-coded `ct0` value takes priority.
If you accidentally paste the header label as `Cookie: auth_token=...`, the app strips `Cookie:` automatically.

> Keep `config.php` private. The Cookie value is your authenticated browser session.

## 2. Run

From this folder:

```bash
php -S 127.0.0.1:8888
```

Open:

```text
http://127.0.0.1:8888
```

## Features

- Schedule a Website Card Tweet about one minute ahead through the X Ads API
- Load/search X Ads Media Library
- Media pagination
- Select up to 4 images
- Select 1 GIF or 1 video
- Website URL input with browser autofill support
- Website Card mode uses one selected media item, matching the X Ads UI `MEDIA` component
- Website Card scheduled Tweets use `nullcast=true`, matching the X Ads UI flow
- Preview the last scheduled Tweet through X `tweet_previews`
- When a Website Card is used, media is attached to the card, so the Scheduled Tweet response may show `media_keys: []`.

## X endpoints used

```text
GET    /11/accounts/:account_id/media_library
GET    /11/accounts/:account_id/media_library/:media_key

POST   /11/accounts/:account_id/scheduled_tweets
POST   /11/accounts/:account_id/cards
GET    /11/accounts/:account_id/tweet_previews
```

## If you get 401 / 403

Your X browser session is probably stale or the Cookie header changed.

Copy a fresh Cookie header from a working `ads-api.x.com` request and replace the value in `config.php`.

If X changes the frontend Bearer later, update `bearer` in `config.php` from a current working request.

## Security

Run this only on localhost:

```bash
php -S 127.0.0.1:8888
```

Do not bind it to `0.0.0.0` and do not upload `config.php` to a public server or Git repository.
