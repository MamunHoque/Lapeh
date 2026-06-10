<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminWebController extends Controller
{
    public function loginForm()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate(['phone' => 'required', 'password' => 'required']);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password) || $user->role !== 'admin') {
            return back()->withErrors(['phone' => 'Invalid credentials or not an admin.']);
        }

        if ($user->status === 'suspended') {
            return back()->withErrors(['phone' => 'Account suspended.']);
        }

        Auth::login($user, true);
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        return redirect()->route('admin.login');
    }

    public function dashboard()
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
            'orders_month' => Order::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $liveOrders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['restaurant', 'driver.user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentOrders = Order::with(['restaurant', 'driver.user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'liveOrders', 'recentOrders'));
    }

    public function live()
    {
        $orders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['restaurant', 'driver.user'])
            ->orderByDesc('created_at')
            ->get();

        $drivers = Driver::with('user')
            ->whereIn('status', ['online', 'on_delivery'])
            ->whereNotNull('current_lat')
            ->get();

        return view('admin.live', compact('orders', 'drivers'));
    }

    public function orders(Request $request)
    {
        $q = Order::with(['restaurant', 'driver.user'])->orderByDesc('created_at');

        if ($request->status) $q->where('status', $request->status);
        if ($request->restaurant_id) $q->where('restaurant_id', $request->restaurant_id);
        if ($request->search) $q->where(function ($q) use ($request) {
            $q->where('order_no', 'like', "%{$request->search}%")
              ->orWhere('customer_name', 'like', "%{$request->search}%");
        });

        $orders = $q->paginate(25)->withQueryString();
        $restaurants = Restaurant::select('id', 'name')->get();

        return view('admin.orders.index', compact('orders', 'restaurants'));
    }

    public function orderShow(Order $order)
    {
        $order->load(['restaurant', 'driver.user', 'statusLogs', 'proof', 'rating', 'payment', 'complaint']);
        return view('admin.orders.show', compact('order'));
    }

    public function restaurants(Request $request)
    {
        $restaurants = Restaurant::with(['zone', 'user'])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(25)->withQueryString();

        return view('admin.restaurants.index', compact('restaurants'));
    }

    public function restaurantCreate()
    {
        $zones = Zone::where('status', 'active')->get();
        return view('admin.restaurants.create', compact('zones'));
    }

    public function restaurantStore(Request $request)
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

        Restaurant::create([
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

        return redirect()->route('admin.restaurants')->with('success', 'Restaurant created.');
    }

    public function restaurantEdit(Restaurant $restaurant)
    {
        $zones = Zone::where('status', 'active')->get();
        return view('admin.restaurants.edit', compact('restaurant', 'zones'));
    }

    public function restaurantUpdate(Request $request, Restaurant $restaurant)
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
            'status' => 'required|in:active,inactive',
        ]);

        $restaurant->update($data);
        return redirect()->route('admin.restaurants')->with('success', 'Restaurant updated.');
    }

    public function restaurantDestroy(Restaurant $restaurant)
    {
        $restaurant->delete();
        return redirect()->route('admin.restaurants')->with('success', 'Restaurant deleted.');
    }

    public function drivers(Request $request)
    {
        $drivers = Driver::with(['user', 'fleet'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")->orWhere('phone', 'like', "%{$request->search}%")))
            ->orderByDesc('created_at')
            ->paginate(25)->withQueryString();

        return view('admin.drivers.index', compact('drivers'));
    }

    public function driverCreate()
    {
        $fleets = Fleet::where('status', 'active')->get();
        return view('admin.drivers.create', compact('fleets'));
    }

    public function driverStore(Request $request)
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
        Driver::create([
            'user_id' => $user->id,
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'fleet_id' => $data['fleet_id'] ?? null,
        ]);

        return redirect()->route('admin.drivers')->with('success', 'Driver created.');
    }

    public function driverEdit(Driver $driver)
    {
        $fleets = Fleet::where('status', 'active')->get();
        return view('admin.drivers.edit', compact('driver', 'fleets'));
    }

    public function driverUpdate(Request $request, Driver $driver)
    {
        $data = $request->validate([
            'vehicle_type' => 'required|in:bike,car',
            'vehicle_plate' => 'nullable|string',
            'fleet_id' => 'nullable|exists:fleets,id',
            'is_verified' => 'sometimes|boolean',
            'status' => 'required|in:online,offline,on_delivery',
        ]);

        $driver->update($data);

        if ($request->has('name') || $request->has('phone')) {
            $userUpdate = [];
            if ($request->name) $userUpdate['name'] = $request->name;
            if ($request->status_user) $userUpdate['status'] = $request->status_user;
            $driver->user->update($userUpdate);
        }

        return redirect()->route('admin.drivers')->with('success', 'Driver updated.');
    }

    public function driverDestroy(Driver $driver)
    {
        $driver->delete();
        return redirect()->route('admin.drivers')->with('success', 'Driver deleted.');
    }

    public function zones()
    {
        $zones = Zone::orderByDesc('created_at')->get();
        return view('admin.zones', compact('zones'));
    }

    public function zoneStore(Request $request)
    {
        Zone::create($request->validate([
            'name' => 'required|string',
            'base_fee' => 'nullable|numeric',
            'per_km_fee' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]));
        return redirect()->route('admin.zones')->with('success', 'Zone created.');
    }

    public function zoneUpdate(Request $request, Zone $zone)
    {
        $zone->update($request->validate([
            'name' => 'required|string',
            'base_fee' => 'nullable|numeric',
            'per_km_fee' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]));
        return redirect()->route('admin.zones')->with('success', 'Zone updated.');
    }

    public function zoneDestroy(Zone $zone)
    {
        $zone->delete();
        return redirect()->route('admin.zones')->with('success', 'Zone deleted.');
    }

    public function pricing()
    {
        $pricing = PricingSetting::current();
        return view('admin.pricing', compact('pricing'));
    }

    public function pricingUpdate(Request $request)
    {
        $data = $request->validate([
            'base_fee' => 'required|numeric|min:0',
            'per_km_fee' => 'required|numeric|min:0',
            'min_fee' => 'required|numeric|min:0',
            'search_radius_km' => 'required|numeric|min:1',
            'request_timeout_sec' => 'required|integer|min:10',
        ]);

        PricingSetting::current()->update($data);
        return redirect()->route('admin.pricing')->with('success', 'Pricing updated.');
    }

    public function users(Request $request)
    {
        $users = User::when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('phone', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(25)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function complaints(Request $request)
    {
        $complaints = Complaint::with(['restaurant', 'order'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(25)->withQueryString();

        return view('admin.complaints.index', compact('complaints'));
    }

    public function complaintShow(Complaint $complaint)
    {
        $complaint->load(['restaurant', 'order', 'attachments', 'resolver']);
        return view('admin.complaints.show', compact('complaint'));
    }

    public function complaintUpdate(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'status' => 'required|in:open,under_review,resolved',
            'resolution_note' => 'nullable|string',
        ]);

        $complaint->update(array_merge($data, [
            'resolved_by' => $data['status'] === 'resolved' ? Auth::id() : $complaint->resolved_by,
        ]));

        return redirect()->route('admin.complaints.show', $complaint)->with('success', 'Complaint updated.');
    }

    public function ratings()
    {
        $ratings = DriverRating::with(['driver.user', 'restaurant', 'order'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.ratings', compact('ratings'));
    }

    public function payments(Request $request)
    {
        $payments = Payment::with('order.restaurant')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(25)->withQueryString();

        return view('admin.payments', compact('payments'));
    }

    public function reports()
    {
        $daily = Order::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status="delivered" THEN delivery_fee ELSE 0 END) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        $topDrivers = Order::selectRaw('driver_id, COUNT(*) as deliveries, SUM(delivery_fee) as earnings')
            ->where('status', 'delivered')
            ->whereNotNull('driver_id')
            ->with('driver.user:id,name,phone')
            ->groupBy('driver_id')
            ->orderByDesc('earnings')
            ->limit(10)
            ->get();

        return view('admin.reports', compact('daily', 'topDrivers'));
    }

    public function sms()
    {
        $templates = SmsTemplate::all();
        $logs = SmsLog::orderByDesc('created_at')->limit(50)->get();
        return view('admin.sms', compact('templates', 'logs'));
    }

    public function activityLogs()
    {
        $logs = ActivityLog::with('user:id,name')->orderByDesc('created_at')->paginate(50);
        return view('admin.activity-logs', compact('logs'));
    }

    public function settings()
    {
        return view('admin.settings');
    }
}
