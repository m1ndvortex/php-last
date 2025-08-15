<?php

namespace App\Http\Controllers;

use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Get recent activities
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $activities = $this->activityService->getRecentActivities($limit);

        return response()->json([
            'success' => true,
            'data' => $activities,
            'meta' => [
                'count' => count($activities),
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * Get activities by type
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $activities = $this->activityService->getActivitiesByType($type, $limit);

        return response()->json([
            'success' => true,
            'data' => $activities,
            'meta' => [
                'type' => $type,
                'count' => count($activities),
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * Get activities for a specific reference
     */
    public function forReference(Request $request, string $referenceType, int $referenceId): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $activities = $this->activityService->getActivitiesForReference($referenceType, $referenceId, $limit);

        return response()->json([
            'success' => true,
            'data' => $activities,
            'meta' => [
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'count' => count($activities),
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * Get activity statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->activityService->getActivityStats();
        $counts = $this->activityService->getActivityCounts();

        return response()->json([
            'success' => true,
            'data' => [
                'database_stats' => $stats,
                'activity_log_counts' => $counts,
            ]
        ]);
    }

    /**
     * Get pending activities
     */
    public function pending(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $activities = $this->activityService->getPendingActivities($limit);

        return response()->json([
            'success' => true,
            'data' => $activities,
            'meta' => [
                'count' => count($activities),
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * Log a custom activity
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'status' => 'sometimes|string|in:pending,completed,failed',
            'reference_type' => 'sometimes|string|max:255',
            'reference_id' => 'sometimes|integer',
            'metadata' => 'sometimes|array',
        ]);

        $activity = $this->activityService->logActivity(
            $request->type,
            $request->description,
            auth()->id(),
            auth()->user()?->name,
            $request->get('status', 'completed'),
            $request->reference_type,
            $request->reference_id,
            $request->metadata
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity logged successfully',
            'data' => [
                'id' => $activity->id,
                'type' => $activity->type,
                'description' => $activity->description,
                'status' => $activity->status,
                'created_at' => $activity->created_at->toISOString(),
            ]
        ], 201);
    }
}