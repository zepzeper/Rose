<?php

namespace Rose\Queue\Events;

use Rose\Contracts\Queue\Job;
use Throwable;

/**
 * Event fired when a job has failed.
 */
class JobFailed
{
    /**
     * The job instance.
     *
     * @var Job
     */
    public Job $job;
    
    /**
     * The exception that caused the job to fail.
     *
     * @var Throwable
     */
    public Throwable $exception;
    
    /**
     * Create a new event instance.
     *
     * @param  Job  $job
     * @param  Throwable  $exception
     * @return void
     */
    public function __construct(Job $job, Throwable $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
    }
}
