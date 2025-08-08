<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\PDFGenerationService;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    protected $invoiceService;
    protected $pdfService;

    public function __construct(InvoiceService $invoiceService, PDFGenerationService $pdfService)
    {
        $this->invoiceService = $invoiceService;
        $this->pdfService = $pdfService;
    }

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'start_date', 'end_date', 'customer_id', 
                'language', 'tags', 'search', 'sort_by', 'sort_order', 'per_page',
                'main_category_id', 'category_id', 'gold_purity_min', 'gold_purity_max'
            ]);

            $invoices = $this->invoiceService->getInvoicesWithFilters($filters);

            return response()->json([
                'success' => true,
                'data' => $invoices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category-based invoice statistics.
     */
    public function getCategoryStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'status']);
            $stats = $this->invoiceService->getCategoryBasedStats($filters);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get gold purity distribution in invoices.
     */
    public function getGoldPurityStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'category_id', 'main_category_id']);
            $stats = $this->invoiceService->getGoldPurityStats($filters);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve gold purity statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        try {
            $invoice->load(['customer', 'items', 'tags', 'template', 'attachments']);

            return response()->json([
                'success' => true,
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            $updatedInvoice = $this->invoiceService->updateInvoice($invoice, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $updatedInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        try {
            $this->invoiceService->deleteInvoice($invoice);

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate an invoice.
     */
    public function duplicate(Invoice $invoice, Request $request): JsonResponse
    {
        try {
            $overrides = $request->only(['customer_id', 'issue_date', 'due_date', 'language']);
            $duplicatedInvoice = $this->invoiceService->duplicateInvoice($invoice, $overrides);

            return response()->json([
                'success' => true,
                'message' => 'Invoice duplicated successfully',
                'data' => $duplicatedInvoice,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate PDF for an invoice.
     */
    public function generatePDF(Invoice $invoice): JsonResponse
    {
        try {
            $result = $this->pdfService->generateInvoicePDF($invoice);

            return response()->json([
                'success' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'pdf_url' => $result['url'],
                    'pdf_path' => $result['path'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download PDF for an invoice.
     */
    public function downloadPDF(Invoice $invoice)
    {
        try {
            // Generate PDF if not exists
            if (!$invoice->pdf_path || !Storage::exists($invoice->pdf_path)) {
                $this->pdfService->generateInvoicePDF($invoice);
                $invoice->refresh();
            }

            if (!Storage::exists($invoice->pdf_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF file not found',
                ], 404);
            }

            $filename = "invoice_{$invoice->invoice_number}.pdf";
            
            return Storage::download($invoice->pdf_path, $filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): JsonResponse
    {
        try {
            $updatedInvoice = $this->invoiceService->markAsSent($invoice);

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as sent',
                'data' => $updatedInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as sent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark invoice as paid with payment data.
     */
    public function markAsPaid(Invoice $invoice, Request $request): JsonResponse
    {
        try {
            $paymentData = $request->validate([
                'payment_method' => 'nullable|string|in:cash,card,bank_transfer,check,other',
                'amount' => 'nullable|numeric|min:0',
                'payment_date' => 'nullable|date',
                'transaction_id' => 'nullable|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            $updatedInvoice = $this->invoiceService->markAsPaid($invoice, $paymentData);

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as paid',
                'data' => $updatedInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as paid',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Invoice $invoice, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $cancelledInvoice = $this->invoiceService->cancelInvoice($invoice, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Invoice cancelled successfully',
                'data' => $cancelledInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process overdue invoices.
     */
    public function processOverdue(): JsonResponse
    {
        try {
            $processed = $this->invoiceService->processOverdueInvoices();

            return response()->json([
                'success' => true,
                'message' => "Processed {$processed} overdue invoices",
                'data' => ['processed_count' => $processed],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process overdue invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate batch PDFs.
     */
    public function generateBatchPDFs(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'invoice_ids' => 'required|array',
                'invoice_ids.*' => 'exists:invoices,id',
            ]);

            $results = $this->pdfService->generateBatchPDFs($request->invoice_ids);

            return response()->json([
                'success' => true,
                'message' => 'Batch PDF generation completed',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate batch PDFs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download batch PDFs as ZIP.
     */
    public function downloadBatchPDFs(Request $request)
    {
        try {
            $request->validate([
                'invoice_ids' => 'required|array',
                'invoice_ids.*' => 'exists:invoices,id',
            ]);

            $zipResult = $this->pdfService->createBatchPDFZip($request->invoice_ids);

            return response()->download($zipResult['path'], $zipResult['filename'])->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create batch PDF ZIP',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add attachment to invoice.
     */
    public function addAttachment(Invoice $invoice, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
            ]);

            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('invoices/attachments', $filename);

            $attachment = $invoice->attachments()->create([
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attachment added successfully',
                'data' => $attachment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add attachment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove attachment from invoice.
     */
    public function removeAttachment(Invoice $invoice, $attachmentId): JsonResponse
    {
        try {
            $attachment = $invoice->attachments()->findOrFail($attachmentId);
            
            // Delete file from storage
            if (Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }
            
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attachment removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove attachment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}