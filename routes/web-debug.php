<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-auth-debug', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'user_email' => auth()->user()?->email,
        'browser_testing_env' => env('BROWSER_TESTING'),
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
    ]);
})->middleware('web');
