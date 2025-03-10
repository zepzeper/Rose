<?php

namespace Rose\Queue\Drivers;

use Redis;
use Rose\Contracts\Queue\Job as JobContract;
use Rose\Contracts\Queue\QueueDriver;
use Rose\Queue\Job;
use Rose\Support\SerializableClosure;

class RedisQueueDriver implements QueueDriver
{
    /**
     * The Redis connection.
     *
     * @var Redis
     */
    protected Redis $redis;
    
    /**
     * The connection name.
     *
     * @var string
     */
    protected string $name;
    
    /**
     * The key prefix.
     * 
     * @var string
     */
    protected string $keyPrefix = 'queue:';
    
    /**
     * Create a new Redis queue driver instance.
     *
     * @param Redis $redis
     * @param string $keyPrefix
     * @return void
     */
    public function __construct(Redis $redis, string $keyPrefix = 'queue:')
    {
        $this->redis = $redis;
        $this->keyPrefix = $keyPrefix;
    }
    
    /**
     * {@inheritdoc}
     */
    public function push(string $job, mixed $data = null, string $queue = 'default'): string
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }
    
    /**
     * {@inheritdoc}
     */
    public function later(int $delay, string $job, mixed $data = null, string $queue = 'default'): string
    {
        $payload = $this->createPayload($job, $data);
        
        // Get the current unix timestamp
        $now = time();
        
        // Add the job to the delayed set with the score being the timestamp when it should run
        $this->redis->zAdd(
            $this->getDelayedKey($queue),
            $now + $delay,
            json_encode($payload)
        );
        
        return $payload['id'];
    }
    
    /**
     * Push a raw payload onto the queue.
     *
     * @param array $payload
     * @param string $queue
     * @return string
     */
    protected function pushRaw(array $payload, string $queue = 'default'): string
    {
        $this->redis->rPush(
            $this->getKey($queue),
            json_encode($payload)
        );
        
        return $payload['id'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function pop(string $queue = 'default'): ?JobContract
    {
        // Move expired delayed jobs to the queue
        $this->migrateExpiredJobs($queue);
        
        // Try to get a job from the queue
        $payload = $this->redis->lPop($this->getKey($queue));
        
        if (!$payload) {
            return null;
        }
        
        $payload = json_decode($payload, true);
        $job = $payload['job'];
        $data = $payload['data'] ?? null;
        $attempts = $payload['attempts'] ?? 0;
        
        // Increment the attempts count for the next time the job is popped
        $payload['attempts'] = $attempts + 1;
        
        return new Job(
            $this,
            $payload['id'],
            $queue,
            $job,
            $data,
            $attempts,
            $payload
        );
    }
    
    /**
     * Migrate any delayed jobs that are ready to be processed.
     *
     * @param string $queue
     * @return void
     */
    protected function migrateExpiredJobs(string $queue): void
    {
        $now = time();
        
        // Get all jobs that should have run by now
        $jobs = $this->redis->zRangeByScore(
            $this->getDelayedKey($queue),
            '-inf',
            $now
        );
        
        if (empty($jobs)) {
            return;
        }
        
        // Begin a multi/exec transaction
        $this->redis->multi();
        
        // Move jobs to the main queue
        foreach ($jobs as $job) {
            $this->redis->rPush($this->getKey($queue), $job);
            $this->redis->zRem($this->getDelayedKey($queue), $job);
        }
        
        // Execute the transaction
        $this->redis->exec();
    }
    
    /**
     * Create a payload for the given job.
     *
     * @param string|SerializableClosure $job
     * @param mixed $data
     * @return array
     */
    protected function createPayload(string|SerializableClosure $job, mixed $data): array
    {
        return [
            'id' => $this->generateJobId(),
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
            'created_at' => time(),
        ];
    }
    
    /**
     * Generate a unique job ID.
     *
     * @return string
     */
    protected function generateJobId(): string
    {
        return uniqid('job_', true);
    }
    
    /**
     * Get the Redis key for the given queue.
     *
     * @param string $queue
     * @return string
     */
    protected function getKey(string $queue): string
    {
        return $this->keyPrefix . $queue;
    }
    
    /**
     * Get the Redis key for the delayed queue.
     *
     * @param string $queue
     * @return string
     */
    protected function getDelayedKey(string $queue): string
    {
        return $this->keyPrefix . $queue . ':delayed';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConnectionName(): string
    {
        return $this->name ?? 'redis';
    }
    
    /**
     * {@inheritdoc}
     */
    public function setConnectionName(string $name): QueueDriver
    {
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function size(string $queue = 'default'): int
    {
        return $this->redis->lLen($this->getKey($queue));
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear(string $queue = 'default'): void
    {
        $this->redis->del($this->getKey($queue));
        $this->redis->del($this->getDelayedKey($queue));
    }
}
