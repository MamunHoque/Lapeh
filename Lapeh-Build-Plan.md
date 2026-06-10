# Lapeh — Complete Build Plan

> **A delivery dispatch platform for restaurants.** Restaurants receive orders through their own channels (phone, WhatsApp, Instagram) and use Lapeh to dispatch the nearest available driver. The customer confirms location and pays through a shareable web link. This document is a step-by-step build specification intended to be executed by an AI coding agent (Claude Code, Cursor, etc.).

---

## 0. How to Use This Document

- Build **phase by phase, top to bottom**. Do not skip ahead — later phases depend on earlier ones.
- After each phase there is an **✅ Acceptance Checklist**. Do not move to the next phase until every item passes.
- Treat the **Database Schema (Section 4)** as the single source of truth. All API and app work references it.
- Brand: name **Lapeh**, primary color `#FB0E72` (hot magenta), dark ink `#14192B`. Currency **AED**. Default locale `en`, with full Arabic (`ar`) RTL support.
- Anything marked **(future-ready)** must be accounted for in the **database and architecture now**, but the full UI is a later phase.

---

## 1. Tech Stack

| Layer | Technology |
|---|---|
| Mobile app | Flutter (Dart 3+), single app with role-based UI |
| Backend API | Laravel 11 (PHP 8.2+), REST + JSON |
| Database | MySQL 8 |
| Admin portal | Laravel + Blade + Alpine.js + Tailwind (or Vue 3 SPA if preferred) |
| Customer web page | Blade + Tailwind (lightweight, mobile-first, no login) |
| Real-time | Pusher (or Laravel Reverb) via Laravel Echo |
| Push notifications | Firebase Cloud Messaging (FCM) |
| Maps & geocoding | Google Maps Platform (Maps SDK, Directions, Distance Matrix, Geocoding) |
| Payments | Pluggable payment gateway (Stripe-style interface; UAE gateway e.g. Telr / PayTabs / Stripe) |
| Auth | Laravel Sanctum (token-based for mobile + SPA) |
| File storage | Local disk for dev → S3-compatible for production (proof photos, complaint images) |

**State management (Flutter):** Riverpod or Bloc. **HTTP:** Dio. **Maps:** `google_maps_flutter`. **Location:** `geolocator`. **Push:** `firebase_messaging`. **i18n:** `flutter_localizations` + `intl`.

---

## 2. System Architecture

```
                       ┌──────────────────────────┐
                       │      MySQL Database        │
                       └────────────▲───────────────┘
                                    │
                       ┌────────────┴───────────────┐
                       │     Laravel Backend API     │
                       │  (Sanctum auth, REST, jobs) │
                       │  Pusher · FCM · Maps · Pay   │
                       └──▲────────▲────────▲────────┘
                          │        │        │
        ┌─────────────────┘        │        └──────────────────┐
        │                          │                           │
┌───────┴────────┐      ┌──────────┴─────────┐      ┌──────────┴─────────┐
│  Flutter App   │      │  Customer Web Page  │      │   Admin Portal     │
│ (Restaurant +  │      │ (no app — link only)│      │ (web, staff only)  │
│  Driver roles) │      │ location/pay/track  │      │                    │
└────────────────┘      └─────────────────────┘      └────────────────────┘
```

- **One Flutter app, role-based.** After login the API returns `role` (`restaurant` or `driver`); the app routes to the matching interface. Code is modular so the two roles can later be split into separate apps with no rewrite.
- **Customer never installs anything.** They open an SMS/WhatsApp link to a tokenized web page.

---

## 3. Repository Structure

```
lapeh/
├── lapeh-api/            # Laravel backend + admin portal + customer web page
│   ├── app/
│   ├── database/migrations/
│   ├── routes/{api.php, web.php, channels.php}
│   └── resources/views/{admin, customer}/
└── lapeh-app/            # Flutter app
    ├── lib/
    │   ├── core/         # api client, theme, i18n, router, constants
    │   ├── features/
    │   │   ├── auth/
    │   │   ├── restaurant/
    │   │   └── driver/
    │   └── shared/       # widgets, maps, notifications
    └── assets/
```

---

## 4. Database Schema (Source of Truth)

> Use Laravel migrations. All tables have `id` (bigint, PK), `created_at`, `updated_at`. Use `soft_deletes` where noted. Money stored as `decimal(10,2)`. Coordinates as `decimal(10,7)`.

### 4.1 Users & Access

**users**
| column | type | notes |
|---|---|---|
| name | string | |
| email | string, unique, nullable | |
| phone | string, unique | primary login for drivers |
| password | string | hashed |
| role | enum(`admin`,`restaurant`,`driver`,`fleet`) | drives app routing |
| status | enum(`active`,`suspended`) | default active |
| locale | enum(`en`,`ar`) | default en |
| fcm_token | string, nullable | for push |
| avatar | string, nullable | |

**roles / permissions** *(use spatie/laravel-permission for admin RBAC)* — roles like `super_admin`, `dispatcher`, `finance`, `support`.

### 4.2 Restaurants

**restaurants**
| column | type | notes |
|---|---|---|
| user_id | FK users | the owner/manager login |
| name | string | |
| name_ar | string, nullable | Arabic name |
| phone | string | |
| area | string | |
| address | string | |
| lat / lng | decimal | pickup location |
| zone_id | FK zones, nullable | |
| status | enum(`active`,`inactive`) | |
| logo | string, nullable | |

### 4.3 Drivers & Fleets

**drivers**
| column | type | notes |
|---|---|---|
| user_id | FK users | |
| fleet_id | FK fleets, nullable | **(future-ready)** null = individual driver |
| vehicle_type | enum(`bike`,`car`) | |
| vehicle_plate | string, nullable | |
| status | enum(`online`,`offline`,`on_delivery`) | default offline |
| current_lat / current_lng | decimal, nullable | live position |
| last_location_at | timestamp, nullable | |
| rating_avg | decimal(3,2) | default 5.00 |
| rating_count | int | default 0 |
| is_verified | boolean | default false |

**fleets** *(future-ready — create table now, full UI later)*
| column | type | notes |
|---|---|---|
| user_id | FK users | fleet account login (role=`fleet`) |
| company_name | string | |
| contact_phone | string | |
| commission_rate | decimal(5,2), nullable | platform cut |
| status | enum(`active`,`inactive`) | |

> A driver belongs to **either** a fleet (`fleet_id` set) **or** is individual (`fleet_id` null). Fleet reporting queries group by `fleet_id`.

### 4.4 Zones & Pricing

**zones**
| column | type | notes |
|---|---|---|
| name | string | |
| polygon | json, nullable | geofence points (future) |
| base_fee | decimal | overrides global if set |
| per_km_fee | decimal | overrides global if set |
| status | enum(`active`,`inactive`) | |

**pricing_settings** *(single config row, editable from admin)*
| column | type | notes |
|---|---|---|
| base_fee | decimal | default 7.00 (AED) |
| per_km_fee | decimal | default 1.50 (AED) |
| min_fee | decimal | default 7.00 |
| currency | string | default `AED` |
| search_radius_km | decimal | default 5.0 (driver matching radius) |
| request_timeout_sec | int | default 30 (accept/reject countdown) |

### 4.5 Orders (core)

**orders**
| column | type | notes |
|---|---|---|
| order_no | string, unique | e.g. `LPH-204188` |
| restaurant_id | FK restaurants | |
| driver_id | FK drivers, nullable | assigned driver |
| customer_name | string | |
| customer_phone | string | |
| order_value | decimal | value of the food/goods |
| prep_time_min | int, nullable | |
| notes | text, nullable | |
| customer_lat / customer_lng | decimal, nullable | set when customer confirms |
| customer_address | string, nullable | |
| distance_km | decimal, nullable | restaurant→customer |
| delivery_fee | decimal, nullable | auto-calculated |
| total_amount | decimal, nullable | order_value + delivery_fee |
| status | enum (see 5.1) | |
| location_token | string, unique | for customer web link |
| payment_status | enum(`pending`,`paid`,`failed`,`refunded`) | |
| otp_code | string(4), nullable | delivery verification |
| assigned_at / picked_up_at / delivered_at | timestamp, nullable | |
| cancelled_reason | string, nullable | |

**order_status_logs** — `order_id`, `status`, `actor` (user_id/system), `note`, `created_at`. (Audit trail of every status transition.)

**delivery_offers** — tracks who an order was offered to: `order_id`, `driver_id`, `status`(`offered`,`accepted`,`rejected`,`expired`), `offered_at`, `responded_at`. (Lets you offer to next-nearest driver on reject/timeout.)

### 4.6 Payments

**payments**
| column | type | notes |
|---|---|---|
| order_id | FK orders | |
| amount | decimal | |
| currency | string | AED |
| gateway | string | e.g. `stripe`,`telr` |
| gateway_reference | string, nullable | transaction id |
| status | enum(`pending`,`paid`,`failed`,`refunded`) | |
| paid_at | timestamp, nullable | |
| raw_payload | json, nullable | webhook body |

**payment_splits** *(future-ready — create table now, no logic yet)* — `payment_id`, `payee_type`(`restaurant`,`driver`,`fleet`,`platform`), `payee_id`, `amount`, `status`. *Designed so marketplace/split settlement can be added without schema rebuild.*

### 4.7 Proof of Delivery

**delivery_proofs**
| column | type | notes |
|---|---|---|
| order_id | FK orders | |
| photo_path | string, nullable | delivery photo |
| signature_path | string, nullable | customer signature image |
| otp_verified | boolean | default false |
| captured_at | timestamp | |

### 4.8 Ratings & Complaints

**driver_ratings** — `order_id`, `restaurant_id`, `driver_id`, `rating`(1–5), `tags` (json: e.g. `["excellent_service"]`), `comment` (text, nullable).
> Predefined tags: `excellent_service`, `late_arrival`, `poor_communication`, `damaged_delivery`, `polite`, `fast`.

**complaints** — `order_id` (nullable), `restaurant_id`, `type` (enum: `late`,`damaged`,`driver_behavior`,`payment`,`other`), `description` (text), `status` (enum: `open`,`under_review`,`resolved`), `resolution_note` (text, nullable), `resolved_by` (FK users, nullable).

**complaint_attachments** — `complaint_id`, `path`. (Uploaded photos.)

### 4.9 Notifications & Logs

**notifications** — `user_id`, `title`, `body`, `data`(json), `read_at`. **sms_templates** — `key`, `content_en`, `content_ar`, `variables`(json). **sms_logs** — `to`, `template_key`, `body`, `status`, `provider_ref`. **activity_logs** — `user_id`, `action`, `subject_type`, `subject_id`, `meta`(json).

---

## 5. Core Logic Specifications

### 5.1 Order Status Lifecycle

```
created
  └─> waiting_for_location   (link sent to customer)
        └─> location_confirmed
              └─> waiting_for_payment
                    └─> paid
                          └─> searching_driver   (dispatch begins)
                                └─> driver_assigned
                                      └─> arrived_at_restaurant
                                            └─> picked_up
                                                  └─> on_the_way
                                                        └─> delivered   (terminal)
cancelled  (terminal, reachable from any pre-delivered state)
```

Every transition writes a row to `order_status_logs` and (where relevant) broadcasts a real-time event + push notification.

### 5.2 Delivery Fee Calculation

```
distance_km = Google Distance Matrix (restaurant.lat/lng → customer.lat/lng)
fee = base_fee + (per_km_fee × distance_km)      // from pricing_settings or zone override
fee = max(fee, min_fee)
delivery_fee = round(fee, 2)
total_amount = order_value + delivery_fee
```
Defaults: `base_fee = 7.00 AED`, `per_km_fee = 1.50 AED`. All values editable from Admin → Pricing Config.

### 5.3 Driver Dispatch Algorithm

1. On `paid`, set order to `searching_driver`.
2. Query `drivers` where `status='online'` within `search_radius_km` of the restaurant, ordered by distance (Haversine on `current_lat/lng`).
3. Offer to the nearest driver → create `delivery_offers` row (`offered`), send FCM push + real-time event with a `request_timeout_sec` countdown.
4. If accepted → assign (`driver_assigned`), mark driver `on_delivery`, close other offers.
5. If rejected or timed out → mark offer `rejected`/`expired`, offer to next-nearest driver.
6. If no driver found after radius exhausted → keep `searching_driver`, alert admin, optionally widen radius.

### 5.4 Customer Web Flow (tokenized, no login)

`GET /c/{location_token}` resolves the order. Three states served by the same link:
1. **Confirm location** — map pin + address fields → `POST` sets `customer_lat/lng`, computes `distance_km` + `delivery_fee`, moves to `waiting_for_payment`.
2. **Pay** — shows order_value + delivery_fee + total → gateway checkout → webhook sets `paid`.
3. **Track** — live driver position (polling or Pusher), status timeline, OTP code shown for hand-off.

---

## 6. API Endpoints (Laravel `routes/api.php`)

> All under `/api`. Auth via Sanctum bearer token unless marked **public**.

**Auth**
```
POST   /auth/login                 → {token, user{role,...}}
POST   /auth/register-driver
POST   /auth/logout
GET    /auth/me
POST   /auth/fcm-token             → save device token
PATCH  /auth/locale                → switch en/ar
```

**Restaurant role**
```
GET    /restaurant/dashboard       → today stats + active deliveries
POST   /restaurant/orders          → create delivery request (sends links)
GET    /restaurant/orders          → list (filter by status)
GET    /restaurant/orders/{id}     → detail + status timeline + driver position
POST   /restaurant/orders/{id}/resend-link
POST   /restaurant/orders/{id}/cancel
POST   /restaurant/orders/{id}/rate-driver   → rating + tags + comment
GET    /restaurant/history
POST   /restaurant/complaints      → open complaint (+ photo upload)
GET    /restaurant/complaints
```

**Driver role**
```
PATCH  /driver/status              → online | offline
POST   /driver/location            → push current lat/lng (throttled)
GET    /driver/offers/current      → pending offer (if any)
POST   /driver/offers/{id}/accept
POST   /driver/offers/{id}/reject
GET    /driver/orders/current
POST   /driver/orders/{id}/status  → arrived_at_restaurant | picked_up | on_the_way
POST   /driver/orders/{id}/deliver → otp + photo + signature (proof)
GET    /driver/earnings            → today + history
```

**Customer (public, token-based)**
```
GET    /c/{location_token}                  → order public view + current state
POST   /c/{location_token}/confirm-location → lat/lng/address → recompute fee
POST   /c/{location_token}/pay-intent       → create gateway session
GET    /c/{location_token}/track            → live status + driver position
POST   /webhooks/payment                    → gateway webhook (public, signed)
```

**Admin** (role=admin)
```
GET    /admin/dashboard
RESOURCE /admin/restaurants  /admin/drivers  /admin/fleets  /admin/zones  /admin/users
GET/PUT  /admin/pricing
GET    /admin/orders                 → all orders, filters
GET    /admin/live                   → live deliveries feed
GET    /admin/payments
GET    /admin/payouts
GET/PATCH /admin/complaints/{id}     → review & resolve
GET    /admin/ratings
GET    /admin/reports/{type}         → daily | monthly | revenue | driver-earnings | fleet
GET    /admin/sms-templates  /admin/sms-logs  /admin/activity-logs
```

**Real-time channels (`routes/channels.php`)**
```
private order.{id}            → status updates, driver position (restaurant + customer)
private driver.{id}           → new offers, assignments
private admin.dispatch        → global live feed
```

---

## 7. Build Phases (Execute In Order)

### Phase 1 — Backend Foundation
1. `composer create-project laravel/laravel lapeh-api`. Configure `.env` (DB, app locale, timezone `Asia/Dubai`).
2. Install: `sanctum`, `spatie/laravel-permission`, `pusher/pusher-php-server`, `laravel/reverb` (optional), `kreait/laravel-firebase` (FCM), `google maps` HTTP client wrapper, payment gateway SDK.
3. Create **all migrations** from Section 4. Run `migrate`. Add model relationships + factories + seeders (admin user, pricing_settings row, sample zones, SMS templates).
4. Implement Sanctum auth endpoints (Section 6 → Auth). Role-based middleware.
5. Build a `MapService` (distance matrix + geocoding), `FeeCalculator` (5.2), `OrderNumberGenerator`.

**✅ Acceptance:** migrations run clean; can log in and get a token with correct `role`; fee calculator unit-tested against known distances.

---

### Phase 2 — Order Engine + Customer Web Page
1. Implement `POST /restaurant/orders` → creates order (`created`→`waiting_for_location`), generates `location_token` + `otp_code`, sends link via SMS template (stub provider in dev, log to `sms_logs`).
2. Implement order state machine + `order_status_logs` writing on every transition.
3. Build customer web page (Blade, mobile-first, Tailwind, Lapeh theme): confirm-location (Google Map pin) → recompute fee → payment page → track page.
4. Integrate payment gateway: checkout session + `POST /webhooks/payment` → set `payment_status=paid`, advance order to `searching_driver`.
5. Broadcast `order.{id}` events on each transition.

**✅ Acceptance:** create an order → receive link (in logs) → open web page → confirm location → fee recalculates correctly → pay (sandbox) → order auto-moves to `searching_driver`; status timeline updates live.

---

### Phase 3 — Dispatch Engine + Driver API
1. Implement driver `online/offline`, throttled `location` updates.
2. Build dispatch algorithm (5.3): nearest-online-driver query, `delivery_offers`, FCM push, countdown.
3. Implement accept/reject/timeout flow + re-offer to next driver.
4. Implement driver status updates (`arrived`,`picked_up`,`on_the_way`) and `deliver` (OTP check + proof photo/signature upload → `delivery_proofs` → `delivered`).
5. Earnings endpoint (sum delivery_fee by driver/day).

**✅ Acceptance:** paid order offers to nearest online driver; accept assigns + notifies restaurant & customer; reject re-offers; full status chain to `delivered` works; OTP + photo required at delivery.

---

### Phase 4 — Admin Portal
1. Scaffold admin layout matching the approved mockup (dark navy sidebar `#14192B`, pink `#FB0E72` accents, grouped nav). Tailwind + Blade + Alpine (or Vue SPA).
2. Build module by module: **Dashboard** (KPIs, live deliveries, network overview, quick actions) → **Live Deliveries** → **Orders** → **Restaurants** → **Drivers** → **Fleets** → **Zones** → **Pricing Config** → **Payments** → **Payouts** → **Complaints** → **Ratings** → **Users & Roles** → **Reports** → **SMS** → **Activity Log** → **Settings**.
3. Live deliveries view subscribes to `admin.dispatch`.
4. Reports: daily/monthly orders, delivery revenue, driver earnings, fleet reporting (group by `fleet_id`).

**✅ Acceptance:** admin can manage restaurants/drivers/zones, edit pricing (reflected in fee calc), watch live deliveries, review & resolve complaints, view reports.

---

### Phase 5 — Flutter App (Shared + Restaurant Role)
1. Scaffold app: theme (Lapeh colors, Sora/DM-Sans-equiv fonts), Dio API client, Riverpod, GoRouter, **i18n (en + ar, RTL)**, FCM setup, Google Maps.
2. Auth: login → store token → route by `role`.
3. Restaurant interface (match approved prototype): dashboard, create delivery request, send-link/wait screen, auto-fee + dispatch, live driver tracking, history, rate driver, complaints.

**✅ Acceptance:** restaurant user logs in, creates request, sees customer progress live, tracks assigned driver on map, can rate & file complaint; UI switches fully to Arabic RTL.

---

### Phase 6 — Flutter App (Driver Role)
1. Driver interface (match approved prototype): online/offline toggle + live location broadcast, incoming offer modal with countdown (accept/reject), navigate to restaurant & customer (Google Maps), 6-step status updates, OTP + delivery photo + optional signature, earnings.
2. Background location while `on_delivery`; FCM handling for offers.

**✅ Acceptance:** driver goes online, receives offer, accepts, navigates, updates each status, completes delivery with OTP+photo; restaurant/customer see every update in real time.

---

### Phase 7 — Polish, QA & Deployment
1. Full end-to-end test across all three surfaces (restaurant app ↔ customer web ↔ driver app ↔ admin).
2. Arabic translation pass (all strings, RTL layout, Arabic order/restaurant names).
3. Seed predefined rating tags & complaint types; finalize SMS templates (en/ar).
4. Security pass: validate token signing on customer/webhook routes, rate-limit location & auth, authorize every resource by ownership/role.
5. Deploy Laravel (production server, queue worker for jobs/notifications, scheduler), point FCM/Maps/Payment to **client-owned accounts**. Build Flutter APK + IPA.
6. Handover: private Git repo, `.env.example`, README, DB seed, deployment notes.

**✅ Acceptance:** clean end-to-end run in production/staging; Arabic verified; all third-party keys are client-owned; signed builds produced; repo handed over.

---

## 8. Cross-Cutting Requirements

- **Internationalization:** every user-facing string in `en` + `ar` (Laravel lang files; Flutter `intl` ARB). App + customer page must render correct RTL when locale is `ar`. English is default.
- **Fleet-ready (future):** `fleets` table + `drivers.fleet_id` exist from Phase 1; fleet reporting groups by `fleet_id`. Full fleet management UI is post-MVP but **no schema change** required to add it.
- **Split-payment-ready (future):** `payment_splits` table exists from Phase 1. Payment service written behind an interface so marketplace/multi-party settlement plugs in later without rebuild.
- **Proof of delivery:** OTP **plus** optional photo + signature, stored in `delivery_proofs`, viewable from admin & restaurant order detail.
- **Ownership:** all source code (Flutter, Laravel, DB, admin) delivered via private Git repo; all third-party accounts (Google, Firebase, payment gateway, Apple, Play) registered under the client's own credentials from day one.

---

## 9. Environment / Accounts Checklist (client-owned)

- [ ] Google Cloud project → Maps SDK (Android/iOS), Directions, Distance Matrix, Geocoding API keys
- [ ] Firebase project → Android + iOS apps, FCM server key, `google-services.json` / `GoogleService-Info.plist`
- [ ] Payment gateway merchant account → API keys + webhook secret
- [ ] SMS / WhatsApp provider (e.g. Twilio / local UAE provider) → API credentials
- [ ] Apple Developer account + Google Play Console (for store release — post-MVP)
- [ ] Production server (PHP 8.2, MySQL 8, queue worker, HTTPS domain)

---

## 10. Definition of Done (MVP)

A restaurant logs an order → customer confirms location and pays via link → nearest online driver is auto-offered and accepts → driver navigates, picks up, delivers with OTP + photo proof → restaurant rates the driver → admin sees the whole flow live and can manage the network, pricing, complaints, and reports — all available in English and Arabic, on a fleet-ready and split-payment-ready architecture, with 100% of the code and accounts owned by the client.

---

*Lapeh — Build Plan · prepared for development with an AI coding agent. Brand color `#FB0E72`, ink `#14192B`. Currency AED. Base fee 7.00 + 1.50/km (configurable).*
