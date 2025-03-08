<?php

namespace Rose\Contracts\Concurrency;

interface Task
{
    /**
     * Execute the task.
     *
     * @return mixed
     */
    public function execute();
    
    /**
     * Get the result of the task.
     *
     * @return mixed
     */
    public function getResult();
    
    /**
     * Check if the task has succeeded.
     *
     * @return bool
     */
    public function isSuccessful(): bool;
    
    /**
     * Get the exception thrown by the task if it failed.
     *
     * @return \Throwable|null
     */
    public function getException(): ?\Throwable;
}
