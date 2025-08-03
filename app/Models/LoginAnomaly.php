<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAnomaly extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'ip_address',
        'user_agent',
        'location',
        'detection_data',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
        'is_false_positive'
    ];

    protected $casts = [
        'detection_data' => 'array',
        'is_resolved' => 'boolean',
        'is_false_positive' => 'boolean',
        'resolved_at' => 'datetime'
    ];

    /**
     * Get the user that owns the anomaly
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark anomaly as resolved
     */
    public function resolve(string $notes = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    /**
     * Mark as false positive
     */
    public function markAsFalsePositive(string $notes = null): void
    {
        $this->update([
            'is_false_positive' => true,
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    /**
     * Scope for unresolved anomalies
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope for resolved anomalies
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope for false positives
     */
    public function scopeFalsePositives($query)
    {
        return $query->where('is_false_positive', true);
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for user anomalies
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get human readable type
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'suspicious_ip' => 'Suspicious IP Address',
            'new_device' => 'New Device/Browser',
            'rapid_attempts' => 'Rapid Login Attempts',
            'geo_anomaly' => 'Geographic Anomaly',
            'time_anomaly' => 'Unusual Login Time',
            'brute_force' => 'Brute Force Attack',
            'credential_stuffing' => 'Credential Stuffing',
            default => ucfirst(str_replace('_', ' ', $this->type))
        };
    }

    /**
     * Get risk score based on type and severity
     */
    public function getRiskScoreAttribute(): int
    {
        $severityScores = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4
        ];

        $typeMultipliers = [
            'suspicious_ip' => 1.2,
            'new_device' => 1.0,
            'rapid_attempts' => 1.5,
            'geo_anomaly' => 1.3,
            'time_anomaly' => 0.8,
            'brute_force' => 2.0,
            'credential_stuffing' => 1.8,
        ];

        $baseScore = $severityScores[$this->severity] ?? 1;
        $multiplier = $typeMultipliers[$this->type] ?? 1.0;

        return (int) round($baseScore * $multiplier * 10);
    }
}