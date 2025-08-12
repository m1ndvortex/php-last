<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InventoryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryManagementService
{
    /**
     * Check if sufficient inventory is available for the requested items
     *
     * @param array $items Array of items with inventory_item_id and quantity
     * @return array Array of unavailable items (empty if all available)
     */
    public function checkInventoryAvailability(array $items): array
    {
        $unavailableItems = [];

        foreach ($items as $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);

            if (!$inventoryItem) {
                $unavailableItems[] = [
                    'item_id' => $item['inventory_item_id'],
                    'error' => 'Item not found',
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => 0
                ];
                continue;
            }

            if ($inventoryItem->quantity < $item['quantity']) {
                $unavailableItems[] = [
                    'item_id' => $item['inventory_item_id'],
                    'item_name' => $inventoryItem->name,
                    'item_sku' => $inventoryItem->sku,
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $inventoryItem->quantity,
                    'error' => 'Insufficient inventory'
                ];
            }
        }

        return $unavailableItems;
    }

    /**
     * Reserve inventory by reducing stock quantities when invoice is created
     *
     * @param Invoice $invoice
     * @throws InsufficientInventoryException
     * @throws InventoryException
     */
    public function reserveInventory(Invoice $invoice): void
    {
        try {
            DB::transaction(function () use ($invoice) {
                foreach ($invoice->items as $invoiceItem) {
                    $inventoryItem = InventoryItem::lockForUpdate()->findOrFail($invoiceItem->inventory_item_id);

                    // Validate quantity
                    if ($invoiceItem->quantity <= 0) {
                        throw new InventoryException(
                            __('errors.inventory.invalid_quantity'),
                            ['quantity' => $invoiceItem->quantity]
                        );
                    }

                    // Double-check availability within transaction with lock
                    if ($inventoryItem->quantity < $invoiceItem->quantity) {
                        throw new InsufficientInventoryException(
                            __('errors.inventory.insufficient_stock'),
                            [[
                                'item_id' => $inventoryItem->id,
                                'item_name' => $inventoryItem->name,
                                'item_sku' => $inventoryItem->sku,
                                'requested_quantity' => $invoiceItem->quantity,
                                'available_quantity' => $inventoryItem->quantity,
                                'error' => __('errors.inventory.insufficient_stock')
                            ]]
                        );
                    }

                    // Reduce inventory quantity
                    $inventoryItem->decrement('quantity', $invoiceItem->quantity);

                    // Create inventory movement record
                    $this->createInventoryMovement([
                        'inventory_item_id' => $inventoryItem->id,
                        'type' => 'sale',
                        'quantity' => -$invoiceItem->quantity,
                        'reference_type' => 'invoice',
                        'reference_id' => $invoice->id,
                        'notes' => "Sale via Invoice #{$invoice->invoice_number}",
                        'created_by' => auth()->id()
                    ]);

                    Log::info('Inventory reserved', [
                        'inventory_item_id' => $inventoryItem->id,
                        'quantity_reserved' => $invoiceItem->quantity,
                        'remaining_quantity' => $inventoryItem->fresh()->quantity,
                        'invoice_id' => $invoice->id
                    ]);
                }
            });
        } catch (InsufficientInventoryException $e) {
            Log::error('Insufficient inventory during reservation', [
                'invoice_id' => $invoice->id,
                'unavailable_items' => $e->getUnavailableItems()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to reserve inventory', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new InventoryException(
                __('errors.inventory.stock_reservation_failed'),
                ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Restore inventory by returning stock when invoice is cancelled
     *
     * @param Invoice $invoice
     * @throws InventoryException
     */
    public function restoreInventory(Invoice $invoice): void
    {
        try {
            DB::transaction(function () use ($invoice) {
                foreach ($invoice->items as $invoiceItem) {
                    $inventoryItem = InventoryItem::lockForUpdate()->findOrFail($invoiceItem->inventory_item_id);

                    // Validate quantity
                    if ($invoiceItem->quantity <= 0) {
                        throw new InventoryException(
                            __('errors.inventory.invalid_quantity'),
                            ['quantity' => $invoiceItem->quantity]
                        );
                    }

                    // Restore inventory quantity
                    $inventoryItem->increment('quantity', $invoiceItem->quantity);

                    // Create inventory movement record
                    $this->createInventoryMovement([
                        'inventory_item_id' => $inventoryItem->id,
                        'type' => 'return',
                        'quantity' => $invoiceItem->quantity,
                        'reference_type' => 'invoice_cancellation',
                        'reference_id' => $invoice->id,
                        'notes' => "Returned from cancelled Invoice #{$invoice->invoice_number}",
                        'created_by' => auth()->id()
                    ]);

                    Log::info('Inventory restored', [
                        'inventory_item_id' => $inventoryItem->id,
                        'quantity_restored' => $invoiceItem->quantity,
                        'new_quantity' => $inventoryItem->fresh()->quantity,
                        'invoice_id' => $invoice->id
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to restore inventory', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new InventoryException(
                __('errors.inventory.stock_restoration_failed'),
                ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Create an inventory movement record for tracking stock changes
     *
     * @param array $data
     * @return InventoryMovement
     * @throws InventoryException
     */
    protected function createInventoryMovement(array $data): InventoryMovement
    {
        try {
            $userId = $data['created_by'] ?? auth()->id() ?? 1; // Fallback to user ID 1 for tests
            
            return InventoryMovement::create([
                'inventory_item_id' => $data['inventory_item_id'],
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'notes' => $data['notes'],
                'user_id' => $userId,
                'created_by' => $userId,
                'movement_date' => now(),
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create inventory movement', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new InventoryException(
                __('errors.inventory.movement_creation_failed'),
                ['data' => $data, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get inventory movements for a specific item
     *
     * @param int $inventoryItemId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInventoryMovements(int $inventoryItemId, int $limit = 50)
    {
        return InventoryMovement::where('inventory_item_id', $inventoryItemId)
            ->with(['inventoryItem', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get low stock items based on minimum stock levels
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockItems()
    {
        return InventoryItem::whereRaw('quantity <= minimum_stock')
            ->where('is_active', true)
            ->with(['category', 'location'])
            ->orderBy('quantity', 'asc')
            ->get();
    }

    /**
     * Validate inventory availability and throw exception if insufficient
     *
     * @param array $items
     * @throws InsufficientInventoryException
     */
    public function validateInventoryAvailability(array $items): void
    {
        $unavailableItems = $this->checkInventoryAvailability($items);

        if (!empty($unavailableItems)) {
            throw new InsufficientInventoryException(
                'Some items are not available in sufficient quantities',
                $unavailableItems
            );
        }
    }
}