<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\PricingSetting;
use App\Models\Restaurant;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Lapeh Admin',
            'email' => 'admin@lapeh.app',
            'phone' => '+9710000000',
            'password' => Hash::make('admin1234'),
            'role' => 'admin',
        ]);

        // Default pricing
        PricingSetting::create([
            'base_fee' => 7.00,
            'per_km_fee' => 1.50,
            'min_fee' => 7.00,
            'currency' => 'AED',
            'search_radius_km' => 5.00,
            'request_timeout_sec' => 30,
        ]);

        // Sample zones
        $zones = [
            Zone::create(['name' => 'Downtown Dubai', 'status' => 'active']),
            Zone::create(['name' => 'Jumeirah', 'status' => 'active']),
            Zone::create(['name' => 'Marina', 'base_fee' => 8.00, 'per_km_fee' => 2.00, 'status' => 'active']),
            Zone::create(['name' => 'Business Bay', 'status' => 'active']),
            Zone::create(['name' => 'DIFC', 'base_fee' => 9.00, 'status' => 'active']),
        ];

        // Sample restaurant
        $restUser = User::create([
            'name' => 'Al Safadi Manager',
            'phone' => '+971501111111',
            'password' => Hash::make('rest1234'),
            'role' => 'restaurant',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $restUser->id,
            'zone_id' => $zones[1]->id,
            'name' => 'Al Safadi',
            'name_ar' => 'الصفدي',
            'phone' => '+97144001234',
            'area' => 'Jumeirah',
            'address' => 'Jumeirah Beach Road, Dubai',
            'lat' => 25.2048,
            'lng' => 55.2708,
        ]);

        // Sample drivers
        $driver1User = User::create([
            'name' => 'Bilal Hassan',
            'phone' => '+971502222222',
            'password' => Hash::make('driver1234'),
            'role' => 'driver',
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
        ]);
        Driver::create([
            'user_id' => $driver2User->id,
            'vehicle_type' => 'car',
            'vehicle_plate' => 'B 67890',
            'status' => 'offline',
            'is_verified' => true,
        ]);

        // SMS templates
        SmsTemplate::create([
            'key' => 'order_created',
            'content_en' => 'Hi! Your delivery from {restaurant} is ready. Confirm your location and pay here: {link} — Order {order_no}',
            'content_ar' => 'مرحباً! طلبك من {restaurant} جاهز. أكد موقعك وادفع هنا: {link} — طلب {order_no}',
            'variables' => ['restaurant', 'link', 'order_no'],
        ]);

        SmsTemplate::create([
            'key' => 'driver_assigned',
            'content_en' => 'Great news! {driver_name} is on the way with your order {order_no}. Track here: {link}',
            'content_ar' => 'أخبار رائعة! {driver_name} في طريقه بطلبك {order_no}. تتبع هنا: {link}',
            'variables' => ['driver_name', 'order_no', 'link'],
        ]);

        SmsTemplate::create([
            'key' => 'order_delivered',
            'content_en' => 'Your order {order_no} has been delivered! Enjoy your meal.',
            'content_ar' => 'تم توصيل طلبك {order_no}! بالهناء والشفاء.',
            'variables' => ['order_no'],
        ]);
    }
}
