<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    /**
     * Default relationships to eager load
     */
    protected $with = ['customer'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'template_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'payment_method',
        'language',
        'notes',
        'internal_notes',
        'pdf_path',
        'sent_at',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Gold pricing fields
        'gold_price_per_gram' => 'decimal:2',
        'labor_percentage' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
    ];

    /**
     * Get the customer that owns the invoice.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class)->select(['id', 'name', 'email', 'phone', 'preferred_language']);
    }

    /**
     * Get the invoice items with optimized loading.
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->with(['inventoryItem:id,name,name_persian,sku,gold_purity,weight']);
    }

    /**
     * Get the invoice template.
     */
    public function template()
    {
        return $this->belongsTo(InvoiceTemplate::class)->select(['id', 'name', 'template_data']);
    }

    /**
     * Get the invoice tags.
     */
    public function tags()
    {
        return $this->hasMany(InvoiceTag::class);
    }

    /**
     * Get the invoice attachments.
     */
    public function attachments()
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    /**
     * Scope for filtering by status with index optimization.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by date range with index optimization.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by language with index optimization.
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope for recent invoices (optimized with index).
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('issue_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['paid', 'cancelled']);
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Generate next invoice number with proper sequencing.
     */
    public static function generateInvoiceNumber()
    {
        // Get current year and month for better organization
        $year = now()->format('Y');
        $month = now()->format('m');
        
        // Get the last invoice number for this year/month
        $lastInvoice = static::where('invoice_number', 'like', "INV-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract the sequence number and increment
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return "INV-{$year}{$month}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date < now() && !in_array($this->status, ['paid', 'cancelled']);
    }

    /**
     * Get formatted total amount.
     */
    public function getFormattedTotalAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'fa') {
            $formatted = number_format($this->total_amount, 0, '.', ',');
            $persianNumerals = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            return str_replace($englishNumerals, $persianNumerals, $formatted) . ' ریال';
        }
        return '$' . number_format($this->total_amount, 2);
    }
}