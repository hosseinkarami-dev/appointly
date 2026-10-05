<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_local_time');
            $table->time('end_local_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'staff_profile_id', 'weekday']);
        });

        Schema::create('days_off', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->date('local_date');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'local_date', 'staff_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('days_off');
        Schema::dropIfExists('working_hours');
    }
};
