<?php

namespace Rose\Queue;

use Rose\Contracts\Queue\Job as JobContract;
use Rose\Contracts\Queue\QueueDriver;
use Rose\Support\SerializableClosure;
use Throwable;

class Job implements JobContract
{
    /**
     * The job ID.
     *
     * @var string
     */
    protected string $id;
    
    /**
     * The queue the job belongs to.
     *
     * @var string
     */
    protected string $queue;
    
    /**
     * The job to be run.
     *
     * @var string|SerializableClosure
     */
    protected string|SerializableClosure $job;
    
    /**
     * The data to be passed to the job.
     *
     * @var mixed
     */
    protected mixed $data;
    
    /**
     * The number of times the job has been attempted.
     *
     * @var int
     */
    protected int $attempts = 0;
    
    /**
     * The queue driver.
     *
     * @var QueueDriver
     */
    protected QueueDriver $driver;
    
    /**
     * The job's raw payload.
     *
     * @var array
     */
    protected array $payload;
    
    /**
     * Create a new job instance.
     *
     * @param QueueDriver $driver
     * @param string $id
     * @param string $queue
     * @param string|SerializableClosure $job
     * @param mixed $data
     * @param int $attempts
     * @param array $payload
     * @return void
     */
    public function __construct(
        QueueDriver $driver,
        string $id,
        string $queue,
        string|SerializableClosure $job,
        mixed $data = null,
        int $attempts = 0,
        array $payload = []
    ) {
        $this->driver = $driver;
        $this->id = $id;
        $this->queue = $queue;
        $this->job = $job;
        $this->data = $data;
        $this->attempts = $attempts;
        $this->payload = $payload;
    }
    
    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Get the queue name.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }
    
    /**
     * Get the job payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    /**
     * Get the job class name.
     *
     * @return string|SerializableClosure
     */
    public function getJob(): string|SerializableClosure
    {
        return $this->job;
    }
    
    /**
     * Get the job data.
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }
    
    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return $this->attempts;
    }
    
    /**
     * Handle the job.
     *
     * @return mixed
     */
    public function handle(): mixed
    {
        $this->attempts++;
        
        try {
            if ($this->job instanceof SerializableClosure) {
                return ($this->job)($this->data);
            }
            
            $instance = new $this->job($this->data);
            
            if (method_exists($instance, 'handle')) {
                return $instance->handle();
            }
            
            return $instance($this->data);
        } catch (Throwable $e) {
            $this->fail($e);
            
            throw $e;
        }
    }
    
    /**
     * Release the job back onto the queue.
     *
     * @param int $delay The delay in seconds
     * @return void
     */
    public function release(int $delay = 0): void
    {
        $this->driver->later(
            $delay,
            $this->job,
            $this->data,
            $this->queue
        );
        
        $this->delete();
    }
    
    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void
    {
        // The implementation will depend on the driver, but typically
        // the driver will need to remove the job from its storage
    }
    
    /**
     * Mark the job as failed.
     *
     * @param Throwable $e
     * @return void
     */
    public function fail(Throwable $e): void
    {
        // Here you might log the failed job, notify someone, etc.
        // For now, we'll just delete it
        $this->delete();
    }
    
    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return json_encode($this->payload);
    }
}
