<?php

namespace App\Http\Controllers;

use App\Models\StockAudit;
use App\Models\StockAuditItem;
use App\Services\StockAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockAuditController extends Controller
{
    protected StockAuditService $stockAuditService;

    public function __construct(StockAuditService $stockAuditService)
    {
        $this->stockAuditService = $stockAuditService;
    }

    /**
     * Get all stock audits.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockAudit::with(['location', 'auditor']);

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        // Filter by auditor
        if ($request->has('auditor_id')) {
            $query->byAuditor($request->auditor_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        $audits = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }

    /**
     * Create a new stock audit.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'audit_date' => 'nullable|date',
            'auditor_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $audit = $this->stockAuditService->createAudit($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock audit created successfully',
            'data' => $audit->load(['location', 'auditor', 'auditItems.inventoryItem']),
        ], 201);
    }

    /**
     * Get a specific stock audit.
     */
    public function show(StockAudit $stockAudit): JsonResponse
    {
        $audit = $stockAudit->load([
            'location',
            'auditor',
            'auditItems.inventoryItem.category',
            'auditItems.inventoryItem.location'
        ]);

        $summary = $this->stockAuditService->getAuditSummary($stockAudit);

        return response()->json([
            'success' => true,
            'data' => [
                'audit' => $audit,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Start an audit.
     */
    public function start(StockAudit $stockAudit): JsonResponse
    {
        if ($stockAudit->status !== StockAudit::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Audit can only be started from pending status',
            ], 422);
        }

        $audit = $this->stockAuditService->startAudit($stockAudit);

        return response()->json([
            'success' => true,
            'message' => 'Audit started successfully',
            'data' => $audit,
        ]);
    }

    /**
     * Complete an audit.
     */
    public function complete(StockAudit $stockAudit): JsonResponse
    {
        if ($stockAudit->status !== StockAudit::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'Audit can only be completed from in-progress status',
            ], 422);
        }

        $audit = $this->stockAuditService->completeAudit($stockAudit);

        return response()->json([
            'success' => true,
            'message' => 'Audit completed successfully',
            'data' => $audit,
        ]);
    }

    /**
     * Cancel an audit.
     */
    public function cancel(StockAudit $stockAudit): JsonResponse
    {
        if ($stockAudit->status === StockAudit::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a completed audit',
            ], 422);
        }

        $audit = $this->stockAuditService->cancelAudit($stockAudit);

        return response()->json([
            'success' => true,
            'message' => 'Audit cancelled successfully',
            'data' => $audit,
        ]);
    }

    /**
     * Update audit item count.
     */
    public function updateItem(Request $request, StockAudit $stockAudit, StockAuditItem $auditItem): JsonResponse
    {
        if ($stockAudit->status !== StockAudit::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'Can only update items for audits in progress',
            ], 422);
        }

        $validated = $request->validate([
            'physical_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $updatedItem = $this->stockAuditService->updateAuditItemCount(
            $auditItem,
            $validated['physical_quantity'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Audit item updated successfully',
            'data' => $updatedItem->load('inventoryItem'),
        ]);
    }

    /**
     * Bulk update audit items.
     */
    public function bulkUpdate(Request $request, StockAudit $stockAudit): JsonResponse
    {
        if ($stockAudit->status !== StockAudit::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'Can only update items for audits in progress',
            ], 422);
        }

        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'updates.*.physical_quantity' => 'required|numeric|min:0',
            'updates.*.notes' => 'nullable|string',
        ]);

        $this->stockAuditService->bulkUpdateAuditItems($stockAudit, $validated['updates']);

        return response()->json([
            'success' => true,
            'message' => 'Audit items updated successfully',
        ]);
    }

    /**
     * Get variance report for an audit.
     */
    public function varianceReport(StockAudit $stockAudit): JsonResponse
    {
        $variances = $this->stockAuditService->getVarianceReport($stockAudit);

        return response()->json([
            'success' => true,
            'data' => $variances,
        ]);
    }

    /**
     * Get uncounted items for an audit.
     */
    public function uncountedItems(StockAudit $stockAudit): JsonResponse
    {
        $uncountedItems = $this->stockAuditService->getUncountedItems($stockAudit);

        return response()->json([
            'success' => true,
            'data' => $uncountedItems,
        ]);
    }

    /**
     * Export audit results.
     */
    public function export(StockAudit $stockAudit): JsonResponse
    {
        $exportData = $this->stockAuditService->exportAuditResults($stockAudit);

        return response()->json([
            'success' => true,
            'data' => $exportData,
        ]);
    }

    /**
     * Delete a stock audit.
     */
    public function destroy(StockAudit $stockAudit): JsonResponse
    {
        if ($stockAudit->status === StockAudit::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a completed audit',
            ], 422);
        }

        $stockAudit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock audit deleted successfully',
        ]);
    }
}
