<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DayOffController;
use App\Http\Controllers\Api\V1\PublicAvailabilityController;
use App\Http\Controllers\Api\V1\PublicBusinessController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StaffServiceController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantMemberController;
use App\Http\Controllers\Api\V1\WorkingHourController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('request.id')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registration');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-attempts');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
        Route::get('tenant', [TenantController::class, 'show']);
        Route::patch('tenant', [TenantController::class, 'update'])->middleware('tenant.role:owner');
        Route::get('tenant/members', [TenantMemberController::class, 'index'])->middleware('tenant.role:owner');
        Route::post('tenant/members', [TenantMemberController::class, 'store'])->middleware('tenant.role:owner');
        Route::apiResource('services', ServiceController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('tenant.role:owner');
        Route::apiResource('staff', StaffController::class)
            ->only(['index', 'store', 'update'])
            ->middleware('tenant.role:owner');
        Route::get('staff/{staff}/working-hours', [WorkingHourController::class, 'index'])
            ->middleware('tenant.role:owner');
        Route::post('staff/{staff}/working-hours', [WorkingHourController::class, 'store'])
            ->middleware('tenant.role:owner');
        Route::put('staff/{staff}/services/{service}', [StaffServiceController::class, 'store'])
            ->middleware('tenant.role:owner');
        Route::delete('staff/{staff}/services/{service}', [StaffServiceController::class, 'destroy'])
            ->middleware('tenant.role:owner');
        Route::delete('staff/{staff}/working-hours/{workingHour}', [WorkingHourController::class, 'destroy'])
            ->middleware('tenant.role:owner');
        Route::get('days-off', [DayOffController::class, 'index'])->middleware('tenant.role:owner');
        Route::post('days-off', [DayOffController::class, 'store'])->middleware('tenant.role:owner');
        Route::delete('days-off/{dayOff}', [DayOffController::class, 'destroy'])->middleware('tenant.role:owner');
        Route::post('appointments', [AppointmentController::class, 'store']);
        Route::get('appointments', [AppointmentController::class, 'index']);
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
        Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'update']);
        Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])
            ->middleware('tenant.role:owner,staff');
        Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
            ->middleware('tenant.role:owner,staff');
        Route::post('appointments/{appointment}/complete', [AppointmentController::class, 'complete'])
            ->middleware('tenant.role:owner,staff');
    });

    Route::get('public/businesses/{tenant:slug}/availability', PublicAvailabilityController::class)->middleware('throttle:public-api');
    Route::get('public/businesses/{tenant:slug}', [PublicBusinessController::class, 'show']);
    Route::get('public/businesses/{tenant:slug}/services', [PublicBusinessController::class, 'services']);
    Route::get('public/businesses/{tenant:slug}/staff', [PublicBusinessController::class, 'staff']);
    Route::post('public/businesses/{tenant:slug}/appointments', [AppointmentController::class, 'store'])->middleware('throttle:public-booking');
    Route::get('public/appointments/{token}', [AppointmentController::class, 'publicShow']);
});
