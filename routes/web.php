<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Sanctum routes for SPA authentication
Route::middleware('web')->group(function () {
    // This route is automatically registered by Sanctum
    // but we ensure it's available with proper middleware
});

require __DIR__.'/auth.php';