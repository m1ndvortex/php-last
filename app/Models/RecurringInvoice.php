<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RecurringInvoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'template_id',
        'name',
        'description',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'next_invoice_date',
        'max_invoices',
        'invoices_generated',
        'amount',
        'language',
        'is_active',
        'invoice_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_invoice_date' => 'date',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'invoice_data' => 'array',
    ];

    /**
     * Get the customer that owns the recurring invoice.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the invoice template.
     */
    public function template()
    {
        return $this->belongsTo(InvoiceTemplate::class);
    }

    /**
     * Scope for active recurring invoices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for due recurring invoices.
     */
    public function scopeDue($query)
    {
        return $query->where('next_invoice_date', '<=', now()->toDateString())
            ->where('is_active', true);
    }

    /**
     * Calculate next invoice date based on frequency and interval.
     */
    public function calculateNextInvoiceDate()
    {
        $nextDate = Carbon::parse($this->next_invoice_date);

        switch ($this->frequency) {
            case 'daily':
                $nextDate->addDays($this->interval);
                break;
            case 'weekly':
                $nextDate->addWeeks($this->interval);
                break;
            case 'monthly':
                $nextDate->addMonths($this->interval);
                break;
            case 'quarterly':
                $nextDate->addMonths($this->interval * 3);
                break;
            case 'yearly':
                $nextDate->addYears($this->interval);
                break;
        }

        return $nextDate->toDateString();
    }

    /**
     * Check if recurring invoice should be deactivated.
     */
    public function shouldDeactivate()
    {
        if ($this->end_date && now()->toDateString() > $this->end_date) {
            return true;
        }

        if ($this->max_invoices && $this->invoices_generated >= $this->max_invoices) {
            return true;
        }

        return false;
    }
}
