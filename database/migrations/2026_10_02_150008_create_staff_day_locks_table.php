<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_day_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $table->date('local_date');
            $table->timestamps();
            $table->unique(['tenant_id', 'staff_profile_id', 'local_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_day_locks');
    }
};
