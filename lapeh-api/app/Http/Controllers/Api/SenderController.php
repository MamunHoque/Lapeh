<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\DriverRating;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Services\OrderService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SenderController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $sender = $request->user()->sender;
        $today = now()->startOfDay();

        $active = Order::where('sender_id', $sender->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['driver.user', 'items'])
            ->orderByDesc('created_at')
            ->get();

        $todayStats = Order::where('sender_id', $sender->id)
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

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $sender = $user->sender;

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'business_category' => 'nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'default_pickup_address' => 'nullable|string|max:500',
            'default_pickup_lat' => 'nullable|numeric|between:-90,90',
            'default_pickup_lng' => 'nullable|numeric|between:-180,180',
        ]);

        if ($request->has('name')) {
            $user->update(['name' => $data['name']]);
        }

        // type (individual/business) is set at registration and stays read-only.
        $sender->update(collect($data)->except('name')->all());

        ActivityLog::record('sender.profile_updated', $sender, ['name' => $user->name]);

        return response()->json(['user' => $user->fresh(['sender'])->apiPayload()]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $user = $request->user();

        // Unverified senders cannot create delivery requests.
        abort_unless($user->isPhoneVerified(), 403, 'Verify your phone number first.');

        $sender = $user->sender;

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string',
            'notes' => 'nullable|string',
            // Pickup (prefilled from sender default, editable per request)
            'pickup_address' => 'nullable|string|max:500',
            'pickup_lat' => 'nullable|numeric|between:-90,90',
            'pickup_lng' => 'nullable|numeric|between:-180,180',
            // Package items
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string|max:500',
        ]);

        // Fall back to the sender's default pickup when not provided per request.
        $pickupAddress = $data['pickup_address'] ?? $sender->default_pickup_address;
        $pickupLat = $data['pickup_lat'] ?? $sender->default_pickup_lat;
        $pickupLng = $data['pickup_lng'] ?? $sender->default_pickup_lng;

        $orderValue = collect($data['items'])
            ->sum(fn($i) => (float) $i['unit_price'] * (int) $i['quantity']);

        $order = Order::create([
            'order_no' => OrderService::generateOrderNo(),
            'sender_id' => $sender->id,
            'pickup_address' => $pickupAddress,
            'pickup_lat' => $pickupLat,
            'pickup_lng' => $pickupLng,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'order_value' => $orderValue,
            'notes' => $data['notes'] ?? null,
            'status' => 'waiting_for_location',
            'location_token' => OrderService::generateLocationToken(),
            'otp_code' => OrderService::generateOtp(),
        ]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => round((float) $item['unit_price'] * (int) $item['quantity'], 2),
                'description' => $item['description'] ?? null,
            ]);
        }

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'waiting_for_location',
            'actor' => $user->id,
        ]);

        $customerLink = url("/c/{$order->location_token}");

        app(SmsService::class)->send(
            $order->customer_phone,
            'order_created',
            ['link' => $customerLink, 'order_no' => $order->order_no, 'sender' => $sender->displayName()],
            $user->locale ?? 'en',
        );

        ActivityLog::record('order.created', $order, [
            'order_no' => $order->order_no,
            'customer_name' => $order->customer_name,
            'order_value' => (float) $order->order_value,
        ]);

        return response()->json([
            'order' => $this->orderDetail($order->fresh(['sender.user', 'statusLogs', 'items'])),
            'customer_link' => $customerLink,
        ], 201);
    }

    public function listOrders(Request $request): JsonResponse
    {
        $sender = $request->user()->sender;

        $query = Order::where('sender_id', $sender->id)
            ->with(['driver.user', 'items'])
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
        abort_unless($order->sender_id === $request->user()->sender->id, 403);

        return response()->json([
            'order' => $this->orderDetail($order->load(['sender.user', 'driver.user', 'statusLogs', 'proof', 'rating', 'items'])),
        ]);
    }

    public function resendLink(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->sender_id === $request->user()->sender->id, 403);

        $link = url("/c/{$order->location_token}");
        app(SmsService::class)->send(
            $order->customer_phone,
            'order_created',
            ['link' => $link, 'order_no' => $order->order_no, 'sender' => $request->user()->sender->displayName()],
        );

        ActivityLog::record('order.link_resent', $order, ['order_no' => $order->order_no]);

        return response()->json(['message' => 'Link resent.', 'customer_link' => $link]);
    }

    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->sender_id === $request->user()->sender->id, 403);
        abort_if($order->isTerminal(), 422, 'Order already in terminal state.');

        $data = $request->validate(['reason' => 'nullable|string']);

        $order->update(['status' => 'cancelled', 'cancelled_reason' => $data['reason'] ?? null]);

        OrderStatusLog::create([
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
        abort_unless($order->sender_id === $request->user()->sender->id, 403);
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
            'sender_id' => $order->sender_id,
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
        $sender = $request->user()->sender;

        $orders = Order::where('sender_id', $sender->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with(['driver.user', 'items'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'orders' => $orders->through(fn($o) => $this->orderSummary($o)),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $sender = $request->user()->sender;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $todayAgg = Order::where('sender_id', $sender->id)
            ->where('created_at', '>=', $today)
            ->selectRaw('
                COUNT(*) as orders,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = "delivered" THEN delivery_fee ELSE 0 END) as revenue,
                SUM(CASE WHEN status = "delivered" THEN COALESCE(sender_commission, 0) ELSE 0 END) as commission,
                AVG(CASE WHEN status = "delivered" THEN delivery_fee ELSE NULL END) as avg_fee
            ')->first();

        $yesterdayRevenue = Order::where('sender_id', $sender->id)
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$yesterday, $today])
            ->sum('delivery_fee');

        $recent = Order::where('sender_id', $sender->id)
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
                'commission' => (float) ($todayAgg->commission ?? 0),
                'avg_fee' => (float) ($todayAgg->avg_fee ?? 0),
            ],
            'yesterday_revenue' => (float) $yesterdayRevenue,
            'recent' => $recent->map(fn($o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'customer_name' => $o->customer_name,
                'status' => $o->status,
                'delivery_fee' => (float) ($o->delivery_fee ?? 0),
                'commission' => (float) ($o->sender_commission ?? 0),
                'created_at' => $o->created_at,
            ]),
        ]);
    }

    public function createComplaint(Request $request): JsonResponse
    {
        $sender = $request->user()->sender;

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
            'sender_id' => $sender->id,
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
            'sender' => $sender->displayName(),
        ]);

        return response()->json(['complaint' => $complaint->load('attachments')], 201);
    }

    public function listComplaints(Request $request): JsonResponse
    {
        $sender = $request->user()->sender;

        $complaints = Complaint::where('sender_id', $sender->id)
            ->with(['order', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['complaints' => $complaints]);
    }

    protected function itemsPayload(Order $order): array
    {
        return $order->items->map(fn($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'quantity' => $i->quantity,
            'unit_price' => (float) $i->unit_price,
            'total_price' => (float) $i->total_price,
            'description' => $i->description,
        ])->all();
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
            'items_count' => $order->relationLoaded('items') ? $order->items->count() : null,
            'items' => $order->relationLoaded('items') ? $this->itemsPayload($order) : [],
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
            'customer_address' => $order->customer_address,
            'customer_lat' => $order->customer_lat,
            'customer_lng' => $order->customer_lng,
            'pickup_address' => $order->pickup_address,
            'pickup_lat' => $order->pickup_lat,
            'pickup_lng' => $order->pickup_lng,
            'sender_name' => $order->sender?->displayName(),
            'items' => $this->itemsPayload($order),
            'otp_code' => $order->otp_code,
            'assigned_at' => $order->assigned_at,
            'picked_up_at' => $order->picked_up_at,
            'delivered_at' => $order->delivered_at,
            'cancelled_reason' => $order->cancelled_reason,
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
