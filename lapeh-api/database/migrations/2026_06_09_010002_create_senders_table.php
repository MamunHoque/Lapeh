<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['individual', 'business'])->default('individual');

            // Business-only fields (nullable for individuals).
            $table->string('business_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('contact_person_name')->nullable();

            // Default pickup location, prefilled when creating a request.
            $table->string('default_pickup_address')->nullable();
            $table->decimal('default_pickup_lat', 10, 7)->nullable();
            $table->decimal('default_pickup_lng', 10, 7)->nullable();

            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senders');
    }
};
