<?php

namespace App\Http\Controllers\Customer;

use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\ActivityLog;
use App\Services\FeeCalculator;
use App\Services\MapService;
use App\Services\DispatchService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function show(Request $request, string $token)
    {
        $order = Order::where('location_token', $token)
            ->with(['sender.user', 'driver', 'statusLogs', 'items'])
            ->firstOrFail();

        // Locale: ?lang override → session → English.
        $locale = $request->query('lang');
        if (! in_array($locale, ['en', 'ar'], true)) {
            $locale = session('customer_locale', 'en');
        }
        session(['customer_locale' => $locale]);
        app()->setLocale($locale);
        $rtl = $locale === 'ar';
        $mapsKey = config('services.google_maps.key');

        return view('customer.order', compact('order', 'locale', 'rtl', 'mapsKey'));
    }

    public function confirmLocation(Request $request, string $token)
    {
        $order = Order::where('location_token', $token)->firstOrFail();

        abort_unless(
            in_array($order->status, ['waiting_for_location', 'location_confirmed']),
            422,
            'Location already confirmed or order in wrong state.'
        );

        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
        ]);

        $map = app(MapService::class);
        $calculator = app(FeeCalculator::class);

        // Distance from the order's pickup location to the receiver.
        $pickupLat = $order->pickup_lat ?? $data['lat'];
        $pickupLng = $order->pickup_lng ?? $data['lng'];
        $distanceKm = $map->distanceKm((float) $pickupLat, (float) $pickupLng, $data['lat'], $data['lng']);
        $feeData = $calculator->calculate($distanceKm);
        $address = $data['address'] ?? $map->reverseGeocode($data['lat'], $data['lng']);

        $order->update([
            'customer_lat' => $data['lat'],
            'customer_lng' => $data['lng'],
            'customer_address' => $address,
            'distance_km' => $feeData['distance_km'],
            'delivery_fee' => $feeData['delivery_fee'],
            'total_amount' => round($order->order_value + $feeData['delivery_fee'], 2),
            'status' => 'location_confirmed',
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'location_confirmed',
            'note' => "Customer confirmed: {$address}",
        ]);

        broadcast(new OrderStatusUpdated($order->fresh()));

        \App\Models\ActivityLog::record('order.location_confirmed', $order, [
            'order_no' => $order->order_no,
            'address' => $address,
            'distance_km' => (float) $feeData['distance_km'],
        ], null, 'customer');

        if (request()->wantsJson()) {
            return response()->json([
                'distance_km' => $feeData['distance_km'],
                'delivery_fee' => $feeData['delivery_fee'],
                'total_amount' => $order->total_amount,
                'status' => 'location_confirmed',
            ]);
        }

        return redirect("/c/{$token}");
    }

    public function payIntent(Request $request, string $token)
    {
        $order = Order::where('location_token', $token)
            ->with('sender')
            ->firstOrFail();

        abort_unless($order->status === 'location_confirmed', 422, 'Confirm location first.');

        // Route through the active gateway (Stripe or Telr). In sandbox mode
        // with no live credentials the driver auto-approves, preserving the
        // local demo flow; with live keys it returns a hosted-checkout redirect.
        $gateway = app(PaymentManager::class)->active();
        $result = $gateway->charge($order);

        if ($result['status'] === 'paid') {
            $this->recordPaidPayment($order, $gateway->name(), $result['reference']);
            $this->advanceOrderPaid($order, $gateway->name());

            if ($request->wantsJson()) {
                return response()->json(['status' => 'paid', 'message' => $result['message'] ?? 'Payment accepted.']);
            }
            return redirect("/c/{$token}");
        }

        if ($result['status'] === 'pending') {
            Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'currency' => $this->currency(),
                'gateway' => $gateway->name(),
                'gateway_reference' => $result['reference'],
                'status' => 'pending',
            ]);
            $order->update(['status' => 'waiting_for_payment']);

            if (! empty($result['redirect_url']) && ! $request->wantsJson()) {
                return redirect($result['redirect_url']);
            }
            return response()->json([
                'status' => 'pending',
                'gateway' => $gateway->name(),
                'reference' => $result['reference'],
                'redirect_url' => $result['redirect_url'] ?? null,
            ]);
        }

        return response()->json(['message' => $result['message'] ?? 'Payment failed.'], 502);
    }

    private function currency(): string
    {
        return (string) settings('payment.currency', 'AED');
    }

    /** Upsert the order's payment row as paid. */
    private function recordPaidPayment(Order $order, string $gateway, string $reference): void
    {
        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'amount' => $order->total_amount,
                'currency' => $this->currency(),
                'gateway' => $gateway,
                'gateway_reference' => $reference,
                'status' => 'paid',
                'paid_at' => now(),
            ],
        );
    }

    /** Move a paid order into dispatch and notify drivers. Idempotent. */
    private function advanceOrderPaid(Order $order, string $gateway): void
    {
        if (in_array($order->status, ['searching_driver', 'driver_assigned', 'arrived_at_pickup', 'picked_up', 'on_the_way', 'delivered'])) {
            return;
        }

        $order->update(['payment_status' => 'paid', 'status' => 'paid']);
        OrderStatusLog::create(['order_id' => $order->id, 'status' => 'paid', 'note' => ucfirst($gateway) . ' payment']);

        $order->update(['status' => 'searching_driver']);
        OrderStatusLog::create(['order_id' => $order->id, 'status' => 'searching_driver']);

        broadcast(new OrderStatusUpdated($order->fresh()));
        app(DispatchService::class)->dispatch($order);

        ActivityLog::record('order.paid', $order, [
            'order_no' => $order->order_no,
            'amount' => (float) $order->total_amount,
            'gateway' => $gateway,
        ], null, 'customer');
    }

    public function track(string $token)
    {
        $order = Order::where('location_token', $token)
            ->with(['driver', 'statusLogs'])
            ->firstOrFail();

        return response()->json([
            'status' => $order->status,
            'driver' => $order->driver ? [
                'name' => $order->driver->user->name ?? null,
                'lat' => $order->driver->current_lat,
                'lng' => $order->driver->current_lng,
                'vehicle_type' => $order->driver->vehicle_type,
            ] : null,
            'timeline' => $order->statusLogs->map(fn($l) => [
                'status' => $l->status,
                'note' => $l->note,
                'at' => $l->created_at,
            ]),
            'otp_code' => in_array($order->status, ['on_the_way', 'delivered']) ? $order->otp_code : null,
        ]);
    }

    public function paymentWebhook(Request $request)
    {
        // Verify the signature against the active gateway (Stripe or Telr).
        $gateway = app(PaymentManager::class)->active();
        abort_unless($gateway->verifyWebhook($request), 401, 'Invalid webhook signature.');

        Log::info('Payment webhook received', ['gateway' => $gateway->name()]);

        // Reconcile by the gateway reference present in the payload.
        $ref = $request->input('reference')
            ?? $request->input('id')
            ?? $request->input('data.object.id')
            ?? $request->input('tran_ref')
            ?? data_get($request->all(), 'order.ref');

        if ($ref) {
            $payment = Payment::where('gateway_reference', $ref)->first();
            if ($payment && $payment->status !== 'paid') {
                $payment->update(['status' => 'paid', 'paid_at' => now()]);
                if ($payment->order) {
                    $this->advanceOrderPaid($payment->order, $gateway->name());
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
