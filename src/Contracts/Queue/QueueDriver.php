<?php

namespace Rose\Contracts\Queue;

interface QueueDriver
{
    /**
     * Push a new job onto the queue.
     *
     * @param string $job The job class name
     * @param mixed $data The data to pass to the job
     * @param string $queue The queue to push to
     * @return string The job ID
     */
    public function push(string $job, mixed $data = null, string $queue = 'default'): string;
    
    /**
     * Push a job onto the queue after a delay.
     *
     * @param int $delay The delay in seconds
     * @param string $job The job class name
     * @param mixed $data The data to pass to the job
     * @param string $queue The queue to push to
     * @return string The job ID
     */
    public function later(int $delay, string $job, mixed $data = null, string $queue = 'default'): string;
    
    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue The queue to pop from
     * @return Job|null The job or null if no jobs are available
     */
    public function pop(string $queue = 'default'): ?Job;
    
    /**
     * Get the connection name.
     *
     * @return string
     */
    public function getConnectionName(): string;
    
    /**
     * Set the connection name.
     *
     * @param string $name
     * @return $this
     */
    public function setConnectionName(string $name): self;

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     * @return int
     */
    public function size(string $queue = 'default'): int;
    
    /**
     * Clear the queue.
     *
     * @param string $queue
     * @return void
     */
    public function clear(string $queue = 'default'): void;
}
