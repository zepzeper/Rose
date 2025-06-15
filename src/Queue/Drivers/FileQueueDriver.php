<?php

namespace Rose\Queue\Drivers;

use Rose\Contracts\Queue\Job as JobContract;
use Rose\Contracts\Queue\QueueDriver;
use Rose\Queue\Job;
use Rose\Support\SerializableClosure;

class FileQueueDriver implements QueueDriver
{
    /**
     * The connection name.
     *
     * @var string
     */
    protected string $name;
    
    /**
     * The base path for storing queued jobs.
     *
     * @var string
     */
    protected string $basePath;
    
    /**
     * Create a new file queue driver instance.
     *
     * @param string $basePath
     * @return void
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        
        // Ensure the base directory exists
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
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
        $payload['available_at'] = time() + $delay;
        
        return $this->pushRaw($payload, $queue);
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
        $this->ensureQueueDirectoryExists($queue);
        
        $jobId = $payload['id'];
        $jobFile = $this->getJobFilePath($queue, $jobId);
        
        file_put_contents($jobFile, json_encode($payload));
        
        // If this is a delayed job, we'll put it in a separate directory
        if (isset($payload['available_at']) && $payload['available_at'] > time()) {
            $delayDir = $this->getQueuePath($queue) . '/delayed';
            if (!is_dir($delayDir)) {
                mkdir($delayDir, 0755, true);
            }
            
            $delayedFile = $delayDir . '/' . $jobId;
            file_put_contents($delayedFile, $payload['available_at']);
        }
        
        return $jobId;
    }
    
    /**
     * {@inheritdoc}
     */
    public function pop(string $queue = 'default'): ?JobContract
    {
        $this->ensureQueueDirectoryExists($queue);
        $this->moveExpiredDelayedJobs($queue);
        
        $queuePath = $this->getQueuePath($queue);
        $files = glob($queuePath . '/*.job');
        
        if (empty($files)) {
            return null;
        }
        
        // Sort by creation time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });
        
        // Get the first job file
        $jobFile = $files[0];
        $jobId = basename($jobFile, '.job');

				if (@file_get_contents($queuePath . '/delayed/' . $jobId) > time()) {
					// Delayed job
					return null;
				}
        
        // Read and delete the job file
        $payload = $this->readAndDeleteJobFile($jobFile);
        if ($payload === null) {
            return null;
        }

        
        // Create a job instance
        $job = $payload['job'];
        $data = $payload['data'] ?? null;
        $attempts = $payload['attempts'] ?? 0;
        
        return new Job(
            $this,
            $jobId,
            $queue,
            $job,
            $data,
            $attempts,
            $payload
        );
    }
    
    /**
     * Read and delete a job file.
     *
     * @param string $jobFile
     * @return array|null
     */
    protected function readAndDeleteJobFile(string $jobFile): ?array
    {
        // Use file locking to prevent race conditions
        $fp = fopen($jobFile, 'r+');
        if (!$fp) {
            return null;
        }
        
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            // Another process has the lock, skip this file
            fclose($fp);
            return null;
        }
        
        $content = fread($fp, filesize($jobFile));
        $payload = json_decode($content, true);
        
        // Increment the attempts count
        $payload['attempts'] = ($payload['attempts'] ?? 0) + 1;
        
        // Delete the file
        ftruncate($fp, 0);
        flock($fp, LOCK_UN);
        fclose($fp);
        unlink($jobFile);
        
        return $payload;
    }
    
    /**
     * Move expired delayed jobs to the main queue directory.
     *
     * @param string $queue
     * @return void
     */
    protected function moveExpiredDelayedJobs(string $queue): void
    {
        $delayDir = $this->getQueuePath($queue) . '/delayed';
        if (!is_dir($delayDir)) {
            return;
        }
        
        $now = time();
        $files = glob($delayDir . '/*');
        
        foreach ($files as $file) {
            $jobId = basename($file);
            $availableAt = (int) file_get_contents($file);
            
            if ($availableAt <= $now) {
                $jobFile = $this->getQueuePath($queue) . '/' . $jobId . '.job';
                if (file_exists($jobFile)) {
                    // Move from delayed to ready
                    unlink($file);
                }
            }
        }
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
     * Ensure that the queue directory exists.
     *
     * @param string $queue
     * @return void
     */
    protected function ensureQueueDirectoryExists(string $queue): void
    {
        $queuePath = $this->getQueuePath($queue);
        
        if (!is_dir($queuePath)) {
            mkdir($queuePath, 0755, true);
        }
    }
    
    /**
     * Get the path for the specified queue.
     *
     * @param string $queue
     * @return string
     */
    protected function getQueuePath(string $queue): string
    {
        return $this->basePath . '/' . $queue;
    }
    
    /**
     * Get the file path for a job.
     *
     * @param string $queue
     * @param string $jobId
     * @return string
     */
    protected function getJobFilePath(string $queue, string $jobId): string
    {
        return $this->getQueuePath($queue) . '/' . $jobId . '.job';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConnectionName(): string
    {
        return $this->name ?? 'file';
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
        $this->ensureQueueDirectoryExists($queue);
        $this->moveExpiredDelayedJobs($queue);

        $queuePath = $this->getQueuePath($queue);
        $files = glob($queuePath . '/*.job');
        
        return count($files);
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear(string $queue = 'default'): void
    {
        $queuePath = $this->getQueuePath($queue);
        
        if (is_dir($queuePath)) {
            $files = glob($queuePath . '/*.job');
            foreach ($files as $file) {
                unlink($file);
            }
            
            $delayDir = $queuePath . '/delayed';
            if (is_dir($delayDir)) {
                $delayedFiles = glob($delayDir . '/*');
                foreach ($delayedFiles as $file) {
                    unlink($file);
                }
            }
        }
    }
}
