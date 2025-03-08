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

    protected mixed $output;
    protected mixed $errorOutput;

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
                throw ProcessException::fromMessage("Timeout reached while waiting for processes to finnish.");
            }
            
            usleep(1000); 
        }

        // Get the ouput
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
        if (is_resource($this->process))
        {
            foreach ($this->pipes as $pipe)
            {
                if (is_resource($pipe))
                {
                    fclose($pipe);
                }
            }

            // Kill process
            proc_terminate($this->process);

            $this->isRunning = false;
        }
        
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

        try {
            return unserialize($output);
        } catch (\Throwable $e)
        {
            throw ProcessException::fromMessage("Failed to unserialize output");
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
        $this->stop();
    }
}
