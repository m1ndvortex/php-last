<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AuditTrailService
{
    /**
     * Create comprehensive audit log entry
     */
    public function logActivity(
        $model, 
        string $action, 
        ?array $oldValues = null, 
        ?array $newValues = null, 
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log transaction approval/rejection
     */
    public function logTransactionApproval(Transaction $transaction, string $action, ?string $comments = null): AuditLog
    {
        $metadata = [
            'transaction_amount' => $transaction->total_amount,
            'transaction_type' => $transaction->type,
            'approval_comments' => $comments,
            'approval_level' => $this->getCurrentApprovalLevel($transaction),
        ];

        return $this->logActivity($transaction, $action, null, null, $metadata);
    }

    /**
     * Generate comprehensive audit report
     */
    public function generateAuditReport(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $query = AuditLog::whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        $auditLogs = $query->with(['user', 'auditable'])
            ->orderBy('created_at', 'desc')
            ->get();

        $report = [
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'generated_at' => now()->toISOString(),
            'total_activities' => $auditLogs->count(),
            'summary' => [
                'by_action' => [],
                'by_user' => [],
                'by_model' => [],
                'by_day' => [],
            ],
            'activities' => [],
            'security_events' => [],
            'compliance_notes' => [],
        ];

        // Process audit logs
        foreach ($auditLogs as $log) {
            // Summary by action
            $action = $log->action;
            if (!isset($report['summary']['by_action'][$action])) {
                $report['summary']['by_action'][$action] = 0;
            }
            $report['summary']['by_action'][$action]++;

            // Summary by user
            $userName = $log->user ? $log->user->name : 'System';
            if (!isset($report['summary']['by_user'][$userName])) {
                $report['summary']['by_user'][$userName] = 0;
            }
            $report['summary']['by_user'][$userName]++;

            // Summary by model
            $modelType = class_basename($log->auditable_type);
            if (!isset($report['summary']['by_model'][$modelType])) {
                $report['summary']['by_model'][$modelType] = 0;
            }
            $report['summary']['by_model'][$modelType]++;

            // Summary by day
            $day = $log->created_at->toDateString();
            if (!isset($report['summary']['by_day'][$day])) {
                $report['summary']['by_day'][$day] = 0;
            }
            $report['summary']['by_day'][$day]++;

            // Add to activities
            $report['activities'][] = [
                'id' => $log->id,
                'timestamp' => $log->created_at->toISOString(),
                'user' => $userName,
                'action' => $log->action,
                'model_type' => $modelType,
                'model_id' => $log->auditable_id,
                'description' => $this->generateActivityDescription($log),
                'ip_address' => $log->ip_address,
                'changes' => $this->formatChanges($log),
                'metadata' => $log->metadata ? json_decode($log->metadata, true) : null,
            ];

            // Identify security events
            if ($this->isSecurityEvent($log)) {
                $report['security_events'][] = [
                    'timestamp' => $log->created_at->toISOString(),
                    'user' => $userName,
                    'action' => $log->action,
                    'ip_address' => $log->ip_address,
                    'description' => $this->generateSecurityEventDescription($log),
                    'severity' => $this->getSecurityEventSeverity($log),
                ];
            }
        }

        // Add compliance notes
        $report['compliance_notes'] = $this->generateComplianceNotes($auditLogs);

        return $report;
    }

    /**
     * Create approval workflow
     */
    public function createApprovalWorkflow(string $workflowType, array $steps, array $conditions = []): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflowType, $steps, $conditions) {
            $workflow = ApprovalWorkflow::create([
                'name' => $workflowType,
                'description' => "Approval workflow for {$workflowType}",
                'type' => $workflowType,
                'conditions' => json_encode($conditions),
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            foreach ($steps as $index => $stepData) {
                ApprovalStep::create([
                    'workflow_id' => $workflow->id,
                    'step_order' => $index + 1,
                    'name' => $stepData['name'],
                    'description' => $stepData['description'] ?? null,
                    'approver_type' => $stepData['approver_type'], // user, role, amount_based
                    'approver_id' => $stepData['approver_id'] ?? null,
                    'required_approvals' => $stepData['required_approvals'] ?? 1,
                    'conditions' => json_encode($stepData['conditions'] ?? []),
                    'is_parallel' => $stepData['is_parallel'] ?? false,
                    'timeout_hours' => $stepData['timeout_hours'] ?? 72,
                ]);
            }

            return $workflow;
        });
    }

    /**
     * Process approval request
     */
    public function processApprovalRequest($model, string $workflowType): array
    {
        $workflow = ApprovalWorkflow::where('type', $workflowType)
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            return [
                'requires_approval' => false,
                'message' => 'No approval workflow found',
            ];
        }

        // Check if model meets workflow conditions
        if (!$this->meetsWorkflowConditions($model, $workflow)) {
            return [
                'requires_approval' => false,
                'message' => 'Model does not meet workflow conditions',
            ];
        }

        // Create approval request
        $approvalRequest = $model->approvalRequests()->create([
            'workflow_id' => $workflow->id,
            'status' => 'pending',
            'current_step' => 1,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
        ]);

        // Start first approval step
        $firstStep = $workflow->steps()->where('step_order', 1)->first();
        $this->initiateApprovalStep($approvalRequest, $firstStep);

        $this->logActivity($model, 'approval_requested', null, null, [
            'workflow_id' => $workflow->id,
            'approval_request_id' => $approvalRequest->id,
        ]);

        return [
            'requires_approval' => true,
            'approval_request_id' => $approvalRequest->id,
            'current_step' => $firstStep->name,
            'message' => 'Approval request created successfully',
        ];
    }

    /**
     * Submit approval decision
     */
    public function submitApprovalDecision(int $approvalRequestId, string $decision, ?string $comments = null): array
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalRequestId);
        $currentStep = $approvalRequest->workflow->steps()
            ->where('step_order', $approvalRequest->current_step)
            ->first();

        if (!$currentStep) {
            throw new \Exception('Invalid approval step');
        }

        // Record approval decision
        $approvalRequest->approvals()->create([
            'step_id' => $currentStep->id,
            'user_id' => auth()->id(),
            'decision' => $decision,
            'comments' => $comments,
            'approved_at' => now(),
        ]);

        // Check if step is complete
        $approvedCount = $approvalRequest->approvals()
            ->where('step_id', $currentStep->id)
            ->where('decision', 'approved')
            ->count();

        $rejectedCount = $approvalRequest->approvals()
            ->where('step_id', $currentStep->id)
            ->where('decision', 'rejected')
            ->count();

        if ($decision === 'rejected' || $rejectedCount > 0) {
            // Rejection - end workflow
            $approvalRequest->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            $this->logActivity($approvalRequest->approvable, 'approval_rejected', null, null, [
                'approval_request_id' => $approvalRequestId,
                'rejected_by' => auth()->id(),
                'comments' => $comments,
            ]);

            return [
                'status' => 'rejected',
                'message' => 'Request has been rejected',
            ];
        }

        if ($approvedCount >= $currentStep->required_approvals) {
            // Step completed - move to next step or complete workflow
            $nextStep = $approvalRequest->workflow->steps()
                ->where('step_order', $approvalRequest->current_step + 1)
                ->first();

            if ($nextStep) {
                // Move to next step
                $approvalRequest->update(['current_step' => $nextStep->step_order]);
                $this->initiateApprovalStep($approvalRequest, $nextStep);

                return [
                    'status' => 'pending',
                    'current_step' => $nextStep->name,
                    'message' => 'Moved to next approval step',
                ];
            } else {
                // Workflow complete
                $approvalRequest->update([
                    'status' => 'approved',
                    'completed_at' => now(),
                ]);

                $this->logActivity($approvalRequest->approvable, 'approval_completed', null, null, [
                    'approval_request_id' => $approvalRequestId,
                ]);

                return [
                    'status' => 'approved',
                    'message' => 'Request has been fully approved',
                ];
            }
        }

        return [
            'status' => 'pending',
            'message' => 'Approval recorded, waiting for additional approvals',
        ];
    }

    /**
     * Get pending approvals for user
     */
    public function getPendingApprovalsForUser(int $userId): Collection
    {
        return ApprovalRequest::where('status', 'pending')
            ->whereHas('workflow.steps', function ($query) use ($userId) {
                $query->where('step_order', DB::raw('approval_requests.current_step'))
                    ->where(function ($subQuery) use ($userId) {
                        $subQuery->where('approver_type', 'user')
                            ->where('approver_id', $userId);
                    });
            })
            ->with(['approvable', 'workflow.steps'])
            ->get();
    }

    protected function getCurrentApprovalLevel(Transaction $transaction): ?int
    {
        $approvalRequest = $transaction->approvalRequests()->latest()->first();
        return $approvalRequest ? $approvalRequest->current_step : null;
    }

    protected function generateActivityDescription(AuditLog $log): string
    {
        $modelType = class_basename($log->auditable_type);
        $action = $log->action;
        $userName = $log->user ? $log->user->name : 'System';

        return match ($action) {
            'created' => "{$userName} created {$modelType} #{$log->auditable_id}",
            'updated' => "{$userName} updated {$modelType} #{$log->auditable_id}",
            'deleted' => "{$userName} deleted {$modelType} #{$log->auditable_id}",
            'approved' => "{$userName} approved {$modelType} #{$log->auditable_id}",
            'rejected' => "{$userName} rejected {$modelType} #{$log->auditable_id}",
            'locked' => "{$userName} locked {$modelType} #{$log->auditable_id}",
            'unlocked' => "{$userName} unlocked {$modelType} #{$log->auditable_id}",
            default => "{$userName} performed {$action} on {$modelType} #{$log->auditable_id}",
        };
    }

    protected function formatChanges(AuditLog $log): ?array
    {
        if (!$log->old_values || !$log->new_values) {
            return null;
        }

        $oldValues = json_decode($log->old_values, true);
        $newValues = json_decode($log->new_values, true);
        $changes = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ];
            }
        }

        return $changes;
    }

    protected function isSecurityEvent(AuditLog $log): bool
    {
        $securityActions = [
            'login_failed',
            'login_success',
            'logout',
            'password_changed',
            'permission_denied',
            'unauthorized_access',
            'data_export',
            'bulk_delete',
            'admin_access',
        ];

        return in_array($log->action, $securityActions) ||
               $this->isUnusualActivity($log);
    }

    protected function isUnusualActivity(AuditLog $log): bool
    {
        // Check for unusual patterns
        if ($log->user_id) {
            $recentActivities = AuditLog::where('user_id', $log->user_id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            // Flag if user has more than 100 activities in the last hour
            if ($recentActivities > 100) {
                return true;
            }
        }

        // Check for activities outside business hours
        $hour = $log->created_at->hour;
        if ($hour < 6 || $hour > 22) {
            return true;
        }

        return false;
    }

    protected function generateSecurityEventDescription(AuditLog $log): string
    {
        if ($this->isUnusualActivity($log)) {
            return 'Unusual activity pattern detected';
        }

        return $this->generateActivityDescription($log);
    }

    protected function getSecurityEventSeverity(AuditLog $log): string
    {
        $highSeverityActions = ['unauthorized_access', 'permission_denied', 'bulk_delete'];
        
        if (in_array($log->action, $highSeverityActions)) {
            return 'high';
        }

        if ($this->isUnusualActivity($log)) {
            return 'medium';
        }

        return 'low';
    }

    protected function generateComplianceNotes(Collection $auditLogs): array
    {
        $notes = [];

        // Check for data retention compliance
        $oldestLog = $auditLogs->min('created_at');
        if ($oldestLog && Carbon::parse($oldestLog)->diffInYears(now()) > 7) {
            $notes[] = [
                'type' => 'data_retention',
                'message' => 'Some audit logs are older than 7 years and may need archival',
                'severity' => 'info',
            ];
        }

        // Check for missing audit trails
        $transactionCount = Transaction::count();
        $transactionAuditCount = $auditLogs->where('auditable_type', Transaction::class)->count();
        
        if ($transactionCount > 0 && $transactionAuditCount / $transactionCount < 0.8) {
            $notes[] = [
                'type' => 'missing_audits',
                'message' => 'Some transactions may be missing audit trails',
                'severity' => 'warning',
            ];
        }

        return $notes;
    }

    protected function meetsWorkflowConditions($model, ApprovalWorkflow $workflow): bool
    {
        $conditions = json_decode($workflow->conditions, true) ?? [];

        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];

            $modelValue = data_get($model, $field);

            $result = match ($operator) {
                '>' => $modelValue > $value,
                '>=' => $modelValue >= $value,
                '<' => $modelValue < $value,
                '<=' => $modelValue <= $value,
                '=' => $modelValue == $value,
                '!=' => $modelValue != $value,
                'in' => in_array($modelValue, (array) $value),
                'not_in' => !in_array($modelValue, (array) $value),
                default => true,
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    protected function initiateApprovalStep($approvalRequest, ApprovalStep $step): void
    {
        // Send notifications to approvers
        $approvers = $this->getStepApprovers($step);
        
        foreach ($approvers as $approver) {
            // Send notification (email, in-app, etc.)
            // This would integrate with your notification system
        }

        $this->logActivity($approvalRequest->approvable, 'approval_step_initiated', null, null, [
            'step_name' => $step->name,
            'step_order' => $step->step_order,
            'approvers_count' => count($approvers),
        ]);
    }

    protected function getStepApprovers(ApprovalStep $step): array
    {
        switch ($step->approver_type) {
            case 'user':
                return [$step->approver_id];
            case 'role':
                return User::whereHas('roles', function ($query) use ($step) {
                    $query->where('id', $step->approver_id);
                })->pluck('id')->toArray();
            case 'amount_based':
                // Logic for amount-based approvers
                return $this->getAmountBasedApprovers($step);
            default:
                return [];
        }
    }

    protected function getAmountBasedApprovers(ApprovalStep $step): array
    {
        // This would implement logic for amount-based approval routing
        // For now, return empty array
        return [];
    }
}