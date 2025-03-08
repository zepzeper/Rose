<?php

namespace Rose\Concurrency;

class PoolStatus
{
    protected int $pending;
    protected int $running;
    protected int $successful;
    protected int $failed;
    
    /**
     * Create a new pool status instance.
     *
     * @param int $pending
     * @param int $running
     * @param int $successful
     * @param int $failed
     */
    public function __construct(int $pending, int $running, int $successful, int $failed)
    {
        $this->pending = $pending;
        $this->running = $running;
        $this->successful = $successful;
        $this->failed = $failed;
    }
    
    /**
     * Get the number of pending processes.
     *
     * @return int
     */
    public function getPending(): int
    {
        return $this->pending;
    }
    
    /**
     * Get the number of running processes.
     *
     * @return int
     */
    public function getRunning(): int
    {
        return $this->running;
    }
    
    /**
     * Get the number of successful processes.
     *
     * @return int
     */
    public function getSuccessful(): int
    {
        return $this->successful;
    }
    
    /**
     * Get the number of failed processes.
     *
     * @return int
     */
    public function getFailed(): int
    {
        return $this->failed;
    }
    
    /**
     * Get the total number of processes.
     *
     * @return int
     */
    public function getTotal(): int
    {
        return $this->pending + $this->running + $this->successful + $this->failed;
    }
    
    /**
     * Check if all processes have finished.
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->pending === 0 && $this->running === 0;
    }
    
    /**
     * Check if all processes have succeeded.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->isFinished() && $this->failed === 0;
    }
}
