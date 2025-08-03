<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * Log model activity
     */
    public function logModelActivity(
        Model $model,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        string $severity = 'info',
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $this->sanitizeValues($oldValues),
            'new_values' => $this->sanitizeValues($newValues),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'request_data' => $this->sanitizeRequestData(),
            'description' => $description ?? $this->generateDescription($model, $event),
            'severity' => $severity,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log authentication events
     */
    public function logAuthEvent(
        string $event,
        ?User $user = null,
        string $severity = 'info',
        ?array $metadata = null
    ): AuditLog {
        $descriptions = [
            'login' => 'User logged in successfully',
            'login_failed' => 'Failed login attempt',
            'logout' => 'User logged out',
            '2fa_enabled' => 'Two-factor authentication enabled',
            '2fa_disabled' => 'Two-factor authentication disabled',
            '2fa_failed' => 'Two-factor authentication failed',
            'password_changed' => 'Password changed',
            'password_reset' => 'Password reset requested',
        ];

        return AuditLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $user ? get_class($user) : 'Auth',
            'auditable_id' => $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'description' => $descriptions[$event] ?? "Authentication event: {$event}",
            'severity' => $severity,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log system events
     */
    public function logSystemEvent(
        string $event,
        string $description,
        string $severity = 'info',
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => null,
            'event' => $event,
            'auditable_type' => 'System',
            'auditable_id' => null,
            'description' => $description,
            'severity' => $severity,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(
        string $event,
        string $description,
        ?User $user = null,
        string $severity = 'warning',
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => 'Security',
            'auditable_id' => null,
            'description' => $description,
            'severity' => $severity,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }

    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): array
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['event'])) {
            $query->byEvent($filters['event']);
        }

        if (isset($filters['severity'])) {
            $query->bySeverity($filters['severity']);
        }

        if (isset($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (isset($filters['ip_address'])) {
            $query->byIpAddress($filters['ip_address']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', "%{$filters['search']}%")
                  ->orWhere('event', 'like', "%{$filters['search']}%");
            });
        }

        $logs = $query->paginate($perPage);

        return [
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ];
    }

    /**
     * Get audit statistics
     */
    public function getAuditStatistics(array $filters = []): array
    {
        $query = AuditLog::query();

        // Apply date filter if provided
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        } else {
            // Default to last 30 days
            $query->byDateRange(now()->subDays(30), now());
        }

        return [
            'total_events' => $query->count(),
            'events_by_severity' => $query->select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'events_by_type' => $query->select('event', DB::raw('count(*) as count'))
                ->groupBy('event')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'event')
                ->toArray(),
            'top_users' => $query->select('user_id', DB::raw('count(*) as count'))
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->with('user:id,name')
                ->get()
                ->map(function ($item) {
                    return [
                        'user_name' => $item->user->name ?? 'Unknown',
                        'count' => $item->count
                    ];
                })
                ->toArray(),
            'events_by_day' => $query->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }

    /**
     * Export audit logs
     */
    public function exportAuditLogs(array $filters = [], string $format = 'csv'): string
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Apply same filters as getAuditLogs
        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['event'])) {
            $query->byEvent($filters['event']);
        }

        if (isset($filters['severity'])) {
            $query->bySeverity($filters['severity']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        $logs = $query->get();

        if ($format === 'csv') {
            return $this->exportToCsv($logs);
        } elseif ($format === 'json') {
            return $this->exportToJson($logs);
        }

        throw new \InvalidArgumentException('Unsupported export format');
    }

    /**
     * Clean up old audit logs
     */
    public function cleanupOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get model audit trail
     */
    public function getModelAuditTrail(Model $model): array
    {
        return AuditLog::forModel(get_class($model), $model->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'user' => $log->user?->name ?? 'System',
                    'description' => $log->description,
                    'changes' => $log->changed_fields,
                    'created_at' => $log->created_at,
                    'severity' => $log->severity,
                ];
            })
            ->toArray();
    }

    /**
     * Sanitize values to remove sensitive data
     */
    private function sanitizeValues(?array $values): ?array
    {
        if (!$values) {
            return null;
        }

        $sensitiveFields = [
            'password',
            'password_confirmation',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'remember_token',
            'api_token',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }

    /**
     * Sanitize request data
     */
    private function sanitizeRequestData(): array
    {
        return request()->except([
            'password',
            'password_confirmation',
            '_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);
    }

    /**
     * Generate description for model events
     */
    private function generateDescription(Model $model, string $event): string
    {
        $modelName = class_basename($model);
        $modelId = $model->id ?? 'new';

        $descriptions = [
            'created' => "Created {$modelName} #{$modelId}",
            'updated' => "Updated {$modelName} #{$modelId}",
            'deleted' => "Deleted {$modelName} #{$modelId}",
            'viewed' => "Viewed {$modelName} #{$modelId}",
            'exported' => "Exported {$modelName} data",
        ];

        return $descriptions[$event] ?? "{$event} {$modelName} #{$modelId}";
    }

    /**
     * Export logs to CSV
     */
    private function exportToCsv($logs): string
    {
        $csv = "Date,User,Event,Model,Description,IP Address,Severity\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log->created_at ?? '',
                $log->user?->name ?? 'System',
                $log->event ?? '',
                $log->auditable_type ?? '',
                str_replace('"', '""', $log->description ?? ''),
                $log->ip_address ?? '',
                $log->severity ?? 'info'
            );
        }

        return $csv;
    }

    /**
     * Export logs to JSON
     */
    private function exportToJson($logs): string
    {
        return json_encode($logs->map(function ($log) {
            return [
                'date' => $log->created_at?->toISOString(),
                'user' => $log->user?->name ?? 'System',
                'event' => $log->event ?? '',
                'model' => $log->auditable_type ?? '',
                'description' => $log->description ?? '',
                'ip_address' => $log->ip_address ?? '',
                'severity' => $log->severity ?? 'info',
                'changes' => $log->changed_fields ?? [],
            ];
        }), JSON_PRETTY_PRINT);
    }
}