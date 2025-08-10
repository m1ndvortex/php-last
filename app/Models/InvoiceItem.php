<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'inventory_item_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'gold_purity',
        'weight',
        'serial_number',
        'category_id',
        'main_category_id',
        'category_path',
        // Price breakdown fields
        'base_gold_cost',
        'labor_cost',
        'profit_amount',
        'tax_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'gold_purity' => 'decimal:3',
        'weight' => 'decimal:3',
        // Price breakdown fields
        'base_gold_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    /**
     * Get the invoice that owns the item.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the inventory item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the category (subcategory) for this invoice item.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the main category for this invoice item.
     */
    public function mainCategory()
    {
        return $this->belongsTo(Category::class, 'main_category_id');
    }

    /**
     * Get the formatted gold purity for display.
     */
    public function getFormattedGoldPurityAttribute(): ?string
    {
        if (!$this->gold_purity) {
            return null;
        }

        $locale = app()->getLocale();
        $purity = $this->gold_purity;
        
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
     * Get the category path display.
     */
    public function getCategoryDisplayAttribute(): ?string
    {
        if ($this->category_path) {
            return $this->category_path;
        }

        $path = [];
        if ($this->mainCategory) {
            $path[] = $this->mainCategory->localized_name;
        }
        if ($this->category && $this->category->id !== $this->main_category_id) {
            $path[] = $this->category->localized_name;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Get the category image URL for display.
     */
    public function getCategoryImageUrlAttribute(): ?string
    {
        // First try subcategory image
        if ($this->category && $this->category->image_path) {
            return Storage::url($this->category->image_path);
        }
        
        // Then try main category image
        if ($this->mainCategory && $this->mainCategory->image_path) {
            return Storage::url($this->mainCategory->image_path);
        }
        
        return null;
    }

    /**
     * Get the localized category name for display.
     */
    public function getLocalizedCategoryNameAttribute(): ?string
    {
        $locale = app()->getLocale();
        
        if ($this->category) {
            return $locale === 'fa' && $this->category->name_persian 
                ? $this->category->name_persian 
                : $this->category->name;
        }
        
        if ($this->mainCategory) {
            return $locale === 'fa' && $this->mainCategory->name_persian 
                ? $this->mainCategory->name_persian 
                : $this->mainCategory->name;
        }
        
        return null;
    }
}
