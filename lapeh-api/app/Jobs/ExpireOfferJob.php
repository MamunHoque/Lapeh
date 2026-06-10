<?php

namespace App\Jobs;

use App\Models\DeliveryOffer;
use App\Services\DispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $offerId) {}

    public function handle(DispatchService $dispatch): void
    {
        $offer = DeliveryOffer::find($this->offerId);

        if (!$offer || $offer->status !== 'offered') {
            return;
        }

        $offer->update(['status' => 'expired', 'responded_at' => now()]);

        // Re-dispatch to next driver
        $dispatch->dispatch($offer->order);
    }
}
