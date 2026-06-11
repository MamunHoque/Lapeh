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
            ->whereIn('status', ['driver_assigned', 'arrived_at_pickup', 'picked_up', 'on_the_way'])
            ->first();

        broadcast(new DriverLocationUpdated($driver, $activeOrder));

        return response()->json(['message' => 'Location updated.']);
    }

    public function currentOffer(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $offer = DeliveryOffer::where('driver_id', $driver->id)
            ->where('status', 'offered')
            ->with(['order.sender'])
            ->latest()
            ->first();

        if (!$offer) {
            return response()->json(['offer' => null]);
        }

        $settings = \App\Models\PricingSetting::current();
        $order = $offer->order;

        return response()->json([
            'offer' => [
                'id' => $offer->id,
                'order_no' => $order->order_no,
                'pickup_name' => $order->sender?->displayName(),
                'pickup_lat' => $order->pickup_lat,
                'pickup_lng' => $order->pickup_lng,
                'pickup_address' => $order->pickup_address,
                'delivery_fee' => $order->delivery_fee,
                'distance_km' => $order->distance_km,
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
            'order' => $this->activeOrderPayload($order->fresh(['sender'])),
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
            ->whereIn('status', ['driver_assigned', 'arrived_at_pickup', 'picked_up', 'on_the_way'])
            ->with(['sender', 'statusLogs', 'items'])
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
            'status' => 'required|in:arrived_at_pickup,picked_up,on_the_way',
        ]);

        $transitions = [
            'arrived_at_pickup' => ['driver_assigned'],
            'picked_up' => ['arrived_at_pickup'],
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

        broadcast(new OrderStatusUpdated($order->fresh(['driver', 'sender'])));

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

        // Snapshot platform commission / driver payout from current settings.
        $commission = app(\App\Services\CommissionService::class)->snapshot($order);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'actor' => $request->user()->id,
        ]);

        $driver->update(['status' => 'online']);

        broadcast(new OrderStatusUpdated($order->fresh(['driver', 'sender'])));

        ActivityLog::record('order.delivered', $order, [
            'order_no' => $order->order_no,
            'driver' => $request->user()->name,
            'delivery_fee' => (float) $order->delivery_fee,
            'driver_payout' => $commission['driver_payout'],
            'platform_revenue' => $commission['platform_revenue'],
        ]);

        return response()->json(['message' => 'Delivery confirmed.']);
    }

    public function earnings(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        // "earnings" is the driver's net take-home (delivery fee minus the
        // platform's commission). COALESCE keeps pre-commission orders correct.
        $net = 'COALESCE(driver_payout, delivery_fee)';

        // Aggregate delivered earnings for this driver since $from (null = all time).
        $agg = function (?\Carbon\CarbonInterface $from) use ($driver, $net) {
            return Order::where('driver_id', $driver->id)
                ->where('status', 'delivered')
                ->when($from, fn($q) => $q->where('delivered_at', '>=', $from))
                ->selectRaw("
                    COUNT(*) as trips,
                    SUM($net) as earnings,
                    SUM(delivery_fee) as gross,
                    SUM(COALESCE(driver_commission, 0)) as commission,
                    AVG($net) as avg_earning,
                    SUM(distance_km) as distance_km
                ")->first();
        };

        $todayAgg = $agg($today);
        $weekAgg = $agg($weekStart);
        $monthAgg = $agg($monthStart);
        $allAgg = $agg(null);

        // PDO returns aggregate columns as strings — cast explicitly.
        $period = fn($a) => [
            'earnings' => (float) ($a->earnings ?? 0),
            'gross' => (float) ($a->gross ?? 0),
            'commission' => (float) ($a->commission ?? 0),
            'trips' => (int) $a->trips,
            'avg_earning' => (float) ($a->avg_earning ?? 0),
            'distance_km' => (float) ($a->distance_km ?? 0),
        ];

        $yesterdayEarnings = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$yesterday, $today])
            ->selectRaw("SUM($net) as e")->value('e');

        // Last 7 days, grouped by date; fill gaps so every day is present.
        $rows = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw("DATE(delivered_at) as d, SUM($net) as earnings, COUNT(*) as trips")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $row = $rows->get($date);
            $daily[] = [
                'date' => $date,
                'earnings' => (float) ($row->earnings ?? 0),
                'trips' => (int) ($row->trips ?? 0),
            ];
        }

        $history = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->with(['sender.user'])
            ->orderByDesc('delivered_at')
            ->paginate(20);

        return response()->json([
            'today' => $period($todayAgg),
            'week' => $period($weekAgg),
            'month' => $period($monthAgg),
            'all_time' => [
                'earnings' => (float) ($allAgg->earnings ?? 0),
                'commission' => (float) ($allAgg->commission ?? 0),
                'trips' => (int) $allAgg->trips,
            ],
            'yesterday_earnings' => (float) $yesterdayEarnings,
            'daily_breakdown' => $daily,
            'history' => $history->through(fn($o) => [
                'order_no' => $o->order_no,
                'sender' => $o->sender?->displayName(),
                'area' => $o->customer_address,
                'earning' => (float) ($o->driver_payout ?? $o->delivery_fee),
                'gross' => (float) $o->delivery_fee,
                'commission' => (float) ($o->driver_commission ?? 0),
                'distance_km' => $o->distance_km !== null ? (float) $o->distance_km : null,
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
            // Pickup details (was restaurant) the driver navigates to first.
            'pickup_name' => $order->sender?->displayName(),
            'pickup_lat' => $order->pickup_lat,
            'pickup_lng' => $order->pickup_lng,
            'pickup_address' => $order->pickup_address,
            'pickup_phone' => $order->sender?->user?->phone,
            'items' => $order->relationLoaded('items') ? $order->items->map(fn($i) => [
                'name' => $i->name,
                'quantity' => $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'total_price' => (float) $i->total_price,
                'description' => $i->description,
            ])->all() : [],
            'created_at' => $order->created_at,
        ];
    }
}
