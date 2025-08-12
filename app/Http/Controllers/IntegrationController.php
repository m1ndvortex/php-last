<?php

namespace App\Http\Controllers;

use App\Services\IntegrationEventService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    protected $integrationService;

    public function __construct(IntegrationEventService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * Validate data consistency across all modules
     */
    public function validateDataConsistency(): JsonResponse
    {
        try {
            $issues = $this->integrationService->validateDataConsistency();

            return response()->json([
                'success' => true,
                'message' => 'Data consistency validation completed',
                'data' => [
                    'issues_found' => count($issues),
                    'issues' => $issues,
                    'status' => empty($issues) ? 'consistent' : 'issues_found',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data consistency validation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get integration status and statistics
     */
    public function getIntegrationStatus(): JsonResponse
    {
        try {
            // Get various integration statistics
            $stats = [
                'invoices_with_inventory_updates' => \App\Models\Invoice::whereHas('items.inventoryItem')->count(),
                'inventory_movements_with_accounting' => \App\Models\InventoryMovement::whereHas('accountingEntries')->count(),
                'customers_with_purchase_history' => \App\Models\Customer::whereNotNull('last_purchase_date')->count(),
                'active_alerts' => \App\Models\Alert::where('status', 'active')->count(),
                'recent_integrations' => [
                    'invoices_today' => \App\Models\Invoice::whereDate('created_at', today())->count(),
                    'inventory_movements_today' => \App\Models\InventoryMovement::whereDate('created_at', today())->count(),
                    'customer_updates_today' => \App\Models\Customer::whereDate('updated_at', today())->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Integration status retrieved successfully',
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve integration status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger manual data consistency fix
     */
    public function fixDataConsistency(Request $request): JsonResponse
    {
        try {
            $fixType = $request->input('fix_type', 'all');
            $results = [];

            switch ($fixType) {
                case 'inventory_accounting':
                    $results['inventory_accounting'] = $this->fixInventoryAccountingConsistency();
                    break;
                case 'customer_statistics':
                    $results['customer_statistics'] = $this->fixCustomerStatistics();
                    break;
                case 'all':
                    $results['inventory_accounting'] = $this->fixInventoryAccountingConsistency();
                    $results['customer_statistics'] = $this->fixCustomerStatistics();
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid fix type specified');
            }

            return response()->json([
                'success' => true,
                'message' => 'Data consistency fixes applied successfully',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply data consistency fixes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fix inventory-accounting consistency issues
     */
    protected function fixInventoryAccountingConsistency(): array
    {
        $fixed = 0;
        $errors = [];

        try {
            // Find inventory movements without accounting entries
            $movementsWithoutAccounting = \App\Models\InventoryMovement::whereDoesntHave('accountingEntries')
                ->where('type', 'adjustment')
                ->get();

            foreach ($movementsWithoutAccounting as $movement) {
                try {
                    $this->integrationService->handleInventoryAdjustment($movement);
                    $fixed++;
                } catch (\Exception $e) {
                    $errors[] = "Movement {$movement->id}: {$e->getMessage()}";
                }
            }

        } catch (\Exception $e) {
            $errors[] = "General error: {$e->getMessage()}";
        }

        return [
            'fixed_count' => $fixed,
            'errors' => $errors,
        ];
    }

    /**
     * Fix customer statistics consistency
     */
    protected function fixCustomerStatistics(): array
    {
        $fixed = 0;
        $errors = [];

        try {
            $customers = \App\Models\Customer::all();

            foreach ($customers as $customer) {
                try {
                    // Recalculate customer statistics
                    $totalInvoiceAmount = $customer->invoices()->sum('total_amount');
                    $lastInvoiceDate = $customer->invoices()->max('issue_date');
                    $invoiceCount = $customer->invoices()->count();

                    $customer->update([
                        'last_purchase_date' => $lastInvoiceDate,
                        'total_purchases' => $totalInvoiceAmount,
                        'purchase_count' => $invoiceCount,
                    ]);

                    $fixed++;
                } catch (\Exception $e) {
                    $errors[] = "Customer {$customer->id}: {$e->getMessage()}";
                }
            }

        } catch (\Exception $e) {
            $errors[] = "General error: {$e->getMessage()}";
        }

        return [
            'fixed_count' => $fixed,
            'errors' => $errors,
        ];
    }
}