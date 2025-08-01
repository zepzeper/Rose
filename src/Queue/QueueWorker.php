<?php

namespace Rose\Queue;

use Rose\Concurrency\Pool;
use Rose\Contracts\Container\Container;
use Rose\Contracts\Queue\Job;
use Rose\Queue\Events\JobFailed;
use Rose\Queue\Events\JobProcessed;
use Rose\Queue\Events\JobProcessing;
use Rose\Support\Traits\WorkerMetricsTrait;
use Throwable;

class QueueWorker
{
    use WorkerMetricsTrait;
    
    /**
     * The queue manager instance.
     *
     * @var QueueManager
     */
    protected QueueManager $manager;
    
    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected Container $container;
    
    /**
     * Indicates if the worker should exit.
     *
     * @var bool
     */
    protected bool $shouldQuit = false;
    
    /**
     * Create a new queue worker.
     *
     * @param  QueueManager  $manager
     * @param  Container  $container
     * @param  int  $memoryLimitMB  Memory limit in megabytes
     * @param  int|null  $maxRuntimeHours  Maximum runtime in hours (null for unlimited)
     * @return void
     */
    public function __construct(
        QueueManager $manager, 
        Container $container, 
        int $memoryLimitMB = 128, 
        ?int $maxRuntimeHours = null
    ) {
        $this->manager = $manager;
        $this->container = $container;
        
        // Initialize metrics from the trait
        $this->initializeMetrics($memoryLimitMB, $maxRuntimeHours);
    }
    
    /**
     * Run the worker in daemon mode.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  int  $concurrency
     * @return void
     */
    public function daemon(
        string $connection,
        string $queue = 'default',
        int $sleep = 3,
        int $maxTries = 1,
        int $concurrency = 5
    ): void {
        $this->shouldQuit = false;
        
        // Create a process pool to handle the jobs concurrently
        $pool = Pool::async($concurrency);
        
        // Register callbacks for the pool
        $pool->whenTaskSucceeded(function ($event) {
            $this->incrementProcessed();
            if (method_exists($this, 'onTaskSucceeded')) {
            }
        });
        
        $pool->whenTaskFailed(function ($event) {
            $this->incrementFailed();
            if (method_exists($this, 'onTaskFailed')) {
            }
        });

        // Every 5 minutes, log health stats
        $nextHealthCheck = time() + 300;
        
        while (!$this->shouldQuit) {
            // Check for any jobs in the queue
            $jobs = $this->getJobs($connection, $queue, $concurrency);
            
            if (!empty($jobs)) {
                foreach ($jobs as $job) {
                    // Add the job to the pool
                    $pool->add(function () use ($job, $maxTries) {
                        $this->processJob($job, $maxTries);
                    });
                }
                
                // Run the pool and wait for completion
                $pool->run()->wait();

                // After processing batch, check health
                $this->performHealthChecks();
            } else {
                // No jobs found, sleep for a bit
                sleep($sleep);
            }

            // Check if we need to log health metrics
            if (time() >= $nextHealthCheck) {
                $this->logHealthMetrics();
                $nextHealthCheck = time() + 300; // 5 minutes
            }

            // Perform health checks
            if ($this->shouldQuitDueToHealth()) {
                $this->logMessage('warning', "Worker stopping due to health constraint: " . $this->getHealthReason());
                $this->shouldQuit = true;
                break;
            }
        }

        $this->logHealthMetrics(true); // Final health log
        $this->logMessage('info', "Worker stopped");
    }

    /**
     * Get a batch of jobs from the queue.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $count
     * @return array
     */
    protected function getJobs(string $connection, string $queue, int $count): array
    {
        $jobs = [];
        
        for ($i = 0; $i < $count; $i++) {
            $job = $this->manager->connection($connection)->pop($queue);

            if ($job) {
                $jobs[] = $job;
            } else {
                // No more jobs available
                break;
            }
        }
        
        return $jobs;
    }
    
    /**
     * Process a given job from the queue.
     *
     * @param  Job  $job
     * @param  int  $maxTries
     * @return mixed
     */
    public function processJob(Job $job, int $maxTries = 1): mixed
    {
        try {
            // Fire event: JobProcessing
            $this->fireEvent(new JobProcessing($job));
            
            // Process the job
            $result = $job->handle();
            
            // Delete the job from the queue if it was processed successfully
            $job->delete();
            
            // Fire event: JobProcessed
            $this->fireEvent(new JobProcessed($job, $result));
            
            return $result;
        } catch (Throwable $e) {
            // Handle job failure
            $this->handleJobFailure($job, $e, $maxTries);
            
            return null;
           /*throw new \Exception($e);*/
        }
    }
    
    /**
     * Handle a job that has failed to process.
     *
     * @param  Job  $job
     * @param  Throwable  $e
     * @param  int  $maxTries
     * @return void
     */
    protected function handleJobFailure(Job $job, Throwable $e, int $maxTries): void
    {
        // If we should retry the job, release it back to the queue
        if ($job->attempts() < $maxTries) {
            $this->releaseJob($job, 10); // 10 second delay before retry
        } else {
            // Otherwise, mark the job as failed
            $this->failJob($job, $e);
        }
        
        // Fire event: JobFailed
        $this->fireEvent(new JobFailed($job, $e));
    }
    
    /**
     * Release a job back onto the queue for retry.
     *
     * @param  Job  $job
     * @param  int  $delay
     * @return void
     */
    protected function releaseJob(Job $job, int $delay = 0): void
    {
        $job->release($delay);
    }
    
    /**
     * Mark a job as failed.
     *
     * @param  Job  $job
     * @param  Throwable  $e
     * @return void
     */
    protected function failJob(Job $job, Throwable $e): void
    {
        try {
            $job->fail($e);
        } catch (Throwable) {
            // Ignore any exceptions when marking job as failed
        }
    }
    
    /**
     * Fire an event if the container has an event dispatcher.
     *
     * @param  object  $event
     * @return void
     */
    protected function fireEvent(object $event): void
    {
        if ($this->container->make('events')) {
            $this->container->get('events')->dispatch($event);
        }
    }
 
    /**
     * Callback for tasks that fail
     *
     * @param Throwable $event
     * @return void
     */
    private function onTaskFailed(Throwable $exception): void
    {
    }

    /**
     * Callback for tasks that succeedes
     *
     * @param  string $event
     * @return void
     */
    private function onTaskSucceeded(string $exception): void
    {
    }
    
    /**
     * Stop the worker.
     *
     * @return void
     */
    public function stop(): void
    {
        $this->shouldQuit = true;
    }
}
