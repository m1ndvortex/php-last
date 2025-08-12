<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseCache
{
    /**
     * Cache duration for different endpoint types (in seconds)
     */
    private const CACHE_DURATIONS = [
        'dashboard' => 300,      // 5 minutes
        'inventory' => 600,      // 10 minutes
        'customers' => 900,      // 15 minutes
        'categories' => 1800,    // 30 minutes
        'locations' => 1800,     // 30 minutes
        'reports' => 1200,       // 20 minutes
        'accounting' => 600,     // 10 minutes
        'default' => 300         // 5 minutes
    ];

    /**
     * Endpoints that should be cached
     */
    private const CACHEABLE_ENDPOINTS = [
        'GET:/api/dashboard/kpis',
        'GET:/api/dashboard/sales-chart',
        'GET:/api/dashboard/category-performance',
        'GET:/api/dashboard/gold-purity-performance',
        'GET:/api/dashboard/category-stock-alerts',
        'GET:/api/inventory/categories',
        'GET:/api/inventory/locations',
        'GET:/api/inventory/gold-purity-options',
        'GET:/api/inventory/summary/location',
        'GET:/api/inventory/summary/category',
        'GET:/api/categories/hierarchy',
        'GET:/api/categories/for-select',
        'GET:/api/categories/main-categories',
        'GET:/api/accounting/chart-of-accounts',
        'GET:/api/config/business-info',
        'GET:/api/config/all',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $endpoint = $request->method() . ':' . $request->getPathInfo();
        
        // Check if endpoint should be cached
        if (!$this->shouldCache($endpoint)) {
            return $next($request);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($request);
        
        // Try to get cached response
        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse) {
            Log::debug('API Cache Hit', [
                'endpoint' => $endpoint,
                'cache_key' => $cacheKey,
                'user_id' => auth()->id()
            ]);
            
            return response()->json($cachedResponse['data'], $cachedResponse['status'])
                ->withHeaders($cachedResponse['headers'] ?? [])
                ->header('X-Cache-Status', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        // Process request
        $response = $next($request);

        // Cache successful JSON responses
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            $cacheDuration = $this->getCacheDuration($endpoint);
            
            $cacheData = [
                'data' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
                'headers' => $this->getCacheableHeaders($response)
            ];
            
            Cache::put($cacheKey, $cacheData, $cacheDuration);
            
            Log::debug('API Cache Miss - Cached', [
                'endpoint' => $endpoint,
                'cache_key' => $cacheKey,
                'duration' => $cacheDuration,
                'user_id' => auth()->id()
            ]);
            
            $response->header('X-Cache-Status', 'MISS')
                    ->header('X-Cache-Key', $cacheKey)
                    ->header('X-Cache-Duration', $cacheDuration);
        }

        return $response;
    }

    /**
     * Check if endpoint should be cached
     */
    private function shouldCache(string $endpoint): bool
    {
        // Check exact matches first
        if (in_array($endpoint, self::CACHEABLE_ENDPOINTS)) {
            return true;
        }

        // Check pattern matches
        foreach (self::CACHEABLE_ENDPOINTS as $pattern) {
            if ($this->matchesPattern($endpoint, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if endpoint matches a pattern
     */
    private function matchesPattern(string $endpoint, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
        return preg_match("/^{$regex}$/", $endpoint);
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey(Request $request): string
    {
        $parts = [
            'api_cache',
            $request->method(),
            str_replace('/', '_', trim($request->getPathInfo(), '/')),
            auth()->id() ?? 'guest',
            md5(serialize($request->query->all()))
        ];

        return implode(':', $parts);
    }

    /**
     * Get cache duration for endpoint
     */
    private function getCacheDuration(string $endpoint): int
    {
        foreach (self::CACHE_DURATIONS as $type => $duration) {
            if ($type === 'default') {
                continue;
            }
            
            if (strpos($endpoint, $type) !== false) {
                return $duration;
            }
        }

        return self::CACHE_DURATIONS['default'];
    }

    /**
     * Get cacheable headers from response
     */
    private function getCacheableHeaders(Response $response): array
    {
        $cacheableHeaders = ['Content-Type', 'Content-Language'];
        $headers = [];

        foreach ($cacheableHeaders as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }

        return $headers;
    }

    /**
     * Clear cache for specific patterns
     */
    public static function clearCache(string $pattern = null): void
    {
        if ($pattern) {
            // Clear specific pattern cache
            $keys = Cache::getRedis()->keys("api_cache:*{$pattern}*");
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            // Clear all API cache
            $keys = Cache::getRedis()->keys('api_cache:*');
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }
}