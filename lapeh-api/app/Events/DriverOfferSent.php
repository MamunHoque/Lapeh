<?php

namespace App\Events;

use App\Models\DeliveryOffer;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverOfferSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Driver $driver,
        public Order $order,
        public DeliveryOffer $offer,
        public int $timeoutSec,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("driver.{$this->driver->id}")];
    }

    public function broadcastAs(): string
    {
        return 'driver.offer';
    }

    public function broadcastWith(): array
    {
        $restaurant = $this->order->restaurant;
        return [
            'offer_id' => $this->offer->id,
            'order_no' => $this->order->order_no,
            'restaurant_name' => $restaurant->name,
            'restaurant_lat' => $restaurant->lat,
            'restaurant_lng' => $restaurant->lng,
            'restaurant_address' => $restaurant->address,
            'delivery_fee' => $this->order->delivery_fee,
            'distance_km' => $this->order->distance_km,
            'timeout_sec' => $this->timeoutSec,
        ];
    }
}
