<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use Illuminate\Console\Command;

class ProcessScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled reports and send them to recipients';

    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled reports...');

        try {
            $this->reportService->processScheduledReports();
            $this->info('Scheduled reports processed successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to process scheduled reports: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
