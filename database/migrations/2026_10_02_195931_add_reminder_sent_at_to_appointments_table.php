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
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dateTime('reminder_sent_at')->nullable()->after('completed_at');
            $table->index(['status', 'start_at', 'reminder_sent_at'], 'appointments_reminder_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_reminder_idx');
            $table->dropColumn('reminder_sent_at');
        });
    }
};
