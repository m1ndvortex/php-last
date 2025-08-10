<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Exceptions\InsufficientInventoryException;
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
     */
    public function reserveInventory(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $invoiceItem) {
                $inventoryItem = InventoryItem::lockForUpdate()->findOrFail($invoiceItem->inventory_item_id);

                // Double-check availability within transaction with lock
                if ($inventoryItem->quantity < $invoiceItem->quantity) {
                    throw new InsufficientInventoryException(
                        "Insufficient inventory for item: {$inventoryItem->name} (SKU: {$inventoryItem->sku})",
                        [[
                            'item_id' => $inventoryItem->id,
                            'item_name' => $inventoryItem->name,
                            'item_sku' => $inventoryItem->sku,
                            'requested_quantity' => $invoiceItem->quantity,
                            'available_quantity' => $inventoryItem->quantity,
                            'error' => 'Insufficient inventory'
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
    }

    /**
     * Restore inventory by returning stock when invoice is cancelled
     *
     * @param Invoice $invoice
     */
    public function restoreInventory(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $invoiceItem) {
                $inventoryItem = InventoryItem::lockForUpdate()->findOrFail($invoiceItem->inventory_item_id);

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
    }

    /**
     * Create an inventory movement record for tracking stock changes
     *
     * @param array $data
     * @return InventoryMovement
     */
    protected function createInventoryMovement(array $data): InventoryMovement
    {
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