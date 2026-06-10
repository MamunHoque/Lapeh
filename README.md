# Lapeh

Delivery dispatch platform: Laravel API + admin portal, and a Flutter app for restaurants and drivers.

For production deployment, background workers, and the client-owned accounts checklist, see [DEPLOYMENT.md](./DEPLOYMENT.md).

## Prerequisites

| Tool | Version | Notes |
|------|---------|-------|
| PHP | 8.3+ | With `pdo_mysql`, `redis` extensions |
| Composer | 2.x | |
| MySQL | 8.x | Database named `lapeh` (see `.env`) |
| Redis | 7.x | Required for cache, queues, and Reverb |
| Node.js | 20.19+ | Optional; only needed for Vite asset hot-reload |
| Flutter | 3.3+ | For `lapeh_app` |

On macOS with [Laravel Herd](https://herd.laravel.com), PHP and Redis are usually already available.

## First-time setup

```bash
# API + admin portal
cd lapeh-api
cp .env.example .env   # skip if .env already exists
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed

# Flutter app
cd ../lapeh_app
flutter pub get
```

Ensure MySQL is running and `lapeh-api/.env` has the correct `DB_*` credentials. Test credentials and demo flows are in [TEST_CREDENTIALS.md](./TEST_CREDENTIALS.md).

## Quick start (admin + app)

From the repo root:

```bash
chmod +x start.sh   # first time only
./start.sh
```

This starts:

| Service | URL |
|---------|-----|
| Admin portal | http://localhost:8000/admin/login |
| API | http://localhost:8000/api |
| Reverb (WebSockets) | http://localhost:8080 |
| Flutter app | Chrome (debug mode) |

Options:

```bash
./start.sh --device macos    # run Flutter on macOS instead of Chrome
./start.sh --api-only        # API + admin only, no Flutter app
./start.sh --no-open         # skip opening the admin login page in a browser
```

Press `Ctrl+C` to stop everything.

## Manual start

**Terminal 1 — API, admin, queue, and WebSockets:**

```bash
cd lapeh-api
npx concurrently \
  "php artisan serve" \
  "php artisan queue:listen --tries=1 --timeout=0" \
  "php artisan reverb:start" \
  --names server,queue,reverb --kill-others
```

**Terminal 2 — Flutter app:**

```bash
cd lapeh_app
flutter run -d chrome
```

If Redis is not running:

```bash
redis-cli ping || redis-server --daemonize yes
```

## Project layout

```
lapeh/
├── lapeh-api/       Laravel API, admin portal, customer tracking pages
├── lapeh_app/       Flutter app (restaurant + driver roles)
├── TEST_CREDENTIALS.md
├── DEPLOYMENT.md    Production deployment & handover
└── start.sh         Start admin + app together
```

## Troubleshooting

**`Table 'lapeh.sessions' doesn't exist`** — run migrations:

```bash
cd lapeh-api && php artisan migrate
```

**Redis connection refused** — start Redis (`redis-server --daemonize yes` or via Herd).

**`composer dev` / Vite fails** — the admin portal works without Vite in dev. Use `./start.sh` or the manual commands above. To fix Vite, upgrade Node to 20.19+ and reinstall npm deps in `lapeh-api`.

**Flutter can't reach the API** — `lapeh_app/lib/core/constants.dart` uses `http://127.0.0.1:8000/api`. On a physical device, change this to your machine's LAN IP.
