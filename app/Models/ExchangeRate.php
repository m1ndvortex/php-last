<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
    ];

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc');
    }

    public static function getRate(string $fromCurrency, string $toCurrency, ?string $date = null): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $date = $date ?? now()->toDateString();
        
        $rate = static::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->forDate($date)
            ->first();

        return $rate ? $rate->rate : 1.0;
    }
}