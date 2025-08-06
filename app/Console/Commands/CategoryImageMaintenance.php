<?php

namespace App\Console\Commands;

use App\Services\CategoryImageService;
use Illuminate\Console\Command;

class CategoryImageMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'category:image-maintenance 
                            {action : The maintenance action to perform (cleanup|optimize|stats|validate)}
                            {--force : Force the action without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Perform maintenance tasks on category images';

    private CategoryImageService $imageService;

    public function __construct(CategoryImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'cleanup':
                return $this->cleanupOrphanedImages();
            case 'optimize':
                return $this->optimizeImages();
            case 'stats':
                return $this->showStats();
            case 'validate':
                return $this->validateSetup();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: cleanup, optimize, stats, validate');
                return 1;
        }
    }

    /**
     * Clean up orphaned image files.
     */
    private function cleanupOrphanedImages(): int
    {
        $this->info('Scanning for orphaned category images...');

        if (!$this->option('force')) {
            if (!$this->confirm('This will permanently delete orphaned image files. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $cleanedCount = $this->imageService->cleanupOrphanedImages();

        $this->info("Cleaned up {$cleanedCount} orphaned image files.");
        return 0;
    }

    /**
     * Optimize existing images.
     */
    private function optimizeImages(): int
    {
        $this->info('Optimizing existing category images...');

        if (!$this->option('force')) {
            if (!$this->confirm('This will regenerate thumbnails for all images. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $optimizedCount = $this->imageService->optimizeExistingImages();

        $this->info("Optimized {$optimizedCount} category images.");
        return 0;
    }

    /**
     * Show storage statistics.
     */
    private function showStats(): int
    {
        $this->info('Category Image Storage Statistics');
        $this->line('=====================================');

        $stats = $this->imageService->getStorageStats();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Images', $stats['total_images']],
                ['Categories with Images', $stats['total_categories_with_images']],
                ['Storage Used (MB)', $stats['storage_used_mb']],
                ['Orphaned Files', $stats['orphaned_files']],
            ]
        );

        if ($stats['orphaned_files'] > 0) {
            $this->warn("Found {$stats['orphaned_files']} orphaned files. Run 'category:image-maintenance cleanup' to remove them.");
        }

        return 0;
    }

    /**
     * Validate storage setup.
     */
    private function validateSetup(): int
    {
        $this->info('Validating category image storage setup...');

        $issues = $this->imageService->validateStorageSetup();

        if (empty($issues)) {
            $this->info('✅ Storage setup is valid. No issues found.');
            return 0;
        }

        $this->error('❌ Storage setup issues found:');
        foreach ($issues as $issue) {
            $this->line("  • {$issue}");
        }

        return 1;
    }
}