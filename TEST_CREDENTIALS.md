# Lapeh — Test Credentials

## URLs

| Interface | URL |
|-----------|-----|
| Admin portal | http://localhost:8000/admin |
| API base | http://localhost:8000/api |
| Customer web | http://localhost:8000/c/{location_token} |

---

## Admin Portal (web browser)

| Field | Value |
|-------|-------|
| URL | http://localhost:8000/admin/login |
| Phone | `+9710000000` |
| Password | `admin1234` |
| Name | Lapeh Admin |
| Email | admin@lapeh.app |

---

## Flutter App / API — Restaurant

| Field | Value |
|-------|-------|
| Phone | `+971501111111` |
| Password | `rest1234` |
| Name | Al Safadi Manager |
| Restaurant | Al Safadi · Jumeirah |
| Zone | Jumeirah |

---

## Flutter App / API — Driver 1 (online)

| Field | Value |
|-------|-------|
| Phone | `+971502222222` |
| Password | `driver1234` |
| Name | Bilal Hassan |
| Vehicle | Bike · Plate A 12345 |
| Status | online (at startup) |

---

## Flutter App / API — Driver 2 (offline)

| Field | Value |
|-------|-------|
| Phone | `+971503333333` |
| Password | `driver1234` |
| Name | Karim Nasser |
| Vehicle | Car · Plate B 67890 |
| Status | offline (at startup) |

---

## Services to start before testing

```bash
cd /Volumes/Mamun/Code/lapeh/lapeh-api

php artisan serve          # API + admin portal on :8000
php artisan queue:work     # background jobs (offer dispatch, expiry)
php artisan reverb:start   # WebSocket on :8080
```

---

## Sandbox order flow (happy path)

1. Log in as **restaurant** → New delivery → fill customer name, phone, order value → Create
2. Copy the customer link from the waiting screen (or check the log for the SMS)
3. Open the customer link in a browser → confirm location → click Pay (sandbox auto-pays)
4. App auto-advances to tracking; dispatch engine offers the order to **Bilal Hassan**
5. Log in as **Bilal Hassan** in the driver app → go online → accept the incoming request
6. Step through delivery flow → enter the OTP shown on the customer tracking page → Confirm

---

## API quick test (curl)

```bash
# Login as restaurant
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"phone":"+971501111111","password":"rest1234"}' | jq .

# Login as driver
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"phone":"+971502222222","password":"driver1234"}' | jq .

# Login as admin (API)
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"phone":"+9710000000","password":"admin1234"}' | jq .
```
