<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->index(['tenant_id', 'staff_profile_id', 'start_at', 'end_at', 'status'], 'appointments_staff_time_status_idx');
                $table->index(['tenant_id', 'customer_id', 'start_at'], 'appointments_customer_time_idx');
            });

            return;
        }

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('service_name');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->date('local_date');
            $table->string('status', 32)->default('pending');
            $table->text('notes')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'staff_profile_id', 'start_at', 'end_at', 'status'], 'appointments_staff_time_status_idx');
            $table->index(['tenant_id', 'customer_id', 'start_at'], 'appointments_customer_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
