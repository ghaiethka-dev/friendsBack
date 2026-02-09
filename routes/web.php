<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => true,
        'message' => 'CloseFriend backend is running on Railway 🚀'
    ]);
});
