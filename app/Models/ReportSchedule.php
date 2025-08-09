<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'subtype',
        'parameters',
        'schedule',
        'delivery',
        'is_active',
        'next_run_at',
        'last_run_at'
    ];

    protected $casts = [
        'parameters' => 'array',
        'schedule' => 'array',
        'delivery' => 'array',
        'is_active' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime'
    ];

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for due schedules
     */
    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now());
    }

    /**
     * Get the frequency display name
     */
    public function getFrequencyDisplayAttribute(): string
    {
        $frequencies = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly'
        ];

        return $frequencies[$this->schedule['frequency']] ?? 'Unknown';
    }

    /**
     * Get the delivery method display name
     */
    public function getDeliveryMethodDisplayAttribute(): string
    {
        $methods = [
            'email' => 'Email',
            'download' => 'Download'
        ];

        return $methods[$this->delivery['method']] ?? 'Unknown';
    }
}