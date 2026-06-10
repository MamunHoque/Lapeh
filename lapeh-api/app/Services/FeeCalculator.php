<?php

namespace App\Services;

use App\Models\PricingSetting;
use App\Models\Zone;

class FeeCalculator
{
    public function calculate(float $distanceKm, ?int $zoneId = null): array
    {
        $settings = PricingSetting::current();

        $baseFee = $settings->base_fee;
        $perKmFee = $settings->per_km_fee;
        $minFee = $settings->min_fee;

        if ($zoneId) {
            $zone = Zone::find($zoneId);
            if ($zone) {
                if ($zone->base_fee !== null) $baseFee = $zone->base_fee;
                if ($zone->per_km_fee !== null) $perKmFee = $zone->per_km_fee;
            }
        }

        $fee = $baseFee + ($perKmFee * $distanceKm);
        $fee = max($fee, $minFee);
        $deliveryFee = round($fee, 2);

        return [
            'base_fee' => $baseFee,
            'per_km_fee' => $perKmFee,
            'distance_km' => round($distanceKm, 2),
            'delivery_fee' => $deliveryFee,
        ];
    }
}
