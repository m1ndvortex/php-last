<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'preferred_language',
        'customer_type',
        'credit_limit',
        'payment_terms',
        'notes',
        'birthday',
        'anniversary',
        'preferred_communication_method',
        'is_active',
        'crm_stage',
        'lead_source',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms' => 'integer',
        'birthday' => 'date',
        'anniversary' => 'date',
        'is_active' => 'boolean',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes with default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'preferred_language' => 'en',
        'customer_type' => 'retail',
        'credit_limit' => 0.00,
        'payment_terms' => 30,
        'is_active' => true,
        'crm_stage' => 'lead',
    ];

    /**
     * Customer types
     */
    const CUSTOMER_TYPES = [
        'retail' => 'Retail Customer',
        'wholesale' => 'Wholesale Customer',
        'vip' => 'VIP Customer',
    ];

    /**
     * CRM stages
     */
    const CRM_STAGES = [
        'lead' => 'Lead',
        'prospect' => 'Prospect',
        'customer' => 'Customer',
        'inactive' => 'Inactive',
    ];

    /**
     * Lead sources
     */
    const LEAD_SOURCES = [
        'referral' => 'Referral',
        'website' => 'Website',
        'social_media' => 'Social Media',
        'walk_in' => 'Walk-in',
        'advertisement' => 'Advertisement',
        'other' => 'Other',
    ];

    /**
     * Get the invoices for the customer with optimized loading.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class)->select([
            'id', 'customer_id', 'invoice_number', 'issue_date', 'due_date', 
            'total_amount', 'status', 'language'
        ]);
    }

    /**
     * Get recent invoices for the customer.
     */
    public function recentInvoices()
    {
        return $this->hasMany(Invoice::class)
                    ->select(['id', 'customer_id', 'invoice_number', 'issue_date', 'total_amount', 'status'])
                    ->latest('issue_date')
                    ->limit(10);
    }

    /**
     * Get the communications for the customer with optimized loading.
     */
    public function communications()
    {
        return $this->hasMany(Communication::class)->select([
            'id', 'customer_id', 'type', 'subject', 'sent_at', 'status'
        ]);
    }

    /**
     * Get the customer's full address formatted for display.
     *
     * @return string
     */
    public function getFormattedAddressAttribute(): string
    {
        return $this->address ?? '';
    }

    /**
     * Get the customer's display name with type.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        $type = self::CUSTOMER_TYPES[$this->customer_type] ?? '';
        return $this->name . ($type ? " ({$type})" : '');
    }

    /**
     * Get the customer's age based on birthday.
     *
     * @return int|null
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birthday ? $this->birthday->age : null;
    }

    /**
     * Check if customer has upcoming birthday (within 30 days).
     *
     * @return bool
     */
    public function hasUpcomingBirthday(): bool
    {
        if (!$this->birthday) {
            return false;
        }

        $nextBirthday = $this->birthday->setYear(now()->year);
        if ($nextBirthday->isPast()) {
            $nextBirthday = $nextBirthday->addYear();
        }

        return $nextBirthday->diffInDays(now()) <= 30;
    }

    /**
     * Check if customer has upcoming anniversary (within 30 days).
     *
     * @return bool
     */
    public function hasUpcomingAnniversary(): bool
    {
        if (!$this->anniversary) {
            return false;
        }

        $nextAnniversary = $this->anniversary->setYear(now()->year);
        if ($nextAnniversary->isPast()) {
            $nextAnniversary = $nextAnniversary->addYear();
        }

        return $nextAnniversary->diffInDays(now()) <= 30;
    }

    /**
     * Get customer's total invoice amount.
     *
     * @return float
     */
    public function getTotalInvoiceAmount(): float
    {
        try {
            return $this->invoices()->sum('total_amount') ?? 0.00;
        } catch (\Exception $e) {
            // Return 0 if invoices table doesn't exist yet
            return 0.00;
        }
    }

    /**
     * Get customer's outstanding balance.
     *
     * @return float
     */
    public function getOutstandingBalance(): float
    {
        try {
            return $this->invoices()
                ->whereIn('status', ['pending', 'overdue'])
                ->sum('total_amount') ?? 0.00;
        } catch (\Exception $e) {
            // Return 0 if invoices table doesn't exist yet
            return 0.00;
        }
    }

    /**
     * Get customer's last invoice date.
     *
     * @return Carbon|null
     */
    public function getLastInvoiceDate(): ?Carbon
    {
        try {
            $lastInvoice = $this->invoices()->latest('issue_date')->first();
            return $lastInvoice ? $lastInvoice->issue_date : null;
        } catch (\Exception $e) {
            // Return null if invoices table doesn't exist yet
            return null;
        }
    }

    /**
     * Scope a query to only include active customers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by customer type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('customer_type', $type);
    }

    /**
     * Scope a query to filter by CRM stage.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $stage
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStage($query, string $stage)
    {
        return $query->where('crm_stage', $stage);
    }

    /**
     * Scope a query to search customers by name, email, or phone.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to include customers with upcoming birthdays.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithUpcomingBirthdays($query)
    {
        $today = now();
        $thirtyDaysFromNow = now()->addDays(30);
        $currentYear = $today->year;
        
        return $query->whereNotNull('birthday')
            ->where(function ($q) use ($today, $thirtyDaysFromNow, $currentYear) {
                // For SQLite compatibility, we'll use a different approach
                $q->whereRaw("strftime('%m-%d', birthday) BETWEEN ? AND ?", [
                    $today->format('m-d'),
                    $thirtyDaysFromNow->format('m-d')
                ])
                ->orWhere(function ($subQ) use ($today, $thirtyDaysFromNow, $currentYear) {
                    // Handle year boundary (December to January)
                    if ($today->month == 12 && $thirtyDaysFromNow->month == 1) {
                        $subQ->whereRaw("strftime('%m-%d', birthday) >= ?", [$today->format('m-d')])
                             ->orWhereRaw("strftime('%m-%d', birthday) <= ?", [$thirtyDaysFromNow->format('m-d')]);
                    }
                });
            });
    }
}