<?php

namespace App\Services;

use App\Models\Order;

/**
 * Platform commission on the delivery fee. The admin can charge the sender,
 * the driver, both, or neither (see the "commission" settings group). Each
 * side is independently a percentage or a fixed amount of the delivery fee.
 *
 *   driver_payout     = delivery_fee − driver_commission
 *   platform_revenue  = sender_commission + driver_commission
 *
 * Values are snapshotted onto the order at delivery so reports stay stable
 * even if the rates change later.
 */
class CommissionService
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Compute commission for a given delivery fee.
     *
     * @return array{sender_commission: float, driver_commission: float, driver_payout: float, platform_revenue: float}
     */
    public function compute(float $deliveryFee): array
    {
        $deliveryFee = max(0.0, $deliveryFee);

        $senderCommission = $this->settings->get('commission.charge_sender', false)
            ? $this->portion($deliveryFee, (string) $this->settings->get('commission.sender_type', 'percent'), (float) $this->settings->get('commission.sender_rate', 0))
            : 0.0;

        $driverCommission = $this->settings->get('commission.charge_driver', false)
            ? $this->portion($deliveryFee, (string) $this->settings->get('commission.driver_type', 'percent'), (float) $this->settings->get('commission.driver_rate', 0))
            : 0.0;

        // The driver's cut can never exceed the fee they earned.
        $driverCommission = min($driverCommission, $deliveryFee);

        return [
            'sender_commission' => round($senderCommission, 2),
            'driver_commission' => round($driverCommission, 2),
            'driver_payout' => round($deliveryFee - $driverCommission, 2),
            'platform_revenue' => round($senderCommission + $driverCommission, 2),
        ];
    }

    /** Persist the computed commission snapshot onto the order. */
    public function snapshot(Order $order): array
    {
        $c = $this->compute((float) ($order->delivery_fee ?? 0));

        $order->forceFill([
            'sender_commission' => $c['sender_commission'],
            'driver_commission' => $c['driver_commission'],
            'driver_payout' => $c['driver_payout'],
        ])->save();

        return $c;
    }

    private function portion(float $base, string $type, float $rate): float
    {
        return $type === 'fixed' ? $rate : $base * $rate / 100;
    }
}
