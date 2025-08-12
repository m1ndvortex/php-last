<?php

namespace App\Observers;

use App\Services\ApiCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CacheInvalidationObserver
{
    protected ApiCacheService $cacheService;

    public function __construct(ApiCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->invalidateCache($model, 'created');
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->invalidateCache($model, 'updated');
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateCache($model, 'deleted');
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->invalidateCache($model, 'restored');
    }

    /**
     * Invalidate cache for the given model
     */
    protected function invalidateCache(Model $model, string $action): void
    {
        try {
            $modelClass = get_class($model);
            $this->cacheService->clearCacheForModel($modelClass, $action);
            
            Log::debug('Cache invalidated for model', [
                'model' => $modelClass,
                'action' => $action,
                'model_id' => $model->getKey()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache for model', [
                'model' => get_class($model),
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }
}