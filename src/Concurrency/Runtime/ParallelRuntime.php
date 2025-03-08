<?php

namespace Rose\Concurrency\Runtime;

use Rose\Concurrency\Runtime\AbstractRuntime;
use Rose\Exceptions\Concurrency\ProcessException;

class ParallelRuntime extends AbstractRuntime
{
    protected string $workerScript;

    /**
     * Create a new paralle runtime instance
     *
     * @param string $workerScript 
     * @return void 
     */
    public function __construct(string $workerScript) {
        $this->workerScript = $workerScript;
    }

    /**
     * Start a new process
     *
     * @return array.
     */
    public function start(): array
    {
        // Use process using pcntl_fork 
        if (function_exists('pcntl_fork'))
        {
            return $this->startWithPcntl();
        }
        // Fall back to proc_open
        $process = proc_open(
            'php ' . $this->workerScript,
            self::DESCRIPTOR,
            $pipes
        );
        
        if (!is_resource($process)) {
            throw ProcessException::fromMessage('Failed to start process');
        }
        
        return [$process, $pipes];

    }

    /**
     * Start a new process using pcntl_fork.
     *
     * @return array
     */
    protected function startWithPcntl(): array
    {
        // Communication pipes
        $stdin = [];
        $stdout = [];
        $stderr = [];

        if (!posix_mkfifo('/tmp/concurrency_stdin_' . uniqid(), 0600)) {
            throw ProcessException::fromMessage('Failed to create stdin fifo');
        }
        
        if (!posix_mkfifo('/tmp/concurrency_stdout_' . uniqid(), 0600)) {
            throw ProcessException::fromMessage('Failed to create stdout fifo');
        }
        
        if (!posix_mkfifo('/tmp/concurrency_stderr_' . uniqid(), 0600)) {
            throw ProcessException::fromMessage('Failed to create stderr fifo');
        }
        
        // Fork process
        $pid = pcntl_fork();

        if ($pid == -1)
        {
            throw ProcessException::fromMessage('Failed to fork process');
        }

        if ($pid) {
            // Parent process
            $pipes = [
                fopen($stdin[0], 'r'),
                fopen($stdout[0], 'w'),
                fopen($stderr[0], 'w'),
            ];
            
            // Create a process resource
            $process = proc_open(
                'echo',
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $dummyPipes
            );
            
            return [$process, $pipes];
        } else {
            // Child process
            // Redirect stdin, stdout, stderr to fifos
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            
            $stdin  = fopen($stdin[1], 'r');
            $stdout = fopen($stdout[1], 'w');
            $stderr = fopen($stderr[1], 'w');
            
            // Execute worker script
            require $this->workerScript;
            
            exit(0);
        }

    }
}
