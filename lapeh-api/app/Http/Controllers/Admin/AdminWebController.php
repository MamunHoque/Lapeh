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
use App\Models\Sender;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\Zone;
use App\Support\DateRange;
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
        ActivityLog::record('auth.login', $user, ['name' => $user->name, 'portal' => 'admin'], $user);
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
            'total_senders' => Sender::where('status', 'active')->count(),
            'orders_month' => Order::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $liveOrders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['sender.user', 'driver.user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentOrders = Order::with(['sender.user', 'driver.user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'liveOrders', 'recentOrders'));
    }

    public function live()
    {
        $orders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->with(['sender.user', 'driver.user'])
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
        $q = Order::with(['sender.user', 'driver.user'])->orderByDesc('created_at');

        if ($request->status) $q->where('status', $request->status);
        if ($request->sender_id) $q->where('sender_id', $request->sender_id);
        if ($request->search) $q->where(function ($q) use ($request) {
            $q->where('order_no', 'like', "%{$request->search}%")
              ->orWhere('customer_name', 'like', "%{$request->search}%");
        });
        DateRange::apply($q, $request);

        $orders = $q->paginate(25)->withQueryString();
        $senders = Sender::with('user')->get();

        return view('admin.orders.index', compact('orders', 'senders'));
    }

    public function orderShow(Order $order)
    {
        $order->load(['sender.user', 'driver.user', 'statusLogs', 'proof', 'rating', 'payment', 'complaint', 'items']);
        return view('admin.orders.show', compact('order'));
    }

    public function senders(Request $request)
    {
        $q = Sender::with('user')
            ->when($request->search, fn($q) => $q->where('business_name', 'like', "%{$request->search}%")
                ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")->orWhere('phone', 'like', "%{$request->search}%")))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');
        DateRange::apply($q, $request);

        $senders = $q->paginate(25)->withQueryString();

        return view('admin.senders.index', compact('senders'));
    }

    public function senderCreate()
    {
        return view('admin.senders.create');
    }

    public function senderStore(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:individual,business',
            'user_name' => 'required|string',
            'user_phone' => 'required|string|unique:users,phone',
            'user_password' => 'required|string|min:6',
            'business_name' => 'required_if:type,business|nullable|string',
            'business_category' => 'nullable|string',
            'contact_person_name' => 'nullable|string',
            'default_pickup_address' => 'nullable|string',
            'default_pickup_lat' => 'nullable|numeric',
            'default_pickup_lng' => 'nullable|numeric',
        ]);

        $user = User::create([
            'name' => $data['user_name'],
            'phone' => $data['user_phone'],
            'password' => $data['user_password'],
            'role' => 'sender',
            // Admin-created senders are verified/active immediately.
            'phone_verified_at' => now(),
        ]);

        $sender = Sender::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'business_name' => $data['type'] === 'business' ? ($data['business_name'] ?? null) : null,
            'business_category' => $data['type'] === 'business' ? ($data['business_category'] ?? null) : null,
            'contact_person_name' => $data['type'] === 'business' ? ($data['contact_person_name'] ?? null) : null,
            'default_pickup_address' => $data['default_pickup_address'] ?? null,
            'default_pickup_lat' => $data['default_pickup_lat'] ?? null,
            'default_pickup_lng' => $data['default_pickup_lng'] ?? null,
            'status' => 'active',
        ]);

        ActivityLog::record('sender.created', $sender, ['name' => $sender->displayName()]);

        return redirect()->route('admin.senders')->with('success', 'Sender created.');
    }

    public function senderEdit(Sender $sender)
    {
        return view('admin.senders.edit', compact('sender'));
    }

    public function senderUpdate(Request $request, Sender $sender)
    {
        $data = $request->validate([
            'type' => 'required|in:individual,business',
            'business_name' => 'nullable|string',
            'business_category' => 'nullable|string',
            'contact_person_name' => 'nullable|string',
            'default_pickup_address' => 'nullable|string',
            'default_pickup_lat' => 'nullable|numeric',
            'default_pickup_lng' => 'nullable|numeric',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $sender->update($data);
        ActivityLog::record('sender.updated', $sender, ['name' => $sender->displayName()]);
        return redirect()->route('admin.senders')->with('success', 'Sender updated.');
    }

    public function senderDestroy(Sender $sender)
    {
        ActivityLog::record('sender.deleted', $sender, ['name' => $sender->displayName()]);
        $sender->delete();
        return redirect()->route('admin.senders')->with('success', 'Sender deleted.');
    }

    public function drivers(Request $request)
    {
        $q = Driver::with(['user', 'fleet'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")->orWhere('phone', 'like', "%{$request->search}%")))
            ->orderByDesc('created_at');
        DateRange::apply($q, $request);

        $drivers = $q->paginate(25)->withQueryString();

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
        $driver = Driver::create([
            'user_id' => $user->id,
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'fleet_id' => $data['fleet_id'] ?? null,
        ]);

        ActivityLog::record('driver.created', $driver, ['name' => $user->name]);

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

        ActivityLog::record('driver.updated', $driver, ['name' => $driver->user->name ?? null]);

        return redirect()->route('admin.drivers')->with('success', 'Driver updated.');
    }

    public function driverDestroy(Driver $driver)
    {
        ActivityLog::record('driver.deleted', $driver, ['name' => $driver->user->name ?? null]);
        $driver->delete();
        return redirect()->route('admin.drivers')->with('success', 'Driver deleted.');
    }

    public function zones()
    {
        $q = Zone::when(request('search'), fn($q) => $q->where('name', 'like', '%'.request('search').'%'))
            ->orderByDesc('created_at');
        DateRange::apply($q, request());
        $zones = $q->paginate(25)->withQueryString();
        return view('admin.zones', compact('zones'));
    }

    public function zoneStore(Request $request)
    {
        $zone = Zone::create($request->validate([
            'name' => 'required|string',
            'base_fee' => 'nullable|numeric',
            'per_km_fee' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]));
        ActivityLog::record('zone.created', $zone, ['name' => $zone->name]);
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
        ActivityLog::record('zone.updated', $zone, ['name' => $zone->name]);
        return redirect()->route('admin.zones')->with('success', 'Zone updated.');
    }

    public function zoneDestroy(Zone $zone)
    {
        ActivityLog::record('zone.deleted', $zone, ['name' => $zone->name]);
        $zone->delete();
        return redirect()->route('admin.zones')->with('success', 'Zone deleted.');
    }

    public function pricing()
    {
        // Pricing now lives inside the Settings hub as a tab.
        return redirect()->route('admin.settings.tab', 'pricing');
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

        $pricing = PricingSetting::current();
        $pricing->update($data);
        ActivityLog::record('pricing.updated', $pricing, $data);
        return redirect()->route('admin.settings.tab', 'pricing')->with('success', __('admin.settings_saved'));
    }

    public function users(Request $request)
    {
        $q = User::when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('phone', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at');
        DateRange::apply($q, $request);

        $users = $q->paginate(25)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function complaints(Request $request)
    {
        $q = Complaint::with(['sender.user', 'order'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->where('description', 'like', "%{$request->search}%")
                ->orWhereHas('sender.user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                ->orWhereHas('order', fn($o) => $o->where('order_no', 'like', "%{$request->search}%")))
            ->orderByDesc('created_at');
        DateRange::apply($q, $request);

        $complaints = $q->paginate(25)->withQueryString();

        return view('admin.complaints.index', compact('complaints'));
    }

    public function complaintShow(Complaint $complaint)
    {
        $complaint->load(['sender.user', 'order', 'attachments', 'resolver']);
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

        ActivityLog::record('complaint.updated', $complaint, [
            'status' => $complaint->status,
            'type' => $complaint->type,
        ]);

        return redirect()->route('admin.complaints.show', $complaint)->with('success', 'Complaint updated.');
    }

    public function ratings()
    {
        $q = DriverRating::with(['driver.user', 'sender.user', 'order'])
            ->when(request('status'), fn($q) => $q->where('rating', request('status')))
            ->when(request('search'), fn($q) => $q
                ->whereHas('driver.user', fn($u) => $u->where('name', 'like', '%'.request('search').'%'))
                ->orWhereHas('sender.user', fn($u) => $u->where('name', 'like', '%'.request('search').'%'))
                ->orWhereHas('order', fn($o) => $o->where('order_no', 'like', '%'.request('search').'%')))
            ->orderByDesc('created_at');
        DateRange::apply($q, request());

        $ratings = $q->paginate(25)->withQueryString();

        return view('admin.ratings', compact('ratings'));
    }

    public function payments(Request $request)
    {
        $payments = $this->paymentsQuery($request)->paginate(25)->withQueryString();
        $summary = $this->paymentsSummary($request);

        return view('admin.payments', compact('payments', 'summary'));
    }

    public function paymentsExport(Request $request)
    {
        $rows = $this->paymentsQuery($request)->get()->map(fn($p) => [
            $p->order?->order_no,
            $p->order?->sender?->displayName(),
            ucfirst((string) $p->gateway),
            $p->gateway_reference,
            number_format((float) $p->amount, 2, '.', ''),
            $p->currency,
            $p->status,
            optional($p->paid_at)?->format('Y-m-d H:i'),
            $p->created_at->format('Y-m-d H:i'),
        ]);

        return $this->streamCsv('payments', [
            'Order', 'Sender', 'Gateway', 'Reference', 'Amount', 'Currency', 'Status', 'Paid at', 'Created',
        ], $rows);
    }

    private function paymentsQuery(Request $request)
    {
        $q = Payment::with('order.sender.user')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('gateway_reference', 'like', "%{$request->search}%")
                ->orWhereHas('order', fn($o) => $o->where('order_no', 'like', "%{$request->search}%")))
            ->orderByDesc('created_at');
        DateRange::apply($q, $request);

        return $q;
    }

    /** Collection totals for the payments page (respects search + date range, not status). */
    private function paymentsSummary(Request $request): object
    {
        $q = Payment::query()
            ->when($request->search, fn($q) => $q->where('gateway_reference', 'like', "%{$request->search}%")
                ->orWhereHas('order', fn($o) => $o->where('order_no', 'like', "%{$request->search}%")));
        DateRange::apply($q, $request);

        return $q->selectRaw('
            SUM(CASE WHEN status="paid" THEN amount ELSE 0 END) as collected,
            SUM(CASE WHEN status="paid" THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status="pending" THEN amount ELSE 0 END) as pending,
            SUM(CASE WHEN status="failed" THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN status="refunded" THEN amount ELSE 0 END) as refunded,
            SUM(CASE WHEN status="paid" AND gateway="stripe" THEN amount ELSE 0 END) as stripe_total,
            SUM(CASE WHEN status="paid" AND gateway="telr" THEN amount ELSE 0 END) as telr_total
        ')->first();
    }

    // ─── Reports ──────────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $overview = $this->reportOverview($request);
        $payments = $this->paymentsSummary($request);
        $daily = $this->dailyReportRows($request, 30);
        $topDrivers = $this->topDriversRows($request, 10);
        $topSenders = $this->topSendersRows($request, 10);

        return view('admin.reports', compact('overview', 'payments', 'daily', 'topDrivers', 'topSenders'));
    }

    public function reportsExport(Request $request, string $type)
    {
        return match ($type) {
            'daily' => $this->streamCsv('report-daily',
                ['Date', 'Orders', 'Delivered', 'Cancelled', 'Revenue', 'Commission'],
                $this->dailyReportRows($request)->map(fn($r) => [
                    $r->date, $r->orders, $r->delivered, $r->cancelled,
                    number_format((float) $r->revenue, 2, '.', ''),
                    number_format((float) $r->commission, 2, '.', ''),
                ])),
            'drivers' => $this->streamCsv('report-drivers',
                ['Driver', 'Phone', 'Deliveries', 'Net earnings', 'Commission'],
                $this->topDriversRows($request)->map(fn($r) => [
                    $r->driver?->user?->name, $r->driver?->user?->phone, $r->deliveries,
                    number_format((float) $r->earnings, 2, '.', ''),
                    number_format((float) $r->commission, 2, '.', ''),
                ])),
            'senders' => $this->streamCsv('report-senders',
                ['Sender', 'Orders', 'Delivered', 'Delivery fees', 'Commission'],
                $this->topSendersRows($request)->map(fn($r) => [
                    $r->sender?->displayName(), $r->orders, $r->delivered,
                    number_format((float) $r->fees, 2, '.', ''),
                    number_format((float) $r->commission, 2, '.', ''),
                ])),
            default => abort(404),
        };
    }

    private function reportOverview(Request $request): object
    {
        $q = Order::query();
        DateRange::apply($q, $request);

        return $q->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status="delivered" THEN delivery_fee ELSE 0 END) as gross_fees,
            SUM(CASE WHEN status="delivered" THEN COALESCE(sender_commission,0)+COALESCE(driver_commission,0) ELSE 0 END) as commission,
            SUM(CASE WHEN status="delivered" THEN COALESCE(driver_payout, delivery_fee) ELSE 0 END) as driver_net,
            SUM(CASE WHEN status="delivered" THEN order_value ELSE 0 END) as order_value,
            AVG(CASE WHEN status="delivered" THEN delivery_fee ELSE NULL END) as avg_fee,
            AVG(CASE WHEN status="delivered" THEN distance_km ELSE NULL END) as avg_distance
        ')->first();
    }

    private function dailyReportRows(Request $request, ?int $limit = null)
    {
        $q = Order::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END) as cancelled, SUM(CASE WHEN status="delivered" THEN delivery_fee ELSE 0 END) as revenue, SUM(CASE WHEN status="delivered" THEN COALESCE(sender_commission,0)+COALESCE(driver_commission,0) ELSE 0 END) as commission')
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('date');
        DateRange::apply($q, $request);

        return ($limit ? $q->limit($limit) : $q)->get();
    }

    private function topDriversRows(Request $request, ?int $limit = null)
    {
        $q = Order::selectRaw('driver_id, COUNT(*) as deliveries, SUM(COALESCE(driver_payout, delivery_fee)) as earnings, SUM(COALESCE(driver_commission,0)) as commission')
            ->where('status', 'delivered')
            ->whereNotNull('driver_id')
            ->with('driver.user:id,name,phone')
            ->groupBy('driver_id')
            ->orderByDesc('earnings');
        DateRange::apply($q, $request);

        return ($limit ? $q->limit($limit) : $q)->get();
    }

    private function topSendersRows(Request $request, ?int $limit = null)
    {
        $q = Order::selectRaw('sender_id, COUNT(*) as orders, SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status="delivered" THEN delivery_fee ELSE 0 END) as fees, SUM(CASE WHEN status="delivered" THEN COALESCE(sender_commission,0) ELSE 0 END) as commission')
            ->whereNotNull('sender_id')
            ->with('sender.user')
            ->groupBy('sender_id')
            ->orderByDesc('fees');
        DateRange::apply($q, $request);

        return ($limit ? $q->limit($limit) : $q)->get();
    }

    /** Stream an array of rows as a downloadable CSV. */
    private function streamCsv(string $name, array $headers, iterable $rows)
    {
        $filename = $name . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, (array) $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function sms(Request $request)
    {
        $templates = SmsTemplate::all();
        $q = SmsLog::when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('to', 'like', "%{$request->search}%")
                ->orWhere('template_key', 'like', "%{$request->search}%")
                ->orWhere('body', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at');
        DateRange::apply($q, $request);
        $logs = $q->paginate(25)->withQueryString();
        return view('admin.sms', compact('templates', 'logs'));
    }

    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('user:id,name');

        // Free-text search across action, subject, IP and actor name.
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                if (ctype_digit($search)) {
                    $q->orWhere('subject_id', (int) $search);
                }
            });
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($role = $request->query('role')) {
            $query->where('actor_role', $role);
        }
        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        $logs = $query->orderByDesc('created_at')->paginate(40)->withQueryString();

        // Distinct values to populate the filter dropdowns.
        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action');
        $roles = ActivityLog::query()->distinct()->whereNotNull('actor_role')->orderBy('actor_role')->pluck('actor_role');

        return view('admin.activity-logs', compact('logs', 'actions', 'roles'));
    }
}
