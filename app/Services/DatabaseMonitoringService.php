<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DatabaseMonitoringService
{
    /**
     * Monitor database performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return Cache::remember('db_performance_metrics', 300, function () {
            try {
                $metrics = [
                    'connection_status' => $this->getConnectionStatus(),
                    'query_performance' => $this->getQueryPerformance(),
                    'table_statistics' => $this->getTableStatistics(),
                    'index_usage' => $this->getIndexUsage(),
                    'slow_queries' => $this->getSlowQueries(),
                    'connection_info' => $this->getConnectionInfo(),
                ];

                return $metrics;
            } catch (\Exception $e) {
                Log::error('Database monitoring failed: ' . $e->getMessage());
                return [
                    'error' => 'Unable to retrieve database metrics',
                    'message' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Get database connection status
     */
    private function getConnectionStatus(): array
    {
        try {
            $startTime = microtime(true);
            $result = DB::select('SELECT 1 as test');
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            return [
                'status' => 'connected',
                'response_time_ms' => round($responseTime, 2),
                'timestamp' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get query performance statistics
     */
    private function getQueryPerformance(): array
    {
        try {
            // Get MySQL status variables related to queries
            $queryStats = DB::select("
                SHOW STATUS WHERE Variable_name IN (
                    'Queries', 'Questions', 'Slow_queries', 'Connections',
                    'Threads_connected', 'Threads_running', 'Uptime'
                )
            ");

            $stats = [];
            foreach ($queryStats as $stat) {
                $stats[$stat->Variable_name] = $stat->Value;
            }

            $uptime = $stats['Uptime'] ?? 1;
            $queries = $stats['Queries'] ?? 0;
            $slowQueries = $stats['Slow_queries'] ?? 0;

            return [
                'total_queries' => (int) $queries,
                'slow_queries' => (int) $slowQueries,
                'queries_per_second' => round($queries / $uptime, 2),
                'slow_query_percentage' => $queries > 0 ? round(($slowQueries / $queries) * 100, 2) : 0,
                'connections' => (int) ($stats['Connections'] ?? 0),
                'threads_connected' => (int) ($stats['Threads_connected'] ?? 0),
                'threads_running' => (int) ($stats['Threads_running'] ?? 0),
                'uptime_seconds' => (int) $uptime,
            ];
        } catch (\Exception $e) {
            Log::warning('Could not retrieve query performance stats: ' . $e->getMessage());
            return [
                'error' => 'Query performance stats unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get table statistics
     */
    private function getTableStatistics(): array
    {
        try {
            $tableStats = DB::select("
                SELECT 
                    TABLE_NAME as table_name,
                    TABLE_ROWS as row_count,
                    DATA_LENGTH as data_size,
                    INDEX_LENGTH as index_size,
                    (DATA_LENGTH + INDEX_LENGTH) as total_size
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
                LIMIT 10
            ");

            $formattedStats = [];
            foreach ($tableStats as $stat) {
                $formattedStats[] = [
                    'table_name' => $stat->table_name,
                    'row_count' => (int) $stat->row_count,
                    'data_size_mb' => round($stat->data_size / 1024 / 1024, 2),
                    'index_size_mb' => round($stat->index_size / 1024 / 1024, 2),
                    'total_size_mb' => round($stat->total_size / 1024 / 1024, 2),
                ];
            }

            return $formattedStats;
        } catch (\Exception $e) {
            Log::warning('Could not retrieve table statistics: ' . $e->getMessage());
            return [
                'error' => 'Table statistics unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get index usage statistics
     */
    private function getIndexUsage(): array
    {
        try {
            $indexStats = DB::select("
                SELECT 
                    TABLE_NAME as table_name,
                    INDEX_NAME as index_name,
                    NON_UNIQUE as non_unique,
                    COLUMN_NAME as column_name
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND INDEX_NAME != 'PRIMARY'
                ORDER BY TABLE_NAME, INDEX_NAME
            ");

            $groupedStats = [];
            foreach ($indexStats as $stat) {
                $tableName = $stat->table_name;
                if (!isset($groupedStats[$tableName])) {
                    $groupedStats[$tableName] = [];
                }
                
                $indexName = $stat->index_name;
                if (!isset($groupedStats[$tableName][$indexName])) {
                    $groupedStats[$tableName][$indexName] = [
                        'index_name' => $indexName,
                        'unique' => $stat->non_unique == 0,
                        'columns' => [],
                    ];
                }
                
                $groupedStats[$tableName][$indexName]['columns'][] = $stat->column_name;
            }

            return $groupedStats;
        } catch (\Exception $e) {
            Log::warning('Could not retrieve index usage: ' . $e->getMessage());
            return [
                'error' => 'Index usage stats unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get slow queries (if slow query log is enabled)
     */
    private function getSlowQueries(): array
    {
        try {
            // Check if slow query log is enabled
            $slowLogStatus = DB::select("SHOW VARIABLES LIKE 'slow_query_log'");
            $longQueryTime = DB::select("SHOW VARIABLES LIKE 'long_query_time'");

            $isEnabled = !empty($slowLogStatus) && $slowLogStatus[0]->Value === 'ON';
            $threshold = !empty($longQueryTime) ? (float) $longQueryTime[0]->Value : 10.0;

            return [
                'slow_log_enabled' => $isEnabled,
                'long_query_time_seconds' => $threshold,
                'recommendations' => [
                    'Enable slow query log to track performance issues',
                    'Set long_query_time to 2 seconds for better monitoring',
                    'Review slow queries regularly and optimize them',
                    'Add appropriate indexes for slow queries',
                ],
            ];
        } catch (\Exception $e) {
            Log::warning('Could not retrieve slow query info: ' . $e->getMessage());
            return [
                'error' => 'Slow query info unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database connection information
     */
    private function getConnectionInfo(): array
    {
        try {
            $variables = DB::select("
                SHOW VARIABLES WHERE Variable_name IN (
                    'version', 'max_connections', 'innodb_buffer_pool_size',
                    'query_cache_size', 'table_open_cache', 'thread_cache_size'
                )
            ");

            $info = [];
            foreach ($variables as $var) {
                $info[$var->Variable_name] = $var->Value;
            }

            return [
                'mysql_version' => $info['version'] ?? 'Unknown',
                'max_connections' => (int) ($info['max_connections'] ?? 0),
                'innodb_buffer_pool_size' => $this->formatBytes($info['innodb_buffer_pool_size'] ?? 0),
                'query_cache_size' => $this->formatBytes($info['query_cache_size'] ?? 0),
                'table_open_cache' => (int) ($info['table_open_cache'] ?? 0),
                'thread_cache_size' => (int) ($info['thread_cache_size'] ?? 0),
            ];
        } catch (\Exception $e) {
            Log::warning('Could not retrieve connection info: ' . $e->getMessage());
            return [
                'error' => 'Connection info unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Log performance metrics
     */
    public function logPerformanceMetrics(): void
    {
        $metrics = $this->getPerformanceMetrics();
        
        if (isset($metrics['error'])) {
            Log::error('Database monitoring error', $metrics);
            return;
        }

        // Log key performance indicators
        $queryPerf = $metrics['query_performance'] ?? [];
        if (!empty($queryPerf)) {
            Log::info('Database Performance Metrics', [
                'queries_per_second' => $queryPerf['queries_per_second'] ?? 0,
                'slow_query_percentage' => $queryPerf['slow_query_percentage'] ?? 0,
                'threads_connected' => $queryPerf['threads_connected'] ?? 0,
                'threads_running' => $queryPerf['threads_running'] ?? 0,
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Alert on high slow query percentage
        if (isset($queryPerf['slow_query_percentage']) && $queryPerf['slow_query_percentage'] > 5) {
            Log::warning('High slow query percentage detected', [
                'percentage' => $queryPerf['slow_query_percentage'],
                'total_queries' => $queryPerf['total_queries'] ?? 0,
                'slow_queries' => $queryPerf['slow_queries'] ?? 0,
            ]);
        }
    }

    /**
     * Get database health status
     */
    public function getHealthStatus(): array
    {
        $metrics = $this->getPerformanceMetrics();
        
        if (isset($metrics['error'])) {
            return [
                'status' => 'unhealthy',
                'issues' => [$metrics['error']],
                'timestamp' => now()->toISOString(),
            ];
        }

        $issues = [];
        $warnings = [];

        // Check connection status
        $connectionStatus = $metrics['connection_status'] ?? [];
        if (($connectionStatus['status'] ?? '') !== 'connected') {
            $issues[] = 'Database connection failed';
        } elseif (($connectionStatus['response_time_ms'] ?? 0) > 1000) {
            $warnings[] = 'High database response time: ' . $connectionStatus['response_time_ms'] . 'ms';
        }

        // Check query performance
        $queryPerf = $metrics['query_performance'] ?? [];
        if (($queryPerf['slow_query_percentage'] ?? 0) > 10) {
            $issues[] = 'High slow query percentage: ' . $queryPerf['slow_query_percentage'] . '%';
        } elseif (($queryPerf['slow_query_percentage'] ?? 0) > 5) {
            $warnings[] = 'Elevated slow query percentage: ' . $queryPerf['slow_query_percentage'] . '%';
        }

        if (($queryPerf['threads_running'] ?? 0) > 10) {
            $warnings[] = 'High number of running threads: ' . $queryPerf['threads_running'];
        }

        // Determine overall status
        $status = 'healthy';
        if (!empty($issues)) {
            $status = 'unhealthy';
        } elseif (!empty($warnings)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'warnings' => $warnings,
            'timestamp' => now()->toISOString(),
        ];
    }
}