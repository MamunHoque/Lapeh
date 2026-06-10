<?php

namespace App\Http\Controllers\Customer;

use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Services\FeeCalculator;
use App\Services\MapService;
use App\Services\DispatchService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function show(Request $request, string $token)
    {
        $order = Order::where('location_token', $token)
            ->with(['restaurant', 'driver', 'statusLogs'])
            ->firstOrFail();

        // Locale: ?lang override → session → order's stored locale → English.
        $locale = $request->query('lang');
        if (! in_array($locale, ['en', 'ar'], true)) {
            $locale = session('customer_locale', $order->customer_locale ?? 'en');
        }
        session(['customer_locale' => $locale]);
        app()->setLocale($locale);
        $rtl = $locale === 'ar';

        return view('customer.order', compact('order', 'locale', 'rtl'));
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

        $restaurant = $order->restaurant;
        $map = app(MapService::class);
        $calculator = app(FeeCalculator::class);

        $distanceKm = $map->distanceKm($restaurant->lat, $restaurant->lng, $data['lat'], $data['lng']);
        $feeData = $calculator->calculate($distanceKm, $restaurant->zone_id);
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
            ->with('restaurant')
            ->firstOrFail();

        abort_unless($order->status === 'location_confirmed', 422, 'Confirm location first.');

        // In dev: advance straight to paid (no real gateway)
        if (config('app.env') === 'local') {
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'currency' => 'AED',
                'gateway' => 'sandbox',
                'gateway_reference' => 'SANDBOX-' . strtoupper(uniqid()),
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $order->update(['payment_status' => 'paid', 'status' => 'paid']);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 'paid',
                'note' => 'Sandbox payment',
            ]);

            // Advance to searching_driver and trigger dispatch
            $order->update(['status' => 'searching_driver']);
            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 'searching_driver',
            ]);

            broadcast(new OrderStatusUpdated($order->fresh()));
            app(DispatchService::class)->dispatch($order);

            if (request()->wantsJson()) {
                return response()->json(['status' => 'paid', 'message' => 'Sandbox payment accepted.']);
            }

            return redirect("/c/{$token}");
        }

        // TODO: Real gateway integration
        return response()->json(['message' => 'Payment gateway not configured.'], 501);
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
        // Verify HMAC signature from the gateway before trusting the payload.
        $secret = config('services.payment.webhook_secret');
        abort_if(blank($secret), 500, 'Payment webhook secret not configured.');

        $signature = $request->header('X-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        abort_unless(hash_equals($expected, $signature), 401, 'Invalid webhook signature.');

        $payload = $request->all();
        \Illuminate\Support\Facades\Log::info('Payment webhook received', $payload);

        // Reconcile payment status from the verified payload.
        if (! empty($payload['reference'])) {
            $payment = Payment::where('gateway_reference', $payload['reference'])->first();
            if ($payment && ($payload['status'] ?? null) === 'paid') {
                $payment->update(['status' => 'paid', 'paid_at' => now()]);
            }
        }

        return response()->json(['received' => true]);
    }
}
