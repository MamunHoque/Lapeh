# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Lapeh is a generic parcel-delivery platform (sender → courier, Uber-Parcel style). A **sender** creates a delivery request with package items; the **receiver/customer** confirms drop-off location and pays via a tokenized public web link; a **driver** is dispatched and delivers. The monorepo has three deployable parts that share one Laravel backend:

- `lapeh-api/` — Laravel API **and** the server-rendered admin portal (Blade) **and** the public customer tracking/confirmation pages.
- `lapeh_app/` — Flutter app serving both **sender** and **driver** roles (chosen at login/signup).

Older `.md` files in the root (`Lapeh-Build-Plan.md`, `IMPLEMENTATION_PLAN.md`, `APP_ANALYSIS.md`) are historical planning docs. `DEPLOYMENT.md` and `TEST_CREDENTIALS.md` are current.

## Commands

Run the whole stack (API + queue + Reverb + Flutter) from repo root:

```bash
./start.sh                 # admin portal, API, queue, Reverb, Flutter on Chrome
./start.sh --api-only      # backend only, no Flutter
./start.sh --device macos  # Flutter on macOS instead of Chrome
```

`start.sh` clears Laravel caches, ensures Redis is up, and runs `flutter clean` before launch.

Backend (`cd lapeh-api`):

```bash
composer dev               # serve + queue:listen + pail (logs) + vite, concurrently
php artisan test           # full test suite (clears config first)
php artisan test --filter=DriverEarningsTest          # single test class
php artisan test tests/Feature/NotificationTest.php   # single file
php artisan migrate && php artisan db:seed             # DB setup
php artisan optimize:clear  # clear all caches (do this after config/route changes)
```

Flutter (`cd lapeh_app`):

```bash
flutter pub get
flutter run -d chrome --dart-define=API_URL=http://127.0.0.1:8000/api
flutter analyze
flutter test
```

The Flutter app reads the backend URL from the `API_URL` dart-define (default `http://127.0.0.1:8000/api` in `lib/core/constants.dart`). Use `http://10.0.2.2:8000/api` for the Android emulator.

## Backend architecture

**Roles & auth.** Sanctum bearer tokens. `users.role` is one of `admin | sender | driver`; a `User` has-one `Sender` or `Driver` profile row. API routes are guarded by `role:` middleware (`app/Http/Middleware/RoleMiddleware.php`) grouped by role prefix in `routes/api.php` (`/sender/*`, `/driver/*`, `/admin/*`). The admin **web** portal uses session auth + `admin.role` middleware (`routes/web.php`). Spatie `HasRoles` is also present on `User`.

**Order lifecycle** is an explicit state machine. Statuses (enum in the orders migration):
`created → waiting_for_location → location_confirmed → waiting_for_payment → paid → searching_driver → driver_assigned → arrived_at_pickup → picked_up → on_the_way → delivered` (plus `cancelled`). Every change goes through `OrderService::transition()`, which writes an `OrderStatusLog` row and broadcasts `OrderStatusUpdated`. Don't update `orders.status` directly when a transition is meant — use the service so the log and broadcast stay consistent.

**The flow crosses all three surfaces:** sender creates the order (`SenderController`) → customer confirms location + pays on the public token link (`Customer/CustomerController`, routes under `/c/{token}`) → paying advances to `searching_driver` and calls `DispatchService::dispatch()` → driver accepts an offer and drives the order through delivery (`DriverController`).

**Dispatch** (`app/Services/DispatchService.php`): finds nearest **online** drivers within `search_radius_km` using a raw Haversine SQL query, skips already-offered drivers, creates a `DeliveryOffer` for the nearest candidate, broadcasts `DriverOfferSent`, and schedules `ExpireOfferJob` (queued) to re-dispatch if the offer times out unaccepted.

**Service layer** (`app/Services/`) holds the business logic; controllers stay thin:
- `OrderService` — `transition()`, plus generators for order number, location token, OTP.
- `DispatchService` — driver matching & offer lifecycle.
- `FeeCalculator` — delivery fee from zone base + per-km using confirmed distance.
- `MapService` — geocoding / distance.
- `OtpService` — phone verification. In non-prod the OTP is logged and returned in the API response; `MASTER_OTP` (default `123456`) is accepted. Both gated by `config/lapeh.php` `otp.dev_envs`.
- `SmsService` / `FcmService` — outbound SMS (templated, logged to `sms_logs`) and FCM v1 push.

**Real-time** uses Laravel Reverb (WebSockets, port 8080). Events: `DriverLocationUpdated`, `DriverOfferSent`, `OrderStatusUpdated`. Authorized channels in `routes/channels.php`: `order.{orderId}`, `driver.{driverId}`, `admin.dispatch`. The queue worker must be running for jobs/broadcasts to fire.

**Config & i18n.** Domain constants (rating tags, complaint types, OTP, support/FAQ) live in `config/lapeh.php` — with `en`/`ar` labels. Enums there (e.g. `complaint_types`) must stay in sync with the corresponding migration enums. Backend strings are in `lang/en` and `lang/ar`; admin portal locale is set by `SetAdminLocale` middleware.

## Flutter architecture

- **State:** Riverpod (`ProviderScope`). Providers in `lib/core/providers/` (auth, sender, driver, notification) wrap the matching service in `lib/core/services/`.
- **Networking:** single `ApiClient` (Dio) in `lib/core/api_client.dart`. An interceptor attaches the bearer token from `flutter_secure_storage` and drops it on any 401 (so the next launch lands on login). Use `apiErrorMessage(e)` to turn a `DioException` into a localized, user-safe message — never surface raw Dio text.
- **Routing:** GoRouter in `lib/core/router.dart` with an auth redirect; `/sender` and `/driver` shells are the role homes.
- **i18n:** custom `tr('key')` lookup in `lib/core/i18n.dart` (en/ar maps), locale driven by `localeNotifier` in `lib/core/app_state.dart`; RTL supported.
- **Features** are split by role under `lib/features/{auth,sender,driver,shared}/`.
- Firebase init in `main.dart` is wrapped in try/catch — the app runs without `google-services.json` / `GoogleService-Info.plist`, just without push.

## Notes

- Redis is required (cache, queues, Reverb). `start.sh` auto-starts it, including the Herd-bundled `redis-server` on macOS.
- Demo/test accounts and the end-to-end demo flow are in `TEST_CREDENTIALS.md`.
- Payments are sandbox/stubbed; a `webhooks/payment` endpoint reconciles status.
