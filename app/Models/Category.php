<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_persian',
        'description',
        'description_persian',
        'code',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all inventory items in this category.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
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
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root categories (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
