<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Snapshot of who acted (survives user deletion) + where from.
            $table->string('actor_role', 20)->nullable()->after('user_id');
            $table->string('ip_address', 45)->nullable()->after('subject_id');

            // Indexes for fast filtering / pagination on large datasets.
            $table->index('action');
            $table->index('actor_role');
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['action']);
            $table->dropIndex(['actor_role']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['actor_role', 'ip_address']);
        });
    }
};
