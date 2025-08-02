<?php

namespace App\Http\Controllers;

use App\Models\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use App\Http\Requests\StoreRecurringInvoiceRequest;
use App\Http\Requests\UpdateRecurringInvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecurringInvoiceController extends Controller
{
    protected $recurringInvoiceService;

    public function __construct(RecurringInvoiceService $recurringInvoiceService)
    {
        $this->recurringInvoiceService = $recurringInvoiceService;
    }

    /**
     * Display a listing of recurring invoices.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'customer_id', 'active', 'frequency', 'language', 
                'due_soon', 'search', 'sort_by', 'sort_order', 'per_page'
            ]);

            $recurringInvoices = $this->recurringInvoiceService->getRecurringInvoicesWithFilters($filters);

            return response()->json([
                'success' => true,
                'data' => $recurringInvoices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recurring invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created recurring invoice.
     */
    public function store(StoreRecurringInvoiceRequest $request): JsonResponse
    {
        try {
            $recurringInvoice = $this->recurringInvoiceService->createRecurringInvoice($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Recurring invoice created successfully',
                'data' => $recurringInvoice,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create recurring invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified recurring invoice.
     */
    public function show(RecurringInvoice $recurringInvoice): JsonResponse
    {
        try {
            $recurringInvoice->load(['customer', 'template']);

            return response()->json([
                'success' => true,
                'data' => $recurringInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recurring invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified recurring invoice.
     */
    public function update(UpdateRecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        try {
            $updatedRecurringInvoice = $this->recurringInvoiceService->updateRecurringInvoice(
                $recurringInvoice, 
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Recurring invoice updated successfully',
                'data' => $updatedRecurringInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update recurring invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified recurring invoice.
     */
    public function destroy(RecurringInvoice $recurringInvoice): JsonResponse
    {
        try {
            $this->recurringInvoiceService->deleteRecurringInvoice($recurringInvoice);

            return response()->json([
                'success' => true,
                'message' => 'Recurring invoice deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recurring invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate invoice from recurring invoice.
     */
    public function generateInvoice(RecurringInvoice $recurringInvoice): JsonResponse
    {
        try {
            $result = $this->recurringInvoiceService->generateInvoiceFromRecurring($recurringInvoice);

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause a recurring invoice.
     */
    public function pause(RecurringInvoice $recurringInvoice): JsonResponse
    {
        try {
            $pausedInvoice = $this->recurringInvoiceService->pauseRecurringInvoice($recurringInvoice);

            return response()->json([
                'success' => true,
                'message' => 'Recurring invoice paused successfully',
                'data' => $pausedInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause recurring invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume a recurring invoice.
     */
    public function resume(RecurringInvoice $recurringInvoice): JsonResponse
    {
        try {
            $resumedInvoice = $this->recurringInvoiceService->resumeRecurringInvoice($recurringInvoice);

            return response()->json([
                'success' => true,
                'message' => 'Recurring invoice resumed successfully',
                'data' => $resumedInvoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume recurring invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process all due recurring invoices.
     */
    public function processDue(): JsonResponse
    {
        try {
            $results = $this->recurringInvoiceService->processDueRecurringInvoices();

            return response()->json([
                'success' => true,
                'message' => 'Due recurring invoices processed',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process due recurring invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upcoming recurring invoices.
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $upcomingInvoices = $this->recurringInvoiceService->getUpcomingInvoices($days);

            return response()->json([
                'success' => true,
                'data' => $upcomingInvoices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recurring invoice statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->recurringInvoiceService->getRecurringInvoiceStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
