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
    
    // Invoice routes - specific routes first to avoid conflicts
    Route::prefix('invoices')->group(function () {
        Route::post('/batch-pdf', [\App\Http\Controllers\InvoiceController::class, 'generateBatchPDFs']);
        Route::post('/batch-download', [\App\Http\Controllers\InvoiceController::class, 'downloadBatchPDFs']);
        Route::post('/{invoice}/duplicate', [\App\Http\Controllers\InvoiceController::class, 'duplicate']);
        Route::post('/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'generatePDF']);
        Route::get('/{invoice}/pdf/download', [\App\Http\Controllers\InvoiceController::class, 'downloadPDF']);
        Route::post('/{invoice}/mark-sent', [\App\Http\Controllers\InvoiceController::class, 'markAsSent']);
        Route::post('/{invoice}/mark-paid', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid']);
        Route::post('/{invoice}/attachments', [\App\Http\Controllers\InvoiceController::class, 'addAttachment']);
        Route::delete('/{invoice}/attachments/{attachment}', [\App\Http\Controllers\InvoiceController::class, 'removeAttachment']);
    });
    Route::apiResource('invoices', \App\Http\Controllers\InvoiceController::class);
    
    // Invoice Template routes - specific routes first to avoid conflicts
    Route::prefix('invoice-templates')->group(function () {
        Route::get('/default-structure', [\App\Http\Controllers\InvoiceTemplateController::class, 'getDefaultStructure']);
        Route::post('/validate-structure', [\App\Http\Controllers\InvoiceTemplateController::class, 'validateStructure']);
        Route::post('/{invoiceTemplate}/duplicate', [\App\Http\Controllers\InvoiceTemplateController::class, 'duplicate']);
        Route::post('/{invoiceTemplate}/set-default', [\App\Http\Controllers\InvoiceTemplateController::class, 'setAsDefault']);
    });
    Route::apiResource('invoice-templates', \App\Http\Controllers\InvoiceTemplateController::class);
    
    // Recurring Invoice routes - specific routes first to avoid conflicts
    Route::prefix('recurring-invoices')->group(function () {
        Route::post('/process-due', [\App\Http\Controllers\RecurringInvoiceController::class, 'processDue']);
        Route::get('/upcoming', [\App\Http\Controllers\RecurringInvoiceController::class, 'upcoming']);
        Route::get('/stats', [\App\Http\Controllers\RecurringInvoiceController::class, 'stats']);
        Route::post('/{recurringInvoice}/generate', [\App\Http\Controllers\RecurringInvoiceController::class, 'generateInvoice']);
        Route::post('/{recurringInvoice}/pause', [\App\Http\Controllers\RecurringInvoiceController::class, 'pause']);
        Route::post('/{recurringInvoice}/resume', [\App\Http\Controllers\RecurringInvoiceController::class, 'resume']);
    });
    Route::apiResource('recurring-invoices', \App\Http\Controllers\RecurringInvoiceController::class);
    
    // Inventory routes - specific routes first to avoid conflicts
    Route::prefix('inventory')->group(function () {
        Route::get('/low-stock', [\App\Http\Controllers\InventoryController::class, 'lowStock']);
        Route::get('/expiring', [\App\Http\Controllers\InventoryController::class, 'expiring']);
        Route::get('/expired', [\App\Http\Controllers\InventoryController::class, 'expired']);
        Route::get('/summary/location', [\App\Http\Controllers\InventoryController::class, 'summaryByLocation']);
        Route::get('/summary/category', [\App\Http\Controllers\InventoryController::class, 'summaryByCategory']);
        Route::post('/{inventory}/transfer', [\App\Http\Controllers\InventoryController::class, 'transfer']);
        Route::get('/{inventory}/movements', [\App\Http\Controllers\InventoryController::class, 'movements']);
    });
    Route::apiResource('inventory', \App\Http\Controllers\InventoryController::class);
    
    // Stock Audit routes
    Route::prefix('stock-audits')->group(function () {
        Route::post('/{stockAudit}/start', [\App\Http\Controllers\StockAuditController::class, 'start']);
        Route::post('/{stockAudit}/complete', [\App\Http\Controllers\StockAuditController::class, 'complete']);
        Route::post('/{stockAudit}/cancel', [\App\Http\Controllers\StockAuditController::class, 'cancel']);
        Route::put('/{stockAudit}/items/{auditItem}', [\App\Http\Controllers\StockAuditController::class, 'updateItem']);
        Route::post('/{stockAudit}/bulk-update', [\App\Http\Controllers\StockAuditController::class, 'bulkUpdate']);
        Route::get('/{stockAudit}/variance-report', [\App\Http\Controllers\StockAuditController::class, 'varianceReport']);
        Route::get('/{stockAudit}/uncounted-items', [\App\Http\Controllers\StockAuditController::class, 'uncountedItems']);
        Route::get('/{stockAudit}/export', [\App\Http\Controllers\StockAuditController::class, 'export']);
    });
    Route::apiResource('stock-audits', \App\Http\Controllers\StockAuditController::class);
    
    // BOM (Bill of Materials) routes
    Route::prefix('bom')->group(function () {
        Route::post('/production-cost', [\App\Http\Controllers\BOMController::class, 'productionCost']);
        Route::post('/can-produce', [\App\Http\Controllers\BOMController::class, 'canProduce']);
        Route::post('/produce', [\App\Http\Controllers\BOMController::class, 'produce']);
        Route::post('/production-requirements', [\App\Http\Controllers\BOMController::class, 'productionRequirements']);
        Route::get('/tree', [\App\Http\Controllers\BOMController::class, 'bomTree']);
        Route::get('/usage-report', [\App\Http\Controllers\BOMController::class, 'usageReport']);
    });
    Route::apiResource('bom', \App\Http\Controllers\BOMController::class);
    
    // Categories routes
    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
    
    // Locations routes
    Route::apiResource('locations', \App\Http\Controllers\LocationController::class);
    
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