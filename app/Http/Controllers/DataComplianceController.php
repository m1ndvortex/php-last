<?php

namespace App\Http\Controllers;

use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Services\DataComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DataComplianceController extends Controller
{
    private DataComplianceService $complianceService;

    public function __construct(DataComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Get available data types
     */
    public function getDataTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->complianceService->getAvailableDataTypes()
        ]);
    }

    /**
     * Create export request
     */
    public function createExportRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:full_export,partial_export,customer_data',
            'data_types' => 'required|array|min:1',
            'data_types.*' => 'in:customers,invoices,inventory,transactions,audit_logs,user_data',
            'format' => 'required|in:json,csv',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $exportRequest = $this->complianceService->createExportRequest(
            $user,
            $request->type,
            $request->data_types,
            $request->format,
            $request->filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Export request created successfully',
            'data' => $exportRequest
        ], 201);
    }

    /**
     * Get export requests
     */
    public function getExportRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $requests = DataExportRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ]
        ]);
    }

    /**
     * Download export file
     */
    public function downloadExport(DataExportRequest $exportRequest): JsonResponse
    {
        if ($exportRequest->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$exportRequest->isFileAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Export file not available or expired'
            ], 404);
        }

        $downloadUrl = $exportRequest->getDownloadUrl();

        return response()->json([
            'success' => true,
            'data' => ['download_url' => $downloadUrl]
        ]);
    }

    /**
     * Create deletion request
     */
    public function createDeletionRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:full_deletion,partial_deletion,customer_data',
            'data_types' => 'required|array|min:1',
            'data_types.*' => 'in:customers,invoices,inventory,transactions,audit_logs,user_data',
            'reason' => 'required|string|max:500',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $deletionRequest = $this->complianceService->createDeletionRequest(
            $user,
            $request->type,
            $request->data_types,
            $request->reason,
            $request->filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Deletion request created successfully',
            'data' => $deletionRequest
        ], 201);
    }

    /**
     * Get deletion requests
     */
    public function getDeletionRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $requests = DataDeletionRequest::where('user_id', $user->id)
            ->with('approver:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ]
        ]);
    }

    /**
     * Approve deletion request (admin only)
     */
    public function approveDeletionRequest(Request $request, DataDeletionRequest $deletionRequest): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500',
            'scheduled_for' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $approver = $request->user();
        $scheduledFor = $request->scheduled_for ? 
            new \DateTime($request->scheduled_for) : 
            null;

        $deletionRequest->approve($approver, $request->notes, $scheduledFor);

        return response()->json([
            'success' => true,
            'message' => 'Deletion request approved successfully'
        ]);
    }

    /**
     * Reject deletion request (admin only)
     */
    public function rejectDeletionRequest(Request $request, DataDeletionRequest $deletionRequest): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $approver = $request->user();
        $deletionRequest->reject($approver, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Deletion request rejected successfully'
        ]);
    }

    /**
     * Get compliance statistics
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->complianceService->getComplianceStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Process pending export requests (admin/system)
     */
    public function processExportRequests(): JsonResponse
    {
        $pendingRequests = DataExportRequest::pending()->get();
        $processed = 0;
        $failed = 0;

        foreach ($pendingRequests as $request) {
            if ($this->complianceService->processExportRequest($request)) {
                $processed++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Processed {$processed} requests, {$failed} failed"
        ]);
    }

    /**
     * Process approved deletion requests (admin/system)
     */
    public function processDeletionRequests(): JsonResponse
    {
        $readyRequests = DataDeletionRequest::readyToProcess()->get();
        $processed = 0;
        $failed = 0;

        foreach ($readyRequests as $request) {
            if ($this->complianceService->processDeletionRequest($request)) {
                $processed++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Processed {$processed} deletion requests, {$failed} failed"
        ]);
    }
}