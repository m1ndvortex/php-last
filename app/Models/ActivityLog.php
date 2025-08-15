<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'user_name',
        'user_id',
        'status',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for recent activities
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for specific activity type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Log an activity
     */
    public static function logActivity(
        string $type,
        string $description,
        ?int $userId = null,
        ?string $userName = null,
        string $status = 'completed',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'type' => $type,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
            'user_name' => $userName ?? auth()->user()?->name ?? 'System',
            'status' => $status,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => $metadata,
        ]);
    }
}