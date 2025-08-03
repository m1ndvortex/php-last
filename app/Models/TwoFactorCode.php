<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'type',
        'expires_at',
        'used',
        'used_at',
        'ip_address'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Get the user that owns the code
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if code is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if code is valid
     */
    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }

    /**
     * Mark code as used
     */
    public function markAsUsed(string $ipAddress = null): void
    {
        $this->update([
            'used' => true,
            'used_at' => now(),
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Scope for valid codes
     */
    public function scopeValid($query)
    {
        return $query->where('used', false)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired codes
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Generate a random 6-digit code
     */
    public static function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}