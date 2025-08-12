<?php

namespace App\Http\Controllers;

use App\Services\ApiCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiCacheController extends Controller
{
    protected ApiCacheService $cacheService;

    public function __construct(ApiCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get cache statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->cacheService->getCacheStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Cache statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cache statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cache statistics',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cache for specific data type
     */
    public function clearDataType(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'data_type' => 'required|in:dashboard,customers,inventory,invoices,categories,accounts,locations'
            ]);

            $this->cacheService->clearCacheForDataType($request->data_type);

            return response()->json([
                'success' => true,
                'message' => "Cache cleared for data type: {$request->data_type}"
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data type',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to clear cache for data type', [
                'data_type' => $request->data_type ?? 'unknown',
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all API cache
     */
    public function clearAll(): JsonResponse
    {
        try {
            $this->cacheService->clearAllApiCache();

            return response()->json([
                'success' => true,
                'message' => 'All API cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear all API cache', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear all cache',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Warm up cache
     */
    public function warmUp(): JsonResponse
    {
        try {
            $this->cacheService->warmUpCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache warmed up successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to warm up cache', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to warm up cache',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }
}