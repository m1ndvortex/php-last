<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'description',
        'description_persian',
        'transaction_date',
        'type',
        'source_type',
        'source_id',
        'total_amount',
        'currency',
        'exchange_rate',
        'is_locked',
        'is_recurring',
        'recurring_template_id',
        'cost_center_id',
        'tags',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'is_locked' => 'boolean',
        'is_recurring' => 'boolean',
        'tags' => 'array',
        'approved_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class, 'recurring_template_id');
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->description_persian ? $this->description_persian : $this->description;
    }

    public function lock(): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $this->update(['is_locked' => true]);
        
        // Update account balances
        $this->entries->each(function ($entry) {
            $entry->account->updateBalance();
        });

        return true;
    }

    public function unlock(): bool
    {
        if (!$this->is_locked) {
            return false;
        }

        $this->update(['is_locked' => false]);
        
        // Update account balances
        $this->entries->each(function ($entry) {
            $entry->account->updateBalance();
        });

        return true;
    }

    public function isBalanced(): bool
    {
        $totalDebits = $this->entries->sum('debit_amount');
        $totalCredits = $this->entries->sum('credit_amount');
        
        return bccomp($totalDebits, $totalCredits, 2) === 0;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->reference_number)) {
                $transaction->reference_number = static::generateReferenceNumber();
            }
        });
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'TXN';
        $date = now()->format('Ymd');
        $sequence = static::whereDate('created_at', now())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}