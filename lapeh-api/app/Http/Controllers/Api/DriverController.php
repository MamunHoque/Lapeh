<?php

namespace App\Http\Controllers\Api;

use App\Events\DriverLocationUpdated;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DeliveryOffer;
use App\Models\DeliveryProof;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Services\DispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate(['status' => 'required|in:online,offline']);
        $driver = $request->user()->driver;
        $driver->update(['status' => $request->status]);
        ActivityLog::record("driver.{$request->status}", $driver, ['name' => $request->user()->name]);
        return response()->json(['status' => $driver->status]);
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $driver = $request->user()->driver;
        $driver->update([
            'current_lat' => $request->lat,
            'current_lng' => $request->lng,
            'last_location_at' => now(),
        ]);

        $activeOrder = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['driver_assigned', 'arrived_at_restaurant', 'picked_up', 'on_the_way'])
            ->first();

        broadcast(new DriverLocationUpdated($driver, $activeOrder));

        return response()->json(['message' => 'Location updated.']);
    }

    public function currentOffer(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $offer = DeliveryOffer::where('driver_id', $driver->id)
            ->where('status', 'offered')
            ->with(['order.restaurant'])
            ->latest()
            ->first();

        if (!$offer) {
            return response()->json(['offer' => null]);
        }

        $settings = \App\Models\PricingSetting::current();
        $restaurant = $offer->order->restaurant;

        return response()->json([
            'offer' => [
                'id' => $offer->id,
                'order_no' => $offer->order->order_no,
                'restaurant_name' => $restaurant->name,
                'restaurant_lat' => $restaurant->lat,
                'restaurant_lng' => $restaurant->lng,
                'restaurant_address' => $restaurant->address,
                'delivery_fee' => $offer->order->delivery_fee,
                'distance_km' => $offer->order->distance_km,
                'timeout_sec' => $settings->request_timeout_sec,
                'offered_at' => $offer->offered_at,
            ],
        ]);
    }

    public function acceptOffer(Request $request, DeliveryOffer $offer): JsonResponse
    {
        $driver = $request->user()->driver;
        abort_unless($offer->driver_id === $driver->id, 403);
        abort_unless($offer->status === 'offered', 422, 'Offer no longer available.');

        $order = app(DispatchService::class)->accept($offer);

        ActivityLog::record('offer.accepted', $order, [
            'order_no' => $order->order_no,
            'driver' => $request->user()->name,
        ]);

        return response()->json([
            'message' => 'Offer accepted.',
            'order' => $this->activeOrderPayload($order->fresh(['restaurant'])),
        ]);
    }

    public function rejectOffer(Request $request, DeliveryOffer $offer): JsonResponse
    {
        $driver = $request->user()->driver;
        abort_unless($offer->driver_id === $driver->id, 403);
        abort_unless($offer->status === 'offered', 422, 'Offer no longer available.');

        app(DispatchService::class)->reject($offer);

        ActivityLog::record('offer.rejected', $offer->order, [
            'order_no' => $offer->order?->order_no,
            'driver' => $request->user()->name,
        ]);

        return response()->json(['message' => 'Offer rejected.']);
    }

    public function currentOrder(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $order = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['driver_assigned', 'arrived_at_restaurant', 'picked_up', 'on_the_way'])
            ->with(['restaurant', 'statusLogs'])
            ->first();

        return response()->json([
            'order' => $order ? $this->activeOrderPayload($order) : null,
        ]);
    }

    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $driver = $request->user()->driver;
        abort_unless($order->driver_id === $driver->id, 403);

        $request->validate([
            'status' => 'required|in:arrived_at_restaurant,picked_up,on_the_way',
        ]);

        $transitions = [
            'arrived_at_restaurant' => ['driver_assigned'],
            'picked_up' => ['arrived_at_restaurant'],
            'on_the_way' => ['picked_up'],
        ];

        $allowed = $transitions[$request->status] ?? [];
        abort_unless(in_array($order->status, $allowed), 422, "Cannot transition from {$order->status} to {$request->status}.");

        $updateData = ['status' => $request->status];
        if ($request->status === 'picked_up') $updateData['picked_up_at'] = now();

        $order->update($updateData);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $request->status,
            'actor' => $request->user()->id,
        ]);

        broadcast(new OrderStatusUpdated($order->fresh(['driver', 'restaurant'])));

        ActivityLog::record('order.status_updated', $order, [
            'order_no' => $order->order_no,
            'status' => $request->status,
            'driver' => $request->user()->name,
        ]);

        return response()->json(['message' => 'Status updated.', 'status' => $order->status]);
    }

    public function deliver(Request $request, Order $order): JsonResponse
    {
        $driver = $request->user()->driver;
        abort_unless($order->driver_id === $driver->id, 403);
        abort_unless($order->status === 'on_the_way', 422, 'Order not in on_the_way state.');

        $request->validate([
            'otp' => 'required|string|size:4',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
        ]);

        if ($request->otp !== $order->otp_code) {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('proofs', 'public')
            : null;

        $signaturePath = $request->hasFile('signature')
            ? $request->file('signature')->store('signatures', 'public')
            : null;

        DeliveryProof::create([
            'order_id' => $order->id,
            'photo_path' => $photoPath,
            'signature_path' => $signaturePath,
            'otp_verified' => true,
            'captured_at' => now(),
        ]);

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'actor' => $request->user()->id,
        ]);

        $driver->update(['status' => 'online']);

        broadcast(new OrderStatusUpdated($order->fresh(['driver', 'restaurant'])));

        ActivityLog::record('order.delivered', $order, [
            'order_no' => $order->order_no,
            'driver' => $request->user()->name,
            'delivery_fee' => (float) $order->delivery_fee,
        ]);

        return response()->json(['message' => 'Delivery confirmed.']);
    }

    public function earnings(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;
        $today = now()->startOfDay();

        $todayEarnings = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', $today)
            ->sum('delivery_fee');

        $history = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->with(['restaurant'])
            ->orderByDesc('delivered_at')
            ->paginate(20);

        return response()->json([
            'today' => round($todayEarnings, 2),
            'history' => $history->through(fn($o) => [
                'order_no' => $o->order_no,
                'restaurant' => $o->restaurant->name,
                'area' => $o->customer_address,
                'earning' => $o->delivery_fee,
                'delivered_at' => $o->delivered_at,
            ]),
        ]);
    }

    protected function activeOrderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'otp_code' => $order->otp_code,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'customer_lat' => $order->customer_lat,
            'customer_lng' => $order->customer_lng,
            'order_value' => $order->order_value,
            'delivery_fee' => $order->delivery_fee,
            'distance_km' => $order->distance_km,
            'restaurant_name' => $order->restaurant->name,
            'restaurant_lat' => $order->restaurant->lat,
            'restaurant_lng' => $order->restaurant->lng,
            'restaurant_address' => $order->restaurant->address,
            'restaurant_phone' => $order->restaurant->phone,
            'created_at' => $order->created_at,
        ];
    }
}
