<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InventoryMovement;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Events\InvoiceCreatedEvent;
use App\Events\InventoryAdjustmentEvent;
use App\Events\CustomerUpdatedEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class IntegrationEventService
{
    protected $inventoryService;
    protected $accountingService;
    protected $customerService;
    protected $alertService;

    public function __construct()
    {
        // Services will be resolved lazily to avoid circular dependencies
    }

    protected function getInventoryService()
    {
        if (!$this->inventoryService) {
            $this->inventoryService = app(InventoryService::class);
        }
        return $this->inventoryService;
    }

    protected function getAccountingService()
    {
        if (!$this->accountingService) {
            $this->accountingService = app(AccountingService::class);
        }
        return $this->accountingService;
    }

    protected function getCustomerService()
    {
        if (!$this->customerService) {
            $this->customerService = app(CustomerService::class);
        }
        return $this->customerService;
    }

    protected function getAlertService()
    {
        if (!$this->alertService) {
            $this->alertService = app(AlertService::class);
        }
        return $this->alertService;
    }

    /**
     * Handle invoice creation with cross-module updates
     */
    public function handleInvoiceCreated(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            try {
                // Update inventory levels for each item
                foreach ($invoice->items as $item) {
                    $this->getInventoryService()->updateStock(
                        $item->inventory_item_id,
                        -$item->quantity,
                        'sale',
                        "Invoice #{$invoice->invoice_number}",
                        $invoice->id
                    );
                }

                // Create accounting entries for the sale
                $this->getAccountingService()->createSaleEntries($invoice);

                // Update customer purchase history and statistics
                $this->getCustomerService()->updatePurchaseHistory($invoice->customer_id, $invoice);

                // Check for low stock alerts
                $this->checkLowStockAlerts($invoice);

                // Log the integration event
                Log::info('Invoice integration completed', [
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'total_amount' => $invoice->total_amount
                ]);

                // Trigger event for other listeners
                event(new InvoiceCreatedEvent($invoice));

            } catch (Exception $e) {
                Log::error('Invoice integration failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle inventory adjustments with accounting integration
     */
    public function handleInventoryAdjustment(InventoryMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            try {
                // Create accounting entry for inventory adjustment
                $this->getAccountingService()->createInventoryAdjustmentEntry($movement);

                // Update item valuation
                $this->getInventoryService()->recalculateItemValuation($movement->inventory_item_id);

                // Check for reorder alerts
                $this->getAlertService()->checkReorderLevels($movement->inventory_item_id);

                // Update related reports cache
                $this->invalidateReportsCache(['inventory', 'financial']);

                Log::info('Inventory adjustment integration completed', [
                    'movement_id' => $movement->id,
                    'item_id' => $movement->inventory_item_id,
                    'quantity_change' => $movement->quantity_change
                ]);

                event(new InventoryAdjustmentEvent($movement));

            } catch (Exception $e) {
                Log::error('Inventory adjustment integration failed', [
                    'movement_id' => $movement->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle customer updates across modules
     */
    public function handleCustomerUpdated(Customer $customer, array $changes): void
    {
        DB::transaction(function () use ($customer, $changes) {
            try {
                // Update related invoices if customer info changed
                if (isset($changes['name']) || isset($changes['email']) || isset($changes['phone'])) {
                    $this->updateRelatedInvoices($customer);
                }

                // Update communication preferences
                if (isset($changes['communication_preferences'])) {
                    $this->updateCommunicationSettings($customer);
                }

                // Recalculate customer statistics
                $this->getCustomerService()->recalculateCustomerStatistics($customer->id);

                // Update accounting customer records
                $this->getAccountingService()->updateCustomerAccount($customer);

                Log::info('Customer update integration completed', [
                    'customer_id' => $customer->id,
                    'changes' => array_keys($changes)
                ]);

                event(new CustomerUpdatedEvent($customer, $changes));

            } catch (Exception $e) {
                Log::error('Customer update integration failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle returns and refunds across modules
     */
    public function handleReturn(Invoice $originalInvoice, array $returnItems): void
    {
        DB::transaction(function () use ($originalInvoice, $returnItems) {
            try {
                // Restore inventory for returned items
                foreach ($returnItems as $returnItem) {
                    $this->getInventoryService()->updateStock(
                        $returnItem['inventory_item_id'],
                        $returnItem['quantity'],
                        'return',
                        "Return from Invoice #{$originalInvoice->invoice_number}",
                        $originalInvoice->id
                    );
                }

                // Create accounting entries for the return
                $this->getAccountingService()->createReturnEntries($originalInvoice, $returnItems);

                // Update customer statistics
                $this->getCustomerService()->updateReturnHistory($originalInvoice->customer_id, $returnItems);

                // Update invoice status
                $originalInvoice->update(['status' => 'partially_returned']);

                Log::info('Return integration completed', [
                    'original_invoice_id' => $originalInvoice->id,
                    'returned_items_count' => count($returnItems)
                ]);

            } catch (Exception $e) {
                Log::error('Return integration failed', [
                    'invoice_id' => $originalInvoice->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Ensure data consistency across all modules
     */
    public function validateDataConsistency(): array
    {
        $issues = [];

        try {
            // Check inventory-accounting consistency
            $inventoryIssues = $this->validateInventoryAccountingConsistency();
            if (!empty($inventoryIssues)) {
                $issues['inventory_accounting'] = $inventoryIssues;
            }

            // Check customer-invoice consistency
            $customerIssues = $this->validateCustomerInvoiceConsistency();
            if (!empty($customerIssues)) {
                $issues['customer_invoice'] = $customerIssues;
            }

            // Check invoice-inventory consistency
            $invoiceInventoryIssues = $this->validateInvoiceInventoryConsistency();
            if (!empty($invoiceInventoryIssues)) {
                $issues['invoice_inventory'] = $invoiceInventoryIssues;
            }

            Log::info('Data consistency check completed', [
                'issues_found' => count($issues),
                'categories' => array_keys($issues)
            ]);

        } catch (Exception $e) {
            Log::error('Data consistency check failed', ['error' => $e->getMessage()]);
            $issues['validation_error'] = $e->getMessage();
        }

        return $issues;
    }

    /**
     * Check for low stock alerts after invoice creation
     */
    protected function checkLowStockAlerts(Invoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            $inventoryItem = InventoryItem::find($item->inventory_item_id);
            if ($inventoryItem && $inventoryItem->quantity <= $inventoryItem->reorder_level) {
                $this->getAlertService()->createLowStockAlert($inventoryItem);
            }
        }
    }

    /**
     * Update related invoices when customer info changes
     */
    protected function updateRelatedInvoices(Customer $customer): void
    {
        // Update draft invoices with new customer information
        $customer->invoices()
            ->where('status', 'draft')
            ->update([
                'updated_at' => now()
            ]);
    }

    /**
     * Update communication settings
     */
    protected function updateCommunicationSettings(Customer $customer): void
    {
        // Update communication preferences in related systems
        // This could include email marketing systems, SMS services, etc.
    }

    /**
     * Invalidate reports cache when data changes
     */
    protected function invalidateReportsCache(array $reportTypes): void
    {
        try {
            foreach ($reportTypes as $type) {
                cache()->tags(['reports', $type])->flush();
            }
        } catch (\Exception $e) {
            // Fallback to simple cache clearing if tagging is not supported
            foreach ($reportTypes as $type) {
                cache()->forget("reports_{$type}");
            }
        }
    }

    /**
     * Validate inventory-accounting consistency
     */
    protected function validateInventoryAccountingConsistency(): array
    {
        $issues = [];
        
        // Check if inventory movements have corresponding accounting entries
        $movementsWithoutAccounting = InventoryMovement::whereDoesntHave('accountingEntries')->count();
        if ($movementsWithoutAccounting > 0) {
            $issues[] = "Found {$movementsWithoutAccounting} inventory movements without accounting entries";
        }

        return $issues;
    }

    /**
     * Validate customer-invoice consistency
     */
    protected function validateCustomerInvoiceConsistency(): array
    {
        $issues = [];
        
        // Check for invoices with invalid customer references
        $invalidInvoices = Invoice::whereDoesntHave('customer')->count();
        if ($invalidInvoices > 0) {
            $issues[] = "Found {$invalidInvoices} invoices with invalid customer references";
        }

        return $issues;
    }

    /**
     * Validate invoice-inventory consistency
     */
    protected function validateInvoiceInventoryConsistency(): array
    {
        $issues = [];
        
        // Check for invoice items with invalid inventory references
        $invalidItems = DB::table('invoice_items')
            ->leftJoin('inventory_items', 'invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->whereNull('inventory_items.id')
            ->count();
            
        if ($invalidItems > 0) {
            $issues[] = "Found {$invalidItems} invoice items with invalid inventory references";
        }

        return $issues;
    }
}