<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\LapehNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
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
        Sender::create([
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

        // ── Drivers ──────────────────────────────────────────────────────────
        $driver1User = User::create([
            'name' => 'Bilal Hassan',
            'phone' => '+971502222222',
            'password' => Hash::make('driver1234'),
            'role' => 'driver',
            'phone_verified_at' => now(),
        ]);
        Driver::create([
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
        Driver::create([
            'user_id' => $driver2User->id,
            'vehicle_type' => 'car',
            'vehicle_plate' => 'B 67890',
            'status' => 'offline',
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
