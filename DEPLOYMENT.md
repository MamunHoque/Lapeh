# Lapeh — Production Deployment & Handover

This document covers deploying the Laravel API + admin portal to a production
server, building the Flutter apps, and the client-owned accounts the system
depends on. For local development see [README.md](./README.md).

## 1. Server requirements

| Component | Requirement |
|-----------|-------------|
| OS | Ubuntu 22.04 LTS (or similar) |
| PHP | 8.3+ with `pdo_mysql`, `redis`, `mbstring`, `bcmath`, `gd` |
| MySQL | 8.x |
| Redis | 7.x (cache, queues, Reverb) |
| Web server | Nginx + PHP-FPM, HTTPS via Let's Encrypt |
| Process manager | Supervisor (queue worker + Reverb) |
| Cron | For Laravel scheduler |

## 2. Environment configuration

```bash
cp .env.example .env
php artisan key:generate
```

Set production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.your-domain.com
APP_TIMEZONE=Asia/Dubai

DB_DATABASE=lapeh
DB_USERNAME=...
DB_PASSWORD=...            # use a strong secret, not the dev password

QUEUE_CONNECTION=redis
CACHE_STORE=redis
BROADCAST_CONNECTION=reverb

# Client-owned third-party credentials (see §6)
GOOGLE_MAPS_API_KEY=...
# FCM HTTP v1 — path to the service-account JSON + project id (NOT the legacy server key)
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=lapeh-51e34
PAYMENT_GATEWAY=...
PAYMENT_KEY=...
PAYMENT_SECRET=...
PAYMENT_WEBHOOK_SECRET=...  # REQUIRED — webhook endpoint rejects requests without a valid HMAC
SMS_PROVIDER=...            # 'log' only logs; switch to the live provider for production
```

> **Important:** `PAYMENT_WEBHOOK_SECRET` must be set in production. The
> `/api/webhooks/payment` endpoint verifies an `X-Signature` HMAC-SHA256 header
> against the raw request body and returns `500` if the secret is missing,
> `401` if the signature is invalid.

## 3. Deploy steps

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force      # first deploy only (admin user, pricing, zones, SMS templates)

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link         # serve uploaded complaint/proof images
```

Point the Nginx document root at `lapeh-api/public`.

## 4. Background processes

**Queue worker** (notifications, dispatch jobs) — Supervisor program:

```ini
[program:lapeh-queue]
command=php /var/www/lapeh-api/artisan queue:work --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/lapeh-queue.log
```

**Reverb WebSocket server** (live order/driver tracking) — Supervisor program:

```ini
[program:lapeh-reverb]
command=php /var/www/lapeh-api/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
```

Proxy `wss://` traffic to port 8080 via Nginx. Update the Reverb `REVERB_HOST`,
`REVERB_SCHEME=https`, and `REVERB_PORT=443` env values accordingly.

**Scheduler** — add to the deploy user's crontab:

```cron
* * * * * cd /var/www/lapeh-api && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Flutter app builds

Before building, point the app at the production API in
`lapeh_app/lib/core/constants.dart`:

```dart
const baseUrl = 'https://api.your-domain.com/api';
```

```bash
cd lapeh_app
flutter pub get
flutter build apk --release          # Android → build/app/outputs/flutter-apk/
flutter build appbundle --release    # Android Play Store
flutter build ipa --release          # iOS (requires macOS + Apple Developer account)
```

iOS push requires `GoogleService-Info.plist` from the client's Firebase console
placed in `lapeh_app/ios/Runner/`.

## 6. Client-owned accounts checklist

All third-party accounts must be registered under the client's own credentials.

- [ ] **Google Cloud** — Maps SDK (Android/iOS), Directions, Distance Matrix,
      Geocoding API keys → `GOOGLE_MAPS_API_KEY` + `AndroidManifest.xml` +
      `AppDelegate.swift`
- [ ] **Firebase** — Android + iOS apps + client configs `google-services.json`
      (`android/app/`) and `GoogleService-Info.plist` (`ios/Runner/`). For
      backend push, generate a **service-account JSON** (Project Settings →
      Service accounts → Generate new private key) and place it at
      `storage/app/firebase/service-account.json` (git-ignored) →
      `FIREBASE_CREDENTIALS`. The legacy FCM server key is **not** used (Google
      retired it June 2024); the backend uses the HTTP v1 API.
- [ ] **Payment gateway** — merchant account → `PAYMENT_KEY`, `PAYMENT_SECRET`,
      `PAYMENT_WEBHOOK_SECRET`; register webhook URL `https://api.your-domain.com/api/webhooks/payment`
- [ ] **SMS / WhatsApp provider** (Twilio or UAE provider) → credentials,
      set `SMS_PROVIDER`
- [ ] **Apple Developer** + **Google Play Console** (store release)
- [ ] **Production server** (PHP 8.3, MySQL 8, Redis, HTTPS domain)

## 7. Security notes

- Rate limiting is enforced via `throttle` middleware: login `10/min`,
  driver registration `5/min`, customer token routes `30/min`, driver location
  push `120/min`, payment webhook `60/min`.
- Every API resource is authorized by ownership/role (Sanctum + `role`
  middleware); restaurant/driver controllers verify `restaurant_id`/`driver_id`
  on each order before acting.
- Customer links use a unique unguessable `location_token`; the rate limit
  guards against enumeration.
- Rotate `APP_KEY`, DB password, and all third-party secrets from their dev
  values before going live.

## 8. Handover

- Source: private Git repo (Laravel API, admin portal, Flutter app, DB migrations/seeders).
- `.env.example` documents every required key.
- Reference data (rating tags, complaint types) lives in `config/lapeh.php` and
  is served at `GET /api/meta`; SMS templates (en/ar) are seeded and editable in
  the admin portal.
