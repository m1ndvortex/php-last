<?php

namespace App\Console\Commands;

use App\Services\AssetService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessMonthlyDepreciation extends Command
{
    protected $signature = 'accounting:process-depreciation {--date= : Process depreciation for specific date (YYYY-MM-DD)}';
    protected $description = 'Process monthly depreciation for all active assets';

    public function handle(AssetService $assetService): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $date = $date->endOfMonth();

        $this->info("Processing depreciation for: {$date->format('F Y')}");

        try {
            $transactions = $assetService->processDepreciation($date);

            if ($transactions->isEmpty()) {
                $this->info('No depreciation entries were created.');
                return 0;
            }

            $this->info("Created {$transactions->count()} depreciation entries:");

            $tableData = [];
            foreach ($transactions as $entry) {
                $tableData[] = [
                    $entry['asset_name'],
                    number_format($entry['depreciation_expense'], 2),
                    number_format($entry['accumulated_depreciation'], 2),
                    number_format($entry['current_value'], 2),
                ];
            }

            $this->table(
                ['Asset', 'Depreciation Expense', 'Accumulated Depreciation', 'Current Value'],
                $tableData
            );

            $totalDepreciation = $transactions->sum('depreciation_expense');
            $this->info("Total depreciation expense: " . number_format($totalDepreciation, 2));

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to process depreciation: ' . $e->getMessage());
            return 1;
        }
    }
}