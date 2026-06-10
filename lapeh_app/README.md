# Lapeh — Flutter App

A role-based Flutter app for the **Lapeh** delivery dispatch platform. One app, two roles (**Restaurant** and **Driver**), selected at login. Connects to the Laravel API at `http://127.0.0.1:8000/api`.

Brand: primary `#FB0E72`, ink `#14192B`. Currency AED. English + Arabic (RTL) scaffold included.

> **Run admin + app together:** from the repo root, see [../README.md](../README.md) or run `./start.sh`.

---

## Run it

**With the API (recommended):**

```bash
# from repo root — starts API, admin portal, and this app
./start.sh
```

**App only** (requires the API already running on :8000):

```bash
cd lapeh_app
flutter pub get
flutter run -d chrome
```

Test logins: [../TEST_CREDENTIALS.md](../TEST_CREDENTIALS.md).

**Demo flow**
- **Login** → toggle **Restaurant** or **Driver**, tap *Sign in* (any/empty credentials work).
- **Restaurant**: Dashboard → *New delivery request* → fill form → *Create & send links* → *Simulate: location & payment done* → *Dispatch* → live tracking. Bottom tabs: Home / Deliveries / Reports / Profile.
- **Driver**: flip **online** → an incoming request appears (or tap *Simulate incoming request*) → *Accept* → navigate → arrived → picked up → navigate → arrived → enter OTP (tap *Confirm* once to autofill `4193`, again to complete) → Delivered. Bottom tabs: Home / Trips / Earnings / Profile.
- **Language**: tap the globe on the login screen, or Profile → Language, to switch to Arabic (RTL).

---

## Project structure

```
lib/
├── main.dart                      # MaterialApp, locale + RTL
├── core/
│   ├── theme.dart                 # AppColors, theme, text styles
│   ├── app_state.dart             # locale + driverOnline notifiers
│   ├── i18n.dart                  # tr() + en/ar strings (migrate to ARB later)
│   └── mock_data.dart             # demo orders/trips + status meta
├── shared/
│   ├── widgets.dart               # LapehButton, StatusBadge, AppCard, StatusTimeline, LabeledField…
│   └── map_placeholder.dart       # CustomPaint fake map (swap for google_maps_flutter)
└── features/
    ├── auth/login_screen.dart
    ├── shared/profile_screen.dart
    ├── restaurant/
    │   ├── restaurant_shell.dart  # bottom nav
    │   ├── dashboard_screen.dart
    │   ├── create_request_screen.dart
    │   ├── waiting_screen.dart
    │   ├── dispatch_screen.dart
    │   ├── tracking_screen.dart
    │   ├── deliveries_screen.dart
    │   └── reports_screen.dart
    └── driver/
        ├── driver_shell.dart       # bottom nav
        ├── driver_home_screen.dart # online/offline toggle
        ├── incoming_request_sheet.dart
        ├── delivery_flow.dart      # navigate→pickup→navigate→OTP→delivered
        ├── trips_screen.dart
        └── earnings_screen.dart
```

---

## Wiring to the backend (next steps for your AI agent)

This prototype is intentionally dependency-light. To connect it to the Laravel API from `Lapeh-Build-Plan.md`:

1. **Add packages**: `dio` (HTTP), `flutter_riverpod` (state), `go_router` (routing), `google_maps_flutter`, `geolocator`, `firebase_messaging`, `intl`.
2. **Auth**: replace the login `_signIn()` with a real `POST /auth/login`; store the Sanctum token; route by the returned `role`.
3. **Replace mock data** in `core/mock_data.dart` with API models + repositories. Each screen reads from providers instead of `MockData`.
4. **Maps**: replace `MapPlaceholder` with `GoogleMap`; feed restaurant/customer/driver coordinates.
5. **Real-time**: subscribe to `order.{id}` / `driver.{id}` channels (Pusher/Echo) to drive status + live driver position.
6. **Push**: wire `firebase_messaging` so the driver's incoming-request sheet is triggered by an FCM message instead of the demo timer.
7. **i18n**: migrate `core/i18n.dart` keys to ARB files + `flutter gen-l10n`. RTL already works via `Locale('ar')`.
8. **Proof of delivery**: in `delivery_flow.dart` OTP step, wire the *Add photo* button to camera + upload, plus optional signature pad.

The UI, theme, navigation, and screen flow are all in place — the agent's job is to swap mock data/timers for real API/streams.

---

*Lapeh · UI prototype · mock data. Built to match the approved design prototype.*
