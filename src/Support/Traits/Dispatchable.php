<?php

namespace Rose\Support\Traits;

trait Dispatchable
{
    /**
     * Dispatch the job with the given arguments after the specified delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  mixed  ...$args
     * @return mixed
     */
    public static function dispatchAfter($delay, ...$args)
    {
        return (new static(...$args))->delay($delay)->dispatch();
    }
    
    /**
     * Set the desired queue for the job.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->queue = $queue;
        
        return $this;
    }
    
    /**
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->connection = $connection;
        
        return $this;
    }
    
    /**
     * Set the delay in seconds for the job.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $this->parseDelay($delay);
        
        return $this;
    }
    
    /**
     * Dispatch the job to the queue.
     *
     * @return mixed
     */
    public function dispatch()
    {
        $queue = app('queue');
        
        // If delay is set, use later() method
        if (isset($this->delay) && $this->delay > 0) {
            return $queue->later(
                $this->delay,
                static::class,
                $this,
                $this->queue ?? null,
                $this->connection ?? null
            );
        }
        
        return $queue->push(
            static::class,
            $this,
            $this->queue ?? null,
            $this->connection ?? null
        );
    }
    
    /**
     * Parse the given delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return int
     */
    protected function parseDelay($delay)
    {
        // If the delay is an interval or DateTime instance, convert to seconds
        if ($delay instanceof \DateInterval) {
            $delay = (new \DateTime())->add($delay)->getTimestamp() - time();
        } elseif ($delay instanceof \DateTimeInterface) {
            $delay = $delay->getTimestamp() - time();
        }
        
        return max(0, $delay);
    }
}
