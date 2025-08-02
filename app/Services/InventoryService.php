<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryService
{
    /**
     * Create a new inventory item.
     */
    public function createItem(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::create($data);
            
            // Create initial stock movement if quantity > 0
            if ($item->quantity > 0) {
                $this->createMovement([
                    'inventory_item_id' => $item->id,
                    'to_location_id' => $item->location_id,
                    'type' => InventoryMovement::TYPE_IN,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->cost_price,
                    'reference_type' => 'initial_stock',
                    'notes' => 'Initial stock entry',
                    'user_id' => auth()->id(),
                    'movement_date' => now(),
                ]);
            }
            
            return $item;
        });
    }

    /**
     * Update an inventory item.
     */
    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        return DB::transaction(function () use ($item, $data) {
            $originalQuantity = $item->quantity;
            $originalLocationId = $item->location_id;
            
            $item->update($data);
            
            // Handle quantity changes
            if (isset($data['quantity']) && $data['quantity'] != $originalQuantity) {
                $quantityDifference = $data['quantity'] - $originalQuantity;
                
                $this->createMovement([
                    'inventory_item_id' => $item->id,
                    'to_location_id' => $quantityDifference > 0 ? $item->location_id : null,
                    'from_location_id' => $quantityDifference < 0 ? $item->location_id : null,
                    'type' => InventoryMovement::TYPE_ADJUSTMENT,
                    'quantity' => abs($quantityDifference),
                    'unit_cost' => $item->cost_price,
                    'reference_type' => 'manual_adjustment',
                    'notes' => 'Manual quantity adjustment',
                    'user_id' => auth()->id(),
                    'movement_date' => now(),
                ]);
            }
            
            // Handle location changes
            if (isset($data['location_id']) && $data['location_id'] != $originalLocationId) {
                $this->transferItem($item, $originalLocationId, $data['location_id'], $item->quantity);
            }
            
            return $item;
        });
    }

    /**
     * Transfer item between locations.
     */
    public function transferItem(InventoryItem $item, int $fromLocationId, int $toLocationId, float $quantity, string $notes = null): InventoryMovement
    {
        return DB::transaction(function () use ($item, $fromLocationId, $toLocationId, $quantity, $notes) {
            // Validate sufficient stock
            if ($item->location_id == $fromLocationId && $item->quantity < $quantity) {
                throw new \Exception('Insufficient stock for transfer');
            }
            
            $movement = $this->createMovement([
                'inventory_item_id' => $item->id,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'type' => InventoryMovement::TYPE_TRANSFER,
                'quantity' => $quantity,
                'unit_cost' => $item->cost_price,
                'reference_type' => 'transfer',
                'notes' => $notes ?? 'Location transfer',
                'user_id' => auth()->id(),
                'movement_date' => now(),
            ]);
            
            // Update item location if full quantity is transferred
            if ($item->location_id == $fromLocationId && $item->quantity == $quantity) {
                $item->update(['location_id' => $toLocationId]);
            }
            
            return $movement;
        });
    }

    /**
     * Create an inventory movement.
     */
    public function createMovement(array $data): InventoryMovement
    {
        return InventoryMovement::create($data);
    }

    /**
     * Get low stock items.
     */
    public function getLowStockItems(): Collection
    {
        return InventoryItem::active()
            ->lowStock()
            ->with(['category', 'location'])
            ->get();
    }

    /**
     * Get expiring items.
     */
    public function getExpiringItems(int $days = 30): Collection
    {
        return InventoryItem::active()
            ->expiring($days)
            ->with(['category', 'location'])
            ->get();
    }

    /**
     * Get expired items.
     */
    public function getExpiredItems(): Collection
    {
        return InventoryItem::active()
            ->expired()
            ->with(['category', 'location'])
            ->get();
    }

    /**
     * Get inventory summary by location.
     */
    public function getInventorySummaryByLocation()
    {
        return Location::active()
            ->withCount('inventoryItems')
            ->with(['inventoryItems' => function ($query) {
                $query->active();
            }])
            ->get()
            ->map(function ($location) {
                $totalValue = $location->inventoryItems->sum('total_value');
                $totalCost = $location->inventoryItems->sum('total_cost');
                
                return [
                    'location' => $location,
                    'item_count' => $location->inventory_items_count,
                    'total_value' => $totalValue,
                    'total_cost' => $totalCost,
                    'profit_margin' => $totalCost > 0 ? (($totalValue - $totalCost) / $totalCost) * 100 : 0,
                ];
            });
    }

    /**
     * Get inventory summary by category.
     */
    public function getInventorySummaryByCategory()
    {
        return Category::active()
            ->withCount('inventoryItems')
            ->with(['inventoryItems' => function ($query) {
                $query->active();
            }])
            ->get()
            ->map(function ($category) {
                $totalValue = $category->inventoryItems->sum('total_value');
                $totalCost = $category->inventoryItems->sum('total_cost');
                
                return [
                    'category' => $category,
                    'item_count' => $category->inventory_items_count,
                    'total_value' => $totalValue,
                    'total_cost' => $totalCost,
                    'profit_margin' => $totalCost > 0 ? (($totalValue - $totalCost) / $totalCost) * 100 : 0,
                ];
            });
    }

    /**
     * Get movement history for an item.
     */
    public function getMovementHistory(InventoryItem $item, int $limit = 50): Collection
    {
        return $item->movements()
            ->with(['fromLocation', 'toLocation', 'user'])
            ->orderBy('movement_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate current stock for an item based on movements.
     */
    public function calculateCurrentStock(InventoryItem $item): float
    {
        $inboundMovements = $item->movements()
            ->whereIn('type', [InventoryMovement::TYPE_IN, InventoryMovement::TYPE_PRODUCTION])
            ->sum('quantity');
            
        $outboundMovements = $item->movements()
            ->whereIn('type', [InventoryMovement::TYPE_OUT, InventoryMovement::TYPE_WASTAGE])
            ->sum('quantity');
            
        return $inboundMovements - $outboundMovements;
    }

    /**
     * Generate SKU for new item.
     */
    public function generateSKU(Category $category, string $prefix = null): string
    {
        $prefix = $prefix ?? $category->code;
        $lastItem = InventoryItem::where('sku', 'like', $prefix . '%')
            ->orderBy('sku', 'desc')
            ->first();
            
        if (!$lastItem) {
            return $prefix . '001';
        }
        
        $lastNumber = (int) substr($lastItem->sku, strlen($prefix));
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $nextNumber;
    }

    /**
     * Search inventory items.
     */
    public function searchItems(array $filters): Collection
    {
        $query = InventoryItem::active()->with(['category', 'location']);
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_persian', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['low_stock'])) {
            $query->lowStock();
        }
        
        if (!empty($filters['expiring'])) {
            $query->expiring($filters['expiring_days'] ?? 30);
        }
        
        return $query->get();
    }
}