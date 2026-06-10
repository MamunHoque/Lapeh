<?php

namespace App\Events;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Driver $driver,
        public ?Order $activeOrder = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.dispatch')];

        if ($this->activeOrder) {
            $channels[] = new PrivateChannel("order.{$this->activeOrder->id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'driver.location';
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driver->id,
            'lat' => $this->driver->current_lat,
            'lng' => $this->driver->current_lng,
            'order_id' => $this->activeOrder?->id,
        ];
    }
}
