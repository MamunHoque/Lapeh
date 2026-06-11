<?php

use App\Models\Order;
use App\Services\CommissionService;
use App\Services\SettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('sender_commission', 10, 2)->nullable()->after('total_amount');
            $table->decimal('driver_commission', 10, 2)->nullable()->after('sender_commission');
            $table->decimal('driver_payout', 10, 2)->nullable()->after('driver_commission');
        });

        // Seed the new "commission" settings group (idempotent).
        SettingsService::seedDefaults();

        // Backfill already-delivered orders so reports have consistent values.
        $commission = app(CommissionService::class);
        Order::where('status', 'delivered')->whereNull('driver_payout')->chunkById(200, function ($orders) use ($commission) {
            foreach ($orders as $order) {
                $commission->snapshot($order);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['sender_commission', 'driver_commission', 'driver_payout']);
        });
    }
};
