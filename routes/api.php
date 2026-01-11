<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeServiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/profile', [ProfileController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);


    Route::apiResource('ads', AdController::class)
        ->only(['index', 'store', 'update', 'destroy']);


    Route::apiResource('home-services', HomeServiceController::class);


    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    /*
    |--------------------------------------------------------------------------
    | Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin,superadmin')->group(function () {
        Route::post('/admin/notifications', [NotificationController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:superadmin')->group(function () {
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    });
});
