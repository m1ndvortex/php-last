<?php

namespace App\Jobs;

use App\Services\BatchOperationService;
use App\Models\BatchOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessBatchOperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $batchOperationId;
    protected string $operationType;
    protected array $data;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(int $batchOperationId, string $operationType, array $data, array $options = [])
    {
        $this->batchOperationId = $batchOperationId;
        $this->operationType = $operationType;
        $this->data = $data;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(BatchOperationService $batchService): void
    {
        try {
            Log::info('Starting batch operation job', [
                'batch_id' => $this->batchOperationId,
                'type' => $this->operationType,
                'data_count' => count($this->data)
            ]);

            switch ($this->operationType) {
                case 'invoice_generation':
                    $this->processInvoiceGeneration($batchService);
                    break;
                case 'pdf_generation':
                    $this->processPDFGeneration($batchService);
                    break;
                case 'communication_sending':
                    $this->processCommunicationSending($batchService);
                    break;
                default:
                    throw new Exception("Unknown batch operation type: {$this->operationType}");
            }

            Log::info('Batch operation job completed successfully', [
                'batch_id' => $this->batchOperationId,
                'type' => $this->operationType
            ]);

        } catch (Exception $e) {
            Log::error('Batch operation job failed', [
                'batch_id' => $this->batchOperationId,
                'type' => $this->operationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update batch operation status to failed
            $this->markBatchAsFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * Process invoice generation batch operation
     */
    protected function processInvoiceGeneration(BatchOperationService $batchService): void
    {
        $customerIds = $this->data;
        $options = $this->options;

        // Update existing batch operation instead of creating new one
        $batchOperation = BatchOperation::findOrFail($this->batchOperationId);
        
        // Process the batch operation
        $batchService->processBatchInvoices($customerIds, $options);
    }

    /**
     * Process PDF generation batch operation
     */
    protected function processPDFGeneration(BatchOperationService $batchService): void
    {
        $invoiceIds = $this->data;
        $options = $this->options;

        $batchService->processBatchPDFGeneration($invoiceIds, $options);
    }

    /**
     * Process communication sending batch operation
     */
    protected function processCommunicationSending(BatchOperationService $batchService): void
    {
        $invoiceIds = $this->data;
        $method = $this->options['method'] ?? 'email';
        $communicationOptions = $this->options['communication_options'] ?? [];

        $batchService->processBatchCommunication($invoiceIds, $method, $communicationOptions);
    }

    /**
     * Mark batch operation as failed
     */
    protected function markBatchAsFailed(string $errorMessage): void
    {
        try {
            BatchOperation::where('id', $this->batchOperationId)
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $errorMessage
                ]);
        } catch (Exception $e) {
            Log::error('Failed to update batch operation status', [
                'batch_id' => $this->batchOperationId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('Batch operation job failed permanently', [
            'batch_id' => $this->batchOperationId,
            'type' => $this->operationType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->markBatchAsFailed($exception->getMessage());
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'batch-operation',
            "batch-{$this->batchOperationId}",
            "type-{$this->operationType}"
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30s, 60s, then 120s
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2); // Give up after 2 hours
    }
}