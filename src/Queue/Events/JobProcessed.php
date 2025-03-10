<?php

namespace Rose\Queue\Events;

use Rose\Contracts\Queue\Job;

/**
 * Event fired when a job has been processed.
 */
class JobProcessed
{
    /**
     * The job instance.
     *
     * @var Job
     */
    public Job $job;
    
    /**
     * The job result.
     *
     * @var mixed
     */
    public mixed $result;
    
    /**
     * Create a new event instance.
     *
     * @param  Job  $job
     * @param  mixed  $result
     * @return void
     */
    public function __construct(Job $job, mixed $result)
    {
        $this->job = $job;
        $this->result = $result;
    }
}
