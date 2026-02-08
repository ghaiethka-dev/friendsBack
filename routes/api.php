<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HomeServiceController;
use App\Http\Controllers\AdminManagementController; // الكلاس الجديد
/*
|--------------------------------------------------------------------------
| 1. Public Routes (الكل يراها بما في ذلك الزوار)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);



/*
|--------------------------------------------------------------------------
| 2. Protected Routes (تحتاج تسجيل دخول - أي Role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());
    Route::get('/user', [App\Http\Controllers\ProfileController::class, 'me']);
    Route::get('/profile', [ProfileController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

// 1. إنشاء مشرف جديد (يستخدمها Super Admin لإنشاء Admin، و Admin لإنشاء City Admin)
    Route::post('/create-admin', [AdminManagementController::class, 'store']);

    // 2. عرض موظفي المدن التابعين لمحافظة الأدمن الحالي (يستخدمها Admin)
    Route::get('/city-admins', [AdminManagementController::class, 'getCityAdmins']);

    // 3. عرض كافة المشرفين (يستخدمها Super Admin)
    Route::get('/all-admins', [AdminManagementController::class, 'getAllAdmins']);


    Route::apiResource('home-services', HomeServiceController::class);
    Route::get('/ads', [AdController::class, 'index']);
    /*
    |--------------------------------------------------------------------------
    | 3. Admin & Super Admin Only (إدارة المحتوى)
    |--------------------------------------------------------------------------
    */
    Route::put('/home-services/{id}/status', [HomeServiceController::class, 'updateStatus']);
    Route::middleware('role:admin,super_admin')->group(function () {

        Route::post('/admin/notifications', [NotificationController::class, 'store']);

        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);

        Route::apiResource('ads', AdController::class)->except(['index']);
        Route::delete('/ads/{ad}', [AdController::class, 'destroy']);

        Route::delete('/delete-admin/{id}', [AdminManagementController::class, 'destroy']);
    });
    /*
    |--------------------------------------------------------------------------
    | 4. Super Admin Only (صلاحيات حساسة جداً)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->group(function () {
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    });

});
