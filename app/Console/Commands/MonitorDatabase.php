<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseMonitoringService;
use App\Services\QueryOptimizationService;

class MonitorDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:monitor 
                            {--log : Log the performance metrics}
                            {--health : Show health status only}
                            {--clear-cache : Clear query cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database performance and log metrics';

    /**
     * Database monitoring service
     */
    protected DatabaseMonitoringService $monitoringService;

    /**
     * Query optimization service
     */
    protected QueryOptimizationService $optimizationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        DatabaseMonitoringService $monitoringService,
        QueryOptimizationService $optimizationService
    ) {
        parent::__construct();
        $this->monitoringService = $monitoringService;
        $this->optimizationService = $optimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Database Performance Monitor');
        $this->info('==========================');

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->optimizationService->clearCache();
            $this->info('✓ Query cache cleared');
        }

        // Show health status only
        if ($this->option('health')) {
            $this->showHealthStatus();
            return 0;
        }

        // Get and display performance metrics
        $metrics = $this->monitoringService->getPerformanceMetrics();

        if (isset($metrics['error'])) {
            $this->error('Failed to retrieve database metrics:');
            $this->error($metrics['message']);
            return 1;
        }

        $this->displayMetrics($metrics);

        // Log metrics if requested
        if ($this->option('log')) {
            $this->monitoringService->logPerformanceMetrics();
            $this->info('✓ Performance metrics logged');
        }

        return 0;
    }

    /**
     * Display health status
     */
    private function showHealthStatus(): void
    {
        $health = $this->monitoringService->getHealthStatus();
        
        $statusColor = match($health['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'unhealthy' => 'red',
            default => 'white'
        };

        $this->line('Database Health Status: <fg=' . $statusColor . '>' . strtoupper($health['status']) . '</>');

        if (!empty($health['issues'])) {
            $this->error('Issues:');
            foreach ($health['issues'] as $issue) {
                $this->line('  • ' . $issue);
            }
        }

        if (!empty($health['warnings'])) {
            $this->warn('Warnings:');
            foreach ($health['warnings'] as $warning) {
                $this->line('  • ' . $warning);
            }
        }

        if ($health['status'] === 'healthy' && empty($health['warnings'])) {
            $this->info('✓ All systems operating normally');
        }
    }

    /**
     * Display performance metrics
     */
    private function displayMetrics(array $metrics): void
    {
        // Connection Status
        if (isset($metrics['connection_status'])) {
            $this->displayConnectionStatus($metrics['connection_status']);
        }

        // Query Performance
        if (isset($metrics['query_performance'])) {
            $this->displayQueryPerformance($metrics['query_performance']);
        }

        // Table Statistics
        if (isset($metrics['table_statistics'])) {
            $this->displayTableStatistics($metrics['table_statistics']);
        }

        // Connection Info
        if (isset($metrics['connection_info'])) {
            $this->displayConnectionInfo($metrics['connection_info']);
        }

        // Slow Queries
        if (isset($metrics['slow_queries'])) {
            $this->displaySlowQueryInfo($metrics['slow_queries']);
        }
    }

    /**
     * Display connection status
     */
    private function displayConnectionStatus(array $status): void
    {
        $this->newLine();
        $this->info('Connection Status:');
        $this->line('  Status: ' . ($status['status'] === 'connected' ? '<fg=green>Connected</>' : '<fg=red>Disconnected</>'));
        
        if (isset($status['response_time_ms'])) {
            $responseTime = $status['response_time_ms'];
            $color = $responseTime > 1000 ? 'red' : ($responseTime > 500 ? 'yellow' : 'green');
            $this->line('  Response Time: <fg=' . $color . '>' . $responseTime . 'ms</>');
        }
    }

    /**
     * Display query performance
     */
    private function displayQueryPerformance(array $performance): void
    {
        $this->newLine();
        $this->info('Query Performance:');
        $this->line('  Total Queries: ' . number_format($performance['total_queries'] ?? 0));
        $this->line('  Slow Queries: ' . number_format($performance['slow_queries'] ?? 0));
        
        $slowPercentage = $performance['slow_query_percentage'] ?? 0;
        $color = $slowPercentage > 10 ? 'red' : ($slowPercentage > 5 ? 'yellow' : 'green');
        $this->line('  Slow Query %: <fg=' . $color . '>' . $slowPercentage . '%</>');
        
        $this->line('  Queries/sec: ' . ($performance['queries_per_second'] ?? 0));
        $this->line('  Connections: ' . ($performance['connections'] ?? 0));
        $this->line('  Threads Connected: ' . ($performance['threads_connected'] ?? 0));
        $this->line('  Threads Running: ' . ($performance['threads_running'] ?? 0));
        $this->line('  Uptime: ' . gmdate('H:i:s', $performance['uptime_seconds'] ?? 0));
    }

    /**
     * Display table statistics
     */
    private function displayTableStatistics(array $tables): void
    {
        if (empty($tables) || isset($tables['error'])) {
            return;
        }

        $this->newLine();
        $this->info('Top Tables by Size:');
        
        $headers = ['Table', 'Rows', 'Data (MB)', 'Index (MB)', 'Total (MB)'];
        $rows = [];
        
        foreach (array_slice($tables, 0, 5) as $table) {
            $rows[] = [
                $table['table_name'],
                number_format($table['row_count']),
                $table['data_size_mb'],
                $table['index_size_mb'],
                $table['total_size_mb'],
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Display connection info
     */
    private function displayConnectionInfo(array $info): void
    {
        if (isset($info['error'])) {
            return;
        }

        $this->newLine();
        $this->info('Database Configuration:');
        $this->line('  MySQL Version: ' . ($info['mysql_version'] ?? 'Unknown'));
        $this->line('  Max Connections: ' . ($info['max_connections'] ?? 0));
        $this->line('  InnoDB Buffer Pool: ' . ($info['innodb_buffer_pool_size'] ?? 'Unknown'));
        $this->line('  Query Cache Size: ' . ($info['query_cache_size'] ?? 'Unknown'));
        $this->line('  Table Open Cache: ' . ($info['table_open_cache'] ?? 0));
        $this->line('  Thread Cache Size: ' . ($info['thread_cache_size'] ?? 0));
    }

    /**
     * Display slow query info
     */
    private function displaySlowQueryInfo(array $slowQueries): void
    {
        if (isset($slowQueries['error'])) {
            return;
        }

        $this->newLine();
        $this->info('Slow Query Configuration:');
        $this->line('  Slow Log Enabled: ' . ($slowQueries['slow_log_enabled'] ? '<fg=green>Yes</>' : '<fg=red>No</>'));
        $this->line('  Long Query Time: ' . ($slowQueries['long_query_time_seconds'] ?? 0) . ' seconds');

        if (!empty($slowQueries['recommendations'])) {
            $this->newLine();
            $this->info('Recommendations:');
            foreach ($slowQueries['recommendations'] as $recommendation) {
                $this->line('  • ' . $recommendation);
            }
        }
    }
}