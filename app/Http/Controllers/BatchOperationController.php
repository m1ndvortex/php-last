<?php

namespace App\Http\Controllers;

use App\Services\BatchOperationService;
use App\Models\BatchOperation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Exception;

class BatchOperationController extends Controller
{
    protected BatchOperationService $batchService;

    public function __construct(BatchOperationService $batchService)
    {
        $this->batchService = $batchService;
    }

    /**
     * Get batch operations history
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->validate([
                'type' => ['nullable', Rule::in(['invoice_generation', 'pdf_generation', 'communication_sending'])],
                'status' => ['nullable', Rule::in(['processing', 'completed', 'completed_with_errors', 'failed'])],
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $operations = $this->batchService->getBatchOperationHistory($filters);

            return response()->json([
                'success' => true,
                'data' => $operations
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batch operations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific batch operation details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $operation = $this->batchService->getBatchOperationStatus($id);

            return response()->json([
                'success' => true,
                'data' => $operation
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch operation not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create batch invoices
     */
    public function createBatchInvoices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'required|integer|exists:customers,id',
            'options' => 'required|array',
            'options.language' => ['nullable', Rule::in(['en', 'fa'])],
            'options.due_days' => 'nullable|integer|min:1|max:365',
            'options.notes' => 'nullable|string|max:1000',
            'options.template_id' => 'nullable|integer|exists:invoice_templates,id',
            'options.generate_pdf' => 'nullable|boolean',
            'options.send_immediately' => 'nullable|boolean',
            'options.communication_method' => ['nullable', Rule::in(['email', 'sms', 'whatsapp'])],
            'options.items' => 'nullable|array',
            'options.items.*.inventory_item_id' => 'required_with:options.items|integer|exists:inventory_items,id',
            'options.items.*.quantity' => 'required_with:options.items|numeric|min:0.001',
            'options.items.*.unit_price' => 'nullable|numeric|min:0'
        ]);

        try {
            $batchOperation = $this->batchService->processBatchInvoices(
                $validated['customer_ids'],
                $validated['options']
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch invoice creation started',
                'data' => [
                    'batch_id' => $batchOperation->id,
                    'status' => $batchOperation->status,
                    'progress' => $batchOperation->progress,
                    'total_customers' => count($validated['customer_ids'])
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start batch invoice creation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate batch PDFs
     */
    public function generateBatchPDFs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|integer|exists:invoices,id',
            'options' => 'nullable|array',
            'options.template_id' => 'nullable|integer|exists:invoice_templates,id',
            'options.language' => ['nullable', Rule::in(['en', 'fa'])],
            'options.create_combined_pdf' => 'nullable|boolean'
        ]);

        try {
            $batchOperation = $this->batchService->processBatchPDFGeneration(
                $validated['invoice_ids'],
                $validated['options'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch PDF generation started',
                'data' => [
                    'batch_id' => $batchOperation->id,
                    'status' => $batchOperation->status,
                    'progress' => $batchOperation->progress,
                    'total_invoices' => count($validated['invoice_ids'])
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start batch PDF generation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send batch communications
     */
    public function sendBatchCommunications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|integer|exists:invoices,id',
            'method' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
            'options' => 'nullable|array',
            'options.subject' => 'nullable|string|max:255',
            'options.message' => 'nullable|string|max:2000',
            'options.template_id' => 'nullable|integer|exists:message_templates,id',
            'options.include_pdf' => 'nullable|boolean',
            'options.language' => ['nullable', Rule::in(['en', 'fa'])]
        ]);

        try {
            $batchOperation = $this->batchService->processBatchCommunication(
                $validated['invoice_ids'],
                $validated['method'],
                $validated['options'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch communication sending started',
                'data' => [
                    'batch_id' => $batchOperation->id,
                    'status' => $batchOperation->status,
                    'progress' => $batchOperation->progress,
                    'method' => $validated['method'],
                    'total_invoices' => count($validated['invoice_ids'])
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start batch communication sending',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a batch operation
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $batchOperation = BatchOperation::findOrFail($id);

            if ($batchOperation->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel completed batch operation'
                ], 400);
            }

            $batchOperation->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Operation cancelled by user'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Batch operation cancelled successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel batch operation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry failed items in a batch operation
     */
    public function retryFailed(int $id): JsonResponse
    {
        try {
            $batchOperation = BatchOperation::with('failedItems')->findOrFail($id);

            if ($batchOperation->isInProgress()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot retry items while batch operation is in progress'
                ], 400);
            }

            $failedItems = $batchOperation->failedItems;

            if ($failedItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No failed items to retry'
                ], 400);
            }

            // Create new batch operation for retry
            $retryData = [];
            foreach ($failedItems as $item) {
                if ($item->reference_type === 'invoice' && $item->customer_id) {
                    $retryData[] = $item->customer_id;
                }
            }

            if (empty($retryData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid items to retry'
                ], 400);
            }

            // Use original batch operation metadata for retry
            $originalOptions = $batchOperation->metadata['options'] ?? [];
            
            $newBatchOperation = $this->batchService->processBatchInvoices($retryData, $originalOptions);

            return response()->json([
                'success' => true,
                'message' => 'Retry batch operation started',
                'data' => [
                    'original_batch_id' => $id,
                    'retry_batch_id' => $newBatchOperation->id,
                    'retry_count' => count($retryData)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry batch operation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download combined PDF file
     */
    public function downloadCombinedPDF(int $id): JsonResponse
    {
        try {
            $batchOperation = BatchOperation::findOrFail($id);

            if (!$batchOperation->combined_file_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'No combined PDF file available'
                ], 404);
            }

            $filePath = storage_path('app/' . $batchOperation->combined_file_path);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combined PDF file not found'
                ], 404);
            }

            return response()->download($filePath, "batch_invoices_{$id}.pdf");

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download combined PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch operation statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_operations' => BatchOperation::count(),
                'operations_today' => BatchOperation::whereDate('created_at', today())->count(),
                'operations_this_week' => BatchOperation::where('created_at', '>=', now()->startOfWeek())->count(),
                'operations_this_month' => BatchOperation::where('created_at', '>=', now()->startOfMonth())->count(),
                'by_type' => BatchOperation::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'by_status' => BatchOperation::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'recent_operations' => BatchOperation::with('creator:id,name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'type', 'status', 'progress', 'created_by', 'created_at'])
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}