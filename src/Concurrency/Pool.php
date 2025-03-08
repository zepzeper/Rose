<?php

namespace Rose\Concurrency;

use Closure;
use Exception;
use Rose\Concurrency\Events\ProcessFailed;
use Rose\Concurrency\Events\ProcessSucceeded;
use Rose\Concurrency\Runtime\AsyncRuntime;
use Rose\Concurrency\Runtime\ParallelRuntime;
use Rose\Contracts\Concurrency\Pool as PoolContract;
use Rose\Concurrency\PoolStatus;
use Rose\Exceptions\Concurrency\PoolOverflowException;
use Rose\Support\SerializableClosure;

class Pool implements PoolContract
{

    protected array $queue = [];
    protected array $runningProcesses = [];

    protected array $successful = [];
    protected array $failed = [];
    
    protected Closure|null $beforeTask = null;
    protected Closure|null $successCallback = null;
    protected Closure|null $failedCallback = null;

    protected int $concurrency;
    protected string $runtime;
    protected string $worker;

    public function __construct(int $concurrency = 5, string $runtime = "async") {
        $this->concurrency = $concurrency;
        $this->runtime = $runtime;
        $this->worker = realpath(__DIR__ . "/worker.php");
    }

    /**
     * Create a new pool with default options.
     *
     * @return static
     */
    public static function create(): self
    {
        return new static();
    }
    
    /**
     * Create a new pool with async runtime.
     *
     * @param int $concurrency
     * @return static
     */
    public static function async(int $concurrency = 5): self
    {
        return new static($concurrency, 'async');
    }
    
    /**
     * Create a new pool with parallel runtime.
     *
     * @param int $concurrency
     * @return static
     */
    public static function parallel(int $concurrency = 5): self
    {
        return new static($concurrency, 'parallel');
    }

    /**
     * {@inheritdoc}
     */
    public function add(callable $task): self
    {
        $this->queue[] = $task;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(): self
    {
        while (count($this->queue) > 0 && count($this->runningProcesses) < $this->concurrency)
        {
            $task = array_shift($this->queue);

            if ($this->beforeTask)
            {
                call_user_func($this->beforeTask, $task);
            }

            $process = $this->createProcess($task);
            $process->start();

            $this->runningProcesses[] = $process;
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wait(?int $timeout = null): self
    {
        $startTime = time();
        
        while (count($this->runningProcesses) > 0) {
            foreach ($this->runningProcesses as $index => $process) {
                // Skip if process is already done
                if (!$process->isRunning()) {
                    // Get exit code (will also finalize the process)
                    $exitCode = $process->wait(0); // 0 timeout since it's already done
                    
                    // Remove from running processes
                    unset($this->runningProcesses[$index]);
                    
                    // Process output or exception
                    if ($exitCode === 0) {
                        $output = $process->getOutput();
                        
                        // Add to successful processes
                        $this->successful[] = $process;
                        
                        // Call success callback if set
                        if ($this->successCallback) {
                            $event = new ProcessSucceeded($process, $output);
                            call_user_func($this->successCallback, $event);
                        }
                    } else {
                        // Create exception from output
                        $exception = $this->createExeptionFromOutput($process->getOutput());
                        
                        // Add to failed processes
                        $this->failed[] = $process;
                        
                        // Call failure callback if set
                        if ($this->failedCallback) {
                            $event = new ProcessFailed($process, $exception);
                            call_user_func($this->failedCallback, $event);
                        }
                    }
                    
                    continue;
                }
                
                // Check for timeout
                if ($timeout !== null && (time() - $startTime) >= $timeout) {
                    // Stop all remaining processes
                    foreach ($this->runningProcesses as $runningProcess) {
                        $runningProcess->stop();
                    }
                    
                    return $this;
                }
            }
            
            // Start new processes if queue is not empty
            if (count($this->queue) > 0) {
                $this->run();
            }
            
            // Small delay to prevent CPU hogging
            usleep(1000);
            
            // Reset array keys after potential removal
            $this->runningProcesses = array_values($this->runningProcesses);
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function status(): PoolStatus
    {
        return new PoolStatus(
            count($this->queue),
            count($this->runningProcesses),
            count($this->successful),
            count($this->failed),
        );
    }
    
    /**
     * Set a callback to be executed before a task is started.
     *
     * @param callable $callback.
     * @return self
     */
    public function beforeTask(callable $callback): Pool
    {
        $this->beforeTask = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function concurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whenTaskSucceeded(callable $callback): self
    {
        $this->successCallback = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whenTaskFailed(callable $callback): self
    {
        $this->failedCallback = $callback;

        return $this;
    }

    /**
     * Create a process for the given task
     *
     * @param callable $task.
     * @return Process
     * @throws PoolOverflowException
     */
    protected function createProcess(callable $task): Process
    {
        if (count($this->runningProcesses) >= $this->concurrency)
        {
            throw PoolOverflowException::create($this->concurrency);
        }

        $serialzedTask = new SerializableClosure($task);

        // Create process based on runtime
        $runtime = $this->runtime === "parallel" ? new ParallelRuntime($this->worker) : new AsyncRuntime($this->worker);

        return new Process($runtime, $serialzedTask);
    }

    /**
     * Create an exception from the process output
     *
     * @param array|mixed $output.
     * @return \Throwable
     */
    protected function createExeptionFromOutput($output): \Throwable
    {
        // Handle non-array output
        if (!is_array($output) || !isset($output['error'])) {
            return new Exception("Unknown error occurred", 0);
        }

        $error = $output['error'];

        $exception = new Exception($error['message'] ?? "Unknown error.", $error['code'] ?? 0);

        return $exception;
    }

}
