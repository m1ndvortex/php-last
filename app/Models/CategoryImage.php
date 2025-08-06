<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CategoryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'image_path',
        'alt_text',
        'alt_text_persian',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category that owns this image.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the localized alt text based on current locale.
     */
    public function getLocalizedAltTextAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->alt_text_persian ? $this->alt_text_persian : $this->alt_text;
    }

    /**
     * Get the full URL for the image.
     */
    public function getFullUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Get the absolute path for the image.
     */
    public function getAbsolutePathAttribute(): string
    {
        return Storage::disk('public')->path($this->image_path);
    }

    /**
     * Scope to get only primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }
}