# API Requirements — missing backend endpoints

## 1. GET /api/restaurant/reports  ← IMPLEMENTED (this session)

Used by: `lib/features/restaurant/reports_screen.dart` (was 100% mock data).

- **Method/Path**: `GET /api/restaurant/reports`
- **Auth**: `auth:sanctum` + `role:restaurant`
- **Query params**: none (fixed: today + 7-day window)
- **Validation**: none (derives restaurant from token)
- **Response 200**:
```json
{
  "today": { "orders": 18, "delivered": 16, "cancelled": 1, "revenue": 312.40, "avg_fee": 17.35 },
  "yesterday_revenue": 278.00,
  "recent": [
    { "id": 12, "order_no": "LPH-2039", "customer_name": "Layla",
      "status": "delivered", "delivery_fee": 11.50, "created_at": "..." }
  ]
}
```
- All money fields cast to float, counts to int (PDO aggregate strings — see project gotcha).

## 2. Restaurant coordinates in order detail  ← IMPLEMENTED (this session)

`RestaurantController::orderDetail()` now includes `restaurant_name`, `restaurant_lat`, `restaurant_lng` so the app's tracking map can draw the pickup marker. No new route — field addition only (backward compatible).

## Not missing (exist but unused by app — future UI)
- `POST /api/restaurant/complaints`, `GET /api/restaurant/complaints`
- `POST /api/auth/register-driver`
- `POST /api/restaurant/orders/{order}/cancel` and `rate-driver` (service methods exist in app; no buttons yet)
