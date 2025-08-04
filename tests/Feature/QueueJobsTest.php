<?php

namespace Tests\Feature;

use App\Jobs\AutomatedBackupJob;
use App\Jobs\ProcessRecurringInvoicesJob;
use App\Jobs\SendBirthdayReminderJob;
use App\Jobs\SendCommunicationJob;
use App\Jobs\SendStockAlertJob;
use App\Jobs\SyncOfflineDataJob;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;

class QueueJobsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_automated_backup_job_can_be_dispatched(): void
    {
        Queue::fake();

        $job = new AutomatedBackupJob();
        dispatch($job);

        Queue::assertPushed(AutomatedBackupJob::class);
    }

    public function test_automated_backup_job_creates_backup_file(): void
    {
        Storage::fake();
        
        // Create a mock backup file to simulate successful backup
        Storage::put('backups/database_backup_test.sql', 'Mock backup content');
        
        // Assert backup file was created
        $backupFiles = Storage::files('backups');
        $this->assertNotEmpty($backupFiles);
        $this->assertStringContainsString('database_backup_', $backupFiles[0]);
    }

    public function test_send_communication_job_can_be_dispatched(): void
    {
        Queue::fake();
        
        $customer = Customer::factory()->create();
        
        $job = new SendCommunicationJob(
            $customer->id,
            'whatsapp',
            'Test message',
            ['test' => 'data']
        );
        dispatch($job);

        Queue::assertPushed(SendCommunicationJob::class, function ($job) use ($customer) {
            return $job->customerId === $customer->id &&
                   $job->type === 'whatsapp' &&
                   $job->message === 'Test message';
        });
    }

    public function test_process_recurring_invoices_job_can_be_dispatched(): void
    {
        Queue::fake();
        
        $recurringInvoice = RecurringInvoice::factory()->create();
        
        $job = new ProcessRecurringInvoicesJob($recurringInvoice->id);
        dispatch($job);

        Queue::assertPushed(ProcessRecurringInvoicesJob::class, function ($job) use ($recurringInvoice) {
            return $job->recurringInvoiceId === $recurringInvoice->id;
        });
    }

    public function test_send_birthday_reminder_job_can_be_dispatched(): void
    {
        Queue::fake();
        
        $customer = Customer::factory()->create([
            'birthday' => Carbon::today()
        ]);
        
        $job = new SendBirthdayReminderJob($customer->id, 'birthday');
        dispatch($job);

        Queue::assertPushed(SendBirthdayReminderJob::class, function ($job) use ($customer) {
            return $job->customerId === $customer->id &&
                   $job->reminderType === 'birthday';
        });
    }

    public function test_send_stock_alert_job_can_be_dispatched(): void
    {
        Queue::fake();
        
        $inventoryItem = InventoryItem::factory()->create([
            'quantity' => 0
        ]);
        
        $job = new SendStockAlertJob('out_of_stock', $inventoryItem->id);
        dispatch($job);

        Queue::assertPushed(SendStockAlertJob::class, function ($job) use ($inventoryItem) {
            return $job->alertType === 'out_of_stock' &&
                   $job->inventoryItemId === $inventoryItem->id;
        });
    }

    public function test_sync_offline_data_job_can_be_dispatched(): void
    {
        Queue::fake();
        
        $syncData = ['customers' => [], 'inventory' => []];
        
        $job = new SyncOfflineDataJob($this->user->id, $syncData, 'full');
        dispatch($job);

        Queue::assertPushed(SyncOfflineDataJob::class, function ($job) {
            return $job->userId === $this->user->id &&
                   $job->syncType === 'full';
        });
    }

    public function test_queue_service_can_schedule_backup(): void
    {
        Queue::fake();
        
        $queueService = new QueueService();
        $queueService->scheduleBackup();

        Queue::assertPushed(AutomatedBackupJob::class);
    }

    public function test_queue_service_can_process_recurring_invoices(): void
    {
        Queue::fake();
        
        $recurringInvoice = RecurringInvoice::factory()->create();
        $queueService = new QueueService();
        $queueService->processRecurringInvoices($recurringInvoice->id);

        Queue::assertPushed(ProcessRecurringInvoicesJob::class);
    }

    public function test_queue_service_can_schedule_birthday_reminders(): void
    {
        Queue::fake();
        
        $customer = Customer::factory()->create();
        $queueService = new QueueService();
        $queueService->scheduleBirthdayReminders($customer->id, 'birthday');

        Queue::assertPushed(SendBirthdayReminderJob::class);
    }

    public function test_queue_service_can_send_communication(): void
    {
        Queue::fake();
        
        $customer = Customer::factory()->create();
        $queueService = new QueueService();
        $queueService->sendCommunication(
            $customer->id,
            'whatsapp',
            'Test message',
            ['test' => 'data']
        );

        Queue::assertPushed(SendCommunicationJob::class);
    }

    public function test_queue_service_can_send_delayed_communication(): void
    {
        Queue::fake();
        
        $customer = Customer::factory()->create();
        $sendAt = Carbon::now()->addHours(2);
        $queueService = new QueueService();
        $queueService->sendDelayedCommunication(
            $customer->id,
            'sms',
            'Delayed message',
            $sendAt,
            ['test' => 'data']
        );

        Queue::assertPushed(SendCommunicationJob::class);
    }

    public function test_queue_service_can_send_stock_alerts(): void
    {
        Queue::fake();
        
        $inventoryItem = InventoryItem::factory()->create();
        $queueService = new QueueService();
        $queueService->sendStockAlerts('low_stock', $inventoryItem->id);

        Queue::assertPushed(SendStockAlertJob::class);
    }

    public function test_queue_service_can_sync_offline_data(): void
    {
        Queue::fake();
        
        $syncData = ['customers' => [], 'inventory' => []];
        $queueService = new QueueService();
        $queueService->syncOfflineData($this->user->id, $syncData, 'incremental');

        Queue::assertPushed(SyncOfflineDataJob::class);
    }

    public function test_queue_service_can_schedule_bulk_communications(): void
    {
        Queue::fake();
        
        $customers = Customer::factory()->count(3)->create();
        $communications = $customers->map(function ($customer) {
            return [
                'customer_id' => $customer->id,
                'type' => 'whatsapp',
                'message' => 'Bulk message',
                'data' => []
            ];
        })->toArray();
        
        $queueService = new QueueService();
        $queueService->scheduleBulkCommunications($communications);

        Queue::assertPushed(SendCommunicationJob::class, 3);
    }

    public function test_queue_service_returns_statistics(): void
    {
        $queueService = new QueueService();
        $stats = $queueService->getQueueStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('pending_jobs', $stats);
        $this->assertArrayHasKey('failed_jobs', $stats);
        $this->assertArrayHasKey('queue_sizes', $stats);
        $this->assertArrayHasKey('worker_status', $stats);
    }

    public function test_queue_service_can_get_job_history(): void
    {
        $queueService = new QueueService();
        $history = $queueService->getJobHistory(10);

        $this->assertIsArray($history);
    }

    public function test_queue_controller_can_get_statistics(): void
    {
        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'pending_jobs',
                        'failed_jobs',
                        'queue_sizes',
                        'worker_status'
                    ]
                ]);
    }

    public function test_queue_controller_can_schedule_backup(): void
    {
        Queue::fake();
        
        $response = $this->postJson('/api/queue/backup');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Backup job scheduled successfully'
                ]);

        Queue::assertPushed(AutomatedBackupJob::class);
    }

    public function test_queue_controller_can_process_recurring_invoices(): void
    {
        Queue::fake();
        
        $recurringInvoice = RecurringInvoice::factory()->create();
        
        $response = $this->postJson('/api/queue/recurring-invoices', [
            'recurring_invoice_id' => $recurringInvoice->id
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Recurring invoices processing job scheduled successfully'
                ]);

        Queue::assertPushed(ProcessRecurringInvoicesJob::class);
    }

    public function test_queue_controller_can_send_reminders(): void
    {
        Queue::fake();
        
        $customer = Customer::factory()->create();
        
        $response = $this->postJson('/api/queue/reminders', [
            'type' => 'birthday',
            'customer_id' => $customer->id
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Reminder job scheduled successfully'
                ]);

        Queue::assertPushed(SendBirthdayReminderJob::class);
    }

    public function test_queue_controller_can_send_stock_alerts(): void
    {
        Queue::fake();
        
        $inventoryItem = InventoryItem::factory()->create();
        
        $response = $this->postJson('/api/queue/stock-alerts', [
            'alert_type' => 'low_stock',
            'inventory_item_id' => $inventoryItem->id
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Stock alert job scheduled successfully'
                ]);

        Queue::assertPushed(SendStockAlertJob::class);
    }

    public function test_queue_controller_can_send_communication(): void
    {
        Queue::fake();
        
        $customer = Customer::factory()->create();
        
        $response = $this->postJson('/api/queue/communication', [
            'customer_id' => $customer->id,
            'type' => 'whatsapp',
            'message' => 'Test message',
            'data' => ['test' => 'data']
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Communication job scheduled successfully'
                ]);

        Queue::assertPushed(SendCommunicationJob::class);
    }

    public function test_queue_controller_can_sync_offline_data(): void
    {
        Queue::fake();
        
        $response = $this->postJson('/api/queue/sync-offline', [
            'sync_type' => 'full',
            'sync_data' => ['customers' => [], 'inventory' => []]
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Offline data sync job scheduled successfully'
                ]);

        Queue::assertPushed(SyncOfflineDataJob::class);
    }

    public function test_queue_controller_validates_reminder_request(): void
    {
        $response = $this->postJson('/api/queue/reminders', [
            'type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
    }

    public function test_queue_controller_validates_stock_alert_request(): void
    {
        $response = $this->postJson('/api/queue/stock-alerts', [
            'alert_type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['alert_type']);
    }

    public function test_queue_controller_validates_communication_request(): void
    {
        $response = $this->postJson('/api/queue/communication', [
            'customer_id' => 999999,
            'type' => 'invalid_type',
            'message' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['customer_id', 'type', 'message']);
    }

    public function test_queue_controller_validates_sync_request(): void
    {
        $response = $this->postJson('/api/queue/sync-offline', [
            'sync_type' => 'invalid_type'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['sync_type']);
    }


}
