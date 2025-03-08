<?php

namespace Rose\Concurrency\Runtime;

use Rose\Concurrency\Runtime\AbstractRuntime;
use Rose\Exceptions\Concurrency\ProcessException;

class AsyncRuntime extends AbstractRuntime
{
    protected string $workerScript;

    /**
     * Create a new async runtime instance
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
        // Set environment variables to make the process async
        $env = [];
        foreach ($_ENV as $key => $value) {
            $env[$key] = $value;
        }
        $env['ASYNC_PROCESS'] = 'true';
        
        // Start process with correct PHP path
        $phpBinary = PHP_BINARY;
        $command = escapeshellcmd($phpBinary) . ' ' . escapeshellarg($this->workerScript);
        
        $pipes = [];
        $process = $this->procOpen(
            $command,
            self::DESCRIPTOR,
            $pipes,
            null,
            $env
        );
        
        if (!is_resource($process)) {
            throw ProcessException::fromMessage('Failed to start process');
        }
        
        // Set stdout and stderr to non-blocking mode
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        return [$process, $pipes];       
    }
}
