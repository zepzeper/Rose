<?php

namespace Rose\Contracts\Queue;

use Throwable;

interface Job
{
    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getId(): string;
    
    /**
     * Get the queue name.
     *
     * @return string
     */
    public function getQueue(): string;
    
    /**
     * Get the job class name or closure.
     *
     * @return mixed
     */
    public function getJob(): mixed;
    
    /**
     * Get the job data.
     *
     * @return mixed
     */
    public function getData(): mixed;
    
    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int;
    
    /**
     * Handle the job.
     *
     * @return mixed
     */
    public function handle(): mixed;
    
    /**
     * Release the job back onto the queue.
     *
     * @param int $delay The delay in seconds
     * @return void
     */
    public function release(int $delay = 0): void;
    
    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void;
    
    /**
     * Mark the job as failed.
     *
     * @param Throwable $e
     * @return void
     */
    public function fail(Throwable $e): void;
    
    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string;
}
