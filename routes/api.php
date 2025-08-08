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
        Route::get('/kpis', [\App\Http\Controllers\DashboardController::class, 'getKPIs']);
        Route::get('/sales-chart', [\App\Http\Controllers\DashboardController::class, 'getSalesChart']);
        Route::get('/category-performance', [\App\Http\Controllers\DashboardController::class, 'getCategoryPerformance']);
        Route::get('/gold-purity-performance', [\App\Http\Controllers\DashboardController::class, 'getGoldPurityPerformance']);
        Route::get('/category-stock-alerts', [\App\Http\Controllers\DashboardController::class, 'getCategoryStockAlerts']);
        Route::get('/alerts', [\App\Http\Controllers\DashboardController::class, 'getAlerts']);
        Route::post('/alerts/mark-read', [\App\Http\Controllers\DashboardController::class, 'markAlertAsRead']);
        Route::get('/layout', [\App\Http\Controllers\DashboardController::class, 'getDashboardLayout']);
        Route::post('/layout', [\App\Http\Controllers\DashboardController::class, 'saveDashboardLayout']);
        Route::get('/presets', [\App\Http\Controllers\DashboardController::class, 'getDashboardPresets']);
        Route::post('/presets/apply', [\App\Http\Controllers\DashboardController::class, 'applyDashboardPreset']);
        Route::get('/widgets/available', [\App\Http\Controllers\DashboardController::class, 'getAvailableWidgets']);
        Route::post('/widgets/add', [\App\Http\Controllers\DashboardController::class, 'addWidget']);
        Route::delete('/widgets/remove', [\App\Http\Controllers\DashboardController::class, 'removeWidget']);
        Route::put('/widgets/config', [\App\Http\Controllers\DashboardController::class, 'updateWidgetConfig']);
        Route::post('/reset', [\App\Http\Controllers\DashboardController::class, 'resetDashboard']);
        Route::post('/clear-cache', [\App\Http\Controllers\DashboardController::class, 'clearCache']);
    });

    // Inventory Reports routes
    Route::prefix('inventory-reports')->group(function () {
        Route::get('/category-hierarchy', [\App\Http\Controllers\InventoryReportController::class, 'categoryHierarchyReport']);
        Route::get('/category-sales-performance', [\App\Http\Controllers\InventoryReportController::class, 'categorySalesPerformance']);
        Route::get('/category-stock-levels', [\App\Http\Controllers\InventoryReportController::class, 'categoryStockLevels']);
        Route::get('/gold-purity-analysis', [\App\Http\Controllers\InventoryReportController::class, 'goldPurityAnalysis']);
        Route::get('/inventory-analytics', [\App\Http\Controllers\InventoryReportController::class, 'inventoryAnalytics']);
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
        Route::get('/category-stats', [\App\Http\Controllers\InvoiceController::class, 'getCategoryStats']);
        Route::get('/gold-purity-stats', [\App\Http\Controllers\InvoiceController::class, 'getGoldPurityStats']);
        Route::post('/batch-pdf', [\App\Http\Controllers\InvoiceController::class, 'generateBatchPDFs']);
        Route::post('/batch-download', [\App\Http\Controllers\InvoiceController::class, 'downloadBatchPDFs']);
        Route::post('/process-overdue', [\App\Http\Controllers\InvoiceController::class, 'processOverdue']);
        Route::post('/{invoice}/duplicate', [\App\Http\Controllers\InvoiceController::class, 'duplicate']);
        Route::post('/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'generatePDF']);
        Route::get('/{invoice}/pdf/download', [\App\Http\Controllers\InvoiceController::class, 'downloadPDF']);
        Route::post('/{invoice}/mark-sent', [\App\Http\Controllers\InvoiceController::class, 'markAsSent']);
        Route::post('/{invoice}/mark-paid', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid']);
        Route::post('/{invoice}/cancel', [\App\Http\Controllers\InvoiceController::class, 'cancel']);
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
        Route::get('/gold-purity-options', [\App\Http\Controllers\InventoryController::class, 'goldPurityOptions']);
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
    
    // Categories routes - specific routes first to avoid conflicts
    Route::prefix('categories')->group(function () {
        Route::get('/hierarchy', [\App\Http\Controllers\CategoryController::class, 'getHierarchy']);
        Route::get('/for-select', [\App\Http\Controllers\CategoryController::class, 'getForSelect']);
        Route::get('/main-categories', [\App\Http\Controllers\CategoryController::class, 'getMainCategories']);
        Route::get('/subcategories', [\App\Http\Controllers\CategoryController::class, 'getSubcategories']);
        Route::get('/search', [\App\Http\Controllers\CategoryController::class, 'search']);
        Route::get('/gold-purity-options', [\App\Http\Controllers\CategoryController::class, 'getGoldPurityOptions']);
        Route::post('/reorder', [\App\Http\Controllers\CategoryController::class, 'reorder']);
        Route::post('/{category}/image', [\App\Http\Controllers\CategoryController::class, 'uploadImage']);
        Route::delete('/{category}/image', [\App\Http\Controllers\CategoryController::class, 'removeImage']);
        Route::get('/{category}/path', [\App\Http\Controllers\CategoryController::class, 'getCategoryPath']);
    });
    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
    
    // Locations routes
    Route::apiResource('locations', \App\Http\Controllers\LocationController::class);
    
    // Accounting routes
    Route::prefix('accounting')->group(function () {
        // Account routes
        Route::apiResource('accounts', \App\Http\Controllers\AccountController::class);
        Route::get('accounts/{account}/balance', [\App\Http\Controllers\AccountController::class, 'balance']);
        Route::get('accounts/{account}/ledger', [\App\Http\Controllers\AccountController::class, 'ledger']);
        Route::get('chart-of-accounts', [\App\Http\Controllers\AccountController::class, 'chartOfAccounts']);
        
        // Transaction routes
        Route::apiResource('transactions', \App\Http\Controllers\TransactionController::class);
        Route::post('transactions/{transaction}/lock', [\App\Http\Controllers\TransactionController::class, 'lock']);
        Route::post('transactions/{transaction}/unlock', [\App\Http\Controllers\TransactionController::class, 'unlock']);
        Route::post('transactions/{transaction}/approve', [\App\Http\Controllers\TransactionController::class, 'approve']);
        Route::post('transactions/{transaction}/duplicate', [\App\Http\Controllers\TransactionController::class, 'duplicate']);
        
        // Financial report routes
        Route::prefix('reports')->group(function () {
            Route::get('trial-balance', [\App\Http\Controllers\FinancialReportController::class, 'trialBalance']);
            Route::get('balance-sheet', [\App\Http\Controllers\FinancialReportController::class, 'balanceSheet']);
            Route::get('income-statement', [\App\Http\Controllers\FinancialReportController::class, 'incomeStatement']);
            Route::get('cash-flow-statement', [\App\Http\Controllers\FinancialReportController::class, 'cashFlowStatement']);
            Route::get('aged-receivables', [\App\Http\Controllers\FinancialReportController::class, 'agedReceivables']);
            Route::get('aged-payables', [\App\Http\Controllers\FinancialReportController::class, 'agedPayables']);
            Route::post('custom', [\App\Http\Controllers\FinancialReportController::class, 'customReport']);
        });
        
        // Cost center routes
        Route::apiResource('cost-centers', \App\Http\Controllers\CostCenterController::class);
        
        // Asset routes
        Route::apiResource('assets', \App\Http\Controllers\AssetController::class);
        Route::post('assets/{asset}/dispose', [\App\Http\Controllers\AssetController::class, 'dispose']);
        Route::get('assets/{asset}/depreciation', [\App\Http\Controllers\AssetController::class, 'depreciation']);
        Route::get('assets/{asset}/depreciation-schedule', [\App\Http\Controllers\AssetController::class, 'depreciationSchedule']);
        Route::get('asset-register', [\App\Http\Controllers\AssetController::class, 'register']);
        Route::post('process-depreciation', [\App\Http\Controllers\AssetController::class, 'processDepreciation']);
    });
    
    // Business Configuration routes
    Route::prefix('config')->group(function () {
        Route::get('/business-info', [\App\Http\Controllers\BusinessConfigurationController::class, 'getBusinessInfo']);
        Route::put('/business-info', [\App\Http\Controllers\BusinessConfigurationController::class, 'updateBusinessInfo']);
        Route::post('/logo', [\App\Http\Controllers\BusinessConfigurationController::class, 'uploadLogo']);
        Route::get('/tax', [\App\Http\Controllers\BusinessConfigurationController::class, 'getTaxConfig']);
        Route::put('/tax', [\App\Http\Controllers\BusinessConfigurationController::class, 'updateTaxConfig']);
        Route::get('/profit', [\App\Http\Controllers\BusinessConfigurationController::class, 'getProfitConfig']);
        Route::put('/profit', [\App\Http\Controllers\BusinessConfigurationController::class, 'updateProfitConfig']);
        Route::get('/category/{category}', [\App\Http\Controllers\BusinessConfigurationController::class, 'getByCategory']);
        Route::post('/clear-cache', [\App\Http\Controllers\BusinessConfigurationController::class, 'clearCache']);
    });
    
    // Role and Permission routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\RolePermissionController::class, 'getRoles']);
        Route::post('/', [\App\Http\Controllers\RolePermissionController::class, 'createRole']);
        Route::put('/{role}', [\App\Http\Controllers\RolePermissionController::class, 'updateRole']);
        Route::delete('/{role}', [\App\Http\Controllers\RolePermissionController::class, 'deleteRole']);
        Route::post('/assign', [\App\Http\Controllers\RolePermissionController::class, 'assignRole']);
        Route::post('/remove', [\App\Http\Controllers\RolePermissionController::class, 'removeRole']);
    });
    
    Route::prefix('permissions')->group(function () {
        Route::get('/', [\App\Http\Controllers\RolePermissionController::class, 'getPermissions']);
        Route::get('/user', [\App\Http\Controllers\RolePermissionController::class, 'getUserPermissions']);
        Route::post('/check', [\App\Http\Controllers\RolePermissionController::class, 'checkPermission']);
    });
    
    // Message Template routes
    Route::prefix('message-templates')->group(function () {
        Route::get('/', [\App\Http\Controllers\MessageTemplateController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\MessageTemplateController::class, 'store']);
        Route::get('/by-type-category', [\App\Http\Controllers\MessageTemplateController::class, 'getByTypeAndCategory']);
        Route::post('/render', [\App\Http\Controllers\MessageTemplateController::class, 'render']);
        Route::get('/default-variables', [\App\Http\Controllers\MessageTemplateController::class, 'getDefaultVariables']);
    });
    
    // Security routes
    Route::prefix('security')->group(function () {
        // Two-Factor Authentication
        Route::prefix('2fa')->group(function () {
            Route::post('/enable', [\App\Http\Controllers\SecurityController::class, 'enable2FA']);
            Route::post('/confirm', [\App\Http\Controllers\SecurityController::class, 'confirm2FA']);
            Route::post('/disable', [\App\Http\Controllers\SecurityController::class, 'disable2FA']);
            Route::post('/regenerate-backup-codes', [\App\Http\Controllers\SecurityController::class, 'regenerateBackupCodes']);
        });
        
        // Session Management
        Route::prefix('sessions')->group(function () {
            Route::get('/active', [\App\Http\Controllers\SecurityController::class, 'getActiveSessions']);
            Route::post('/terminate', [\App\Http\Controllers\SecurityController::class, 'terminateSession']);
            Route::post('/terminate-others', [\App\Http\Controllers\SecurityController::class, 'terminateOtherSessions']);
            Route::get('/stats', [\App\Http\Controllers\SecurityController::class, 'getSessionStats']);
        });
        
        // Audit Logs
        Route::prefix('audit')->group(function () {
            Route::get('/logs', [\App\Http\Controllers\SecurityController::class, 'getAuditLogs']);
            Route::get('/statistics', [\App\Http\Controllers\SecurityController::class, 'getAuditStatistics']);
            Route::post('/export', [\App\Http\Controllers\SecurityController::class, 'exportAuditLogs']);
        });
        
        // Login Anomalies
        Route::prefix('anomalies')->group(function () {
            Route::get('/', [\App\Http\Controllers\SecurityController::class, 'getLoginAnomalies']);
            Route::get('/statistics', [\App\Http\Controllers\SecurityController::class, 'getAnomalyStatistics']);
        });
    });
    
    // Data Compliance routes
    Route::prefix('compliance')->group(function () {
        Route::get('/data-types', [\App\Http\Controllers\DataComplianceController::class, 'getDataTypes']);
        Route::get('/statistics', [\App\Http\Controllers\DataComplianceController::class, 'getStatistics']);
        
        // Export requests
        Route::prefix('export')->group(function () {
            Route::post('/', [\App\Http\Controllers\DataComplianceController::class, 'createExportRequest']);
            Route::get('/', [\App\Http\Controllers\DataComplianceController::class, 'getExportRequests']);
            Route::get('/{exportRequest}/download', [\App\Http\Controllers\DataComplianceController::class, 'downloadExport']);
            Route::post('/process', [\App\Http\Controllers\DataComplianceController::class, 'processExportRequests']);
        });
        
        // Deletion requests
        Route::prefix('deletion')->group(function () {
            Route::post('/', [\App\Http\Controllers\DataComplianceController::class, 'createDeletionRequest']);
            Route::get('/', [\App\Http\Controllers\DataComplianceController::class, 'getDeletionRequests']);
            Route::post('/{deletionRequest}/approve', [\App\Http\Controllers\DataComplianceController::class, 'approveDeletionRequest']);
            Route::post('/{deletionRequest}/reject', [\App\Http\Controllers\DataComplianceController::class, 'rejectDeletionRequest']);
            Route::post('/process', [\App\Http\Controllers\DataComplianceController::class, 'processDeletionRequests']);
        });
    });
    
    // Communication routes
    Route::prefix('communications')->group(function () {
        Route::get('customers/{customer}', [\App\Http\Controllers\CommunicationController::class, 'index']);
        Route::post('send', [\App\Http\Controllers\CommunicationController::class, 'send']);
        Route::post('send-invoice', [\App\Http\Controllers\CommunicationController::class, 'sendInvoice']);
        Route::post('send-birthday-reminder', [\App\Http\Controllers\CommunicationController::class, 'sendBirthdayReminder']);
        Route::post('send-anniversary-reminder', [\App\Http\Controllers\CommunicationController::class, 'sendAnniversaryReminder']);
        Route::get('stats', [\App\Http\Controllers\CommunicationController::class, 'stats']);
        Route::get('{communication}/status', [\App\Http\Controllers\CommunicationController::class, 'status']);
        Route::post('{communication}/resend', [\App\Http\Controllers\CommunicationController::class, 'resend']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('stats', [\App\Http\Controllers\NotificationController::class, 'stats']);
        Route::get('realtime', [\App\Http\Controllers\NotificationController::class, 'realTime']);
        Route::post('{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::post('mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::post('send-test', [\App\Http\Controllers\NotificationController::class, 'sendTest']);
    });

    // Queue Management routes
    Route::prefix('queue')->group(function () {
        Route::get('/', [\App\Http\Controllers\QueueController::class, 'index']);
        Route::get('/history', [\App\Http\Controllers\QueueController::class, 'getJobHistory']);
        Route::post('/backup', [\App\Http\Controllers\QueueController::class, 'scheduleBackup']);
        Route::post('/recurring-invoices', [\App\Http\Controllers\QueueController::class, 'processRecurringInvoices']);
        Route::post('/reminders', [\App\Http\Controllers\QueueController::class, 'sendReminders']);
        Route::post('/stock-alerts', [\App\Http\Controllers\QueueController::class, 'sendStockAlerts']);
        Route::post('/communication', [\App\Http\Controllers\QueueController::class, 'sendCommunication']);
        Route::post('/bulk-communications', [\App\Http\Controllers\QueueController::class, 'sendBulkCommunications']);
        Route::post('/sync-offline', [\App\Http\Controllers\QueueController::class, 'syncOfflineData']);
        Route::delete('/failed-jobs', [\App\Http\Controllers\QueueController::class, 'clearFailedJobs']);
        Route::post('/retry-job', [\App\Http\Controllers\QueueController::class, 'retryFailedJob']);
    });

    // Database Performance Monitoring routes
    Route::prefix('database')->group(function () {
        Route::get('/metrics', [\App\Http\Controllers\DatabasePerformanceController::class, 'metrics']);
        Route::get('/health', [\App\Http\Controllers\DatabasePerformanceController::class, 'health']);
        Route::post('/clear-cache', [\App\Http\Controllers\DatabasePerformanceController::class, 'clearCache']);
        Route::get('/slow-queries', [\App\Http\Controllers\DatabasePerformanceController::class, 'slowQueries']);
        Route::get('/dashboard-kpis', [\App\Http\Controllers\DatabasePerformanceController::class, 'dashboardKpis']);
        Route::get('/inventory-stats', [\App\Http\Controllers\DatabasePerformanceController::class, 'inventoryStats']);
    });
});