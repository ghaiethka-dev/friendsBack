<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HomeServiceController;

/*
|--------------------------------------------------------------------------
| Public Routes (الروابط العامة المتاحة للجميع)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);
Route::get('/ads', [AdController::class, 'index']);


/*
|--------------------------------------------------------------------------
| Protected Routes (الروابط المحمية - تتطلب تسجيل دخول)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());

    
    Route::get('/profile', [ProfileController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);


    Route::apiResource('ads', AdController::class)->except(['index']);

    Route::apiResource('home-services', HomeServiceController::class);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Admin & Super Admin Only (إدارة النظام)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::post('/admin/notifications', [NotificationController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->group(function () {
        // حذف التنبيهات نهائياً
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    });
});