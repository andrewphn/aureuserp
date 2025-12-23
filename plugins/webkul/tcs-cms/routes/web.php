<?php

use Illuminate\Support\Facades\Route;
use Webkul\TcsCms\Http\Controllers\PublicController;

/*
|--------------------------------------------------------------------------
| TCS CMS Public Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the TcsCmsServiceProvider and are the
| public-facing website routes for TCS Woodwork.
|
*/

Route::middleware('web')->group(function () {
    // Homepage - this takes priority over FilamentPHP customer panel
    Route::get('/', [PublicController::class, 'home'])->name('tcs.home');

    // Work/Portfolio
    Route::get('/work', [PublicController::class, 'work'])->name('tcs.work');
    Route::get('/work/{slug}', [PublicController::class, 'workShow'])->name('tcs.work.show');

    // Journal
    Route::get('/journal', [PublicController::class, 'journal'])->name('tcs.journal');
    Route::get('/journal/{slug}', [PublicController::class, 'journalShow'])->name('tcs.journal.show');
});
