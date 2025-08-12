<?php

namespace App\Console\Commands;

use App\Services\AccountingService;
use Illuminate\Console\Command;

class InitializeChartOfAccounts extends Command
{
    protected $signature = 'accounting:init-chart-of-accounts {--force : Force recreation of accounts}';
    protected $description = 'Initialize comprehensive chart of accounts';

    public function handle(AccountingService $accountingService): int
    {
        $this->info('Initializing comprehensive chart of accounts...');

        try {
            // Check if accounts already exist
            if (\App\Models\Account::count() > 0 && !$this->option('force')) {
                if (!$this->confirm('Accounts already exist. Do you want to continue?')) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }

            $accountingService->createChartOfAccounts();

            $this->info('Chart of accounts initialized successfully!');
            
            $accountCount = \App\Models\Account::count();
            $this->info("Created {$accountCount} accounts.");

            // Display account summary
            $this->table(
                ['Type', 'Count'],
                [
                    ['Asset', \App\Models\Account::where('type', 'asset')->count()],
                    ['Liability', \App\Models\Account::where('type', 'liability')->count()],
                    ['Equity', \App\Models\Account::where('type', 'equity')->count()],
                    ['Revenue', \App\Models\Account::where('type', 'revenue')->count()],
                    ['Expense', \App\Models\Account::where('type', 'expense')->count()],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to initialize chart of accounts: ' . $e->getMessage());
            return 1;
        }
    }
}