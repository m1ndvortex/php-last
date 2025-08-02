<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'description',
        'description_persian',
        'metadata',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->description_persian ? $this->description_persian : $this->description;
    }

    public function getAmountAttribute(): float
    {
        return $this->debit_amount > 0 ? $this->debit_amount : $this->credit_amount;
    }

    public function isDebit(): bool
    {
        return $this->debit_amount > 0;
    }

    public function isCredit(): bool
    {
        return $this->credit_amount > 0;
    }
}