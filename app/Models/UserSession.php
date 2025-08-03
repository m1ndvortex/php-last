<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location',
        'last_activity',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user that owns the session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Update last activity
     */
    public function updateActivity(): void
    {
        $this->update([
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120))
        ]);
    }

    /**
     * Terminate session
     */
    public function terminate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired sessions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope for user sessions
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get session duration in minutes
     */
    public function getDurationAttribute(): int
    {
        return $this->last_activity->diffInMinutes($this->created_at);
    }

    /**
     * Get formatted location
     */
    public function getFormattedLocationAttribute(): string
    {
        return $this->location ?? 'Unknown Location';
    }

    /**
     * Get device info
     */
    public function getDeviceInfoAttribute(): string
    {
        $parts = array_filter([
            $this->browser,
            $this->platform,
            $this->device_type
        ]);

        return implode(' - ', $parts) ?: 'Unknown Device';
    }
}