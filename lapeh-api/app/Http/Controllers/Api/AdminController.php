<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Driver;
use App\Models\DriverRating;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PricingSetting;
use App\Models\Sender;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Dashboard
    public function dashboard(): JsonResponse
    {
        $today = now()->startOfDay();

        $stats = [
            'orders_today' => Order::where('created_at', '>=', $today)->count(),
            'delivered_today' => Order::where('status', 'delivered')->where('delivered_at', '>=', $today)->count(),
            'revenue_today' => Order::where('status', 'delivered')->where('delivered_at', '>=', $today)->sum('delivery_fee'),
            'active_drivers' => Driver::whereIn('status', ['online', 'on_delivery'])->count(),
            'online_drivers' => Driver::where('status', 'online')->count(),
            'on_delivery' => Driver::where('status', 'on_delivery')->count(),
            'open_complaints' => Complaint::where('status', 'open')->count(),
            'total_senders' => Sender::where('status', 'active')->count(),
        ];

        $liveOrders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['sender.user', 'driver.user'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($o) => $this->orderRow($o));

        return response()->json(['stats' => $stats, 'live_orders' => $liveOrders]);
    }

    // Orders
    public function orders(Request $request): JsonResponse
    {
        $q = Order::with(['sender.user', 'driver.user'])->orderByDesc('created_at');

        if ($request->status) $q->where('status', $request->status);
        if ($request->sender_id) $q->where('sender_id', $request->sender_id);
        if ($request->date_from) $q->where('created_at', '>=', $request->date_from);
        if ($request->date_to) $q->where('created_at', '<=', $request->date_to);
        if ($request->search) $q->where(function ($q) use ($request) {
            $q->where('order_no', 'like', "%{$request->search}%")
              ->orWhere('customer_name', 'like', "%{$request->search}%")
              ->orWhere('customer_phone', 'like', "%{$request->search}%");
        });

        return response()->json(['orders' => $q->paginate(30)->through(fn($o) => $this->orderRow($o))]);
    }

    public function liveOrders(): JsonResponse
    {
        $orders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['sender.user', 'driver.user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => $this->orderRow($o));

        return response()->json(['orders' => $orders]);
    }

    // Senders CRUD
    public function indexSenders(): JsonResponse
    {
        return response()->json(['senders' => Sender::with('user')->paginate(30)]);
    }

    public function storeSender(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:individual,business',
            'business_name' => 'required_if:type,business|nullable|string',
            'business_category' => 'nullable|string',
            'contact_person_name' => 'nullable|string',
            'default_pickup_address' => 'nullable|string',
            'default_pickup_lat' => 'nullable|numeric',
            'default_pickup_lng' => 'nullable|numeric',
            'user_name' => 'required|string',
            'user_phone' => 'required|string|unique:users,phone',
            'user_password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['user_name'],
            'phone' => $data['user_phone'],
            'password' => $data['user_password'],
            'role' => 'sender',
            'phone_verified_at' => now(),
        ]);

        $sender = Sender::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'business_name' => $data['business_name'] ?? null,
            'business_category' => $data['business_category'] ?? null,
            'contact_person_name' => $data['contact_person_name'] ?? null,
            'default_pickup_address' => $data['default_pickup_address'] ?? null,
            'default_pickup_lat' => $data['default_pickup_lat'] ?? null,
            'default_pickup_lng' => $data['default_pickup_lng'] ?? null,
            'status' => 'active',
        ]);

        return response()->json(['sender' => $sender, 'user' => $user], 201);
    }

    public function showSender(Sender $sender): JsonResponse
    {
        return response()->json(['sender' => $sender->load('user')]);
    }

    public function updateSender(Request $request, Sender $sender): JsonResponse
    {
        $data = $request->validate([
            'type' => 'sometimes|in:individual,business',
            'business_name' => 'nullable|string',
            'business_category' => 'nullable|string',
            'contact_person_name' => 'nullable|string',
            'default_pickup_address' => 'nullable|string',
            'default_pickup_lat' => 'nullable|numeric',
            'default_pickup_lng' => 'nullable|numeric',
            'status' => 'sometimes|in:active,inactive,pending',
        ]);

        $sender->update($data);
        return response()->json(['sender' => $sender]);
    }

    public function destroySender(Sender $sender): JsonResponse
    {
        $sender->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // Drivers CRUD
    public function indexDrivers(Request $request): JsonResponse
    {
        $q = Driver::with(['user', 'fleet'])->orderByDesc('created_at');
        if ($request->status) $q->where('status', $request->status);
        return response()->json(['drivers' => $q->paginate(30)]);
    }

    public function storeDriver(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'vehicle_type' => 'required|in:bike,car',
            'vehicle_plate' => 'nullable|string',
            'fleet_id' => 'nullable|exists:fleets,id',
        ]);

        $user = User::create(['name' => $data['name'], 'phone' => $data['phone'], 'password' => $data['password'], 'role' => 'driver']);
        $driver = Driver::create([
            'user_id' => $user->id,
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'fleet_id' => $data['fleet_id'] ?? null,
        ]);

        return response()->json(['driver' => $driver->load('user'), 'user' => $user], 201);
    }

    public function updateDriver(Request $request, Driver $driver): JsonResponse
    {
        $data = $request->validate([
            'vehicle_type' => 'sometimes|in:bike,car',
            'vehicle_plate' => 'nullable|string',
            'fleet_id' => 'nullable|exists:fleets,id',
            'is_verified' => 'sometimes|boolean',
            'status' => 'sometimes|in:online,offline,on_delivery',
        ]);
        $driver->update($data);
        return response()->json(['driver' => $driver->load('user')]);
    }

    public function destroyDriver(Driver $driver): JsonResponse
    {
        $driver->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // Zones CRUD
    public function indexZones(): JsonResponse
    {
        return response()->json(['zones' => Zone::all()]);
    }

    public function storeZone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'polygon' => 'nullable|array',
            'base_fee' => 'nullable|numeric',
            'per_km_fee' => 'nullable|numeric',
            'status' => 'sometimes|in:active,inactive',
        ]);
        return response()->json(['zone' => Zone::create($data)], 201);
    }

    public function updateZone(Request $request, Zone $zone): JsonResponse
    {
        $zone->update($request->validate([
            'name' => 'sometimes|string',
            'polygon' => 'nullable|array',
            'base_fee' => 'nullable|numeric',
            'per_km_fee' => 'nullable|numeric',
            'status' => 'sometimes|in:active,inactive',
        ]));
        return response()->json(['zone' => $zone]);
    }

    public function destroyZone(Zone $zone): JsonResponse
    {
        $zone->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // Pricing
    public function getPricing(): JsonResponse
    {
        return response()->json(['pricing' => PricingSetting::current()]);
    }

    public function updatePricing(Request $request): JsonResponse
    {
        $data = $request->validate([
            'base_fee' => 'sometimes|numeric|min:0',
            'per_km_fee' => 'sometimes|numeric|min:0',
            'min_fee' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'search_radius_km' => 'sometimes|numeric|min:1',
            'request_timeout_sec' => 'sometimes|integer|min:10',
        ]);

        $pricing = PricingSetting::current();
        $pricing->update($data);
        return response()->json(['pricing' => $pricing]);
    }

    // Users
    public function indexUsers(): JsonResponse
    {
        return response()->json(['users' => User::orderByDesc('created_at')->paginate(30)]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'status' => 'sometimes|in:active,suspended',
            'role' => 'sometimes|in:admin,sender,driver,fleet',
        ]);
        $user->update($data);
        return response()->json(['user' => $user]);
    }

    // Fleets
    public function indexFleets(): JsonResponse
    {
        return response()->json(['fleets' => Fleet::with('user')->paginate(30)]);
    }

    // Payments
    public function payments(Request $request): JsonResponse
    {
        $q = Payment::with('order.sender.user')->orderByDesc('created_at');
        if ($request->status) $q->where('status', $request->status);
        return response()->json(['payments' => $q->paginate(30)]);
    }

    // Complaints
    public function complaints(Request $request): JsonResponse
    {
        $q = Complaint::with(['sender.user', 'order', 'attachments'])->orderByDesc('created_at');
        if ($request->status) $q->where('status', $request->status);
        return response()->json(['complaints' => $q->paginate(30)]);
    }

    public function updateComplaint(Request $request, Complaint $complaint): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:open,under_review,resolved',
            'resolution_note' => 'nullable|string',
        ]);

        $complaint->update(array_merge($data, [
            'resolved_by' => $data['status'] === 'resolved' ? $request->user()->id : $complaint->resolved_by,
        ]));

        return response()->json(['complaint' => $complaint]);
    }

    // Ratings
    public function ratings(): JsonResponse
    {
        $ratings = DriverRating::with(['driver.user', 'sender.user', 'order'])
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json(['ratings' => $ratings]);
    }

    // Reports
    public function reports(Request $request, string $type): JsonResponse
    {
        return match ($type) {
            'daily' => $this->dailyReport($request),
            'monthly' => $this->monthlyReport($request),
            'revenue' => $this->revenueReport($request),
            'driver-earnings' => $this->driverEarningsReport($request),
            default => response()->json(['error' => 'Unknown report type.'], 400),
        };
    }

    protected function dailyReport(Request $request): JsonResponse
    {
        $data = Order::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END) as cancelled, SUM(CASE WHEN status="delivered" THEN delivery_fee ELSE 0 END) as revenue, SUM(CASE WHEN status="delivered" THEN COALESCE(sender_commission,0)+COALESCE(driver_commission,0) ELSE 0 END) as commission')
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        return response()->json(['report' => $data]);
    }

    protected function monthlyReport(Request $request): JsonResponse
    {
        $data = Order::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as orders, SUM(CASE WHEN status="delivered" THEN delivery_fee ELSE 0 END) as revenue')
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return response()->json(['report' => $data]);
    }

    protected function revenueReport(Request $request): JsonResponse
    {
        $data = Order::selectRaw('sender_id, COUNT(*) as orders, SUM(delivery_fee) as revenue, SUM(COALESCE(sender_commission,0)) as commission')
            ->where('status', 'delivered')
            ->with('sender.user')
            ->groupBy('sender_id')
            ->orderByDesc('revenue')
            ->get();

        return response()->json(['report' => $data]);
    }

    protected function driverEarningsReport(Request $request): JsonResponse
    {
        $data = Order::selectRaw('driver_id, COUNT(*) as deliveries, SUM(COALESCE(driver_payout, delivery_fee)) as earnings, SUM(delivery_fee) as gross, SUM(COALESCE(driver_commission,0)) as commission')
            ->where('status', 'delivered')
            ->whereNotNull('driver_id')
            ->with('driver.user:id,name,phone')
            ->groupBy('driver_id')
            ->orderByDesc('earnings')
            ->get();

        return response()->json(['report' => $data]);
    }

    // SMS
    public function smsTemplates(): JsonResponse
    {
        return response()->json(['templates' => SmsTemplate::all()]);
    }

    public function updateSmsTemplate(Request $request, SmsTemplate $template): JsonResponse
    {
        $template->update($request->validate([
            'content_en' => 'sometimes|string',
            'content_ar' => 'sometimes|string',
        ]));
        return response()->json(['template' => $template]);
    }

    public function smsLogs(): JsonResponse
    {
        return response()->json(['logs' => SmsLog::orderByDesc('created_at')->paginate(50)]);
    }

    public function activityLogs(): JsonResponse
    {
        return response()->json(['logs' => \App\Models\ActivityLog::with('user:id,name')->orderByDesc('created_at')->paginate(50)]);
    }

    protected function orderRow(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'sender' => $order->sender?->displayName(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'driver' => $order->driver?->user?->name,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'delivery_fee' => $order->delivery_fee,
            'total_amount' => $order->total_amount,
            'distance_km' => $order->distance_km,
            'driver_lat' => $order->driver?->current_lat,
            'driver_lng' => $order->driver?->current_lng,
            'created_at' => $order->created_at,
            'delivered_at' => $order->delivered_at,
        ];
    }
}
