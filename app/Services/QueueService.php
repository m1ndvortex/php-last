<?php

namespace App\Services;

use App\Jobs\AutomatedBackupJob;
use App\Jobs\ProcessRecurringInvoicesJob;
use App\Jobs\SendBirthdayReminderJob;
use App\Jobs\SendCommunicationJob;
use App\Jobs\SendStockAlertJob;
use App\Jobs\SyncOfflineDataJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QueueService
{
    /**
     * Dispatch automated backup job
     */
    public function scheduleBackup(): void
    {
        try {
            AutomatedBackupJob::dispatch();
            Log::info('Automated backup job scheduled');
        } catch (\Exception $e) {
            Log::error('Failed to schedule backup job', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Dispatch recurring invoice processing job
     */
    public function processRecurringInvoices(?int $recurringInvoiceId = null): void
    {
        try {
            ProcessRecurringInvoicesJob::dispatch($recurringInvoiceId);
            Log::info('Recurring invoices processing job scheduled', [
                'recurring_invoice_id' => $recurringInvoiceId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule recurring invoices job', [
                'recurring_invoice_id' => $recurringInvoiceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch birthday/anniversary reminder job
     */
    public function scheduleBirthdayReminders(?int $customerId = null, string $type = 'birthday'): void
    {
        try {
            SendBirthdayReminderJob::dispatch($customerId, $type);
            Log::info('Birthday/anniversary reminder job scheduled', [
                'customer_id' => $customerId,
                'type' => $type
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule birthday reminder job', [
                'customer_id' => $customerId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch communication job
     */
    public function sendCommunication(int $customerId, string $type, string $message, array $data = []): void
    {
        try {
            SendCommunicationJob::dispatch($customerId, $type, $message, $data);
            Log::info('Communication job scheduled', [
                'customer_id' => $customerId,
                'type' => $type
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule communication job', [
                'customer_id' => $customerId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch delayed communication job
     */
    public function sendDelayedCommunication(
        int $customerId, 
        string $type, 
        string $message, 
        Carbon $sendAt, 
        array $data = []
    ): void {
        try {
            SendCommunicationJob::dispatch($customerId, $type, $message, $data)
                ->delay($sendAt);
            
            Log::info('Delayed communication job scheduled', [
                'customer_id' => $customerId,
                'type' => $type,
                'send_at' => $sendAt->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule delayed communication job', [
                'customer_id' => $customerId,
                'type' => $type,
                'send_at' => $sendAt->toISOString(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch stock alert job
     */
    public function sendStockAlerts(string $alertType = 'all', ?int $inventoryItemId = null): void
    {
        try {
            SendStockAlertJob::dispatch($alertType, $inventoryItemId);
            Log::info('Stock alert job scheduled', [
                'alert_type' => $alertType,
                'inventory_item_id' => $inventoryItemId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule stock alert job', [
                'alert_type' => $alertType,
                'inventory_item_id' => $inventoryItemId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch offline data synchronization job
     */
    public function syncOfflineData(int $userId, array $syncData = [], string $syncType = 'full'): void
    {
        try {
            SyncOfflineDataJob::dispatch($userId, $syncData, $syncType);
            Log::info('Offline data sync job scheduled', [
                'user_id' => $userId,
                'sync_type' => $syncType,
                'data_keys' => array_keys($syncData)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule offline data sync job', [
                'user_id' => $userId,
                'sync_type' => $syncType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        try {
            $stats = [
                'pending_jobs' => $this->getPendingJobsCount(),
                'failed_jobs' => $this->getFailedJobsCount(),
                'processed_jobs' => $this->getProcessedJobsCount(),
                'queue_sizes' => $this->getQueueSizes(),
                'worker_status' => $this->getWorkerStatus()
            ];

            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get queue statistics', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get pending jobs count
     */
    private function getPendingJobsCount(): int
    {
        return \DB::table('jobs')->count();
    }

    /**
     * Get failed jobs count
     */
    private function getFailedJobsCount(): int
    {
        return \DB::table('failed_jobs')->count();
    }

    /**
     * Get processed jobs count (from today)
     */
    private function getProcessedJobsCount(): int
    {
        // This would require additional tracking, for now return 0
        return 0;
    }

    /**
     * Get queue sizes by queue name
     */
    private function getQueueSizes(): array
    {
        $queues = ['default', 'backups', 'communications', 'invoices', 'reminders', 'alerts', 'sync'];
        $sizes = [];

        foreach ($queues as $queue) {
            $sizes[$queue] = \DB::table('jobs')
                ->where('queue', $queue)
                ->count();
        }

        return $sizes;
    }

    /**
     * Get worker status (simplified)
     */
    private function getWorkerStatus(): array
    {
        // This would require integration with Horizon or custom worker monitoring
        return [
            'active_workers' => 1,
            'total_workers' => 1,
            'status' => 'running'
        ];
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): int
    {
        try {
            $count = \DB::table('failed_jobs')->count();
            \DB::table('failed_jobs')->truncate();
            
            Log::info('Cleared failed jobs', ['count' => $count]);
            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retry failed job
     */
    public function retryFailedJob(string $jobId): bool
    {
        try {
            $failedJob = \DB::table('failed_jobs')->where('uuid', $jobId)->first();
            
            if (!$failedJob) {
                return false;
            }

            // Recreate and dispatch the job
            $payload = json_decode($failedJob->payload, true);
            $job = unserialize($payload['data']['command']);
            
            dispatch($job);
            
            // Remove from failed jobs table
            \DB::table('failed_jobs')->where('uuid', $jobId)->delete();
            
            Log::info('Retried failed job', ['job_id' => $jobId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to retry job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Schedule bulk communications
     */
    public function scheduleBulkCommunications(array $communications): void
    {
        try {
            foreach ($communications as $communication) {
                $job = SendCommunicationJob::dispatch(
                    $communication['customer_id'],
                    $communication['type'],
                    $communication['message'],
                    $communication['data'] ?? []
                );

                // Add delay if specified
                if (isset($communication['delay_minutes'])) {
                    $job->delay(now()->addMinutes($communication['delay_minutes']));
                }
            }

            Log::info('Bulk communications scheduled', [
                'count' => count($communications)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule bulk communications', [
                'count' => count($communications),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get job history for monitoring
     */
    public function getJobHistory(int $limit = 50): array
    {
        try {
            $failedJobs = \DB::table('failed_jobs')
                ->select('uuid', 'connection', 'queue', 'exception', 'failed_at')
                ->orderBy('failed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->uuid,
                        'queue' => $job->queue,
                        'status' => 'failed',
                        'error' => substr($job->exception, 0, 200),
                        'timestamp' => $job->failed_at
                    ];
                });

            return $failedJobs->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get job history', ['error' => $e->getMessage()]);
            return [];
        }
    }
}