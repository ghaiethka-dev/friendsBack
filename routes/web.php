<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return response()->json([
        'status' => true,
        'message' => 'CloseFriend backend is running on Railway 🚀'
    ]);
});
Route::get('/test-mail', function () {
    Mail::raw('Test email from Laravel', function ($message) {
        $message->to('majdalafef123456@gmail.com')
                ->subject('Test Mail');
    });

    return 'sent';
});

