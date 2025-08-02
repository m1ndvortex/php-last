<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_persian',
        'description',
        'description_persian',
        'sku',
        'category_id',
        'location_id',
        'quantity',
        'unit_price',
        'cost_price',
        'gold_purity',
        'weight',
        'serial_number',
        'batch_number',
        'expiry_date',
        'minimum_stock',
        'maximum_stock',
        'is_active',
        'track_serial',
        'track_batch',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'gold_purity' => 'decimal:3',
        'weight' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'maximum_stock' => 'decimal:3',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'track_serial' => 'boolean',
        'track_batch' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the category that owns the inventory item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the location that owns the inventory item.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get all movements for this inventory item.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get all stock audit items for this inventory item.
     */
    public function stockAuditItems(): HasMany
    {
        return $this->hasMany(StockAuditItem::class);
    }

    /**
     * Get all BOM entries where this item is the finished product.
     */
    public function bomAsFinished(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'finished_item_id');
    }

    /**
     * Get all BOM entries where this item is a component.
     */
    public function bomAsComponent(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'component_item_id');
    }

    /**
     * Get the localized name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->name_persian ? $this->name_persian : $this->name;
    }

    /**
     * Get the localized description based on current locale.
     */
    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->description_persian ? $this->description_persian : $this->description;
    }

    /**
     * Get the total value of this inventory item.
     */
    public function getTotalValueAttribute(): float
    {
        return (float) ($this->quantity * $this->unit_price);
    }

    /**
     * Get the total cost value of this inventory item.
     */
    public function getTotalCostAttribute(): float
    {
        return (float) ($this->quantity * $this->cost_price);
    }

    /**
     * Check if the item is low stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity <= $this->minimum_stock;
    }

    /**
     * Check if the item is expired or expiring soon.
     */
    public function getIsExpiringAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lte(Carbon::now()->addDays(30));
    }

    /**
     * Check if the item is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lt(Carbon::now());
    }

    /**
     * Scope to get only active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= minimum_stock');
    }

    /**
     * Scope to get expiring items.
     */
    public function scopeExpiring($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope to get expired items.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::now());
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by location.
     */
    public function scopeInLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
