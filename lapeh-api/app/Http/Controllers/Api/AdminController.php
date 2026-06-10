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
use App\Models\Restaurant;
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
            'total_restaurants' => Restaurant::where('status', 'active')->count(),
        ];

        $liveOrders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['restaurant', 'driver.user'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($o) => $this->orderRow($o));

        return response()->json(['stats' => $stats, 'live_orders' => $liveOrders]);
    }

    // Orders
    public function orders(Request $request): JsonResponse
    {
        $q = Order::with(['restaurant', 'driver.user'])->orderByDesc('created_at');

        if ($request->status) $q->where('status', $request->status);
        if ($request->restaurant_id) $q->where('restaurant_id', $request->restaurant_id);
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
            ->with(['restaurant', 'driver.user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => $this->orderRow($o));

        return response()->json(['orders' => $orders]);
    }

    // Restaurants CRUD
    public function indexRestaurants(): JsonResponse
    {
        return response()->json(['restaurants' => Restaurant::with('zone')->paginate(30)]);
    }

    public function storeRestaurant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'name_ar' => 'nullable|string',
            'phone' => 'required|string',
            'area' => 'required|string',
            'address' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'zone_id' => 'nullable|exists:zones,id',
            'user_name' => 'required|string',
            'user_phone' => 'required|string|unique:users,phone',
            'user_password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['user_name'],
            'phone' => $data['user_phone'],
            'password' => $data['user_password'],
            'role' => 'restaurant',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'phone' => $data['phone'],
            'area' => $data['area'],
            'address' => $data['address'],
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'zone_id' => $data['zone_id'] ?? null,
        ]);

        return response()->json(['restaurant' => $restaurant, 'user' => $user], 201);
    }

    public function showRestaurant(Restaurant $restaurant): JsonResponse
    {
        return response()->json(['restaurant' => $restaurant->load(['user', 'zone'])]);
    }

    public function updateRestaurant(Request $request, Restaurant $restaurant): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'name_ar' => 'nullable|string',
            'phone' => 'sometimes|string',
            'area' => 'sometimes|string',
            'address' => 'sometimes|string',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
            'zone_id' => 'nullable|exists:zones,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $restaurant->update($data);
        return response()->json(['restaurant' => $restaurant]);
    }

    public function destroyRestaurant(Restaurant $restaurant): JsonResponse
    {
        $restaurant->delete();
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
            'role' => 'sometimes|in:admin,restaurant,driver,fleet',
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
        $q = Payment::with('order.restaurant')->orderByDesc('created_at');
        if ($request->status) $q->where('status', $request->status);
        return response()->json(['payments' => $q->paginate(30)]);
    }

    // Complaints
    public function complaints(Request $request): JsonResponse
    {
        $q = Complaint::with(['restaurant', 'order', 'attachments'])->orderByDesc('created_at');
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
        $ratings = DriverRating::with(['driver.user', 'restaurant', 'order'])
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
        $data = Order::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END) as cancelled, SUM(CASE WHEN status="delivered" THEN delivery_fee ELSE 0 END) as revenue')
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
        $data = Order::selectRaw('restaurant_id, COUNT(*) as orders, SUM(delivery_fee) as revenue')
            ->where('status', 'delivered')
            ->with('restaurant:id,name')
            ->groupBy('restaurant_id')
            ->orderByDesc('revenue')
            ->get();

        return response()->json(['report' => $data]);
    }

    protected function driverEarningsReport(Request $request): JsonResponse
    {
        $data = Order::selectRaw('driver_id, COUNT(*) as deliveries, SUM(delivery_fee) as earnings')
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
            'restaurant' => $order->restaurant?->name,
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
