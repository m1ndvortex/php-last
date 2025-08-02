<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
    
    Route::middleware(['auth:sanctum', 'auth.api'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);
        Route::get('/user', [App\Http\Controllers\Auth\AuthController::class, 'user']);
        Route::put('/profile', [App\Http\Controllers\Auth\AuthController::class, 'updateProfile']);
        Route::put('/password', [App\Http\Controllers\Auth\AuthController::class, 'changePassword']);
    });
});

// Localization routes
Route::prefix('localization')->group(function () {
    Route::get('/current', [App\Http\Controllers\LocalizationController::class, 'getCurrentLocale']);
    Route::get('/supported', [App\Http\Controllers\LocalizationController::class, 'getSupportedLocales']);
    Route::get('/translations', [App\Http\Controllers\LocalizationController::class, 'getTranslations']);
    Route::post('/switch-language', [App\Http\Controllers\LocalizationController::class, 'switchLanguage']);
    Route::get('/calendar', [App\Http\Controllers\LocalizationController::class, 'getCalendarInfo']);
    Route::post('/convert-date', [App\Http\Controllers\LocalizationController::class, 'convertDate']);
    Route::post('/format-number', [App\Http\Controllers\LocalizationController::class, 'formatNumber']);
    Route::post('/number-to-words', [App\Http\Controllers\LocalizationController::class, 'numberToWords']);
});

// Protected routes
Route::middleware(['auth:sanctum', 'auth.api'])->group(function () {
    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/kpis', function () {
            // TODO: Implement KPI calculation
            return response()->json(['message' => 'KPIs endpoint - to be implemented']);
        });
        
        Route::get('/widgets', function () {
            // TODO: Implement widget data
            return response()->json(['message' => 'Widgets endpoint - to be implemented']);
        });
    });
    
    // Customer routes - specific routes first to avoid conflicts
    Route::prefix('customers')->group(function () {
        Route::get('/aging-report', [\App\Http\Controllers\CustomerController::class, 'agingReport']);
        Route::get('/crm-pipeline', [\App\Http\Controllers\CustomerController::class, 'crmPipeline']);
        Route::get('/upcoming-birthdays', [\App\Http\Controllers\CustomerController::class, 'upcomingBirthdays']);
        Route::get('/upcoming-anniversaries', [\App\Http\Controllers\CustomerController::class, 'upcomingAnniversaries']);
        Route::put('/{customer}/crm-stage', [\App\Http\Controllers\CustomerController::class, 'updateCrmStage']);
        Route::post('/{customer}/communicate', [\App\Http\Controllers\CustomerController::class, 'sendCommunication']);
        Route::get('/{customer}/vcard', [\App\Http\Controllers\CustomerController::class, 'exportVCard']);
    });
    Route::apiResource('customers', \App\Http\Controllers\CustomerController::class);
    
    // Invoice routes
    Route::apiResource('invoices', \App\Http\Controllers\InvoiceController::class);
    
    // Inventory routes
    Route::apiResource('inventory', \App\Http\Controllers\InventoryController::class);
    
    // Accounting routes
    Route::prefix('accounting')->group(function () {
        Route::get('/ledger', function () {
            return response()->json(['message' => 'Ledger endpoint - to be implemented']);
        });
        
        Route::get('/reports/{type}', function ($type) {
            return response()->json(['message' => 'Reports endpoint - to be implemented']);
        });
    });
});