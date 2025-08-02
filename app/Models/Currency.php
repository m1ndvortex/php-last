<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_persian',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency', 'code');
    }

    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->name_persian ? $this->name_persian : $this->name;
    }

    public static function getBaseCurrency(): ?Currency
    {
        return static::base()->first();
    }

    public function convertTo(string $toCurrency, float $amount, ?string $date = null): float
    {
        if ($this->code === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($toCurrency, $date);
        return $amount * $rate;
    }

    public function getExchangeRate(string $toCurrency, ?string $date = null): float
    {
        $date = $date ?? now()->toDateString();
        
        $exchangeRate = ExchangeRate::where('from_currency', $this->code)
            ->where('to_currency', $toCurrency)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $exchangeRate ? $exchangeRate->rate : 1.0;
    }
}