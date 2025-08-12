<?php

namespace App\Services;

use App\Http\Middleware\ApiResponseCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiCacheService
{
    /**
     * Cache invalidation patterns for different data types
     */
    private const INVALIDATION_PATTERNS = [
        'dashboard' => [
            'api_cache:GET:dashboard*',
            'api_cache:GET:inventory*summary*',
            'dashboard_kpis*',
            'sales_chart_data*',
            'category_performance*',
            'gold_purity_performance*'
        ],
        'customers' => [
            'api_cache:GET:customers*',
            'api_cache:GET:dashboard*',
            'dashboard_kpis*'
        ],
        'inventory' => [
            'api_cache:GET:inventory*',
            'api_cache:GET:dashboard*',
            'api_cache:GET:categories*',
            'dashboard_kpis*',
            'inventory_statistics*',
            'category_performance*'
        ],
        'invoices' => [
            'api_cache:GET:dashboard*',
            'api_cache:GET:customers*',
            'dashboard_kpis*',
            'sales_chart_data*'
        ],
        'categories' => [
            'api_cache:GET:categories*',
            'api_cache:GET:inventory*',
            'api_cache:GET:dashboard*',
            'category_hierarchy*',
            'category_performance*'
        ],
        'accounts' => [
            'api_cache:GET:accounting*',
            'api_cache:GET:dashboard*'
        ],
        'locations' => [
            'api_cache:GET:inventory*',
            'api_cache:GET:locations*'
        ]
    ];

    /**
     * Clear cache for specific data type
     */
    public function clearCacheForDataType(string $dataType): void
    {
        if (!isset(self::INVALIDATION_PATTERNS[$dataType])) {
            Log::warning("Unknown data type for cache invalidation: {$dataType}");
            return;
        }

        $patterns = self::INVALIDATION_PATTERNS[$dataType];
        $clearedKeys = 0;
        $cacheDriver = config('cache.default');

        foreach ($patterns as $pattern) {
            if ($cacheDriver === 'redis') {
                $keys = $this->findCacheKeys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                    $clearedKeys += count($keys);
                }
            } else {
                // For non-Redis drivers, we'll use Cache::forget for known keys
                // This is less efficient but works with all cache drivers
                $this->clearCacheByPattern($pattern);
                $clearedKeys++;
            }
        }

        Log::info("Cache cleared for data type: {$dataType}", [
            'patterns' => $patterns,
            'keys_cleared' => $clearedKeys,
            'cache_driver' => $cacheDriver
        ]);
    }

    /**
     * Clear cache for multiple data types
     */
    public function clearCacheForDataTypes(array $dataTypes): void
    {
        foreach ($dataTypes as $dataType) {
            $this->clearCacheForDataType($dataType);
        }
    }

    /**
     * Clear all API cache
     */
    public function clearAllApiCache(): void
    {
        $cacheDriver = config('cache.default');
        $clearedKeys = 0;

        if ($cacheDriver === 'redis') {
            $keys = $this->findCacheKeys('api_cache:*');
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
                $clearedKeys += count($keys);
            }

            // Also clear service-level caches
            $serviceCacheKeys = $this->findCacheKeys('dashboard_kpis*');
            $serviceCacheKeys = array_merge($serviceCacheKeys, $this->findCacheKeys('sales_chart_data*'));
            $serviceCacheKeys = array_merge($serviceCacheKeys, $this->findCacheKeys('category_performance*'));
            $serviceCacheKeys = array_merge($serviceCacheKeys, $this->findCacheKeys('gold_purity_performance*'));
            $serviceCacheKeys = array_merge($serviceCacheKeys, $this->findCacheKeys('inventory_statistics*'));
            $serviceCacheKeys = array_merge($serviceCacheKeys, $this->findCacheKeys('category_hierarchy*'));

            if (!empty($serviceCacheKeys)) {
                Cache::getRedis()->del($serviceCacheKeys);
                $clearedKeys += count($serviceCacheKeys);
            }
        } else {
            // For non-Redis drivers, flush all cache
            Cache::flush();
            $clearedKeys = 1; // We can't count exact keys, so use 1 as indicator
        }

        Log::info('All API cache cleared', [
            'keys_cleared' => $clearedKeys,
            'cache_driver' => $cacheDriver
        ]);
    }

    /**
     * Clear cache when specific models are updated
     */
    public function clearCacheForModel(string $modelClass, string $action = 'updated'): void
    {
        $dataTypeMap = [
            'App\Models\Customer' => 'customers',
            'App\Models\InventoryItem' => 'inventory',
            'App\Models\Invoice' => 'invoices',
            'App\Models\Category' => 'categories',
            'App\Models\Account' => 'accounts',
            'App\Models\Location' => 'locations',
            'App\Models\Transaction' => 'accounts'
        ];

        if (isset($dataTypeMap[$modelClass])) {
            $this->clearCacheForDataType($dataTypeMap[$modelClass]);
            
            // Always clear dashboard cache when any core data changes
            if ($action !== 'read') {
                $this->clearCacheForDataType('dashboard');
            }
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        $apiCacheKeys = $this->findCacheKeys('api_cache:*');
        $serviceCacheKeys = $this->findCacheKeys('dashboard_kpis*');
        $serviceCacheKeys = array_merge($serviceCacheKeys, $this->findCacheKeys('sales_chart_data*'));
        $serviceCacheKeys = array_merge($serviceCacheKeys, $this->findCacheKeys('category_performance*'));

        return [
            'api_cache_keys' => count($apiCacheKeys),
            'service_cache_keys' => count($serviceCacheKeys),
            'total_cache_keys' => count($apiCacheKeys) + count($serviceCacheKeys),
            'cache_patterns' => array_keys(self::INVALIDATION_PATTERNS)
        ];
    }

    /**
     * Find cache keys matching pattern
     */
    private function findCacheKeys(string $pattern): array
    {
        try {
            $cacheDriver = config('cache.default');
            
            if ($cacheDriver === 'redis') {
                return Cache::getRedis()->keys($pattern) ?: [];
            }
            
            // For non-Redis drivers, we can't easily list keys
            // So we'll return an empty array and log this limitation
            Log::debug("Cache key listing not supported for driver: {$cacheDriver}");
            return [];
        } catch (\Exception $e) {
            Log::error("Failed to find cache keys for pattern: {$pattern}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Clear cache by pattern for non-Redis drivers
     */
    private function clearCacheByPattern(string $pattern): void
    {
        // For non-Redis drivers, we'll clear known cache keys based on patterns
        $knownKeys = [
            'dashboard_kpis*' => ['dashboard_kpis_', 'dashboard_kpis_null'],
            'sales_chart_data*' => ['sales_chart_data_week', 'sales_chart_data_month', 'sales_chart_data_year'],
            'category_performance*' => ['category_performance'],
            'gold_purity_performance*' => ['gold_purity_performance'],
            'category_hierarchy*' => ['category_hierarchy'],
            'inventory_statistics*' => ['inventory_statistics']
        ];

        if (isset($knownKeys[$pattern])) {
            foreach ($knownKeys[$pattern] as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Warm up frequently accessed cache
     */
    public function warmUpCache(): void
    {
        try {
            // Warm up dashboard KPIs
            app(\App\Services\DashboardService::class)->getKPIs();
            
            // Warm up category hierarchy
            app(\App\Services\QueryOptimizationService::class)->getCategoryHierarchy();
            
            // Warm up inventory statistics
            app(\App\Services\QueryOptimizationService::class)->getInventoryStatistics();

            Log::info('Cache warmed up successfully');
        } catch (\Exception $e) {
            Log::error('Failed to warm up cache', [
                'error' => $e->getMessage()
            ]);
        }
    }
}