<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'finished_item_id',
        'component_item_id',
        'quantity_required',
        'wastage_percentage',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:3',
        'wastage_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the finished item (final product).
     */
    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'finished_item_id');
    }

    /**
     * Get the component item (raw material).
     */
    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'component_item_id');
    }

    /**
     * Get the total quantity required including wastage.
     */
    public function getTotalQuantityRequiredAttribute(): float
    {
        $baseQuantity = (float) $this->quantity_required;
        $wastageMultiplier = 1 + ($this->wastage_percentage / 100);
        
        return $baseQuantity * $wastageMultiplier;
    }

    /**
     * Get the wastage quantity.
     */
    public function getWastageQuantityAttribute(): float
    {
        return (float) $this->quantity_required * ($this->wastage_percentage / 100);
    }

    /**
     * Get the total cost for this BOM line.
     */
    public function getTotalCostAttribute(): float
    {
        return $this->total_quantity_required * (float) $this->componentItem->cost_price;
    }

    /**
     * Scope to get only active BOM entries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get BOM entries for a specific finished item.
     */
    public function scopeForFinishedItem($query, int $finishedItemId)
    {
        return $query->where('finished_item_id', $finishedItemId);
    }

    /**
     * Scope to get BOM entries that use a specific component.
     */
    public function scopeUsingComponent($query, int $componentItemId)
    {
        return $query->where('component_item_id', $componentItemId);
    }
}
