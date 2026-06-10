<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Support\Str;

class OrderService
{
    public static function generateOrderNo(): string
    {
        do {
            $no = 'LPH-' . random_int(100000, 999999);
        } while (Order::where('order_no', $no)->exists());
        return $no;
    }

    public static function generateLocationToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Order::where('location_token', $token)->exists());
        return $token;
    }

    public static function generateOtp(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function transition(Order $order, string $newStatus, ?int $actor = null, ?string $note = null): Order
    {
        $order->update(['status' => $newStatus]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'actor' => $actor,
            'note' => $note,
        ]);

        broadcast(new OrderStatusUpdated($order))->toOthers();

        return $order;
    }
}
