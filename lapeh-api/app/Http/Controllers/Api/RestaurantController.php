<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\DriverRating;
use App\Models\Order;
use App\Services\DispatchService;
use App\Services\FeeCalculator;
use App\Services\MapService;
use App\Services\OrderService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $today = now()->startOfDay();

        $active = Order::where('restaurant_id', $restaurant->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['driver.user'])
            ->orderByDesc('created_at')
            ->get();

        $todayStats = Order::where('restaurant_id', $restaurant->id)
            ->where('created_at', '>=', $today)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = "delivered" THEN delivery_fee ELSE 0 END) as revenue
            ')
            ->first();

        return response()->json([
            // selectRaw aggregates come back as strings from PDO — cast explicitly
            'stats' => [
                'total' => (int) $todayStats->total,
                'delivered' => (int) $todayStats->delivered,
                'cancelled' => (int) $todayStats->cancelled,
                'revenue' => (float) $todayStats->revenue,
            ],
            'active_deliveries' => $active->map(fn($o) => $this->orderSummary($o)),
        ]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string',
            'order_value' => 'required|numeric|min:0',
            'prep_time_min' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $order = Order::create([
            'order_no' => OrderService::generateOrderNo(),
            'restaurant_id' => $restaurant->id,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'order_value' => $data['order_value'],
            'prep_time_min' => $data['prep_time_min'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'waiting_for_location',
            'location_token' => OrderService::generateLocationToken(),
            'otp_code' => OrderService::generateOtp(),
        ]);

        \App\Models\OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'waiting_for_location',
            'actor' => $request->user()->id,
        ]);

        $customerLink = url("/c/{$order->location_token}");

        app(SmsService::class)->send(
            $order->customer_phone,
            'order_created',
            ['link' => $customerLink, 'order_no' => $order->order_no],
            $request->user()->locale ?? 'en',
        );

        ActivityLog::record('order.created', $order, [
            'order_no' => $order->order_no,
            'customer_name' => $order->customer_name,
            'order_value' => (float) $order->order_value,
        ]);

        return response()->json([
            'order' => $this->orderDetail($order->fresh(['restaurant', 'statusLogs'])),
            'customer_link' => $customerLink,
        ], 201);
    }

    public function listOrders(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $query = Order::where('restaurant_id', $restaurant->id)
            ->with(['driver.user'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'orders' => $query->paginate(20)->through(fn($o) => $this->orderSummary($o)),
        ]);
    }

    public function showOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->restaurant_id === $request->user()->restaurant->id, 403);

        return response()->json([
            'order' => $this->orderDetail($order->load(['restaurant', 'driver.user', 'statusLogs', 'proof', 'rating'])),
        ]);
    }

    public function resendLink(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->restaurant_id === $request->user()->restaurant->id, 403);

        $link = url("/c/{$order->location_token}");
        app(SmsService::class)->send(
            $order->customer_phone,
            'order_created',
            ['link' => $link, 'order_no' => $order->order_no],
        );

        ActivityLog::record('order.link_resent', $order, ['order_no' => $order->order_no]);

        return response()->json(['message' => 'Link resent.', 'customer_link' => $link]);
    }

    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->restaurant_id === $request->user()->restaurant->id, 403);
        abort_if($order->isTerminal(), 422, 'Order already in terminal state.');

        $data = $request->validate(['reason' => 'nullable|string']);

        $order->update(['status' => 'cancelled', 'cancelled_reason' => $data['reason'] ?? null]);

        \App\Models\OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'cancelled',
            'actor' => $request->user()->id,
            'note' => $data['reason'] ?? null,
        ]);

        if ($order->driver_id) {
            $order->driver->update(['status' => 'online']);
        }

        broadcast(new \App\Events\OrderStatusUpdated($order));

        ActivityLog::record('order.cancelled', $order, [
            'order_no' => $order->order_no,
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json(['message' => 'Order cancelled.']);
    }

    public function rateDriver(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->restaurant_id === $request->user()->restaurant->id, 403);
        abort_unless($order->status === 'delivered', 422, 'Can only rate delivered orders.');
        abort_if($order->rating()->exists(), 422, 'Already rated.');

        $allowedTags = implode(',', array_keys(config('lapeh.rating_tags')));
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'tags' => 'nullable|array',
            'tags.*' => "string|in:{$allowedTags}",
            'comment' => 'nullable|string|max:500',
        ]);

        $rating = DriverRating::create([
            'order_id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'driver_id' => $order->driver_id,
            'rating' => $data['rating'],
            'tags' => $data['tags'] ?? [],
            'comment' => $data['comment'] ?? null,
        ]);

        // Update driver average
        $driver = $order->driver;
        $avg = DriverRating::where('driver_id', $driver->id)->avg('rating');
        $count = DriverRating::where('driver_id', $driver->id)->count();
        $driver->update(['rating_avg' => round($avg, 2), 'rating_count' => $count]);

        ActivityLog::record('order.rated', $order, [
            'order_no' => $order->order_no,
            'rating' => $data['rating'],
            'driver' => $driver->user->name ?? null,
        ]);

        return response()->json(['rating' => $rating], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $orders = Order::where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with(['driver.user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'orders' => $orders->through(fn($o) => $this->orderSummary($o)),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $todayAgg = Order::where('restaurant_id', $restaurant->id)
            ->where('created_at', '>=', $today)
            ->selectRaw('
                COUNT(*) as orders,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = "delivered" THEN delivery_fee ELSE 0 END) as revenue,
                AVG(CASE WHEN status = "delivered" THEN delivery_fee ELSE NULL END) as avg_fee
            ')->first();

        $yesterdayRevenue = Order::where('restaurant_id', $restaurant->id)
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$yesterday, $today])
            ->sum('delivery_fee');

        $recent = Order::where('restaurant_id', $restaurant->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()->json([
            // PDO returns aggregate columns as strings — cast explicitly.
            'today' => [
                'orders' => (int) $todayAgg->orders,
                'delivered' => (int) $todayAgg->delivered,
                'cancelled' => (int) $todayAgg->cancelled,
                'revenue' => (float) $todayAgg->revenue,
                'avg_fee' => (float) ($todayAgg->avg_fee ?? 0),
            ],
            'yesterday_revenue' => (float) $yesterdayRevenue,
            'recent' => $recent->map(fn($o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'customer_name' => $o->customer_name,
                'status' => $o->status,
                'delivery_fee' => (float) ($o->delivery_fee ?? 0),
                'created_at' => $o->created_at,
            ]),
        ]);
    }

    public function createComplaint(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $allowedTypes = implode(',', array_keys(config('lapeh.complaint_types')));
        $data = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'type' => "required|in:{$allowedTypes}",
            'description' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $complaint = Complaint::create([
            'order_id' => $data['order_id'] ?? null,
            'restaurant_id' => $restaurant->id,
            'type' => $data['type'],
            'description' => $data['description'],
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('complaints', 'public');
                ComplaintAttachment::create(['complaint_id' => $complaint->id, 'path' => $path]);
            }
        }

        ActivityLog::record('complaint.created', $complaint, [
            'type' => $complaint->type,
            'restaurant' => $restaurant->name,
        ]);

        return response()->json(['complaint' => $complaint->load('attachments')], 201);
    }

    public function listComplaints(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $complaints = Complaint::where('restaurant_id', $restaurant->id)
            ->with(['order', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['complaints' => $complaints]);
    }

    protected function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'delivery_fee' => $order->delivery_fee,
            'total_amount' => $order->total_amount,
            'order_value' => $order->order_value,
            'distance_km' => $order->distance_km,
            'location_token' => $order->location_token,
            'customer_link' => url("/c/{$order->location_token}"),
            'driver' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->user->name,
                'phone' => $order->driver->user->phone,
                'lat' => $order->driver->current_lat,
                'lng' => $order->driver->current_lng,
                'vehicle_type' => $order->driver->vehicle_type,
            ] : null,
            'created_at' => $order->created_at,
        ];
    }

    protected function orderDetail(Order $order): array
    {
        return array_merge($this->orderSummary($order), [
            'notes' => $order->notes,
            'prep_time_min' => $order->prep_time_min,
            'customer_address' => $order->customer_address,
            'customer_lat' => $order->customer_lat,
            'customer_lng' => $order->customer_lng,
            'restaurant_name' => $order->restaurant->name,
            'restaurant_lat' => $order->restaurant->lat,
            'restaurant_lng' => $order->restaurant->lng,
            'otp_code' => $order->otp_code,
            'assigned_at' => $order->assigned_at,
            'picked_up_at' => $order->picked_up_at,
            'delivered_at' => $order->delivered_at,
            'cancelled_reason' => $order->cancelled_reason,
            'location_token' => $order->location_token,
            'customer_link' => url("/c/{$order->location_token}"),
            'status_timeline' => $order->statusLogs?->map(fn($l) => [
                'status' => $l->status,
                'note' => $l->note,
                'at' => $l->created_at,
            ]),
            'proof' => $order->proof ? [
                'otp_verified' => $order->proof->otp_verified,
                'photo' => $order->proof->photo_path ? asset("storage/{$order->proof->photo_path}") : null,
                'captured_at' => $order->proof->captured_at,
            ] : null,
            'rating' => $order->rating ? [
                'rating' => $order->rating->rating,
                'tags' => $order->rating->tags,
                'comment' => $order->rating->comment,
            ] : null,
        ]);
    }
}
