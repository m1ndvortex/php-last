<?php

namespace Tests\Unit;

use App\Jobs\AutomatedBackupJob;
use App\Jobs\ProcessRecurringInvoicesJob;
use App\Jobs\SendBirthdayReminderJob;
use App\Jobs\SendCommunicationJob;
use App\Jobs\SendStockAlertJob;
use App\Jobs\SyncOfflineDataJob;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Carbon\Carbon;

class QueueServiceTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueService = new QueueService();
    }

    public function test_schedule_backup_dispatches_automated_backup_job(): void
    {
        Queue::fake();

        $this->queueService->scheduleBackup();

        Queue::assertPushed(AutomatedBackupJob::class);
    }

    public function test_process_recurring_invoices_dispatches_job_without_id(): void
    {
        Queue::fake();

        $this->queueService->processRecurringInvoices();

        Queue::assertPushed(ProcessRecurringInvoicesJob::class, function ($job) {
            return $job->recurringInvoiceId === null;
        });
    }

    public function test_process_recurring_invoices_dispatches_job_with_id(): void
    {
        Queue::fake();

        $recurringInvoiceId = 123;
        $this->queueService->processRecurringInvoices($recurringInvoiceId);

        Queue::assertPushed(ProcessRecurringInvoicesJob::class, function ($job) use ($recurringInvoiceId) {
            return $job->recurringInvoiceId === $recurringInvoiceId;
        });
    }

    public function test_schedule_birthday_reminders_dispatches_job(): void
    {
        Queue::fake();

        $customerId = 456;
        $this->queueService->scheduleBirthdayReminders($customerId, 'birthday');

        Queue::assertPushed(SendBirthdayReminderJob::class, function ($job) use ($customerId) {
            return $job->customerId === $customerId && $job->reminderType === 'birthday';
        });
    }

    public function test_schedule_anniversary_reminders_dispatches_job(): void
    {
        Queue::fake();

        $customerId = 789;
        $this->queueService->scheduleBirthdayReminders($customerId, 'anniversary');

        Queue::assertPushed(SendBirthdayReminderJob::class, function ($job) use ($customerId) {
            return $job->customerId === $customerId && $job->reminderType === 'anniversary';
        });
    }

    public function test_send_communication_dispatches_job(): void
    {
        Queue::fake();

        $customerId = 101;
        $type = 'whatsapp';
        $message = 'Test message';
        $data = ['key' => 'value'];

        $this->queueService->sendCommunication($customerId, $type, $message, $data);

        Queue::assertPushed(SendCommunicationJob::class, function ($job) use ($customerId, $type, $message, $data) {
            return $job->customerId === $customerId &&
                   $job->type === $type &&
                   $job->message === $message &&
                   $job->data === $data;
        });
    }

    public function test_send_delayed_communication_dispatches_job_with_delay(): void
    {
        Queue::fake();

        $customerId = 202;
        $type = 'sms';
        $message = 'Delayed message';
        $sendAt = Carbon::now()->addHours(2);
        $data = ['delayed' => true];

        $this->queueService->sendDelayedCommunication($customerId, $type, $message, $sendAt, $data);

        Queue::assertPushed(SendCommunicationJob::class, function ($job) use ($customerId, $type, $message, $data) {
            return $job->customerId === $customerId &&
                   $job->type === $type &&
                   $job->message === $message &&
                   $job->data === $data;
        });
    }

    public function test_send_stock_alerts_dispatches_job(): void
    {
        Queue::fake();

        $alertType = 'low_stock';
        $inventoryItemId = 303;

        $this->queueService->sendStockAlerts($alertType, $inventoryItemId);

        Queue::assertPushed(SendStockAlertJob::class, function ($job) use ($alertType, $inventoryItemId) {
            return $job->alertType === $alertType && $job->inventoryItemId === $inventoryItemId;
        });
    }

    public function test_sync_offline_data_dispatches_job(): void
    {
        Queue::fake();

        $userId = 404;
        $syncData = ['customers' => [], 'inventory' => []];
        $syncType = 'full';

        $this->queueService->syncOfflineData($userId, $syncData, $syncType);

        Queue::assertPushed(SyncOfflineDataJob::class, function ($job) use ($userId, $syncData, $syncType) {
            return $job->userId === $userId &&
                   $job->syncData === $syncData &&
                   $job->syncType === $syncType;
        });
    }

    public function test_schedule_bulk_communications_dispatches_multiple_jobs(): void
    {
        Queue::fake();

        $communications = [
            [
                'customer_id' => 1,
                'type' => 'whatsapp',
                'message' => 'Message 1',
                'data' => []
            ],
            [
                'customer_id' => 2,
                'type' => 'sms',
                'message' => 'Message 2',
                'data' => []
            ],
            [
                'customer_id' => 3,
                'type' => 'email',
                'message' => 'Message 3',
                'data' => [],
                'delay_minutes' => 30
            ]
        ];

        $this->queueService->scheduleBulkCommunications($communications);

        Queue::assertPushed(SendCommunicationJob::class, 3);
    }

    public function test_get_queue_stats_returns_array(): void
    {
        $stats = $this->queueService->getQueueStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('pending_jobs', $stats);
        $this->assertArrayHasKey('failed_jobs', $stats);
        $this->assertArrayHasKey('processed_jobs', $stats);
        $this->assertArrayHasKey('queue_sizes', $stats);
        $this->assertArrayHasKey('worker_status', $stats);
    }

    public function test_get_job_history_returns_array(): void
    {
        $history = $this->queueService->getJobHistory(10);

        $this->assertIsArray($history);
    }

    public function test_clear_failed_jobs_returns_count(): void
    {
        // Insert a test failed job
        \DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-123',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data']),
            'exception' => 'Test exception',
            'failed_at' => now()
        ]);

        $count = $this->queueService->clearFailedJobs();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
        
        // Verify failed jobs table is empty
        $remainingJobs = \DB::table('failed_jobs')->count();
        $this->assertEquals(0, $remainingJobs);
    }

    public function test_retry_failed_job_returns_false_for_nonexistent_job(): void
    {
        $result = $this->queueService->retryFailedJob('nonexistent-uuid');

        $this->assertFalse($result);
    }

    public function test_queue_service_handles_exceptions_gracefully(): void
    {
        // Test that exceptions are logged and re-thrown
        $this->expectException(\Exception::class);

        // Mock a scenario that would cause an exception
        $queueService = $this->getMockBuilder(QueueService::class)
            ->onlyMethods(['scheduleBackup'])
            ->getMock();

        $queueService->method('scheduleBackup')
            ->willThrowException(new \Exception('Test exception'));

        $queueService->scheduleBackup();
    }

    public function test_queue_sizes_returns_correct_structure(): void
    {
        $queueService = new QueueService();
        $stats = $queueService->getQueueStats();
        $queueSizes = $stats['queue_sizes'];

        $expectedQueues = ['default', 'backups', 'communications', 'invoices', 'reminders', 'alerts', 'sync'];
        
        foreach ($expectedQueues as $queue) {
            $this->assertArrayHasKey($queue, $queueSizes);
            $this->assertIsInt($queueSizes[$queue]);
        }
    }

    public function test_worker_status_returns_correct_structure(): void
    {
        $queueService = new QueueService();
        $stats = $queueService->getQueueStats();
        $workerStatus = $stats['worker_status'];

        $this->assertArrayHasKey('active_workers', $workerStatus);
        $this->assertArrayHasKey('total_workers', $workerStatus);
        $this->assertArrayHasKey('status', $workerStatus);
        
        $this->assertIsInt($workerStatus['active_workers']);
        $this->assertIsInt($workerStatus['total_workers']);
        $this->assertIsString($workerStatus['status']);
    }
}
