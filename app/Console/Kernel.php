<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Automated backup job - runs daily at 2 AM
        $schedule->job(new \App\Jobs\AutomatedBackupJob())
            ->dailyAt('02:00')
            ->name('automated-backup')
            ->withoutOverlapping()
            ->onOneServer();

        // Process recurring invoices - runs daily at 6 AM
        $schedule->job(new \App\Jobs\ProcessRecurringInvoicesJob())
            ->dailyAt('06:00')
            ->name('process-recurring-invoices')
            ->withoutOverlapping()
            ->onOneServer();

        // Birthday reminders - runs daily at 9 AM
        $schedule->job(new \App\Jobs\SendBirthdayReminderJob(null, 'birthday'))
            ->dailyAt('09:00')
            ->name('birthday-reminders')
            ->withoutOverlapping()
            ->onOneServer();

        // Anniversary reminders - runs daily at 9:30 AM
        $schedule->job(new \App\Jobs\SendBirthdayReminderJob(null, 'anniversary'))
            ->dailyAt('09:30')
            ->name('anniversary-reminders')
            ->withoutOverlapping()
            ->onOneServer();

        // Stock alerts - runs every 4 hours
        $schedule->job(new \App\Jobs\SendStockAlertJob('all'))
            ->cron('0 */4 * * *')
            ->name('stock-alerts')
            ->withoutOverlapping()
            ->onOneServer();

        // Low stock alerts - runs twice daily
        $schedule->job(new \App\Jobs\SendStockAlertJob('low_stock'))
            ->twiceDaily(10, 16)
            ->name('low-stock-alerts')
            ->withoutOverlapping()
            ->onOneServer();

        // Expiring items alerts - runs daily at 8 AM
        $schedule->job(new \App\Jobs\SendStockAlertJob('expiring'))
            ->dailyAt('08:00')
            ->name('expiring-items-alerts')
            ->withoutOverlapping()
            ->onOneServer();

        // Clean up failed jobs - runs daily at midnight
        $schedule->command('queue:prune-failed --hours=168')
            ->dailyAt('00:00')
            ->name('cleanup-failed-jobs');

        // Clean up old job batches - runs weekly
        $schedule->command('queue:prune-batches --hours=168')
            ->weekly()
            ->name('cleanup-job-batches');

        // Restart queue workers - runs every 6 hours to prevent memory leaks
        $schedule->command('queue:restart')
            ->cron('0 */6 * * *')
            ->name('restart-queue-workers');

        // Clean up orphaned category images - runs weekly on Sunday at 3 AM
        $schedule->command('category:image-maintenance cleanup --force')
            ->weeklyOn(0, '03:00')
            ->name('cleanup-orphaned-images')
            ->withoutOverlapping()
            ->onOneServer();

        // Database performance monitoring - runs every 15 minutes
        $schedule->command('db:monitor --log')
            ->cron('*/15 * * * *')
            ->name('database-monitoring')
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}