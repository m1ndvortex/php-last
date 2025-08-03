<?php

namespace App\Services;

use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataComplianceService
{
    private AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Create data export request
     */
    public function createExportRequest(
        User $user,
        string $type,
        array $dataTypes,
        string $format = 'json',
        ?array $filters = null
    ): DataExportRequest {
        $request = DataExportRequest::create([
            'user_id' => $user->id,
            'type' => $type,
            'format' => $format,
            'data_types' => $dataTypes,
            'filters' => $filters,
            'status' => 'pending'
        ]);

        $this->auditLogService->logSystemEvent(
            'data_export_requested',
            "Data export request created: {$type}",
            'info',
            [
                'request_id' => $request->id,
                'data_types' => $dataTypes,
                'format' => $format
            ]
        );

        return $request;
    }

    /**
     * Process data export request
     */
    public function processExportRequest(DataExportRequest $request): bool
    {
        try {
            $request->markAsStarted();

            $data = $this->collectExportData($request);
            $filePath = $this->generateExportFile($request, $data);
            $fileSize = Storage::size($filePath);

            $request->markAsCompleted($filePath, $fileSize);

            $this->auditLogService->logSystemEvent(
                'data_export_completed',
                "Data export completed for request #{$request->id}",
                'info',
                [
                    'request_id' => $request->id,
                    'file_size' => $fileSize,
                    'records_exported' => $this->countExportedRecords($data)
                ]
            );

            return true;
        } catch (\Exception $e) {
            $request->markAsFailed($e->getMessage());

            $this->auditLogService->logSystemEvent(
                'data_export_failed',
                "Data export failed for request #{$request->id}: {$e->getMessage()}",
                'error',
                ['request_id' => $request->id]
            );

            return false;
        }
    }

    /**
     * Create data deletion request
     */
    public function createDeletionRequest(
        User $user,
        string $type,
        array $dataTypes,
        string $reason,
        ?array $filters = null
    ): DataDeletionRequest {
        $request = DataDeletionRequest::create([
            'user_id' => $user->id,
            'type' => $type,
            'data_types' => $dataTypes,
            'filters' => $filters,
            'reason' => $reason,
            'status' => 'pending'
        ]);

        $this->auditLogService->logSystemEvent(
            'data_deletion_requested',
            "Data deletion request created: {$type}",
            'warning',
            [
                'request_id' => $request->id,
                'data_types' => $dataTypes,
                'reason' => $reason
            ]
        );

        return $request;
    }

    /**
     * Process data deletion request
     */
    public function processDeletionRequest(DataDeletionRequest $request): bool
    {
        if (!$request->isReadyForProcessing()) {
            return false;
        }

        try {
            $request->markAsStarted();

            // Create backup before deletion
            $backupInfo = $this->createDeletionBackup($request);
            $request->update(['backup_info' => $backupInfo]);

            // Perform deletion
            $deletionSummary = $this->performDataDeletion($request);
            $request->markAsCompleted($deletionSummary);

            $this->auditLogService->logSystemEvent(
                'data_deletion_completed',
                "Data deletion completed for request #{$request->id}",
                'warning',
                [
                    'request_id' => $request->id,
                    'deletion_summary' => $deletionSummary
                ]
            );

            return true;
        } catch (\Exception $e) {
            $request->markAsFailed($e->getMessage());

            $this->auditLogService->logSystemEvent(
                'data_deletion_failed',
                "Data deletion failed for request #{$request->id}: {$e->getMessage()}",
                'error',
                ['request_id' => $request->id]
            );

            return false;
        }
    }

    /**
     * Get available data types for export/deletion
     */
    public function getAvailableDataTypes(): array
    {
        return [
            'customers' => [
                'name' => 'Customer Data',
                'description' => 'Customer profiles, contact information, and preferences',
                'model' => Customer::class
            ],
            'invoices' => [
                'name' => 'Invoice Data',
                'description' => 'Invoices, invoice items, and billing information',
                'model' => Invoice::class
            ],
            'inventory' => [
                'name' => 'Inventory Data',
                'description' => 'Inventory items, stock levels, and movements',
                'model' => InventoryItem::class
            ],
            'transactions' => [
                'name' => 'Transaction Data',
                'description' => 'Financial transactions and accounting records',
                'model' => Transaction::class
            ],
            'audit_logs' => [
                'name' => 'Audit Logs',
                'description' => 'System activity logs and user actions',
                'model' => AuditLog::class
            ],
            'user_data' => [
                'name' => 'User Data',
                'description' => 'User profiles, preferences, and settings',
                'model' => User::class
            ]
        ];
    }

    /**
     * Collect data for export
     */
    private function collectExportData(DataExportRequest $request): array
    {
        $data = [];
        $dataTypes = $this->getAvailableDataTypes();

        foreach ($request->data_types as $type) {
            if (!isset($dataTypes[$type])) {
                continue;
            }

            $modelClass = $dataTypes[$type]['model'];
            $query = $modelClass::query();

            // Apply filters if provided
            if ($request->filters) {
                $query = $this->applyFilters($query, $request->filters, $type);
            }

            $data[$type] = $query->get()->toArray();
        }

        return $data;
    }

    /**
     * Generate export file
     */
    private function generateExportFile(DataExportRequest $request, array $data): string
    {
        $filename = "export_{$request->id}_" . now()->format('Y-m-d_H-i-s');
        
        switch ($request->format) {
            case 'json':
                $content = json_encode($data, JSON_PRETTY_PRINT);
                $filename .= '.json';
                break;
                
            case 'csv':
                $content = $this->convertToCSV($data);
                $filename .= '.csv';
                break;
                
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }

        $filePath = "exports/{$filename}";
        Storage::put($filePath, $content);

        return $filePath;
    }

    /**
     * Create backup before deletion
     */
    private function createDeletionBackup(DataDeletionRequest $request): array
    {
        $backupData = $this->collectDeletionData($request);
        $backupFilename = "deletion_backup_{$request->id}_" . now()->format('Y-m-d_H-i-s') . '.json';
        $backupPath = "backups/{$backupFilename}";
        
        Storage::put($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));

        return [
            'backup_path' => $backupPath,
            'backup_size' => Storage::size($backupPath),
            'records_backed_up' => $this->countExportedRecords($backupData),
            'created_at' => now()->toISOString()
        ];
    }

    /**
     * Perform actual data deletion
     */
    private function performDataDeletion(DataDeletionRequest $request): array
    {
        $deletionSummary = [];
        $dataTypes = $this->getAvailableDataTypes();

        DB::beginTransaction();

        try {
            foreach ($request->data_types as $type) {
                if (!isset($dataTypes[$type])) {
                    continue;
                }

                $modelClass = $dataTypes[$type]['model'];
                $query = $modelClass::query();

                // Apply filters if provided
                if ($request->filters) {
                    $query = $this->applyFilters($query, $request->filters, $type);
                }

                $count = $query->count();
                $deleted = $query->delete();

                $deletionSummary[$type] = [
                    'total_found' => $count,
                    'deleted' => $deleted,
                    'model' => $modelClass
                ];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $deletionSummary;
    }

    /**
     * Collect data for deletion backup
     */
    private function collectDeletionData(DataDeletionRequest $request): array
    {
        $data = [];
        $dataTypes = $this->getAvailableDataTypes();

        foreach ($request->data_types as $type) {
            if (!isset($dataTypes[$type])) {
                continue;
            }

            $modelClass = $dataTypes[$type]['model'];
            $query = $modelClass::query();

            // Apply filters if provided
            if ($request->filters) {
                $query = $this->applyFilters($query, $request->filters, $type);
            }

            $data[$type] = $query->get()->toArray();
        }

        return $data;
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters, string $dataType)
    {
        // Apply date range filters
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Apply specific filters based on data type
        switch ($dataType) {
            case 'customers':
                if (isset($filters['customer_ids'])) {
                    $query->whereIn('id', $filters['customer_ids']);
                }
                break;

            case 'invoices':
                if (isset($filters['customer_id'])) {
                    $query->where('customer_id', $filters['customer_id']);
                }
                break;

            case 'audit_logs':
                if (isset($filters['user_id'])) {
                    $query->where('user_id', $filters['user_id']);
                }
                break;
        }

        return $query;
    }

    /**
     * Convert data to CSV format
     */
    private function convertToCSV(array $data): string
    {
        $csv = '';

        foreach ($data as $type => $records) {
            if (empty($records)) {
                continue;
            }

            $csv .= "=== {$type} ===\n";
            
            // Get headers from first record
            $headers = array_keys($records[0]);
            $csv .= implode(',', $headers) . "\n";

            // Add data rows
            foreach ($records as $record) {
                $row = [];
                foreach ($headers as $header) {
                    $value = $record[$header] ?? '';
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $row[] = '"' . str_replace('"', '""', $value) . '"';
                }
                $csv .= implode(',', $row) . "\n";
            }

            $csv .= "\n";
        }

        return $csv;
    }

    /**
     * Count exported records
     */
    private function countExportedRecords(array $data): int
    {
        $count = 0;
        foreach ($data as $records) {
            $count += count($records);
        }
        return $count;
    }

    /**
     * Get export/deletion statistics
     */
    public function getComplianceStatistics(): array
    {
        return [
            'export_requests' => [
                'total' => DataExportRequest::count(),
                'pending' => DataExportRequest::pending()->count(),
                'completed' => DataExportRequest::completed()->count(),
                'failed' => DataExportRequest::failed()->count(),
            ],
            'deletion_requests' => [
                'total' => DataDeletionRequest::count(),
                'pending' => DataDeletionRequest::pending()->count(),
                'approved' => DataDeletionRequest::approved()->count(),
                'completed' => DataDeletionRequest::completed()->count(),
                'rejected' => DataDeletionRequest::rejected()->count(),
            ]
        ];
    }
}