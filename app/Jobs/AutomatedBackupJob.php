<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutomatedBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('backups');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting automated backup process');

            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupPath = "backups/database_backup_{$timestamp}.sql";

            // Create database backup
            $this->createDatabaseBackup($backupPath);

            // Clean up old backups
            $this->cleanupOldBackups();

            Log::info('Automated backup completed successfully', ['backup_path' => $backupPath]);

        } catch (\Exception $e) {
            Log::error('Automated backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Create database backup using mysqldump
     */
    private function createDatabaseBackup(string $backupPath): void
    {
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPassword = config('database.connections.mysql.password');

        // Create backup command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbPassword),
            escapeshellarg($dbName)
        );

        // Execute backup command
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed with return code: ' . $returnCode);
        }

        // Save backup to storage
        $backupContent = implode("\n", $output);
        Storage::put($backupPath, $backupContent);

        Log::info('Database backup created', [
            'path' => $backupPath,
            'size' => strlen($backupContent)
        ]);
    }

    /**
     * Clean up old backup files based on retention policy
     */
    private function cleanupOldBackups(): void
    {
        $retentionDays = config('app.backup_retention_days', 30);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $backupFiles = Storage::files('backups');
        $deletedCount = 0;

        foreach ($backupFiles as $file) {
            $fileTime = Storage::lastModified($file);
            
            if ($fileTime && Carbon::createFromTimestamp($fileTime)->lt($cutoffDate)) {
                Storage::delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            Log::info('Cleaned up old backups', ['deleted_count' => $deletedCount]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AutomatedBackupJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
