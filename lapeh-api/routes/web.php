<?php

use App\Http\Controllers\Admin\AdminWebController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

// Customer web flow (public, no auth)
Route::prefix('c')->group(function () {
    Route::get('{token}', [CustomerController::class, 'show'])->name('customer.order');
    Route::post('{token}/confirm-location', [CustomerController::class, 'confirmLocation'])->name('customer.confirm');
    Route::post('{token}/pay', [CustomerController::class, 'payIntent'])->name('customer.pay');
    Route::get('{token}/track', [CustomerController::class, 'track'])->name('customer.track');
});

// Admin portal (web, session auth)
Route::prefix('admin')->name('admin.')->middleware('admin.locale')->group(function () {
    Route::get('login', [AdminWebController::class, 'loginForm'])->name('login');
    Route::post('login', [AdminWebController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminWebController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin.role'])->group(function () {
        Route::get('/', [AdminWebController::class, 'dashboard'])->name('dashboard');
        Route::get('live', [AdminWebController::class, 'live'])->name('live');
        Route::get('orders', [AdminWebController::class, 'orders'])->name('orders');
        Route::get('orders/{order}', [AdminWebController::class, 'orderShow'])->name('orders.show');
        Route::get('senders', [AdminWebController::class, 'senders'])->name('senders');
        Route::get('senders/create', [AdminWebController::class, 'senderCreate'])->name('senders.create');
        Route::post('senders', [AdminWebController::class, 'senderStore'])->name('senders.store');
        Route::get('senders/{sender}/edit', [AdminWebController::class, 'senderEdit'])->name('senders.edit');
        Route::put('senders/{sender}', [AdminWebController::class, 'senderUpdate'])->name('senders.update');
        Route::delete('senders/{sender}', [AdminWebController::class, 'senderDestroy'])->name('senders.destroy');
        Route::get('drivers', [AdminWebController::class, 'drivers'])->name('drivers');
        Route::get('drivers/create', [AdminWebController::class, 'driverCreate'])->name('drivers.create');
        Route::post('drivers', [AdminWebController::class, 'driverStore'])->name('drivers.store');
        Route::get('drivers/{driver}/edit', [AdminWebController::class, 'driverEdit'])->name('drivers.edit');
        Route::put('drivers/{driver}', [AdminWebController::class, 'driverUpdate'])->name('drivers.update');
        Route::delete('drivers/{driver}', [AdminWebController::class, 'driverDestroy'])->name('drivers.destroy');
        Route::get('zones', [AdminWebController::class, 'zones'])->name('zones');
        Route::post('zones', [AdminWebController::class, 'zoneStore'])->name('zones.store');
        Route::put('zones/{zone}', [AdminWebController::class, 'zoneUpdate'])->name('zones.update');
        Route::delete('zones/{zone}', [AdminWebController::class, 'zoneDestroy'])->name('zones.destroy');
        Route::get('pricing', [AdminWebController::class, 'pricing'])->name('pricing');
        Route::put('pricing', [AdminWebController::class, 'pricingUpdate'])->name('pricing.update');
        Route::get('users', [AdminWebController::class, 'users'])->name('users');
        Route::get('complaints', [AdminWebController::class, 'complaints'])->name('complaints');
        Route::get('complaints/{complaint}', [AdminWebController::class, 'complaintShow'])->name('complaints.show');
        Route::patch('complaints/{complaint}', [AdminWebController::class, 'complaintUpdate'])->name('complaints.update');
        Route::get('ratings', [AdminWebController::class, 'ratings'])->name('ratings');
        Route::get('payments', [AdminWebController::class, 'payments'])->name('payments');
        Route::get('reports', [AdminWebController::class, 'reports'])->name('reports');
        Route::get('sms', [AdminWebController::class, 'sms'])->name('sms');
        Route::get('activity-logs', [AdminWebController::class, 'activityLogs'])->name('activity-logs');
        // System Settings Hub (tabbed)
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::get('settings/backup/{name}/download', [SettingsController::class, 'backupDownload'])->name('settings.backup.download');
        Route::post('settings/backup', [SettingsController::class, 'backupCreate'])->name('settings.backup.create');
        Route::delete('settings/backup/{name}', [SettingsController::class, 'backupDelete'])->name('settings.backup.delete');
        Route::post('settings/cache/clear', [SettingsController::class, 'clearCache'])->name('settings.cache.clear');
        Route::post('settings/test/email', [SettingsController::class, 'testEmail'])->name('settings.test.email');
        Route::post('settings/test/sms', [SettingsController::class, 'testSms'])->name('settings.test.sms');
        Route::post('settings/test/payment', [SettingsController::class, 'testPayment'])->name('settings.test.payment');
        Route::post('settings/test/push', [SettingsController::class, 'testPush'])->name('settings.test.push');
        Route::put('settings/group/{group}', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('settings/{tab}', [SettingsController::class, 'index'])->name('settings.tab')->where('tab', '[a-z]+');
    });
});

Route::get('/', fn() => redirect('/admin'));
