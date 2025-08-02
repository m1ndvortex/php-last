<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class RecurringTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_persian',
        'description',
        'description_persian',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'next_run_date',
        'max_occurrences',
        'occurrences_count',
        'is_active',
        'transaction_template',
        'tags',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'is_active' => 'boolean',
        'transaction_template' => 'array',
        'tags' => 'array',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'recurring_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_date', '<=', now()->toDateString())
            ->where('is_active', true);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->name_persian ? $this->name_persian : $this->name;
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'fa' && $this->description_persian ? $this->description_persian : $this->description;
    }

    public function shouldRun(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->next_run_date > now()->toDate()) {
            return false;
        }

        if ($this->end_date && $this->next_run_date > $this->end_date) {
            return false;
        }

        if ($this->max_occurrences && $this->occurrences_count >= $this->max_occurrences) {
            return false;
        }

        return true;
    }

    public function calculateNextRunDate(): Carbon
    {
        $nextDate = Carbon::parse($this->next_run_date);

        return match ($this->frequency) {
            'daily' => $nextDate->addDays($this->interval),
            'weekly' => $nextDate->addWeeks($this->interval),
            'monthly' => $nextDate->addMonths($this->interval),
            'quarterly' => $nextDate->addMonths($this->interval * 3),
            'yearly' => $nextDate->addYears($this->interval),
            default => $nextDate
        };
    }

    public function updateNextRunDate(): void
    {
        $this->update([
            'next_run_date' => $this->calculateNextRunDate(),
            'occurrences_count' => $this->occurrences_count + 1,
        ]);
    }

    public function createTransaction(): Transaction
    {
        $template = $this->transaction_template;
        
        $transaction = Transaction::create([
            'reference_number' => Transaction::generateReferenceNumber(),
            'description' => $template['description'],
            'description_persian' => $template['description_persian'] ?? null,
            'transaction_date' => $this->next_run_date,
            'type' => 'recurring',
            'total_amount' => $template['total_amount'],
            'currency' => $template['currency'] ?? 'USD',
            'exchange_rate' => $template['exchange_rate'] ?? 1,
            'is_recurring' => true,
            'recurring_template_id' => $this->id,
            'cost_center_id' => $template['cost_center_id'] ?? null,
            'tags' => $this->tags,
            'notes' => $template['notes'] ?? null,
            'created_by' => 1, // System user
        ]);

        // Create transaction entries
        foreach ($template['entries'] as $entryData) {
            $transaction->entries()->create([
                'account_id' => $entryData['account_id'],
                'debit_amount' => $entryData['debit_amount'] ?? 0,
                'credit_amount' => $entryData['credit_amount'] ?? 0,
                'description' => $entryData['description'] ?? null,
                'description_persian' => $entryData['description_persian'] ?? null,
            ]);
        }

        $this->updateNextRunDate();

        return $transaction;
    }
}