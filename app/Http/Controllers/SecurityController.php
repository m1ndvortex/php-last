<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\LoginAnomalyDetectionService;
use App\Services\SessionManagementService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecurityController extends Controller
{
    private TwoFactorAuthService $twoFactorService;
    private SessionManagementService $sessionService;
    private AuditLogService $auditLogService;
    private LoginAnomalyDetectionService $anomalyService;

    public function __construct(
        TwoFactorAuthService $twoFactorService,
        SessionManagementService $sessionService,
        AuditLogService $auditLogService,
        LoginAnomalyDetectionService $anomalyService
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->sessionService = $sessionService;
        $this->auditLogService = $auditLogService;
        $this->anomalyService = $anomalyService;
    }

    /**
     * Enable 2FA
     */
    public function enable2FA(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:sms,totp',
            'phone' => 'required_if:type,sms|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $result = $this->twoFactorService->enable2FA(
            $user,
            $request->type,
            $request->phone
        );

        return response()->json([
            'success' => true,
            'message' => '2FA setup initiated',
            'data' => $result
        ]);
    }

    /**
     * Confirm 2FA setup
     */
    public function confirm2FA(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
            'backup_codes' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $confirmed = $this->twoFactorService->confirm2FA(
            $user,
            $request->code,
            $request->backup_codes
        );

        if ($confirmed) {
            return response()->json([
                'success' => true,
                'message' => '2FA enabled successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid verification code'
        ], 400);
    }

    /**
     * Disable 2FA
     */
    public function disable2FA(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->twoFactorService->disable2FA($user);

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully'
        ]);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $user = $request->user();
        $backupCodes = $this->twoFactorService->regenerateBackupCodes($user);

        return response()->json([
            'success' => true,
            'message' => 'Backup codes regenerated',
            'data' => ['backup_codes' => $backupCodes]
        ]);
    }

    /**
     * Get user's active sessions
     */
    public function getActiveSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessions = $this->sessionService->getUserActiveSessions($user);

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Terminate session
     */
    public function terminateSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $terminated = $this->sessionService->terminateSession($request->session_id);

        if ($terminated) {
            return response()->json([
                'success' => true,
                'message' => 'Session terminated successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Session not found'
        ], 404);
    }

    /**
     * Terminate all other sessions
     */
    public function terminateOtherSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentSessionId = session()->getId();
        
        $terminatedCount = $this->sessionService->terminateOtherSessions($user, $currentSessionId);

        return response()->json([
            'success' => true,
            'message' => "Terminated {$terminatedCount} sessions"
        ]);
    }

    /**
     * Get audit logs
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'event' => 'nullable|string',
            'severity' => 'nullable|in:info,warning,error,critical',
            'auditable_type' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $validator->validated();
        $perPage = $filters['per_page'] ?? 50;
        unset($filters['per_page']);

        $logs = $this->auditLogService->getAuditLogs($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $logs['data'],
            'pagination' => $logs['pagination']
        ]);
    }

    /**
     * Get audit statistics
     */
    public function getAuditStatistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $statistics = $this->auditLogService->getAuditStatistics($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Export audit logs
     */
    public function exportAuditLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,json',
            'user_id' => 'nullable|exists:users,id',
            'event' => 'nullable|string',
            'severity' => 'nullable|in:info,warning,error,critical',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $validator->validated();
        $format = $filters['format'];
        unset($filters['format']);

        $exportData = $this->auditLogService->exportAuditLogs($filters, $format);

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $contentType = $format === 'csv' ? 'text/csv' : 'application/json';

        return response($exportData)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get login anomalies
     */
    public function getLoginAnomalies(Request $request): JsonResponse
    {
        $user = $request->user();
        $anomalies = $this->anomalyService->getUnresolvedAnomalies($user);

        return response()->json([
            'success' => true,
            'data' => $anomalies
        ]);
    }

    /**
     * Get anomaly statistics
     */
    public function getAnomalyStatistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $days = $request->days ?? 30;
        $statistics = $this->anomalyService->getAnomalyStatistics($days);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->sessionService->getSessionStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}