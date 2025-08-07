<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Communication;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class NotificationService
{
    protected WhatsAppService $whatsAppService;
    protected SMSService $smsService;
    protected CommunicationService $communicationService;

    public function __construct(
        WhatsAppService $whatsAppService,
        SMSService $smsService,
        CommunicationService $communicationService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->smsService = $smsService;
        $this->communicationService = $communicationService;
    }

    /**
     * Get all notifications for the notification center
     *
     * @param int|null $userId
     * @param array $filters
     * @return array
     */
    public function getNotifications(?int $userId = null, array $filters = []): array
    {
        $cacheKey = 'notifications_' . ($userId ?? 'all') . '_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $filters) {
            $notifications = [];

            // Stock alerts
            if (empty($filters['type']) || $filters['type'] === 'stock') {
                $notifications = array_merge($notifications, $this->getStockNotifications($filters));
            }

            // Communication notifications
            if (empty($filters['type']) || $filters['type'] === 'communication') {
                $notifications = array_merge($notifications, $this->getCommunicationNotifications($filters));
            }

            // Birthday/Anniversary reminders
            if (empty($filters['type']) || $filters['type'] === 'reminder') {
                $notifications = array_merge($notifications, $this->getReminderNotifications($filters));
            }

            // System notifications
            if (empty($filters['type']) || $filters['type'] === 'system') {
                $notifications = array_merge($notifications, $this->getSystemNotifications($filters));
            }

            // Sort by priority and date
            usort($notifications, function ($a, $b) {
                if ($a['priority'] !== $b['priority']) {
                    return $b['priority'] <=> $a['priority']; // Higher priority first
                }
                return $b['created_at'] <=> $a['created_at']; // Newer first
            });

            // Apply pagination if requested
            if (!empty($filters['limit'])) {
                $notifications = array_slice($notifications, 0, $filters['limit']);
            }

            return [
                'notifications' => $notifications,
                'unread_count' => count(array_filter($notifications, fn($n) => !$n['read'])),
                'total_count' => count($notifications)
            ];
        });
    }

    /**
     * Get stock-related notifications
     *
     * @param array $filters
     * @return array
     */
    protected function getStockNotifications(array $filters): array
    {
        $notifications = [];

        // Low stock items
        $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'minimum_stock')
            ->where('quantity', '>', 0)
            ->with(['category', 'location'])
            ->get();

        foreach ($lowStockItems as $item) {
            $notifications[] = [
                'id' => 'stock_low_' . $item->id,
                'type' => 'stock',
                'subtype' => 'low_stock',
                'title' => 'Low Stock Alert',
                'message' => "Item '{$item->name}' is running low (Current: {$item->quantity}, Min: {$item->minimum_stock})",
                'data' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'current_quantity' => $item->quantity,
                    'minimum_stock' => $item->minimum_stock,
                    'location' => $item->location->name ?? 'Unknown'
                ],
                'priority' => 2,
                'read' => false,
                'created_at' => $item->updated_at->toISOString(),
                'action_url' => "/inventory/items/{$item->id}"
            ];
        }

        // Out of stock items
        $outOfStockItems = InventoryItem::where('quantity', '<=', 0)
            ->with(['category', 'location'])
            ->get();

        foreach ($outOfStockItems as $item) {
            $notifications[] = [
                'id' => 'stock_out_' . $item->id,
                'type' => 'stock',
                'subtype' => 'out_of_stock',
                'title' => 'Out of Stock Alert',
                'message' => "Item '{$item->name}' is out of stock",
                'data' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'current_quantity' => $item->quantity,
                    'location' => $item->location->name ?? 'Unknown'
                ],
                'priority' => 3,
                'read' => false,
                'created_at' => $item->updated_at->toISOString(),
                'action_url' => "/inventory/items/{$item->id}"
            ];
        }

        // Expiring items
        $expiringItems = InventoryItem::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays(7))
            ->where('expiry_date', '>=', Carbon::now())
            ->with(['category', 'location'])
            ->get();

        foreach ($expiringItems as $item) {
            $daysUntilExpiry = Carbon::parse($item->expiry_date)->diffInDays(Carbon::now());
            
            $notifications[] = [
                'id' => 'stock_expiring_' . $item->id,
                'type' => 'stock',
                'subtype' => 'expiring',
                'title' => 'Item Expiring Soon',
                'message' => "Item '{$item->name}' expires in {$daysUntilExpiry} days",
                'data' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'expiry_date' => $item->expiry_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'location' => $item->location->name ?? 'Unknown'
                ],
                'priority' => 2,
                'read' => false,
                'created_at' => $item->updated_at->toISOString(),
                'action_url' => "/inventory/items/{$item->id}"
            ];
        }

        return $notifications;
    }

    /**
     * Get communication-related notifications
     *
     * @param array $filters
     * @return array
     */
    protected function getCommunicationNotifications(array $filters): array
    {
        $notifications = [];

        // Failed communications
        $failedCommunications = Communication::where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->with(['customer'])
            ->get();

        foreach ($failedCommunications as $communication) {
            $notifications[] = [
                'id' => 'comm_failed_' . $communication->id,
                'type' => 'communication',
                'subtype' => 'failed',
                'title' => 'Communication Failed',
                'message' => "Failed to send {$communication->type} to {$communication->customer->name}",
                'data' => [
                    'communication_id' => $communication->id,
                    'customer_id' => $communication->customer_id,
                    'customer_name' => $communication->customer->name,
                    'communication_type' => $communication->type,
                    'error_message' => $communication->metadata['failure_reason'] ?? 'Unknown error'
                ],
                'priority' => 2,
                'read' => false,
                'created_at' => $communication->created_at->toISOString(),
                'action_url' => "/customers/{$communication->customer_id}/communications"
            ];
        }

        // Pending communications
        $pendingCommunications = Communication::where('status', 'draft')
            ->where('created_at', '>=', Carbon::now()->subDays(3))
            ->with(['customer'])
            ->get();

        foreach ($pendingCommunications as $communication) {
            $notifications[] = [
                'id' => 'comm_pending_' . $communication->id,
                'type' => 'communication',
                'subtype' => 'pending',
                'title' => 'Pending Communication',
                'message' => "Pending {$communication->type} to {$communication->customer->name}",
                'data' => [
                    'communication_id' => $communication->id,
                    'customer_id' => $communication->customer_id,
                    'customer_name' => $communication->customer->name,
                    'communication_type' => $communication->type
                ],
                'priority' => 1,
                'read' => false,
                'created_at' => $communication->created_at->toISOString(),
                'action_url' => "/customers/{$communication->customer_id}/communications"
            ];
        }

        return $notifications;
    }

    /**
     * Get reminder notifications (birthdays, anniversaries)
     *
     * @param array $filters
     * @return array
     */
    protected function getReminderNotifications(array $filters): array
    {
        $notifications = [];
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // Today's birthdays
        $todayBirthdays = Customer::whereNotNull('birthday')
            ->whereRaw('DATE_FORMAT(birthday, "%m-%d") = ?', [$today->format('m-d')])
            ->get();

        foreach ($todayBirthdays as $customer) {
            $age = Carbon::parse($customer->birthday)->age;
            
            $notifications[] = [
                'id' => 'birthday_today_' . $customer->id,
                'type' => 'reminder',
                'subtype' => 'birthday_today',
                'title' => 'Birthday Today',
                'message' => "It's {$customer->name}'s birthday today! (Age: {$age})",
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'age' => $age,
                    'birthday' => $customer->birthday
                ],
                'priority' => 2,
                'read' => false,
                'created_at' => $today->toISOString(),
                'action_url' => "/customers/{$customer->id}"
            ];
        }

        // Tomorrow's birthdays
        $tomorrowBirthdays = Customer::whereNotNull('birthday')
            ->whereRaw('DATE_FORMAT(birthday, "%m-%d") = ?', [$tomorrow->format('m-d')])
            ->get();

        foreach ($tomorrowBirthdays as $customer) {
            $age = Carbon::parse($customer->birthday)->age + 1;
            
            $notifications[] = [
                'id' => 'birthday_tomorrow_' . $customer->id,
                'type' => 'reminder',
                'subtype' => 'birthday_tomorrow',
                'title' => 'Birthday Tomorrow',
                'message' => "{$customer->name}'s birthday is tomorrow (Turning {$age})",
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'age' => $age,
                    'birthday' => $customer->birthday
                ],
                'priority' => 1,
                'read' => false,
                'created_at' => $today->toISOString(),
                'action_url' => "/customers/{$customer->id}"
            ];
        }

        // Today's anniversaries
        $todayAnniversaries = Customer::whereNotNull('anniversary')
            ->whereRaw('DATE_FORMAT(anniversary, "%m-%d") = ?', [$today->format('m-d')])
            ->get();

        foreach ($todayAnniversaries as $customer) {
            $yearsMarried = Carbon::parse($customer->anniversary)->diffInYears(Carbon::now());
            
            $notifications[] = [
                'id' => 'anniversary_today_' . $customer->id,
                'type' => 'reminder',
                'subtype' => 'anniversary_today',
                'title' => 'Anniversary Today',
                'message' => "It's {$customer->name}'s anniversary today! ({$yearsMarried} years)",
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'years_married' => $yearsMarried,
                    'anniversary' => $customer->anniversary
                ],
                'priority' => 2,
                'read' => false,
                'created_at' => $today->toISOString(),
                'action_url' => "/customers/{$customer->id}"
            ];
        }

        return $notifications;
    }

    /**
     * Get system notifications
     *
     * @param array $filters
     * @return array
     */
    protected function getSystemNotifications(array $filters): array
    {
        $notifications = [];

        // Check for backup status
        $lastBackup = Cache::get('last_backup_time');
        if (!$lastBackup || Carbon::parse($lastBackup)->lt(Carbon::now()->subDays(1))) {
            $notifications[] = [
                'id' => 'system_backup_overdue',
                'type' => 'system',
                'subtype' => 'backup_overdue',
                'title' => 'Backup Overdue',
                'message' => 'System backup is overdue. Last backup: ' . ($lastBackup ? Carbon::parse($lastBackup)->diffForHumans() : 'Never'),
                'data' => [
                    'last_backup' => $lastBackup
                ],
                'priority' => 2,
                'read' => false,
                'created_at' => Carbon::now()->toISOString(),
                'action_url' => '/settings/backup'
            ];
        }

        // Check for failed queue jobs
        $failedJobsCount = Cache::get('failed_jobs_count', 0);
        if ($failedJobsCount > 0) {
            $notifications[] = [
                'id' => 'system_failed_jobs',
                'type' => 'system',
                'subtype' => 'failed_jobs',
                'title' => 'Failed Background Jobs',
                'message' => "There are {$failedJobsCount} failed background jobs that need attention",
                'data' => [
                    'failed_jobs_count' => $failedJobsCount
                ],
                'priority' => 1,
                'read' => false,
                'created_at' => Carbon::now()->toISOString(),
                'action_url' => '/admin/queue'
            ];
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     *
     * @param string $notificationId
     * @param int|null $userId
     * @return bool
     */
    public function markAsRead(string $notificationId, ?int $userId = null): bool
    {
        try {
            // Store read status in cache
            $cacheKey = 'notification_read_' . $notificationId . '_' . ($userId ?? 'all');
            Cache::put($cacheKey, true, 86400); // 24 hours

            // Clear notifications cache to refresh
            $this->clearNotificationsCache($userId);

            Log::info('Notification marked as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Mark all notifications as read
     *
     * @param int|null $userId
     * @param string|null $type
     * @return bool
     */
    public function markAllAsRead(?int $userId = null, ?string $type = null): bool
    {
        try {
            $notifications = $this->getNotifications($userId, $type ? ['type' => $type] : []);
            
            foreach ($notifications['notifications'] as $notification) {
                if (!$notification['read']) {
                    $this->markAsRead($notification['id'], $userId);
                }
            }

            Log::info('All notifications marked as read', [
                'user_id' => $userId,
                'type' => $type,
                'count' => count($notifications['notifications'])
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send real-time notification
     *
     * @param array $notificationData
     * @param int|null $userId
     * @return bool
     */
    public function sendRealTimeNotification(array $notificationData, ?int $userId = null): bool
    {
        try {
            // In a real implementation, this would use WebSockets, Server-Sent Events, or push notifications
            // For now, we'll log it and store in cache for polling
            
            $cacheKey = 'realtime_notifications_' . ($userId ?? 'all');
            $existingNotifications = Cache::get($cacheKey, []);
            
            $existingNotifications[] = array_merge($notificationData, [
                'id' => 'realtime_' . uniqid(),
                'timestamp' => Carbon::now()->toISOString(),
                'read' => false
            ]);

            // Keep only last 50 real-time notifications
            if (count($existingNotifications) > 50) {
                $existingNotifications = array_slice($existingNotifications, -50);
            }

            Cache::put($cacheKey, $existingNotifications, 3600); // 1 hour

            Log::info('Real-time notification sent', [
                'user_id' => $userId,
                'type' => $notificationData['type'] ?? 'unknown',
                'title' => $notificationData['title'] ?? 'No title'
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send real-time notification', [
                'user_id' => $userId,
                'notification_data' => $notificationData,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get notification statistics
     *
     * @param int|null $userId
     * @return array
     */
    public function getNotificationStats(?int $userId = null): array
    {
        $notifications = $this->getNotifications($userId);
        
        $stats = [
            'total' => $notifications['total_count'],
            'unread' => $notifications['unread_count'],
            'by_type' => [],
            'by_priority' => [
                'high' => 0,
                'medium' => 0,
                'low' => 0
            ]
        ];

        foreach ($notifications['notifications'] as $notification) {
            // Count by type
            $type = $notification['type'];
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = ['total' => 0, 'unread' => 0];
            }
            $stats['by_type'][$type]['total']++;
            if (!$notification['read']) {
                $stats['by_type'][$type]['unread']++;
            }

            // Count by priority
            $priority = match ($notification['priority']) {
                3 => 'high',
                2 => 'medium',
                default => 'low'
            };
            $stats['by_priority'][$priority]++;
        }

        return $stats;
    }

    /**
     * Clear notifications cache
     *
     * @param int|null $userId
     * @return void
     */
    protected function clearNotificationsCache(?int $userId = null): void
    {
        $pattern = 'notifications_' . ($userId ?? 'all') . '_*';
        
        // In a real implementation, you'd use a more sophisticated cache clearing mechanism
        // For now, we'll just clear the main cache key
        Cache::forget('notifications_' . ($userId ?? 'all') . '_' . md5(serialize([])));
    }
}