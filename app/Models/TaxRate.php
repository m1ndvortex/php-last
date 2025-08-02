<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_persian',
        'rate',
        'type',
        'is_compound',
        'is_active',
        'effective_from',
        'effective_to',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeEffectiveOn($query, ?Carbon $date = null)
    {
        $date = $date ?? now();
        
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            });
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->name_persian ? $this->name_persian : $this->name;
    }

    public function isEffectiveOn(?Carbon $date = null): bool
    {
        $date = $date ?? now();
        
        if ($date->lt($this->effective_from)) {
            return false;
        }
        
        if ($this->effective_to && $date->gt($this->effective_to)) {
            return false;
        }
        
        return $this->is_active;
    }

    public function calculateTax(float $amount, array $otherTaxes = []): float
    {
        if (!$this->is_active) {
            return 0;
        }

        $taxableAmount = $amount;
        
        // If compound tax, add other taxes to the taxable amount
        if ($this->is_compound && !empty($otherTaxes)) {
            $taxableAmount += array_sum($otherTaxes);
        }
        
        return $taxableAmount * $this->rate;
    }

    public static function calculateTotalTax(float $amount, array $taxRateIds, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $taxes = [];
        $totalTax = 0;
        
        $taxRates = static::whereIn('id', $taxRateIds)
            ->effectiveOn($date)
            ->orderBy('is_compound')
            ->get();
        
        $nonCompoundTaxes = [];
        
        foreach ($taxRates as $taxRate) {
            $taxAmount = $taxRate->calculateTax($amount, $nonCompoundTaxes);
            $taxes[$taxRate->id] = [
                'name' => $taxRate->localized_name,
                'rate' => $taxRate->rate,
                'amount' => $taxAmount,
            ];
            
            $totalTax += $taxAmount;
            
            if (!$taxRate->is_compound) {
                $nonCompoundTaxes[] = $taxAmount;
            }
        }
        
        return [
            'taxes' => $taxes,
            'total_tax' => $totalTax,
            'total_with_tax' => $amount + $totalTax,
        ];
    }
}