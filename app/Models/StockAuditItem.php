<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAuditItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_audit_id',
        'inventory_item_id',
        'system_quantity',
        'physical_quantity',
        'variance',
        'unit_cost',
        'variance_value',
        'notes',
        'is_counted',
        'counted_at',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'physical_quantity' => 'decimal:3',
        'variance' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'variance_value' => 'decimal:2',
        'is_counted' => 'boolean',
        'counted_at' => 'datetime',
    ];

    /**
     * Get the stock audit that owns the audit item.
     */
    public function stockAudit(): BelongsTo
    {
        return $this->belongsTo(StockAudit::class);
    }

    /**
     * Get the inventory item being audited.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Check if there is a variance.
     */
    public function getHasVarianceAttribute(): bool
    {
        return $this->variance != 0;
    }

    /**
     * Check if this is a positive variance (overage).
     */
    public function getIsOverageAttribute(): bool
    {
        return $this->variance > 0;
    }

    /**
     * Check if this is a negative variance (shortage).
     */
    public function getIsShortageAttribute(): bool
    {
        return $this->variance < 0;
    }

    /**
     * Get the absolute variance value.
     */
    public function getAbsoluteVarianceAttribute(): float
    {
        return abs((float) $this->variance);
    }

    /**
     * Get the variance percentage.
     */
    public function getVariancePercentageAttribute(): float
    {
        if ($this->system_quantity == 0) {
            return 0;
        }

        return ($this->variance / $this->system_quantity) * 100;
    }

    /**
     * Scope to get items with variances.
     */
    public function scopeWithVariance($query)
    {
        return $query->where('variance', '!=', 0);
    }

    /**
     * Scope to get counted items.
     */
    public function scopeCounted($query)
    {
        return $query->where('is_counted', true);
    }

    /**
     * Scope to get uncounted items.
     */
    public function scopeUncounted($query)
    {
        return $query->where('is_counted', false);
    }

    /**
     * Scope to get overages.
     */
    public function scopeOverages($query)
    {
        return $query->where('variance', '>', 0);
    }

    /**
     * Scope to get shortages.
     */
    public function scopeShortages($query)
    {
        return $query->where('variance', '<', 0);
    }
}
