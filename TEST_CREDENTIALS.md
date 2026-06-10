# Lapeh — Test Credentials

Lapeh is a generic **sender → courier** parcel-delivery platform (Uber-Parcel style).
A *sender* (individual or business) creates a delivery request with package items;
the *receiver* confirms drop-off via a tokenized web link; a *driver* delivers.

## URLs

| Interface | URL |
|-----------|-----|
| Admin portal | http://localhost:8000/admin |
| API base | http://localhost:8000/api |
| Receiver web link | http://localhost:8000/c/{location_token} |

---

## Admin Portal (web browser)

| Field | Value |
|-------|-------|
| URL | http://localhost:8000/admin/login |
| Phone | `+9710000000` |
| Password | `admin1234` |

---

## Sender — Individual (verified & active)

| Field | Value |
|-------|-------|
| Phone | `+971501111111` |
| Password | `sender1234` |
| Name | Mariam Ahmed |
| Type | Individual |
| Default pickup | Jumeirah Beach Road, Dubai |

## Sender — Business (verified & active)

| Field | Value |
|-------|-------|
| Phone | `+971501111112` |
| Password | `sender1234` |
| Name / contact | Omar Haddad |
| Type | Business |
| Business | Gulf Gadgets Store · Electronics |
| Default pickup | Business Bay, Dubai |

> Seeded senders are already OTP-verified — log in and use them immediately.

---

## Driver 1 (online) / Driver 2 (offline)

| Field | Driver 1 | Driver 2 |
|-------|----------|----------|
| Phone | `+971502222222` | `+971503333333` |
| Password | `driver1234` | `driver1234` |
| Name | Bilal Hassan | Karim Nasser |
| Vehicle | Bike · A 12345 | Car · B 67890 |
| Status | online | offline |

---

## Phone OTP (development)

No SMS provider is wired yet. In `local`/`testing`/`development`:
- The OTP is **returned in the API response** (`dev_otp`) and logged to `storage/logs`.
- The **master OTP `123456`** is always accepted (configurable via `MASTER_OTP`).

New signups from the Flutter app prefill the dev OTP automatically.

---

## Services to start before testing

```bash
cd /Volumes/Mamun/Code/lapeh/lapeh-api
php artisan serve          # API + admin portal on :8000
php artisan queue:work     # offer dispatch / expiry jobs
php artisan reverb:start   # WebSocket on :8080
```

---

## Sandbox flow (happy path)

1. **Sign up** a sender in the app (or log in with a seeded sender).
2. Verify the phone with the **dev OTP** (prefilled) or `123456`.
3. **New delivery** → pickup is prefilled from the default; add receiver name/phone and
   one or more **package items** (name, qty, unit value) → Create.
4. Copy the **receiver link** from the waiting screen (or read it from the log).
5. Open the link → it shows the **package items**, then confirm drop-off location → Pay (sandbox auto-pays).
6. Dispatch offers the order to **Bilal Hassan**; accept in the driver app and step through delivery → enter the receiver's OTP → Confirm.
7. **Admin → Senders / Orders** shows the sender, request and its items.

---

## API quick test (curl)

```bash
# Register an individual sender (returns token + dev_otp)
curl -s -X POST http://localhost:8000/api/auth/register-sender \
  -H "Content-Type: application/json" \
  -d '{"type":"individual","name":"Test","phone":"+971509999999","password":"secret123"}' | jq .

# Verify phone (master OTP), using the token from above
curl -s -X POST http://localhost:8000/api/auth/verify-otp \
  -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
  -d '{"code":"123456"}' | jq .

# Log in as a seeded sender / driver / admin
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"phone":"+971501111111","password":"sender1234"}' | jq .

# Create a delivery request with items (sender token)
curl -s -X POST http://localhost:8000/api/sender/orders \
  -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
  -d '{"customer_name":"Layla","customer_phone":"+971558887777",
       "items":[{"name":"Gift","quantity":2,"unit_price":50}]}' | jq .
```
