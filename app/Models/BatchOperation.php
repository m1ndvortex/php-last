<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'progress',
        'processed_count',
        'total_count',
        'metadata',
        'summary',
        'error_message',
        'combined_file_path',
        'created_by',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'summary' => 'array',
        'progress' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the batch operation items
     */
    public function items(): HasMany
    {
        return $this->hasMany(BatchOperationItem::class);
    }

    /**
     * Get the user who created this batch operation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get successful items
     */
    public function successfulItems(): HasMany
    {
        return $this->items()->where('status', 'completed');
    }

    /**
     * Get failed items
     */
    public function failedItems(): HasMany
    {
        return $this->items()->where('status', 'failed');
    }

    /**
     * Check if batch operation is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'completed_with_errors', 'failed']);
    }

    /**
     * Check if batch operation is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Get success rate
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_count === 0) {
            return 0;
        }

        $successfulCount = $this->successfulItems()->count();
        return round(($successfulCount / $this->total_count) * 100, 2);
    }

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get human readable duration
     */
    public function getHumanDurationAttribute(): ?string
    {
        $duration = $this->duration;
        
        if ($duration === null) {
            return null;
        }

        if ($duration < 60) {
            return "{$duration} seconds";
        } elseif ($duration < 3600) {
            $minutes = floor($duration / 60);
            $seconds = $duration % 60;
            return "{$minutes}m {$seconds}s";
        } else {
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
    }

    /**
     * Scope for filtering by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for recent operations
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for completed operations
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'completed_with_errors']);
    }

    /**
     * Scope for failed operations
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}