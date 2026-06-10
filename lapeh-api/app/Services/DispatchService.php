<?php

namespace App\Services;

use App\Events\DriverOfferSent;
use App\Events\OrderStatusUpdated;
use App\Models\DeliveryOffer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\PricingSetting;
use Illuminate\Support\Facades\Log;

class DispatchService
{
    public function dispatch(Order $order): void
    {
        $settings = PricingSetting::current();
        $restaurant = $order->restaurant;

        // Find nearest online drivers within radius using Haversine SQL
        $radiusKm = $settings->search_radius_km;
        $lat = $restaurant->lat;
        $lng = $restaurant->lng;

        $drivers = Driver::with('user')
            ->where('status', 'online')
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(current_lat))
                    * cos(radians(current_lng) - radians(?))
                    + sin(radians(?)) * sin(radians(current_lat))
                )) <= ?
            ", [$lat, $lng, $lat, $radiusKm])
            ->orderByRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(current_lat))
                    * cos(radians(current_lng) - radians(?))
                    + sin(radians(?)) * sin(radians(current_lat))
                )) ASC
            ", [$lat, $lng, $lat])
            ->get();

        // Skip drivers already offered this order
        $offeredDriverIds = $order->offers()->pluck('driver_id');
        $candidates = $drivers->whereNotIn('id', $offeredDriverIds);

        if ($candidates->isEmpty()) {
            Log::info("No available drivers for order {$order->order_no}");
            return;
        }

        $driver = $candidates->first();
        $this->offerToDriver($order, $driver, $settings->request_timeout_sec);
    }

    public function offerToDriver(Order $order, Driver $driver, int $timeoutSec = 30): DeliveryOffer
    {
        $offer = DeliveryOffer::create([
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'status' => 'offered',
            'offered_at' => now(),
        ]);

        broadcast(new DriverOfferSent($driver, $order, $offer, $timeoutSec));

        // FCM push so driver is woken up even when app is backgrounded
        $restaurant = $order->restaurant;
        $offerPayload = [
            'id' => $offer->id,
            'order_no' => $order->order_no,
            'restaurant_name' => $restaurant->name,
            'restaurant_lat' => $restaurant->lat,
            'restaurant_lng' => $restaurant->lng,
            'restaurant_address' => $restaurant->address,
            'delivery_fee' => $order->delivery_fee,
            'distance_km' => $order->distance_km,
            'timeout_sec' => $timeoutSec,
        ];
        if ($driver->user->fcm_token) {
            (new \App\Services\FcmService())->sendOfferToDriver($driver->user->fcm_token, $offerPayload);
        }

        // Schedule timeout job
        \App\Jobs\ExpireOfferJob::dispatch($offer->id)->delay(now()->addSeconds($timeoutSec));

        return $offer;
    }

    public function accept(DeliveryOffer $offer): Order
    {
        $offer->update(['status' => 'accepted', 'responded_at' => now()]);

        // Expire other offers
        DeliveryOffer::where('order_id', $offer->order_id)
            ->where('id', '!=', $offer->id)
            ->where('status', 'offered')
            ->update(['status' => 'expired', 'responded_at' => now()]);

        $order = $offer->order;
        $driver = $offer->driver;

        $order->update([
            'driver_id' => $driver->id,
            'status' => 'driver_assigned',
            'assigned_at' => now(),
        ]);

        $driver->update(['status' => 'on_delivery']);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'driver_assigned',
            'actor' => $driver->user_id,
        ]);

        broadcast(new OrderStatusUpdated($order->fresh(['driver', 'restaurant'])));

        return $order;
    }

    public function reject(DeliveryOffer $offer): void
    {
        $offer->update(['status' => 'rejected', 'responded_at' => now()]);
        $this->dispatch($offer->order);
    }
}
