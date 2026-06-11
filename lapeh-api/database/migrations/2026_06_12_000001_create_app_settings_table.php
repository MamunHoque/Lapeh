<?php

use App\Services\SettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('type')->default('string'); // string|text|bool|int|float|json
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        // Seed defaults from the current .env / config so the store mirrors the
        // running deployment on first migrate. Secrets are left null (still
        // sourced from .env via fallback until an admin sets them in the UI).
        SettingsService::seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
