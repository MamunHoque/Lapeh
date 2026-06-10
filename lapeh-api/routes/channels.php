<?php

use App\Models\Order;
use App\Models\Driver;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('order.{orderId}', function ($user, int $orderId) {
    $order = Order::find($orderId);
    if (!$order) return false;
    if ($user->role === 'admin') return true;
    if ($user->role === 'restaurant' && $user->restaurant?->id === $order->restaurant_id) return true;
    if ($user->role === 'driver' && $user->driver?->id === $order->driver_id) return true;
    return false;
});

Broadcast::channel('driver.{driverId}', function ($user, int $driverId) {
    return $user->role === 'admin' || ($user->role === 'driver' && $user->driver?->id === $driverId);
});

Broadcast::channel('admin.dispatch', function ($user) {
    return $user->role === 'admin';
});
