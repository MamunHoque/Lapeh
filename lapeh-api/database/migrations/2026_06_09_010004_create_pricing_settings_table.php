<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_fee', 10, 2)->default(7.00);
            $table->decimal('per_km_fee', 10, 2)->default(1.50);
            $table->decimal('min_fee', 10, 2)->default(7.00);
            $table->string('currency', 10)->default('AED');
            $table->decimal('search_radius_km', 5, 2)->default(5.00);
            $table->unsignedInteger('request_timeout_sec')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_settings');
    }
};
