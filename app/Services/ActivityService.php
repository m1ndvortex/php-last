<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityService
{
    /**
     * Get recent activities for dashboard
     */
    public function getRecentActivities(int $limit = 10): array
    {
        // First try to get from activity logs
        $loggedActivities = ActivityLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'description' => $log->description,
                    'user' => $log->user_name,
                    'time' => $this->formatTimeAgo($log->created_at),
                    'timestamp' => $log->created_at->toISOString(),
                    'status' => $log->status,
                    'reference_id' => $log->reference_id,
                    'reference_type' => $log->reference_type,
                ];
            })
            ->toArray();

        // If we have enough logged activities, return them
        if (count($loggedActivities) >= $limit) {
            return $loggedActivities;
        }

        // Otherwise, supplement with recent database activities
        $activities = $loggedActivities;

        // Get recent invoices
        $recentInvoices = Invoice::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentInvoices as $invoice) {
            $activities[] = [
                'id' => 'invoice_' . $invoice->id,
                'type' => 'invoice_created',
                'description' => "Invoice #{$invoice->invoice_number} created",
                'user' => $invoice->created_by ? User::find($invoice->created_by)?->name ?? 'System' : 'System',
                'time' => $this->formatTimeAgo($invoice->created_at),
                'timestamp' => $invoice->created_at->toISOString(),
                'status' => $this->mapInvoiceStatus($invoice->status),
                'reference_id' => $invoice->id,
                'reference_type' => 'invoice',
            ];
        }

        // Get recent customers
        $recentCustomers = Customer::orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentCustomers as $customer) {
            $activities[] = [
                'id' => 'customer_' . $customer->id,
                'type' => 'customer_added',
                'description' => "Customer {$customer->name} added",
                'user' => $customer->created_by ? User::find($customer->created_by)?->name ?? 'Admin' : 'Admin',
                'time' => $this->formatTimeAgo($customer->created_at),
                'timestamp' => $customer->created_at->toISOString(),
                'status' => 'completed',
                'reference_id' => $customer->id,
                'reference_type' => 'customer',
            ];
        }

        // Get recent inventory updates
        $recentInventoryUpdates = InventoryItem::orderBy('updated_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentInventoryUpdates as $item) {
            $activities[] = [
                'id' => 'inventory_' . $item->id,
                'type' => 'inventory_updated',
                'description' => "{$item->name} inventory updated",
                'user' => $item->updated_by ? User::find($item->updated_by)?->name ?? 'Admin' : 'Admin',
                'time' => $this->formatTimeAgo($item->updated_at),
                'timestamp' => $item->updated_at->toISOString(),
                'status' => 'completed',
                'reference_id' => $item->id,
                'reference_type' => 'inventory',
            ];
        }

        // Sort all activities by timestamp (most recent first)
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Return limited number of activities
        return array_slice($activities, 0, $limit);
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'today' => [
                'invoices' => Invoice::whereDate('created_at', $today)->count(),
                'customers' => Customer::whereDate('created_at', $today)->count(),
                'transactions' => Transaction::whereDate('created_at', $today)->count(),
            ],
            'this_week' => [
                'invoices' => Invoice::where('created_at', '>=', $thisWeek)->count(),
                'customers' => Customer::where('created_at', '>=', $thisWeek)->count(),
                'transactions' => Transaction::where('created_at', '>=', $thisWeek)->count(),
            ],
            'this_month' => [
                'invoices' => Invoice::where('created_at', '>=', $thisMonth)->count(),
                'customers' => Customer::where('created_at', '>=', $thisMonth)->count(),
                'transactions' => Transaction::where('created_at', '>=', $thisMonth)->count(),
            ],
        ];
    }

    /**
     * Format time ago string
     */
    private function formatTimeAgo(Carbon $timestamp): string
    {
        $diffInMinutes = $timestamp->diffInMinutes(now());

        if ($diffInMinutes < 1) {
            return 'Just now';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . ' minutes ago';
        } elseif ($diffInMinutes < 1440) {
            $hours = floor($diffInMinutes / 60);
            return $hours . ' hours ago';
        } else {
            $days = floor($diffInMinutes / 1440);
            return $days . ' days ago';
        }
    }

    /**
     * Map invoice status to activity status
     */
    private function mapInvoiceStatus(string $invoiceStatus): string
    {
        return match ($invoiceStatus) {
            'paid' => 'completed',
            'sent' => 'completed',
            'overdue' => 'pending',
            'cancelled' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Log a new activity
     */
    public function logActivity(
        string $type,
        string $description,
        ?int $userId = null,
        ?string $userName = null,
        string $status = 'completed',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null
    ): ActivityLog {
        return ActivityLog::logActivity(
            $type,
            $description,
            $userId,
            $userName,
            $status,
            $referenceType,
            $referenceId,
            $metadata
        );
    }

    /**
     * Get activities by type
     */
    public function getActivitiesByType(string $type, int $limit = 10): array
    {
        return ActivityLog::ofType($type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'description' => $log->description,
                    'user' => $log->user_name,
                    'time' => $this->formatTimeAgo($log->created_at),
                    'timestamp' => $log->created_at->toISOString(),
                    'status' => $log->status,
                    'reference_id' => $log->reference_id,
                    'reference_type' => $log->reference_type,
                    'metadata' => $log->metadata,
                ];
            })
            ->toArray();
    }

    /**
     * Get activities for a specific reference
     */
    public function getActivitiesForReference(string $referenceType, int $referenceId, int $limit = 10): array
    {
        return ActivityLog::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'description' => $log->description,
                    'user' => $log->user_name,
                    'time' => $this->formatTimeAgo($log->created_at),
                    'timestamp' => $log->created_at->toISOString(),
                    'status' => $log->status,
                    'metadata' => $log->metadata,
                ];
            })
            ->toArray();
    }

    /**
     * Get activity counts by type for dashboard
     */
    public function getActivityCounts(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();

        return [
            'today' => [
                'total' => ActivityLog::whereDate('created_at', $today)->count(),
                'by_type' => ActivityLog::whereDate('created_at', $today)
                    ->select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ],
            'this_week' => [
                'total' => ActivityLog::where('created_at', '>=', $thisWeek)->count(),
                'by_type' => ActivityLog::where('created_at', '>=', $thisWeek)
                    ->select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ],
        ];
    }

    /**
     * Clean old activities (older than specified days)
     */
    public function cleanOldActivities(int $daysToKeep = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return ActivityLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get pending activities (activities with pending status)
     */
    public function getPendingActivities(int $limit = 10): array
    {
        return ActivityLog::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'description' => $log->description,
                    'user' => $log->user_name,
                    'time' => $this->formatTimeAgo($log->created_at),
                    'timestamp' => $log->created_at->toISOString(),
                    'reference_id' => $log->reference_id,
                    'reference_type' => $log->reference_type,
                    'metadata' => $log->metadata,
                ];
            })
            ->toArray();
    }
}