<?php

namespace App\Http\Controllers;

use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QueueController extends Controller
{
    public function __construct(
        private QueueService $queueService
    ) {}

    /**
     * Get queue statistics and status
     */
    public function index(): JsonResponse
    {
        try {
            $stats = $this->queueService->getQueueStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get queue statistics', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue statistics'
            ], 500);
        }
    }

    /**
     * Schedule manual backup
     */
    public function scheduleBackup(): JsonResponse
    {
        try {
            $this->queueService->scheduleBackup();
            
            return response()->json([
                'success' => true,
                'message' => 'Backup job scheduled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule backup', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule backup job'
            ], 500);
        }
    }

    /**
     * Process recurring invoices manually
     */
    public function processRecurringInvoices(Request $request): JsonResponse
    {
        try {
            $recurringInvoiceId = $request->input('recurring_invoice_id');
            $this->queueService->processRecurringInvoices($recurringInvoiceId);
            
            return response()->json([
                'success' => true,
                'message' => 'Recurring invoices processing job scheduled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule recurring invoices processing', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule recurring invoices processing'
            ], 500);
        }
    }

    /**
     * Send birthday/anniversary reminders manually
     */
    public function sendReminders(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:birthday,anniversary,all',
            'customer_id' => 'nullable|integer|exists:customers,id'
        ]);

        try {
            $this->queueService->scheduleBirthdayReminders(
                $request->input('customer_id'),
                $request->input('type')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Reminder job scheduled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule reminders', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule reminder job'
            ], 500);
        }
    }

    /**
     * Send stock alerts manually
     */
    public function sendStockAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'alert_type' => 'required|in:low_stock,out_of_stock,expiring,all',
            'inventory_item_id' => 'nullable|integer|exists:inventory_items,id'
        ]);

        try {
            $this->queueService->sendStockAlerts(
                $request->input('alert_type'),
                $request->input('inventory_item_id')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Stock alert job scheduled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule stock alerts', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule stock alert job'
            ], 500);
        }
    }

    /**
     * Send communication to customer
     */
    public function sendCommunication(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'type' => 'required|in:whatsapp,sms,email',
            'message' => 'required|string',
            'data' => 'nullable|array',
            'delay_minutes' => 'nullable|integer|min:0'
        ]);

        try {
            if ($request->has('delay_minutes') && $request->input('delay_minutes') > 0) {
                $sendAt = Carbon::now()->addMinutes($request->input('delay_minutes'));
                $this->queueService->sendDelayedCommunication(
                    $request->input('customer_id'),
                    $request->input('type'),
                    $request->input('message'),
                    $sendAt,
                    $request->input('data', [])
                );
            } else {
                $this->queueService->sendCommunication(
                    $request->input('customer_id'),
                    $request->input('type'),
                    $request->input('message'),
                    $request->input('data', [])
                );
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Communication job scheduled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule communication', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule communication job'
            ], 500);
        }
    }

    /**
     * Send bulk communications
     */
    public function sendBulkCommunications(Request $request): JsonResponse
    {
        $request->validate([
            'communications' => 'required|array',
            'communications.*.customer_id' => 'required|integer|exists:customers,id',
            'communications.*.type' => 'required|in:whatsapp,sms,email',
            'communications.*.message' => 'required|string',
            'communications.*.data' => 'nullable|array',
            'communications.*.delay_minutes' => 'nullable|integer|min:0'
        ]);

        try {
            $this->queueService->scheduleBulkCommunications(
                $request->input('communications')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk communications scheduled successfully',
                'count' => count($request->input('communications'))
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule bulk communications', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule bulk communications'
            ], 500);
        }
    }

    /**
     * Sync offline data
     */
    public function syncOfflineData(Request $request): JsonResponse
    {
        $request->validate([
            'sync_type' => 'required|in:full,incremental,specific,upload',
            'sync_data' => 'nullable|array'
        ]);

        try {
            $userId = auth()->id();
            $this->queueService->syncOfflineData(
                $userId,
                $request->input('sync_data', []),
                $request->input('sync_type')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Offline data sync job scheduled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to schedule offline data sync', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule offline data sync'
            ], 500);
        }
    }

    /**
     * Get job history
     */
    public function getJobHistory(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);
            $history = $this->queueService->getJobHistory($limit);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get job history', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job history'
            ], 500);
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): JsonResponse
    {
        try {
            $count = $this->queueService->clearFailedJobs();
            
            return response()->json([
                'success' => true,
                'message' => "Cleared {$count} failed jobs"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear failed jobs'
            ], 500);
        }
    }

    /**
     * Retry failed job
     */
    public function retryFailedJob(Request $request): JsonResponse
    {
        $request->validate([
            'job_id' => 'required|string'
        ]);

        try {
            $success = $this->queueService->retryFailedJob($request->input('job_id'));
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Job retried successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or could not be retried'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Failed to retry job', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry job'
            ], 500);
        }
    }
}
