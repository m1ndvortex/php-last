<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_number',
        'location_id',
        'status',
        'audit_date',
        'auditor_id',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the location being audited.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the auditor (user) who performed the audit.
     */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    /**
     * Get all audit items for this audit.
     */
    public function auditItems(): HasMany
    {
        return $this->hasMany(StockAuditItem::class);
    }

    /**
     * Get the total variance value for this audit.
     */
    public function getTotalVarianceValueAttribute(): float
    {
        return (float) $this->auditItems()->sum('variance_value');
    }

    /**
     * Get the total number of items with variances.
     */
    public function getItemsWithVarianceCountAttribute(): int
    {
        return $this->auditItems()->where('variance', '!=', 0)->count();
    }

    /**
     * Get the completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        $totalItems = $this->auditItems()->count();
        if ($totalItems === 0) {
            return 0;
        }

        $countedItems = $this->auditItems()->where('is_counted', true)->count();
        return ($countedItems / $totalItems) * 100;
    }

    /**
     * Check if the audit is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the audit is in progress.
     */
    public function getIsInProgressAttribute(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by auditor.
     */
    public function scopeByAuditor($query, int $auditorId)
    {
        return $query->where('auditor_id', $auditorId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('audit_date', [$startDate, $endDate]);
    }
}
