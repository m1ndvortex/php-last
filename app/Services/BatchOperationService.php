<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\BatchOperation;
use App\Models\BatchOperationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class BatchOperationService
{
    protected InvoiceService $invoiceService;
    protected PDFGenerationService $pdfService;
    protected CommunicationService $communicationService;

    public function __construct(
        InvoiceService $invoiceService,
        PDFGenerationService $pdfService,
        CommunicationService $communicationService
    ) {
        $this->invoiceService = $invoiceService;
        $this->pdfService = $pdfService;
        $this->communicationService = $communicationService;
    }

    /**
     * Process batch invoice creation
     */
    public function processBatchInvoices(array $customerIds, array $options): BatchOperation
    {
        $batchOperation = $this->createBatchOperation('invoice_generation', [
            'customer_count' => count($customerIds),
            'options' => $options
        ]);

        try {
            DB::transaction(function () use ($customerIds, $options, $batchOperation) {
                $successCount = 0;
                $errorCount = 0;

                foreach ($customerIds as $customerId) {
                    try {
                        $customer = Customer::findOrFail($customerId);
                        
                        // Prepare invoice data based on options
                        $invoiceData = $this->prepareInvoiceData($customer, $options);
                        
                        // Create invoice
                        $invoice = $this->invoiceService->createInvoice($invoiceData);
                        
                        // Create batch operation item
                        $batchItem = $batchOperation->items()->create([
                            'reference_type' => 'invoice',
                            'reference_id' => $invoice->id,
                            'customer_id' => $customerId,
                            'status' => 'completed',
                            'data' => [
                                'invoice_number' => $invoice->invoice_number,
                                'total_amount' => $invoice->total_amount
                            ]
                        ]);

                        // Generate PDF if requested
                        if ($options['generate_pdf'] ?? false) {
                            $this->generateBatchPDF($invoice, $batchItem);
                        }

                        // Send communication if requested
                        if ($options['send_immediately'] ?? false) {
                            $this->sendBatchCommunication($invoice, $batchItem, $options);
                        }

                        $successCount++;
                        
                        // Update progress
                        $this->updateBatchProgress($batchOperation, $successCount + $errorCount, count($customerIds));

                    } catch (Exception $e) {
                        $errorCount++;
                        
                        // Log error and create failed batch item
                        $batchOperation->items()->create([
                            'reference_type' => 'invoice',
                            'reference_id' => null,
                            'customer_id' => Customer::find($customerId) ? $customerId : null,
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'data' => ['customer_id' => $customerId]
                        ]);

                        Log::error('Batch invoice creation failed', [
                            'customer_id' => $customerId,
                            'batch_id' => $batchOperation->id,
                            'error' => $e->getMessage()
                        ]);

                        // Update progress
                        $this->updateBatchProgress($batchOperation, $successCount + $errorCount, count($customerIds));
                    }
                }

                // Update final batch status
                $batchOperation->update([
                    'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
                    'completed_at' => now(),
                    'summary' => [
                        'total_processed' => count($customerIds),
                        'successful' => $successCount,
                        'failed' => $errorCount,
                        'success_rate' => round(($successCount / count($customerIds)) * 100, 2)
                    ]
                ]);
            });

        } catch (Exception $e) {
            $batchOperation->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            Log::error('Batch operation failed completely', [
                'batch_id' => $batchOperation->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }

        return $batchOperation->fresh(['items']);
    }

    /**
     * Process batch PDF generation
     */
    public function processBatchPDFGeneration(array $invoiceIds, array $options = []): BatchOperation
    {
        $batchOperation = $this->createBatchOperation('pdf_generation', [
            'invoice_count' => count($invoiceIds),
            'options' => $options
        ]);

        try {
            $successCount = 0;
            $errorCount = 0;
            $generatedFiles = [];

            foreach ($invoiceIds as $invoiceId) {
                try {
                    $invoice = Invoice::with(['customer', 'items.inventoryItem'])->findOrFail($invoiceId);
                    
                    // Generate PDF
                    $pdfPath = $this->pdfService->generateInvoicePDF($invoice, $options);
                    
                    // Update invoice with PDF path
                    $invoice->update(['pdf_path' => $pdfPath]);
                    
                    $generatedFiles[] = $pdfPath;
                    
                    // Create batch operation item
                    $batchOperation->items()->create([
                        'reference_type' => 'invoice',
                        'reference_id' => $invoice->id,
                        'customer_id' => $invoice->customer_id,
                        'status' => 'completed',
                        'data' => [
                            'invoice_number' => $invoice->invoice_number,
                            'pdf_path' => $pdfPath,
                            'file_size' => filesize(storage_path('app/' . $pdfPath))
                        ]
                    ]);

                    $successCount++;

                } catch (Exception $e) {
                    $errorCount++;
                    
                    $batchOperation->items()->create([
                        'reference_type' => 'invoice',
                        'reference_id' => $invoiceId,
                        'customer_id' => null,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'data' => ['invoice_id' => $invoiceId]
                    ]);

                    Log::error('Batch PDF generation failed', [
                        'invoice_id' => $invoiceId,
                        'batch_id' => $batchOperation->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Update progress
                $this->updateBatchProgress($batchOperation, $successCount + $errorCount, count($invoiceIds));
            }

            // Create combined PDF if requested
            if ($options['create_combined_pdf'] ?? false && !empty($generatedFiles)) {
                $combinedPdfPath = $this->createCombinedPDF($generatedFiles, $batchOperation->id);
                $batchOperation->update(['combined_file_path' => $combinedPdfPath]);
            }

            // Update final status
            $batchOperation->update([
                'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'summary' => [
                    'total_processed' => count($invoiceIds),
                    'successful' => $successCount,
                    'failed' => $errorCount,
                    'generated_files' => count($generatedFiles),
                    'success_rate' => round(($successCount / count($invoiceIds)) * 100, 2)
                ]
            ]);

        } catch (Exception $e) {
            $batchOperation->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }

        return $batchOperation->fresh(['items']);
    }

    /**
     * Process batch communication sending
     */
    public function processBatchCommunication(array $invoiceIds, string $method, array $options = []): BatchOperation
    {
        $batchOperation = $this->createBatchOperation('communication_sending', [
            'invoice_count' => count($invoiceIds),
            'method' => $method,
            'options' => $options
        ]);

        try {
            $successCount = 0;
            $errorCount = 0;

            foreach ($invoiceIds as $invoiceId) {
                try {
                    $invoice = Invoice::with('customer')->findOrFail($invoiceId);
                    
                    // Send communication based on method
                    $result = $this->sendCommunicationByMethod($invoice, $method, $options);
                    
                    // Create batch operation item
                    $batchOperation->items()->create([
                        'reference_type' => 'invoice',
                        'reference_id' => $invoice->id,
                        'customer_id' => $invoice->customer_id,
                        'status' => 'completed',
                        'data' => [
                            'invoice_number' => $invoice->invoice_number,
                            'method' => $method,
                            'recipient' => $result['recipient'] ?? null,
                            'sent_at' => now()->toISOString()
                        ]
                    ]);

                    $successCount++;

                } catch (Exception $e) {
                    $errorCount++;
                    
                    $batchOperation->items()->create([
                        'reference_type' => 'invoice',
                        'reference_id' => $invoiceId,
                        'customer_id' => null,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'data' => [
                            'invoice_id' => $invoiceId,
                            'method' => $method
                        ]
                    ]);

                    Log::error('Batch communication sending failed', [
                        'invoice_id' => $invoiceId,
                        'method' => $method,
                        'batch_id' => $batchOperation->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Update progress
                $this->updateBatchProgress($batchOperation, $successCount + $errorCount, count($invoiceIds));
            }

            // Update final status
            $batchOperation->update([
                'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'summary' => [
                    'total_processed' => count($invoiceIds),
                    'successful' => $successCount,
                    'failed' => $errorCount,
                    'method' => $method,
                    'success_rate' => round(($successCount / count($invoiceIds)) * 100, 2)
                ]
            ]);

        } catch (Exception $e) {
            $batchOperation->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }

        return $batchOperation->fresh(['items']);
    }

    /**
     * Get batch operation status and progress
     */
    public function getBatchOperationStatus(int $batchId): array
    {
        $batch = BatchOperation::with('items')->findOrFail($batchId);
        
        return [
            'id' => $batch->id,
            'type' => $batch->type,
            'status' => $batch->status,
            'progress' => $batch->progress,
            'created_at' => $batch->created_at,
            'completed_at' => $batch->completed_at,
            'summary' => $batch->summary,
            'error_message' => $batch->error_message,
            'items' => $batch->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'reference_type' => $item->reference_type,
                    'reference_id' => $item->reference_id,
                    'customer_id' => $item->customer_id,
                    'status' => $item->status,
                    'error_message' => $item->error_message,
                    'data' => $item->data
                ];
            })
        ];
    }

    /**
     * Get batch operation history
     */
    public function getBatchOperationHistory(array $filters = [])
    {
        $query = BatchOperation::with('items')
            ->orderBy('created_at', 'desc');

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Create a new batch operation record
     */
    protected function createBatchOperation(string $type, array $metadata): BatchOperation
    {
        return BatchOperation::create([
            'type' => $type,
            'status' => 'processing',
            'progress' => 0,
            'metadata' => $metadata,
            'created_by' => auth()->id(),
            'started_at' => now()
        ]);
    }

    /**
     * Update batch operation progress
     */
    protected function updateBatchProgress(BatchOperation $batch, int $processed, int $total): void
    {
        $progress = round(($processed / $total) * 100, 2);
        
        $batch->update([
            'progress' => $progress,
            'processed_count' => $processed,
            'total_count' => $total
        ]);
    }

    /**
     * Prepare invoice data for batch creation
     */
    protected function prepareInvoiceData(Customer $customer, array $options): array
    {
        $baseData = [
            'customer_id' => $customer->id,
            'language' => $options['language'] ?? $customer->preferred_language ?? 'en',
            'due_days' => $options['due_days'] ?? 30,
            'notes' => $options['notes'] ?? null,
            'template_id' => $options['template_id'] ?? null
        ];

        // Add items based on options
        if (isset($options['template_items'])) {
            $baseData['items'] = $options['template_items'];
        } elseif (isset($options['pending_orders']) && $options['pending_orders']) {
            // Get pending orders for customer
            $baseData['items'] = $this->getPendingOrderItems($customer->id);
        } else {
            // Use default items from options
            $baseData['items'] = $options['items'] ?? [];
        }

        return $baseData;
    }

    /**
     * Generate PDF for batch item
     */
    protected function generateBatchPDF(Invoice $invoice, BatchOperationItem $batchItem): void
    {
        try {
            $pdfPath = $this->pdfService->generateInvoicePDF($invoice);
            $invoice->update(['pdf_path' => $pdfPath]);
            
            // Update batch item with PDF info
            $data = $batchItem->data;
            $data['pdf_path'] = $pdfPath;
            $data['pdf_generated_at'] = now()->toISOString();
            $batchItem->update(['data' => $data]);
            
        } catch (Exception $e) {
            Log::error('Batch PDF generation failed', [
                'invoice_id' => $invoice->id,
                'batch_item_id' => $batchItem->id,
                'error' => $e->getMessage()
            ]);
            
            // Update batch item with error
            $data = $batchItem->data;
            $data['pdf_error'] = $e->getMessage();
            $batchItem->update(['data' => $data]);
        }
    }

    /**
     * Send communication for batch item
     */
    protected function sendBatchCommunication(Invoice $invoice, BatchOperationItem $batchItem, array $options): void
    {
        try {
            $method = $options['communication_method'] ?? 'email';
            $result = $this->sendCommunicationByMethod($invoice, $method, $options);
            
            // Update batch item with communication info
            $data = $batchItem->data;
            $data['communication_sent'] = true;
            $data['communication_method'] = $method;
            $data['communication_result'] = $result;
            $data['sent_at'] = now()->toISOString();
            $batchItem->update(['data' => $data]);
            
        } catch (Exception $e) {
            Log::error('Batch communication sending failed', [
                'invoice_id' => $invoice->id,
                'batch_item_id' => $batchItem->id,
                'error' => $e->getMessage()
            ]);
            
            // Update batch item with error
            $data = $batchItem->data;
            $data['communication_error'] = $e->getMessage();
            $batchItem->update(['data' => $data]);
        }
    }

    /**
     * Send communication by specified method
     */
    protected function sendCommunicationByMethod(Invoice $invoice, string $method, array $options): array
    {
        switch ($method) {
            case 'email':
                return $this->communicationService->sendInvoiceEmail($invoice, $options);
            case 'sms':
                return $this->communicationService->sendInvoiceSMS($invoice, $options);
            case 'whatsapp':
                return $this->communicationService->sendInvoiceWhatsApp($invoice, $options);
            default:
                throw new Exception("Unsupported communication method: {$method}");
        }
    }

    /**
     * Get pending order items for customer
     */
    protected function getPendingOrderItems(int $customerId): array
    {
        // This would typically fetch from a pending orders table
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Create combined PDF from multiple files
     */
    protected function createCombinedPDF(array $pdfPaths, int $batchId): string
    {
        // This would use a PDF library to combine multiple PDFs
        // For now, return a placeholder path
        $combinedPath = "batch_pdfs/combined_batch_{$batchId}.pdf";
        
        // TODO: Implement actual PDF combination logic
        Log::info('Combined PDF creation requested', [
            'batch_id' => $batchId,
            'file_count' => count($pdfPaths),
            'output_path' => $combinedPath
        ]);
        
        return $combinedPath;
    }
}