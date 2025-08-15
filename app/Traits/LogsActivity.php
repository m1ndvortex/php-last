<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            $model->logModelActivity('created');
        });

        static::updated(function (Model $model) {
            $model->logModelActivity('updated');
        });

        static::deleted(function (Model $model) {
            $model->logModelActivity('deleted');
        });
    }

    /**
     * Log activity for this model
     */
    public function logModelActivity(string $action, ?array $metadata = null): ActivityLog
    {
        $modelName = class_basename($this);
        $modelId = $this->getKey();
        
        // Get display name for the model
        $displayName = $this->getActivityDisplayName();
        
        $type = strtolower($modelName) . '_' . $action;
        $description = $this->getActivityDescription($action, $displayName);

        return ActivityLog::logActivity(
            $type,
            $description,
            auth()->id(),
            auth()->user()?->name,
            'completed',
            strtolower($modelName),
            $modelId,
            $metadata
        );
    }

    /**
     * Get display name for activity logging
     */
    protected function getActivityDisplayName(): string
    {
        // Try common name fields
        if (isset($this->name)) {
            return $this->name;
        }
        
        if (isset($this->title)) {
            return $this->title;
        }
        
        if (isset($this->invoice_number)) {
            return "#{$this->invoice_number}";
        }
        
        if (isset($this->email)) {
            return $this->email;
        }
        
        // Fallback to model name with ID
        return class_basename($this) . " #{$this->getKey()}";
    }

    /**
     * Get activity description
     */
    protected function getActivityDescription(string $action, string $displayName): string
    {
        $modelName = class_basename($this);
        
        return match ($action) {
            'created' => "{$modelName} '{$displayName}' was created",
            'updated' => "{$modelName} '{$displayName}' was updated",
            'deleted' => "{$modelName} '{$displayName}' was deleted",
            default => "{$modelName} '{$displayName}' was {$action}",
        };
    }

    /**
     * Log custom activity for this model
     */
    public function logCustomActivity(
        string $type,
        string $description,
        string $status = 'completed',
        ?array $metadata = null
    ): ActivityLog {
        return ActivityLog::logActivity(
            $type,
            $description,
            auth()->id(),
            auth()->user()?->name,
            $status,
            strtolower(class_basename($this)),
            $this->getKey(),
            $metadata
        );
    }
}