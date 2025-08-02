<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_persian',
        'type',
        'subtype',
        'parent_id',
        'currency',
        'opening_balance',
        'current_balance',
        'is_active',
        'is_system',
        'description',
        'metadata',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'metadata' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function transactionEntries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->name_persian ? $this->name_persian : $this->name;
    }

    public function updateBalance(): void
    {
        $entries = $this->transactionEntries()
            ->whereHas('transaction', function ($query) {
                $query->where('is_locked', false);
            })
            ->get();

        $totalDebits = $entries->sum('debit_amount');
        $totalCredits = $entries->sum('credit_amount');

        // Calculate balance based on account type
        $balance = match ($this->type) {
            'asset', 'expense' => $this->opening_balance + $totalDebits - $totalCredits,
            'liability', 'equity', 'revenue' => $this->opening_balance + $totalCredits - $totalDebits,
            default => $this->opening_balance
        };

        $this->update(['current_balance' => $balance]);
    }

    public function isDebitAccount(): bool
    {
        return in_array($this->type, ['asset', 'expense']);
    }

    public function isCreditAccount(): bool
    {
        return in_array($this->type, ['liability', 'equity', 'revenue']);
    }
}