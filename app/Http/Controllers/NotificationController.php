<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'limit', 'unread_only']);
            
            if ($request->boolean('unread_only')) {
                $filters['unread_only'] = true;
            }

            $notifications = $this->notificationService->getNotifications(
                auth()->id(),
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get notifications', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications'
            ], 500);
        }
    }

    /**
     * Get notification statistics
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->notificationService->getNotificationStats(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get notification stats', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification statistics'
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read
     *
     * @param Request $request
     * @param string $notificationId
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $success = $this->notificationService->markAsRead($notificationId, auth()->id());

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark notification as read'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'user_id' => auth()->id(),
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type');
            
            $success = $this->notificationService->markAllAsRead(auth()->id(), $type);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'All notifications marked as read'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark all notifications as read'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => auth()->id(),
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read'
            ], 500);
        }
    }

    /**
     * Send a test notification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendTest(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|string|in:stock,communication,reminder,system',
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000'
            ]);

            $notificationData = [
                'type' => $request->input('type'),
                'subtype' => 'test',
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'priority' => 1,
                'data' => [
                    'test' => true,
                    'sent_by' => auth()->user()->name
                ]
            ];

            $success = $this->notificationService->sendRealTimeNotification(
                $notificationData,
                auth()->id()
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test notification sent successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test notification'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification'
            ], 500);
        }
    }

    /**
     * Get real-time notifications (for polling)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function realTime(Request $request): JsonResponse
    {
        try {
            $lastCheck = $request->input('last_check');
            
            // Get notifications from cache
            $cacheKey = 'realtime_notifications_' . auth()->id();
            $notifications = cache($cacheKey, []);

            // Filter by last check time if provided
            if ($lastCheck) {
                $notifications = array_filter($notifications, function ($notification) use ($lastCheck) {
                    return $notification['timestamp'] > $lastCheck;
                });
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => array_values($notifications),
                    'timestamp' => now()->toISOString(),
                    'count' => count($notifications)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get real-time notifications', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve real-time notifications'
            ], 500);
        }
    }
}