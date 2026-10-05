<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('notify_new_appointments')->default(true)->after('booking_horizon_days');
            $table->boolean('notify_appointment_lifecycle')->default(true)->after('notify_new_appointments');
            $table->boolean('reminders_enabled')->default(true)->after('notify_appointment_lifecycle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['notify_new_appointments', 'notify_appointment_lifecycle', 'reminders_enabled']);
        });
    }
};
