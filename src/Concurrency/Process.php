<?php

namespace Rose\Concurrency;

use Rose\Contracts\Concurrency\Process as ProcessContract;
use Rose\Exceptions\Concurrency\ProcessException;
use Rose\Support\SerializableClosure;

class Process implements ProcessContract
{
    protected object $runtime;

    /**
     * @var resource|null.
     */
    protected $process;

    /**
     * @var resource|null.
     */
    protected $stdin;

    /**
     * @var resource|null.
     */
    protected $stdout;

    /**
     * @var resource|null.
     */
    protected $stderr;

    protected array $pipes = [];

    protected ?int $pid = null;
    
    protected bool $isRunning = false;
    protected ?int $exitCode = null;

    protected mixed $output = null;
    protected mixed $errorOutput = null;

    protected SerializableClosure $task;

    /**
     * Create a new instance of a process
     *
     * @param object $runtime.
     * @param SerializableClosure $task.
     */
    public function __construct($runtime, SerializableClosure $task) 
    {
        $this->runtime = $runtime;
        $this->task = $task;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): self
    {
        try {
            [$this->process, $this->pipes] = $this->runtime->start();

            $this->stdin = $this->pipes[0];
            $this->stdout = $this->pipes[1];
            $this->stderr = $this->pipes[2];

            // Write serialized task to the process
            $serialized = serialize($this->task);
            fwrite($this->stdin, $serialized . PHP_EOL);
            fclose($this->stdin);

            $status = proc_get_status($this->process);
            $this->pid = $status['pid'];
            $this->isRunning = $status['running'];

            return $this;
        } catch (\Throwable $e)
        {
            throw ProcessException::fromMessage("Failed to start process {$e->getMessage()}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function wait(?int $timeout = null): int
    {
        $start = time();
        while ($this->isRunning())
        {
            if ($timeout !== null && time() - $start >= $timeout)
            {
                $this->stop();
                throw ProcessException::fromMessage("Timeout reached while waiting for processes to finish.");
            }
            
            usleep(1000); 
        }

        // Get the output
        $this->output = $this->readOutput();
        $this->errorOutput = $this->readErrorOutput();

        // Close remaining pipes
        foreach ($this->pipes as $pipe)
        {
            if (is_resource($pipe))
            {
                fclose($pipe);
            }
        }

        // Close remaining resources
        if (is_resource($this->process))
        {
            $this->exitCode = proc_close($this->process);
        }

        return $this->exitCode ?? -1;
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning(): bool
    {
        if (! is_resource($this->process))
        {
            return false;
        }

        $status = proc_get_status($this->process);
        
        // If status is false, the process is not valid
        if ($status === false) {
            $this->isRunning = false;
            return false;
        }
        
        $this->isRunning = $status['running'];

        if (! $this->isRunning && $this->exitCode === null)
        {
            $this->exitCode = $status['exitcode'];
        }

        return $this->isRunning;
    }

    /**
     * {@inheritdoc}
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): self
    {
        // Close pipes first
        foreach ($this->pipes as $index => $pipe)
        {
            if (is_resource($pipe))
            {
                fclose($pipe);
            }
            unset($this->pipes[$index]);
        }
        
        // Reset pipes array
        $this->pipes = [];

        // Only try to terminate if it's a resource and still running
        if (is_resource($this->process))
        {
            // Check if it's a valid process before terminating
            $status = @proc_get_status($this->process);
            if ($status !== false && isset($status['running']) && $status['running']) {
                @proc_terminate($this->process);
            }
            
            // Close the process resource
            @proc_close($this->process);
            $this->process = null;
        }
        
        $this->isRunning = false;
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput(): mixed
    {
        if ($this->output === null)
        {
            $this->output = $this->readOutput();
        }
        
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorOutput(): mixed
    {
        if ($this->errorOutput === null)
        {
            $this->errorOutput = $this->readErrorOutput();
        }

        return $this->errorOutput;
    }

    /**
     * Read the output of the process
     *
     * @return mixed.
     * @throws ProcessException
     */
    protected function readOutput()
    {
        if (! is_resource($this->stdout))
        {
            return null;
        }

        $output = stream_get_contents($this->stdout);
        
        // If output is empty, return null
        if (empty($output)) {
            return null;
        }

        try {
            $unserialized = @unserialize($output);
            
            // If we successfully unserialized a structured response
            if (is_array($unserialized) && isset($unserialized['success'])) {
                return $unserialized['success'] ? $unserialized['result'] : null;
            }
            
            // Otherwise return the raw output or unserialized data
            return $unserialized !== false ? $unserialized : $output;
        } catch (\Throwable $e) {
            // If unserialize fails, return the raw output
            return $output;
        }
    }

    /**
     * Read the error output of the process
     *
     * @return mixed.
     */
    protected function readErrorOutput(): string
    {
        if (!is_resource($this->stderr)) {
            return '';
        }
        
        return stream_get_contents($this->stderr);
    }

    public function __destruct()
    {
        try {
            $this->stop();
        } catch (\Throwable $e) {
            // Silently handle destruction errors
        }
    }
}
