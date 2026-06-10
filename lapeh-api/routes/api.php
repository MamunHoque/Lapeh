<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

// ─── Auth (public) ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // Throttle credential endpoints to slow brute-force / enumeration.
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('register-driver', [AuthController::class, 'registerDriver'])->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::patch('locale', [AuthController::class, 'updateLocale']);
    });
});

// ─── Public reference data ───────────────────────────────────────────────────
Route::get('meta', [MetaController::class, 'index']);

// ─── Customer (public, token-based) ──────────────────────────────────────────
// Throttle to guard against token enumeration on the public link.
Route::prefix('c')->middleware('throttle:30,1')->group(function () {
    Route::get('{token}', [CustomerController::class, 'show']);
    Route::post('{token}/confirm-location', [CustomerController::class, 'confirmLocation']);
    Route::post('{token}/pay-intent', [CustomerController::class, 'payIntent']);
    Route::get('{token}/track', [CustomerController::class, 'track']);
});

Route::post('webhooks/payment', [CustomerController::class, 'paymentWebhook'])->middleware('throttle:60,1');

// ─── Restaurant role ──────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:restaurant'])->prefix('restaurant')->group(function () {
    Route::get('dashboard', [RestaurantController::class, 'dashboard']);
    Route::post('orders', [RestaurantController::class, 'createOrder']);
    Route::get('orders', [RestaurantController::class, 'listOrders']);
    Route::get('orders/{order}', [RestaurantController::class, 'showOrder']);
    Route::post('orders/{order}/resend-link', [RestaurantController::class, 'resendLink']);
    Route::post('orders/{order}/cancel', [RestaurantController::class, 'cancelOrder']);
    Route::post('orders/{order}/rate-driver', [RestaurantController::class, 'rateDriver']);
    Route::get('history', [RestaurantController::class, 'history']);
    Route::post('complaints', [RestaurantController::class, 'createComplaint']);
    Route::get('complaints', [RestaurantController::class, 'listComplaints']);
});

// ─── Driver role ──────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:driver'])->prefix('driver')->group(function () {
    Route::patch('status', [DriverController::class, 'updateStatus']);
    // GPS push is frequent but cap to guard against runaway clients.
    Route::post('location', [DriverController::class, 'updateLocation'])->middleware('throttle:120,1');
    Route::get('offers/current', [DriverController::class, 'currentOffer']);
    Route::post('offers/{offer}/accept', [DriverController::class, 'acceptOffer']);
    Route::post('offers/{offer}/reject', [DriverController::class, 'rejectOffer']);
    Route::get('orders/current', [DriverController::class, 'currentOrder']);
    Route::post('orders/{order}/status', [DriverController::class, 'updateOrderStatus']);
    Route::post('orders/{order}/deliver', [DriverController::class, 'deliver']);
    Route::get('earnings', [DriverController::class, 'earnings']);
});

// ─── Admin API ────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('dashboard', [AdminController::class, 'dashboard']);
    Route::get('live', [AdminController::class, 'liveOrders']);
    Route::get('orders', [AdminController::class, 'orders']);
    Route::get('payments', [AdminController::class, 'payments']);
    Route::get('ratings', [AdminController::class, 'ratings']);
    Route::get('complaints', [AdminController::class, 'complaints']);
    Route::patch('complaints/{complaint}', [AdminController::class, 'updateComplaint']);
    Route::get('pricing', [AdminController::class, 'getPricing']);
    Route::put('pricing', [AdminController::class, 'updatePricing']);
    Route::get('reports/{type}', [AdminController::class, 'reports']);
    Route::get('sms-templates', [AdminController::class, 'smsTemplates']);
    Route::patch('sms-templates/{template}', [AdminController::class, 'updateSmsTemplate']);
    Route::get('sms-logs', [AdminController::class, 'smsLogs']);
    Route::get('activity-logs', [AdminController::class, 'activityLogs']);
    Route::get('users', [AdminController::class, 'indexUsers']);
    Route::patch('users/{user}', [AdminController::class, 'updateUser']);

    // Resources
    Route::get('restaurants', [AdminController::class, 'indexRestaurants']);
    Route::post('restaurants', [AdminController::class, 'storeRestaurant']);
    Route::get('restaurants/{restaurant}', [AdminController::class, 'showRestaurant']);
    Route::put('restaurants/{restaurant}', [AdminController::class, 'updateRestaurant']);
    Route::delete('restaurants/{restaurant}', [AdminController::class, 'destroyRestaurant']);

    Route::get('drivers', [AdminController::class, 'indexDrivers']);
    Route::post('drivers', [AdminController::class, 'storeDriver']);
    Route::put('drivers/{driver}', [AdminController::class, 'updateDriver']);
    Route::delete('drivers/{driver}', [AdminController::class, 'destroyDriver']);

    Route::get('zones', [AdminController::class, 'indexZones']);
    Route::post('zones', [AdminController::class, 'storeZone']);
    Route::put('zones/{zone}', [AdminController::class, 'updateZone']);
    Route::delete('zones/{zone}', [AdminController::class, 'destroyZone']);

    Route::get('fleets', [AdminController::class, 'indexFleets']);
});
