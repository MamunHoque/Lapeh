<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SenderController;
use App\Http\Controllers\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

// ─── Auth (public) ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // Throttle credential endpoints to slow brute-force / enumeration.
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('register-sender', [AuthController::class, 'registerSender'])->middleware('throttle:5,1');
    Route::post('register-driver', [AuthController::class, 'registerDriver'])->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::patch('locale', [AuthController::class, 'updateLocale']);
        Route::patch('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
        Route::post('resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
    });
});

// ─── Notifications (all authenticated roles) ─────────────────────────────────
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('{notification}/read', [NotificationController::class, 'markRead']);
});

// ─── Public reference data ───────────────────────────────────────────────────
Route::get('meta', [MetaController::class, 'index']);
Route::get('meta/app-config', [MetaController::class, 'appConfig']);
Route::get('geocode/reverse', [MetaController::class, 'reverseGeocode'])
    ->middleware(['auth:sanctum', 'throttle:60,1']);

// ─── Customer (public, token-based) ──────────────────────────────────────────
// Throttle to guard against token enumeration on the public link.
Route::prefix('c')->middleware('throttle:30,1')->group(function () {
    Route::get('{token}', [CustomerController::class, 'show']);
    Route::post('{token}/confirm-location', [CustomerController::class, 'confirmLocation']);
    Route::post('{token}/pay-intent', [CustomerController::class, 'payIntent']);
    Route::get('{token}/track', [CustomerController::class, 'track']);
});

Route::post('webhooks/payment', [CustomerController::class, 'paymentWebhook'])->middleware('throttle:60,1');

// ─── Sender role ──────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:sender'])->prefix('sender')->group(function () {
    Route::get('dashboard', [SenderController::class, 'dashboard']);
    Route::patch('profile', [SenderController::class, 'updateProfile']);
    Route::post('orders', [SenderController::class, 'createOrder']);
    Route::get('orders', [SenderController::class, 'listOrders']);
    Route::get('orders/{order}', [SenderController::class, 'showOrder']);
    Route::post('orders/{order}/resend-link', [SenderController::class, 'resendLink']);
    Route::post('orders/{order}/cancel', [SenderController::class, 'cancelOrder']);
    Route::post('orders/{order}/rate-driver', [SenderController::class, 'rateDriver']);
    Route::get('history', [SenderController::class, 'history']);
    Route::get('reports', [SenderController::class, 'reports']);
    Route::post('complaints', [SenderController::class, 'createComplaint']);
    Route::get('complaints', [SenderController::class, 'listComplaints']);
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
    Route::get('senders', [AdminController::class, 'indexSenders']);
    Route::post('senders', [AdminController::class, 'storeSender']);
    Route::get('senders/{sender}', [AdminController::class, 'showSender']);
    Route::put('senders/{sender}', [AdminController::class, 'updateSender']);
    Route::delete('senders/{sender}', [AdminController::class, 'destroySender']);

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
