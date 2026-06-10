# Lapeh Flutter App — Implementation Plan

Order of work (each step keeps app compiling + tests green):

## Phase 1 — Correctness bugs
1. **Status alignment (B1)** — single source of truth: extend `status_meta.dart` with all 12 backend statuses; fix `tracking_screen` timeline (use backend names incl. `driver_assigned`, `arrived_at_restaurant`), `waiting_screen` auto-advance list, `dashboard_screen` pending check.
2. **DeliveryFlow resume (B2)** — derive initial `step` from `order.status` (`driver_assigned`→0, `arrived_at_restaurant`→1, `picked_up`/`on_the_way`→2).
3. **Send on_the_way (B3)** — after `picked_up` succeeds, chain `on_the_way` push (backend transition picked_up→on_the_way).
4. **Driver status sync (B4)** — seed `driverStatusProvider` from `authProvider` user.driver.status on first read.
5. **Friendly errors (B5)** — `apiErrorMessage(Object e)` helper in api_client.dart: maps DioException (timeout/connection/401/422 validation messages) → readable text + ar keys; use in login, create_request, sheets.
6. **Base URL (B6)** — `ApiConfig.baseUrl` from `String.fromEnvironment('API_URL', defaultValue: ...)`; default switched to `http://10.0.2.2:8000/api` on Android emulator guidance in docs.

## Phase 2 — Google Maps
7. **Restaurant tracking map** — replace MapPlaceholder with real GoogleMap: restaurant marker (needs restaurant lat/lng → add to backend orderDetail), customer marker, live driver marker from 5s poll; auto-fit bounds. Fallback to placeholder when coords absent.
8. **Driver home map** — real GoogleMap centered on current location with "you" marker; permission denial UI with open-settings action (`Geolocator.openAppSettings`).
9. **Customer web map picker** (Blade `customer/order.blade.php`) — Google Maps JS: draggable marker + tap-to-place, reverse geocode to address field, address search (Geocoder), GPS button recenters; saves lat/lng/address via existing confirm-location endpoint.

## Phase 3 — UX / states / validation
10. Retry buttons on error states (dashboard, deliveries, earnings, trips); pull-to-refresh on deliveries tabs; invalidate orders/history/dashboard after create/cancel/track-finish.
11. Form validation: create_request (phone regex `+?[0-9 -]{7,15}`, value > 0, prep time int), login (basic non-empty + phone trim). Keep existing LabeledField style; show inline errors.
12. OTP screen scroll-safety (`SingleChildScrollView` + bounded keypad) — fixes overflow B12.
13. Replace dead mock params in shells (B13); guard double-navigation in driver home (B14: `_navigating` flag).
14. reports_screen → real data from new `GET /restaurant/reports` + tr() i18n.

## Phase 4 — Backend additions (lapeh-api)
15. `GET /api/restaurant/reports` (see API_REQUIREMENTS.md) + restaurant lat/lng in orderDetail payload.
16. Run `php artisan test` + `flutter analyze` + `flutter test`.

## Out of scope (noted, not built)
- Complaints UI in app (endpoint exists; admin portal covers it)
- Driver register screen (endpoint exists)
- WebSockets in app (Reverb) — polling stays for MVP
