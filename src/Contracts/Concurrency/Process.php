<?php

namespace Rose\Contracts\Concurrency;

interface Process
{
    /**
     * Start the process.
     *
     * @return self
     */
    public function start(): self;
    
    /**
     * Wait for the process to finish.
     *
     * @param int|null $timeout
     * @return int The exit code
     */
    public function wait(int|null $timeout = null): int;
    
    /**
     * Check if the process is running.
     *
     * @return bool
     */
    public function isRunning(): bool;
    
    /**
     * Get the process ID.
     *
     * @return int|null
     */
    public function getPid(): ?int;
    
    /**
     * Stop the process.
     *
     * @return self
     */
    public function stop(): self;
    
    /**
     * Get the output of the process.
     *
     * @return mixed
     */
    public function getOutput(): mixed;
    
    /**
     * Get the error output of the process.
     *
     * @return mixed
     */
    public function getErrorOutput(): mixed;
}
