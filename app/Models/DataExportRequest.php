<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DataExportRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'format',
        'data_types',
        'filters',
        'status',
        'file_path',
        'file_size',
        'expires_at',
        'error_message',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'data_types' => 'array',
        'filters' => 'array',
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the user that owns the export request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if export file exists and is not expired
     */
    public function isFileAvailable(): bool
    {
        if (!$this->file_path || $this->status !== 'completed') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return Storage::exists($this->file_path);
    }

    /**
     * Get download URL for the export file
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->isFileAvailable()) {
            return null;
        }

        return Storage::temporaryUrl($this->file_path, now()->addHours(1));
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
    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'completed_at' => now(),
            'expires_at' => now()->addDays(7) // File expires in 7 days
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
     * Clean up expired files
     */
    public static function cleanupExpiredFiles(): int
    {
        $expiredRequests = static::where('expires_at', '<', now())
            ->where('status', 'completed')
            ->whereNotNull('file_path')
            ->get();

        $deletedCount = 0;

        foreach ($expiredRequests as $request) {
            if (Storage::exists($request->file_path)) {
                Storage::delete($request->file_path);
                $deletedCount++;
            }

            $request->update([
                'file_path' => null,
                'file_size' => null
            ]);
        }

        return $deletedCount;
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
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
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
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