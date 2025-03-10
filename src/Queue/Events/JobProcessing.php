<?php

namespace Rose\Queue\Events;

use Rose\Contracts\Queue\Job;

/**
 * Event fired when a job starts processing.
 */
class JobProcessing
{
    /**
     * The job instance.
     *
     * @var Job
     */
    public Job $job;
    
    /**
     * Create a new event instance.
     *
     * @param  Job  $job
     * @return void
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
    }
}
