<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataDeletionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'data_types',
        'filters',
        'status',
        'reason',
        'backup_info',
        'approved_by',
        'approved_at',
        'approval_notes',
        'scheduled_for',
        'started_at',
        'completed_at',
        'deletion_summary',
        'error_message'
    ];

    protected $casts = [
        'data_types' => 'array',
        'filters' => 'array',
        'backup_info' => 'array',
        'deletion_summary' => 'array',
        'approved_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the user that owns the deletion request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved the request
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the deletion request
     */
    public function approve(User $approver, ?string $notes = null, ?\DateTime $scheduledFor = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'scheduled_for' => $scheduledFor ?? now()->addDays(1) // Default to 24 hours delay
        ]);
    }

    /**
     * Reject the deletion request
     */
    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $reason
        ]);
    }

    /**
     * Mark as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now()
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(array $deletionSummary): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'deletion_summary' => $deletionSummary
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }

    /**
     * Check if request is ready for processing
     */
    public function isReadyForProcessing(): bool
    {
        return $this->status === 'approved' && 
               $this->scheduled_for && 
               $this->scheduled_for->isPast();
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for processing requests
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for ready to process
     */
    public function scopeReadyToProcess($query)
    {
        return $query->where('status', 'approved')
                    ->where('scheduled_for', '<=', now());
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'processing' => 'purple',
            'completed' => 'green',
            'rejected' => 'red',
            'failed' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get human readable status
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'failed' => 'Failed',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get processing duration
     */
    public function getProcessingDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }
}