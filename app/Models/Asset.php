<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_number',
        'name',
        'name_persian',
        'description',
        'category',
        'purchase_cost',
        'purchase_date',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'accumulated_depreciation',
        'current_value',
        'status',
        'disposal_date',
        'disposal_value',
        'cost_center_id',
        'metadata',
    ];

    protected $casts = [
        'purchase_cost' => 'decimal:2',
        'purchase_date' => 'date',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'current_value' => 'decimal:2',
        'disposal_date' => 'date',
        'disposal_value' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->name_persian ? $this->name_persian : $this->name;
    }

    public function calculateDepreciation(?Carbon $asOfDate = null): float
    {
        $asOfDate = $asOfDate ?? now();
        $purchaseDate = Carbon::parse($this->purchase_date);
        
        if ($asOfDate->lt($purchaseDate)) {
            return 0;
        }

        $yearsElapsed = $purchaseDate->diffInYears($asOfDate, false);
        $depreciableAmount = $this->purchase_cost - $this->salvage_value;

        return match ($this->depreciation_method) {
            'straight_line' => $this->calculateStraightLineDepreciation($depreciableAmount, $yearsElapsed),
            'declining_balance' => $this->calculateDecliningBalanceDepreciation($yearsElapsed),
            'units_of_production' => $this->calculateUnitsOfProductionDepreciation($depreciableAmount),
            default => 0
        };
    }

    private function calculateStraightLineDepreciation(float $depreciableAmount, float $yearsElapsed): float
    {
        $annualDepreciation = $depreciableAmount / $this->useful_life_years;
        $totalDepreciation = $annualDepreciation * min($yearsElapsed, $this->useful_life_years);
        
        return min($totalDepreciation, $depreciableAmount);
    }

    private function calculateDecliningBalanceDepreciation(float $yearsElapsed): float
    {
        $rate = 2 / $this->useful_life_years; // Double declining balance
        $bookValue = $this->purchase_cost;
        $totalDepreciation = 0;

        for ($year = 1; $year <= min($yearsElapsed, $this->useful_life_years); $year++) {
            $yearlyDepreciation = $bookValue * $rate;
            $yearlyDepreciation = min($yearlyDepreciation, $bookValue - $this->salvage_value);
            
            $totalDepreciation += $yearlyDepreciation;
            $bookValue -= $yearlyDepreciation;
            
            if ($bookValue <= $this->salvage_value) {
                break;
            }
        }

        return $totalDepreciation;
    }

    private function calculateUnitsOfProductionDepreciation(float $depreciableAmount): float
    {
        // This would require additional fields for units produced/used
        // For now, return straight-line as fallback
        return $this->calculateStraightLineDepreciation($depreciableAmount, 
            Carbon::parse($this->purchase_date)->diffInYears(now(), false));
    }

    public function updateCurrentValue(): void
    {
        $depreciation = $this->calculateDepreciation();
        $currentValue = max($this->purchase_cost - $depreciation, $this->salvage_value);
        
        $this->update([
            'accumulated_depreciation' => $depreciation,
            'current_value' => $currentValue,
        ]);
    }

    public function dispose(Carbon $disposalDate, float $disposalValue): void
    {
        $this->update([
            'status' => 'disposed',
            'disposal_date' => $disposalDate,
            'disposal_value' => $disposalValue,
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($asset) {
            if (empty($asset->asset_number)) {
                $asset->asset_number = static::generateAssetNumber();
            }
            $asset->current_value = $asset->purchase_cost;
        });
    }

    public static function generateAssetNumber(): string
    {
        $prefix = 'AST';
        $year = now()->format('Y');
        $sequence = static::whereYear('created_at', now()->year)->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}