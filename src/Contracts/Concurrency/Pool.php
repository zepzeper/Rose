<?php

namespace Rose\Contracts\Concurrency;

interface Pool
{
    /**
     * Add a task to the pool.
     *
     * @param callable $task
     * @return self
     */
    public function add(callable $task): self;
    
    /**
     * Run all the tasks in the pool.
     *
     * @return self
     */
    public function run(): self;
    
    /**
     * Wait for all tasks to finish.
     *
     * @return self
     */
    public function wait(): self;
    
    /**
     * Get the status of the pool.
     *
     * @return mixed
     */
    public function status();
    
    /**
     * Set the maximum number of concurrent processes.
     *
     * @param int $concurrency
     * @return self
     */
    public function concurrency(int $concurrency): self;
    
    /**
     * Set a callback to be executed when a task succeeds.
     *
     * @param callable $callback
     * @return self
     */
    public function whenTaskSucceeded(callable $callback): self;
    
    /**
     * Set a callback to be executed when a task fails.
     *
     * @param callable $callback
     * @return self
     */
    public function whenTaskFailed(callable $callback): self;
}
