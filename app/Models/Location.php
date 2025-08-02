<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_persian',
        'description',
        'description_persian',
        'code',
        'type',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get all inventory items in this location.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get all movements from this location.
     */
    public function movementsFrom(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'from_location_id');
    }

    /**
     * Get all movements to this location.
     */
    public function movementsTo(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'to_location_id');
    }

    /**
     * Get all stock audits for this location.
     */
    public function stockAudits(): HasMany
    {
        return $this->hasMany(StockAudit::class);
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
     * Scope to get only active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by location type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get total stock value for this location.
     */
    public function getTotalStockValueAttribute(): float
    {
        return $this->inventoryItems()
            ->where('is_active', true)
            ->sum(\DB::raw('quantity * unit_price'));
    }
}
