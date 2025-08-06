<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'default_gold_purity',
        'image_path',
        'sort_order',
        'specifications',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_gold_purity' => 'decimal:3',
        'sort_order' => 'integer',
        'specifications' => 'array',
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
     * Get all inventory items where this is the main category.
     */
    public function mainCategoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'main_category_id');
    }

    /**
     * Get all images for this category.
     */
    public function images(): HasMany
    {
        return $this->hasMany(CategoryImage::class);
    }

    /**
     * Get the primary image for this category.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(CategoryImage::class)->where('is_primary', true);
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

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the formatted gold purity for display.
     */
    public function getFormattedGoldPurityAttribute(): ?string
    {
        if (!$this->default_gold_purity) {
            return null;
        }

        $locale = app()->getLocale();
        $purity = $this->default_gold_purity;
        
        if ($locale === 'fa') {
            // Convert to Persian numerals and add Persian text
            $persianNumerals = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $persianPurity = str_replace($englishNumerals, $persianNumerals, number_format($purity, 1));
            return $persianPurity . ' عیار';
        }
        
        return number_format($purity, 1) . 'K';
    }

    /**
     * Get the total count of items in this category and its subcategories.
     */
    public function getItemCountAttribute(): int
    {
        $directItems = $this->inventoryItems()->count();
        $mainCategoryItems = $this->mainCategoryItems()->count();
        $subcategoryItems = $this->children()->withCount('inventoryItems')->get()->sum('inventory_items_count');
        
        return $directItems + $mainCategoryItems + $subcategoryItems;
    }

    /**
     * Check if this category has children.
     */
    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the category image URL (primary image or fallback to image_path).
     */
    public function getImageUrlAttribute(): ?string
    {
        // First try to get primary image from category_images table
        if ($this->primaryImage) {
            return $this->primaryImage->full_url;
        }
        
        // Fallback to direct image_path field
        if ($this->image_path) {
            return \Storage::disk('public')->url($this->image_path);
        }
        
        return null;
    }

    /**
     * Get all descendant categories (children, grandchildren, etc.).
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->with('descendants');
    }

    /**
     * Get all ancestor categories (parent, grandparent, etc.).
     */
    public function ancestors()
    {
        $ancestors = collect();
        $current = $this->parent;
        
        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }
        
        return $ancestors->reverse();
    }
}
