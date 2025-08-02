<?php

namespace App\Services;

use App\Models\StockAudit;
use App\Models\StockAuditItem;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockAuditService
{
    /**
     * Create a new stock audit.
     */
    public function createAudit(array $data): StockAudit
    {
        return DB::transaction(function () use ($data) {
            $audit = StockAudit::create([
                'audit_number' => $this->generateAuditNumber(),
                'location_id' => $data['location_id'] ?? null,
                'status' => StockAudit::STATUS_PENDING,
                'audit_date' => $data['audit_date'] ?? now()->toDateString(),
                'auditor_id' => $data['auditor_id'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Create audit items for all inventory items in the location(s)
            $this->createAuditItems($audit);
            
            return $audit;
        });
    }

    /**
     * Start an audit.
     */
    public function startAudit(StockAudit $audit): StockAudit
    {
        $audit->update([
            'status' => StockAudit::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
        
        return $audit;
    }

    /**
     * Complete an audit.
     */
    public function completeAudit(StockAudit $audit): StockAudit
    {
        return DB::transaction(function () use ($audit) {
            // Calculate variances for all items
            $this->calculateVariances($audit);
            
            // Create adjustment movements for items with variances
            $this->createAdjustmentMovements($audit);
            
            $audit->update([
                'status' => StockAudit::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            
            return $audit;
        });
    }

    /**
     * Cancel an audit.
     */
    public function cancelAudit(StockAudit $audit): StockAudit
    {
        $audit->update([
            'status' => StockAudit::STATUS_CANCELLED,
        ]);
        
        return $audit;
    }

    /**
     * Update audit item count.
     */
    public function updateAuditItemCount(StockAuditItem $auditItem, float $physicalQuantity, string $notes = null): StockAuditItem
    {
        $variance = $physicalQuantity - $auditItem->system_quantity;
        $varianceValue = $variance * $auditItem->unit_cost;
        
        $auditItem->update([
            'physical_quantity' => $physicalQuantity,
            'variance' => $variance,
            'variance_value' => $varianceValue,
            'notes' => $notes,
            'is_counted' => true,
            'counted_at' => now(),
        ]);
        
        return $auditItem;
    }

    /**
     * Create audit items for an audit.
     */
    protected function createAuditItems(StockAudit $audit): void
    {
        $query = InventoryItem::active()->with('location');
        
        // Filter by location if specified
        if ($audit->location_id) {
            $query->where('location_id', $audit->location_id);
        }
        
        $items = $query->get();
        
        foreach ($items as $item) {
            StockAuditItem::create([
                'stock_audit_id' => $audit->id,
                'inventory_item_id' => $item->id,
                'system_quantity' => $item->quantity,
                'unit_cost' => $item->cost_price,
                'variance_value' => 0,
            ]);
        }
    }

    /**
     * Calculate variances for all audit items.
     */
    protected function calculateVariances(StockAudit $audit): void
    {
        $auditItems = $audit->auditItems()->where('is_counted', true)->get();
        
        foreach ($auditItems as $auditItem) {
            if ($auditItem->physical_quantity !== null) {
                $variance = $auditItem->physical_quantity - $auditItem->system_quantity;
                $varianceValue = $variance * $auditItem->unit_cost;
                
                $auditItem->update([
                    'variance' => $variance,
                    'variance_value' => $varianceValue,
                ]);
            }
        }
    }

    /**
     * Create adjustment movements for items with variances.
     */
    protected function createAdjustmentMovements(StockAudit $audit): void
    {
        $itemsWithVariance = $audit->auditItems()
            ->where('variance', '!=', 0)
            ->with('inventoryItem')
            ->get();
            
        foreach ($itemsWithVariance as $auditItem) {
            $item = $auditItem->inventoryItem;
            $variance = $auditItem->variance;
            
            // Create movement record
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'from_location_id' => $variance < 0 ? $item->location_id : null,
                'to_location_id' => $variance > 0 ? $item->location_id : null,
                'type' => InventoryMovement::TYPE_ADJUSTMENT,
                'quantity' => abs($variance),
                'unit_cost' => $auditItem->unit_cost,
                'reference_type' => 'stock_audit',
                'reference_id' => $audit->id,
                'notes' => "Stock audit adjustment - Audit #{$audit->audit_number}",
                'user_id' => $audit->auditor_id,
                'movement_date' => $audit->completed_at ?? now(),
            ]);
            
            // Update inventory item quantity
            $item->update([
                'quantity' => $auditItem->physical_quantity,
            ]);
        }
    }

    /**
     * Generate audit number.
     */
    protected function generateAuditNumber(): string
    {
        $prefix = 'AUD';
        $date = now()->format('Ymd');
        
        $lastAudit = StockAudit::where('audit_number', 'like', $prefix . $date . '%')
            ->orderBy('audit_number', 'desc')
            ->first();
            
        if (!$lastAudit) {
            return $prefix . $date . '001';
        }
        
        $lastNumber = (int) substr($lastAudit->audit_number, -3);
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $nextNumber;
    }

    /**
     * Get audit summary.
     */
    public function getAuditSummary(StockAudit $audit): array
    {
        $auditItems = $audit->auditItems;
        
        return [
            'total_items' => $auditItems->count(),
            'counted_items' => $auditItems->where('is_counted', true)->count(),
            'items_with_variance' => $auditItems->where('variance', '!=', 0)->count(),
            'total_variance_value' => $auditItems->sum('variance_value'),
            'positive_variance_value' => $auditItems->where('variance', '>', 0)->sum('variance_value'),
            'negative_variance_value' => $auditItems->where('variance', '<', 0)->sum('variance_value'),
            'completion_percentage' => $audit->completion_percentage,
        ];
    }

    /**
     * Get variance report.
     */
    public function getVarianceReport(StockAudit $audit): Collection
    {
        return $audit->auditItems()
            ->withVariance()
            ->with(['inventoryItem.category', 'inventoryItem.location'])
            ->orderBy('variance_value', 'desc')
            ->get();
    }

    /**
     * Get uncounted items.
     */
    public function getUncountedItems(StockAudit $audit): Collection
    {
        return $audit->auditItems()
            ->uncounted()
            ->with(['inventoryItem.category', 'inventoryItem.location'])
            ->get();
    }

    /**
     * Bulk update audit items.
     */
    public function bulkUpdateAuditItems(StockAudit $audit, array $updates): void
    {
        DB::transaction(function () use ($audit, $updates) {
            foreach ($updates as $update) {
                $auditItem = $audit->auditItems()
                    ->where('inventory_item_id', $update['inventory_item_id'])
                    ->first();
                    
                if ($auditItem) {
                    $this->updateAuditItemCount(
                        $auditItem,
                        $update['physical_quantity'],
                        $update['notes'] ?? null
                    );
                }
            }
        });
    }

    /**
     * Export audit results.
     */
    public function exportAuditResults(StockAudit $audit): array
    {
        $auditItems = $audit->auditItems()
            ->with(['inventoryItem.category', 'inventoryItem.location'])
            ->get();
            
        return $auditItems->map(function ($auditItem) {
            $item = $auditItem->inventoryItem;
            
            return [
                'sku' => $item->sku,
                'name' => $item->localized_name,
                'category' => $item->category->localized_name,
                'location' => $item->location->localized_name,
                'system_quantity' => $auditItem->system_quantity,
                'physical_quantity' => $auditItem->physical_quantity,
                'variance' => $auditItem->variance,
                'unit_cost' => $auditItem->unit_cost,
                'variance_value' => $auditItem->variance_value,
                'is_counted' => $auditItem->is_counted,
                'notes' => $auditItem->notes,
            ];
        })->toArray();
    }
}