<?php

namespace Rose\Concurrency;

use Closure;
use Exception;
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
    
    protected int $concurrency;

    protected Closure|null $beforeTask;
    protected Closure|null $successCallback;
    protected Closure|null $failedCallback;

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
    public function wait(): self
    {

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
     * Create an exeption from the process output
     *
     * @param array $output.
     * @return \Throwable
     */
    protected function createExeptionFromOutput(array $output): \Throwable
    {
        $error = $output['error'];
        
        $exception = new Exception($error['message'] ?? "Unknown error.", $error['code'] ?? 0);

        return $exception;
    }

}
