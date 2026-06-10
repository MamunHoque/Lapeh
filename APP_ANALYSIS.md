# Lapeh Flutter App — Analysis

Date: 2026-06-10 · Flutter 3.44 / Riverpod 2 / GoRouter 14 / Dio 5 · Backend: Laravel 12 (lapeh-api)

## Architecture (current — keep)

```
lib/
├── core/
│   ├── constants.dart        # ApiConfig.baseUrl + Maps key (hardcoded)
│   ├── api_client.dart       # Dio singleton + Bearer interceptor (secure storage)
│   ├── app_state.dart        # localeNotifier (ValueNotifier)
│   ├── i18n.dart             # custom tr() en/ar maps
│   ├── router.dart           # GoRouter: /login /restaurant /driver + auth redirect
│   ├── models/               # OrderModel, DeliveryOffer, DashboardStats, UserModel
│   ├── providers/            # auth, restaurant (dashboard/orders/history), driver
│   └── services/             # AuthService, RestaurantService, DriverService,
│                             # LocationService (geolocator), FcmService
├── features/
│   ├── auth/login_screen.dart
│   ├── restaurant/  shell, dashboard, create_request, waiting, tracking,
│   │                deliveries, reports(MOCK), dispatch(empty)
│   ├── driver/      shell, home, incoming_request_sheet, delivery_flow(real GMaps),
│   │                trips, earnings
│   └── shared/profile_screen.dart
└── shared/widgets.dart, map_placeholder.dart (fake painted map)
```

State: Riverpod (Async/StateNotifier) + Timer polling (offers 5s, order 8s, waiting 4s, tracking 5s). FCM with polling fallback. Pattern is consistent — keep it.

## Bugs found (ordered by severity)

| # | Bug | Where | Effect |
|---|-----|-------|--------|
| B1 | Status string mismatch with backend: app uses `assigned`, `waiting_payment`; backend emits `driver_assigned`, `waiting_for_payment`, `arrived_at_restaurant`, `location_confirmed`, `paid` | tracking_screen `_buildTimeline`/`_statusLabel`, waiting_screen auto-advance, dashboard `_DeliveryRow`, status_meta.dart | Tracking timeline stuck all-grey; waiting screen never auto-opens tracking when driver assigned; wrong badges |
| B2 | DeliveryFlow always starts at step 0 | delivery_flow.dart | App restart mid-delivery → 422 "Cannot transition from picked_up to arrived_at_restaurant"; driver stuck |
| B3 | `on_the_way` status never sent (step 2→3 is local `step++`) | delivery_flow.dart case 2 | Customer + restaurant stuck at "Picked up"; OTP screen shows on web only at on_the_way |
| B4 | Driver online toggle not synced from backend (`driverStatusProvider` starts 'offline') | driver_provider.dart | After restart, backend says online, toggle shows offline |
| B5 | Raw `DioException…` text shown to user on login/create errors | login_screen, create_request, others | Unreadable errors; no network/401 distinction |
| B6 | `baseUrl = http://127.0.0.1:8000` | constants.dart | Android emulator/device can never reach the API (needs 10.0.2.2 / LAN IP) |
| B7 | Decimal-as-string JSON crashes (`"85.00" not num`, `"0" not int`) | order_model etc. | FIXED earlier this session: `asDouble`/`asInt` helpers + Laravel float casts |
| B8 | Restaurant tracking + driver home show painted fake map (`MapPlaceholder`) though google_maps_flutter installed and key configured | tracking_screen, driver_home_screen | No real map, no live driver marker for restaurants |
| B9 | reports_screen 100% hardcoded mock numbers + English-only | reports_screen.dart | Fake data shown to client; backend endpoint missing (see API_REQUIREMENTS) |
| B10 | Error states have no Retry; deliveries list has no pull-to-refresh; tab data cached forever (FutureProvider.family never invalidated) | dashboard, deliveries, earnings | Stale lists, dead-end errors |
| B11 | No form validation beyond empty check (phone format, value > 0 only) | create_request, login | Garbage reaches API |
| B12 | OTP screen: `Spacer` inside `Column` with keypad + photo can overflow on small screens | delivery_flow `_OtpScreen` | RenderFlex overflow on <700px-tall devices |
| B13 | Restaurant shell passes hardcoded mock 'Al Safadi' / driver 'Bilal Hassan' to ProfileScreen (display falls back to real user — params are dead but misleading) | restaurant_shell, driver_shell | Cosmetic/dead code |
| B14 | `_checkActiveOrder` can push DeliveryFlow while incoming-offer sheet open or repeatedly | driver_home_screen | Possible double navigation |

## Google Maps status

- **Key**: present in AndroidManifest + AppDelegate + constants.dart (same key).
- **Working**: delivery_flow `_NavScreen`/`_PickupScreen` use real `GoogleMap` with markers + external navigation launch.
- **Missing**: restaurant tracking map (fake), driver home map (fake), no camera-follow on location updates, no polyline, markers don't update bounds.
- **Customer location selection** happens on the **customer web page** (Blade), not the app — currently GPS button + manual text only; no map picker, no reverse geocoding, no search. (Improved on web side: see plan.)
- Permissions: Android manifest OK (FINE/COARSE/BACKGROUND + FOREGROUND_SERVICE), iOS plist OK. LocationService handles denied/deniedForever but UI doesn't offer "open settings" when deniedForever.

## API integration matrix

| Flutter call | Backend route | Status |
|---|---|---|
| POST /auth/login, logout, me, fcm-token, PATCH locale | ✓ exists | wired |
| GET /restaurant/dashboard | ✓ | wired (stats cast fixed) |
| POST/GET /restaurant/orders, GET /orders/{id} | ✓ | wired |
| resend-link, cancel, rate-driver, history | ✓ | wired (cancel + rate have **no UI**) |
| GET /restaurant/reports | **✗ missing** | reports screen is mock |
| driver status/location/offers/orders/deliver/earnings | ✓ | wired |
| POST /restaurant/complaints | ✓ exists | no UI (out of scope, noted) |

## Performance notes
- Polling acceptable for MVP; offers poll only while online ✓.
- `ordersProvider(null)` fetches all orders then filters client-side for Active tab — fine at MVP scale; needs invalidation hooks (fixed in plan).
- IndexedStack keeps all 4 tabs alive → dashboardProvider/earnings fetch once; refresh added via pull-to-refresh.
