<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\Driver;
use App\Models\DriverRating;
use App\Models\LapehNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\PricingSetting;
use App\Models\Sender;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\Zone;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ────────────────────────────────────────────────────────────
        User::create([
            'name' => 'Lapeh Admin',
            'email' => 'admin@lapeh.app',
            'phone' => '+9710000000',
            'password' => Hash::make('admin1234'),
            'role' => 'admin',
            'phone_verified_at' => now(),
        ]);

        // ── Pricing ──────────────────────────────────────────────────────────
        PricingSetting::create([
            'base_fee' => 7.00,
            'per_km_fee' => 1.50,
            'min_fee' => 7.00,
            'currency' => 'AED',
            'search_radius_km' => 5.00,
            'request_timeout_sec' => 30,
        ]);

        // ── Zones (admin-managed reference data) ─────────────────────────────
        Zone::create(['name' => 'Downtown Dubai', 'status' => 'active']);
        Zone::create(['name' => 'Jumeirah', 'status' => 'active']);
        Zone::create(['name' => 'Marina', 'base_fee' => 8.00, 'per_km_fee' => 2.00, 'status' => 'active']);
        Zone::create(['name' => 'Business Bay', 'status' => 'active']);

        // ── Individual sender (verified + active for immediate testing) ──────
        $indUser = User::create([
            'name' => 'Mariam Ahmed',
            'phone' => '+971501111111',
            'password' => Hash::make('sender1234'),
            'role' => 'sender',
            'phone_verified_at' => now(),
        ]);
        $individual = Sender::create([
            'user_id' => $indUser->id,
            'type' => 'individual',
            'default_pickup_address' => 'Jumeirah Beach Road, Dubai',
            'default_pickup_lat' => 25.2048,
            'default_pickup_lng' => 55.2708,
            'status' => 'active',
        ]);

        // ── Business sender (verified + active) ──────────────────────────────
        $bizUser = User::create([
            'name' => 'Omar Haddad',
            'phone' => '+971501111112',
            'password' => Hash::make('sender1234'),
            'role' => 'sender',
            'phone_verified_at' => now(),
        ]);
        $business = Sender::create([
            'user_id' => $bizUser->id,
            'type' => 'business',
            'business_name' => 'Gulf Gadgets Store',
            'business_category' => 'Electronics',
            'contact_person_name' => 'Omar Haddad',
            'default_pickup_address' => 'Business Bay, Dubai',
            'default_pickup_lat' => 25.1860,
            'default_pickup_lng' => 55.2620,
            'status' => 'active',
        ]);

        // A third, pending sender so the senders list shows mixed statuses.
        $pendingUser = User::create([
            'name' => 'Sara Idris',
            'phone' => '+971501111113',
            'password' => Hash::make('sender1234'),
            'role' => 'sender',
        ]);
        Sender::create([
            'user_id' => $pendingUser->id,
            'type' => 'individual',
            'default_pickup_address' => 'Al Barsha, Dubai',
            'default_pickup_lat' => 25.1100,
            'default_pickup_lng' => 55.2000,
            'status' => 'pending',
        ]);

        // ── Drivers ──────────────────────────────────────────────────────────
        $driver1User = User::create([
            'name' => 'Bilal Hassan',
            'phone' => '+971502222222',
            'password' => Hash::make('driver1234'),
            'role' => 'driver',
            'phone_verified_at' => now(),
        ]);
        $driver1 = Driver::create([
            'user_id' => $driver1User->id,
            'vehicle_type' => 'bike',
            'vehicle_plate' => 'A 12345',
            'status' => 'online',
            'current_lat' => 25.2070,
            'current_lng' => 55.2750,
            'is_verified' => true,
        ]);

        $driver2User = User::create([
            'name' => 'Karim Nasser',
            'phone' => '+971503333333',
            'password' => Hash::make('driver1234'),
            'role' => 'driver',
            'phone_verified_at' => now(),
        ]);
        $driver2 = Driver::create([
            'user_id' => $driver2User->id,
            'vehicle_type' => 'car',
            'vehicle_plate' => 'B 67890',
            'status' => 'offline',
            'current_lat' => 25.1900,
            'current_lng' => 55.2700,
            'is_verified' => true,
        ]);

        // ── Sample requests with package items ───────────────────────────────
        $this->seedOrder($individual, 'Layla Khan', '+971559876543', 'waiting_for_location', [
            ['name' => 'Documents envelope', 'quantity' => 1, 'unit_price' => 0],
            ['name' => 'Gift box', 'quantity' => 2, 'unit_price' => 75],
        ]);

        $this->seedOrder($individual, 'Yousef Ali', '+971557654321', 'delivered', [
            ['name' => 'Phone case', 'quantity' => 3, 'unit_price' => 40, 'description' => 'Clear silicone'],
        ]);

        // ── Historical delivered orders (drives reports, payments, ratings) ──
        $this->seedHistory([$individual, $business], [$driver1, $driver2]);

        // ── Sample notifications for demo senders ────────────────────────────
        $this->seedNotifications($indUser);
        $this->seedNotifications($bizUser);

        // ── SMS templates ────────────────────────────────────────────────────
        SmsTemplate::create([
            'key' => 'order_created',
            'content_en' => 'Hi! You have a delivery from {sender}. Confirm your drop-off location and pay here: {link} — Order {order_no}',
            'content_ar' => 'مرحباً! لديك توصيل من {sender}. أكد موقع التسليم وادفع هنا: {link} — طلب {order_no}',
            'variables' => ['sender', 'link', 'order_no'],
        ]);

        SmsTemplate::create([
            'key' => 'driver_assigned',
            'content_en' => 'Great news! {driver_name} is on the way with your parcel {order_no}. Track here: {link}',
            'content_ar' => 'أخبار رائعة! {driver_name} في طريقه بطردك {order_no}. تتبع هنا: {link}',
            'variables' => ['driver_name', 'order_no', 'link'],
        ]);

        SmsTemplate::create([
            'key' => 'order_delivered',
            'content_en' => 'Your parcel {order_no} has been delivered!',
            'content_ar' => 'تم توصيل طردك {order_no}!',
            'variables' => ['order_no'],
        ]);
    }

    private function seedNotifications(User $user): void
    {
        $samples = [
            [
                'title' => 'Welcome to Lapeh 👋',
                'body' => 'Your account is ready. Create your first delivery request from the Home tab.',
                'read_at' => now()->subDays(3),
            ],
            [
                'title' => 'Driver assigned',
                'body' => 'Bilal Hassan is on the way to pick up your parcel.',
                'data' => ['type' => 'order_update'],
                'read_at' => now()->subDay(),
            ],
            [
                'title' => 'Delivery completed ✅',
                'body' => 'Your parcel was delivered successfully. Tap to rate your driver.',
                'data' => ['type' => 'delivered'],
                'read_at' => null,
            ],
        ];

        foreach ($samples as $s) {
            LapehNotification::create([
                'user_id' => $user->id,
                'title' => $s['title'],
                'body' => $s['body'],
                'data' => $s['data'] ?? null,
                'read_at' => $s['read_at'] ?? null,
            ]);
        }
    }

    /**
     * Generate a spread of delivered orders over the last ~3 weeks, each with a
     * payment, commission snapshot, and (mostly) a driver rating — plus a set
     * of complaints — so Reports, Payments, Ratings and Complaints have data.
     *
     * @param  list<Sender>  $senders
     * @param  list<Driver>  $drivers
     */
    private function seedHistory(array $senders, array $drivers): void
    {
        $customers = [
            ['Layla Khan', '+971559876501', 'Marina Walk, Dubai'],
            ['Yousef Ali', '+971559876502', 'Downtown, Dubai'],
            ['Hana Saleh', '+971559876503', 'JLT, Dubai'],
            ['Tariq Aziz', '+971559876504', 'Deira, Dubai'],
            ['Noura Faraj', '+971559876505', 'Al Quoz, Dubai'],
            ['Sami Rahman', '+971559876506', 'Mirdif, Dubai'],
            ['Dana Yusuf', '+971559876507', 'Jumeirah, Dubai'],
            ['Khalid Omar', '+971559876508', 'Al Barsha, Dubai'],
        ];
        $itemSets = [
            [['name' => 'Documents envelope', 'quantity' => 1, 'unit_price' => 0]],
            [['name' => 'Gift box', 'quantity' => 2, 'unit_price' => 75]],
            [['name' => 'Phone case', 'quantity' => 3, 'unit_price' => 40]],
            [['name' => 'Headphones', 'quantity' => 1, 'unit_price' => 220]],
            [['name' => 'Grocery bag', 'quantity' => 1, 'unit_price' => 95]],
        ];
        $tagSets = [
            ['fast', 'polite'],
            ['excellent_service'],
            ['careful_handling', 'fast'],
            ['polite'],
            ['late_arrival'],
        ];

        $delivered = [];
        for ($i = 0; $i < 18; $i++) {
            $sender = $senders[$i % count($senders)];
            $driver = $drivers[$i % count($drivers)];
            [$name, $phone, $area] = $customers[$i % count($customers)];
            $items = $itemSets[$i % count($itemSets)];
            $fee = [7.5, 9.0, 12.5, 15.0, 18.5, 22.0][$i % 6];
            $gateway = $i % 2 === 0 ? 'stripe' : 'telr';
            $daysAgo = (int) ($i * 20 / 18); // spread across ~20 days

            $delivered[] = $this->seedDeliveredOrder($sender, $driver, $name, $phone, $area, $items, $daysAgo, $fee, $gateway);
        }

        // Ratings for ~70% of delivered orders.
        foreach ($delivered as $i => $order) {
            if ($i % 10 === 7) {
                continue; // leave a few unrated
            }
            DriverRating::create([
                'order_id' => $order->id,
                'sender_id' => $order->sender_id,
                'driver_id' => $order->driver_id,
                'rating' => [5, 5, 4, 5, 3, 4][$i % 6],
                'tags' => $tagSets[$i % count($tagSets)],
                'comment' => $i % 3 === 0 ? 'Smooth handover, thanks!' : null,
            ]);
        }

        // A handful of complaints across types and statuses.
        $complaints = [
            ['type' => 'late', 'status' => 'resolved', 'desc' => 'Driver arrived 30 minutes late.', 'note' => 'Apologised and credited next delivery.'],
            ['type' => 'damaged', 'status' => 'under_review', 'desc' => 'Box was dented on arrival.', 'note' => null],
            ['type' => 'driver_behavior', 'status' => 'open', 'desc' => 'Driver was not polite on the call.', 'note' => null],
            ['type' => 'payment', 'status' => 'resolved', 'desc' => 'Charged twice for one order.', 'note' => 'Duplicate charge refunded.'],
            ['type' => 'other', 'status' => 'open', 'desc' => 'Wrong drop-off location used.', 'note' => null],
        ];
        $admin = User::where('role', 'admin')->first();
        foreach ($complaints as $i => $c) {
            $order = $delivered[$i] ?? null;
            Complaint::create([
                'order_id' => $order?->id,
                'sender_id' => $order?->sender_id ?? $senders[0]->id,
                'type' => $c['type'],
                'description' => $c['desc'],
                'status' => $c['status'],
                'resolution_note' => $c['note'],
                'resolved_by' => $c['status'] === 'resolved' ? $admin?->id : null,
            ]);
        }
    }

    private function seedDeliveredOrder(Sender $sender, Driver $driver, string $customer, string $phone, string $area, array $items, int $daysAgo, float $fee, string $gateway): Order
    {
        $value = collect($items)->sum(fn($i) => (float) $i['unit_price'] * (int) $i['quantity']);
        $when = now()->subDays($daysAgo)->setTime(rand(9, 20), rand(0, 59));
        $driverCommission = round($fee * 0.15, 2); // 15% platform cut from driver
        $senderCommission = round($fee * 0.05, 2); // 5% platform fee to sender

        $order = Order::create([
            'order_no' => OrderService::generateOrderNo(),
            'sender_id' => $sender->id,
            'driver_id' => $driver->id,
            'pickup_address' => $sender->default_pickup_address,
            'pickup_lat' => $sender->default_pickup_lat,
            'pickup_lng' => $sender->default_pickup_lng,
            'customer_name' => $customer,
            'customer_phone' => $phone,
            'customer_address' => $area,
            'order_value' => $value,
            'distance_km' => round(rand(20, 130) / 10, 1),
            'status' => 'delivered',
            'location_token' => OrderService::generateLocationToken(),
            'otp_code' => OrderService::generateOtp(),
            'delivery_fee' => $fee,
            'total_amount' => $value + $fee,
            'sender_commission' => $senderCommission,
            'driver_commission' => $driverCommission,
            'driver_payout' => round($fee - $driverCommission, 2),
            'payment_status' => 'paid',
            'assigned_at' => $when->copy()->subMinutes(45),
            'picked_up_at' => $when->copy()->subMinutes(30),
            'delivered_at' => $when,
        ]);
        $order->forceFill(['created_at' => $when->copy()->subHour(), 'updated_at' => $when])->save();

        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => round((float) $item['unit_price'] * (int) $item['quantity'], 2),
            ]);
        }

        Payment::create([
            'order_id' => $order->id,
            'amount' => $value + $fee,
            'currency' => 'AED',
            'gateway' => $gateway,
            'gateway_reference' => strtoupper($gateway) . '-' . strtoupper(uniqid()),
            'status' => 'paid',
            'paid_at' => $when,
        ]);

        OrderStatusLog::create(['order_id' => $order->id, 'status' => 'delivered']);

        return $order;
    }

    private function seedOrder(Sender $sender, string $customer, string $phone, string $status, array $items): void
    {
        $value = collect($items)->sum(fn($i) => (float) $i['unit_price'] * (int) $i['quantity']);

        $order = Order::create([
            'order_no' => OrderService::generateOrderNo(),
            'sender_id' => $sender->id,
            'pickup_address' => $sender->default_pickup_address,
            'pickup_lat' => $sender->default_pickup_lat,
            'pickup_lng' => $sender->default_pickup_lng,
            'customer_name' => $customer,
            'customer_phone' => $phone,
            'order_value' => $value,
            'status' => $status,
            'location_token' => OrderService::generateLocationToken(),
            'otp_code' => OrderService::generateOtp(),
            'delivery_fee' => $status === 'delivered' ? 12.50 : null,
            'total_amount' => $status === 'delivered' ? $value + 12.50 : null,
            'payment_status' => $status === 'delivered' ? 'paid' : 'pending',
            'delivered_at' => $status === 'delivered' ? now() : null,
        ]);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => round((float) $item['unit_price'] * (int) $item['quantity'], 2),
                'description' => $item['description'] ?? null,
            ]);
        }

        OrderStatusLog::create(['order_id' => $order->id, 'status' => $status]);
    }
}
