<?php

namespace Rose\Support\Traits;

/**
 * Provides worker health monitoring, metrics, and related helper functions.
 * 
 * Can be used in any long-running process like queue workers, cron jobs,
 * or other background processes that need health monitoring.
 */
trait WorkerMetricsTrait
{
    /**
     * The memory limit in bytes.
     *
     * @var int
     */
    protected int $memoryLimit;
    
    /**
     * The maximum runtime for the worker in seconds.
     *
     * @var int|null
     */
    protected ?int $maxRuntime = null;
    
    /**
     * The timestamp when the worker started.
     *
     * @var int
     */
    protected int $startTime;
    
    /**
     * Health metrics for the worker.
     *
     * @var array
     */
    protected array $metrics = [
        'processed' => 0,
        'failed' => 0,
        'memory_peak' => 0,
        'memory_current' => 0,
        'runtime' => 0,
        'last_activity' => 0,
    ];
    
    /**
     * Initialize the worker metrics.
     *
     * @param int $memoryLimitMB Memory limit in megabytes
     * @param int|null $maxRuntimeHours Maximum runtime in hours (null for unlimited)
     * @return void 
     */
    protected function initializeMetrics(int $memoryLimitMB = 128, ?int $maxRuntimeHours = null): void
    {
        // Convert MB to bytes
        $this->memoryLimit = $memoryLimitMB * 1024 * 1024;
        
        // Convert hours to seconds if provided
        $this->maxRuntime = $maxRuntimeHours !== null ? $maxRuntimeHours * 3600 : null;
        
        // Set start time
        $this->startTime = time();
        $this->metrics['last_activity'] = $this->startTime;
        
        // Initialize memory metrics
        $this->metrics['memory_current'] = memory_get_usage(true);
        $this->metrics['memory_peak'] = memory_get_peak_usage(true);
    }
    
    /**
     * Check if the worker should quit due to health constraints.
     *
     * @return bool
     */
    protected function shouldQuitDueToHealth(): bool
    {
        // Check memory usage
        if ($this->memoryExceeded()) {
            return true;
        }
        
        // Check runtime if configured
        if ($this->maxRuntimeExceeded()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if memory usage has exceeded the limit.
     *
     * @param float $threshold Percentage of memory limit to consider exceeded (0.0-1.0)
     * @return bool
     */
    protected function memoryExceeded(float $threshold = 0.9): bool
    {
        // Update current memory usage
        $memoryUsage = memory_get_usage(true);
        $this->metrics['memory_current'] = $memoryUsage;
        
        // Update peak memory usage
        $peakMemory = memory_get_peak_usage(true);
        $this->metrics['memory_peak'] = max($this->metrics['memory_peak'], $peakMemory);
        
        // Check if usage exceeds the threshold
        return $memoryUsage >= ($this->memoryLimit * $threshold);
    }
    
    /**
     * Check if the worker has exceeded its maximum runtime.
     *
     * @return bool
     */
    protected function maxRuntimeExceeded(): bool
    {
        if ($this->maxRuntime === null) {
            return false;
        }
        
        // Update runtime metric
        $this->metrics['runtime'] = time() - $this->startTime;
        
        return $this->metrics['runtime'] >= $this->maxRuntime;
    }
    
    /**
     * Update the timestamp of last activity.
     *
     * @return void
     */
    protected function recordActivity(): void
    {
        $this->metrics['last_activity'] = time();
    }
    
    /**
     * Increment the count of processed items.
     *
     * @param int $count Number to increment by (default: 1)
     * @return void
     */
    protected function incrementProcessed(int $count = 1): void
    {
        $this->metrics['processed'] += $count;
        $this->recordActivity();
    }
    
    /**
     * Increment the count of failed items.
     *
     * @param int $count Number to increment by (default: 1)
     * @return void
     */
    protected function incrementFailed(int $count = 1): void
    {
        $this->metrics['failed'] += $count;
        $this->recordActivity();
    }
    
    /**
     * Get all health metrics.
     *
     * @return array
     */
    public function getMetrics(): array
    {
        // Update runtime and memory metrics before returning
        $this->metrics['runtime'] = time() - $this->startTime;
        $this->metrics['memory_current'] = memory_get_usage(true);
        $this->metrics['memory_peak'] = memory_get_peak_usage(true);
        
        return $this->metrics;
    }
    
    /**
     * Get a specific health metric.
     *
     * @param string $key The metric key
     * @param mixed $default Default value if metric doesn't exist
     * @return mixed
     */
    public function getMetric(string $key, mixed $default = null): mixed
    {
        return $this->metrics[$key] ?? $default;
    }
    
    /**
     * Perform health checks and maintenance.
     *
     * @return void
     */
    protected function performHealthChecks(): void
    {
        // Check if we should run garbage collection manually to free memory
        if (memory_get_usage(true) > ($this->memoryLimit * 0.7)) {
            if (gc_enabled()) {
                gc_collect_cycles();
            }
            
            // Update memory metrics after GC
            $this->metrics['memory_current'] = memory_get_usage(true);
            $this->metrics['memory_peak'] = memory_get_peak_usage(true);
        }
    }
    
    /**
     * Format bytes to a human-readable format.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Format seconds to a human-readable time.
     *
     * @param int $seconds
     * @return string
     */
    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $seconds = $seconds % 60;
            return "{$minutes}m {$seconds}s";
        }
        
        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;
        
        return "{$hours}h {$minutes}m {$seconds}s";
    }


    /**
     * Log the current health metrics.
     *
     * @param bool $final Whether this is the final health log
     * @return void
     */
    protected function logHealthMetrics(bool $final = false): void
    {
        $prefix = $final ? "Final worker health" : "Worker health";
        $this->logMessage('info', "{$prefix}: " . $this->getHealthReport());
    }

    /**
     * Get the reason for health-based shutdown.
     *
     * @return string
     */
    protected function getHealthReason(): string
    {
        if ($this->memoryExceeded()) {
            return "Memory limit exceeded (" . $this->formatBytes(memory_get_usage(true)) . " of " . 
                   $this->formatBytes($this->memoryLimit) . ")";
        }
        
        if ($this->maxRuntimeExceeded()) {
            return "Maximum runtime exceeded (" . $this->formatTime($this->metrics['runtime']) . ")";
        }
        
        return "Unknown health constraint";
    }


   /**
     * Log a message to the logger if it's available.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array   $context
     * @return void
     */
    protected function logMessage(string $level, string $message, array $context = []): void
    {
        try {
            if ($this->container->make('log')) {
                $logger = $this->container->make('log');
                $logger->{$level}("[Queue Worker] {$message}", $context);
            }
        } catch (\Throwable $e) {
            // If we can't log, there's not much we can do
            // In a production environment, you might want to have a fallback like
            error_log("Failed to log queue message: {$message}. Logger error: {$e->getMessage()}");
        }
    }
    
    
    /**
     * Get a formatted health report.
     *
     * @param bool $detailed Whether to include detailed metrics
     * @return string
     */
    public function getHealthReport(bool $detailed = false): string
    {
        $metrics = $this->getMetrics();
        
        $report = "Processed: {$metrics['processed']}, " .
                  "Failed: {$metrics['failed']}, " .
                  "Memory: " . $this->formatBytes($metrics['memory_current']) . ", " .
                  "Peak: " . $this->formatBytes($metrics['memory_peak']) . ", " .
                  "Uptime: " . $this->formatTime($metrics['runtime']);
        
        if ($detailed) {
            // Add more detailed information
            $memoryPercent = round(($metrics['memory_current'] / $this->memoryLimit) * 100, 1);
            $idleTime = time() - $metrics['last_activity'];
            
            $report .= "\n" .
                "Memory Usage: {$memoryPercent}% of limit, " .
                "Idle Time: " . $this->formatTime($idleTime) . ", " .
                "Started: " . date('Y-m-d H:i:s', $this->startTime);
            
            if ($this->maxRuntime !== null) {
                $timeRemaining = $this->maxRuntime - $metrics['runtime'];
                $report .= "\nTime Remaining: " . $this->formatTime(max(0, $timeRemaining));
            }
        }
        
        return $report;
    }
}
