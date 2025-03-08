<?php

namespace Rose\Concurrency\Runtime;

use Rose\Concurrency\Runtime\AbstractRuntime;
use Rose\Exceptions\Concurrency\ProcessException;

class ParallelRuntime extends AbstractRuntime
{
    protected string $workerScript;

    /**
     * Create a new parallel runtime instance
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
        if ($this->hasPcntl())
        {
            return $this->startWithPcntl();
        }

        // Fall back to proc_open
        return $this->startWithProcOpen();
    }

    /**
     * Start process using proc_open
     * 
     * @return array
     */
    protected function startWithProcOpen(): array
    {
        // Set environment variables
        $env = [];
        foreach ($_ENV as $key => $value) {
            $env[$key] = $value;
        }
        $env['PARALLEL_PROCESS'] = 'true';
        
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
            throw ProcessException::fromMessage('Failed to start process using proc_open');
        }
        
        // Set stdout and stderr to non-blocking mode for better performance
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        return [$process, $pipes];
    }

    /**
     * Start a new process using pcntl_fork.
     *
     * @return array
     */
    protected function startWithPcntl(): array
    {
        // This is a simplified implementation for testing purposes
        // A real implementation would manage pipes between processes
        $pipes = [];
        
        // Create pipes for stdin, stdout, stderr
        $stdinPipes = [];
        $stdoutPipes = [];
        $stderrPipes = [];
        
        if (!pipe($stdinPipes) || !pipe($stdoutPipes) || !pipe($stderrPipes)) {
            throw ProcessException::fromMessage('Failed to create pipes');
        }
        
        // Fork process
        $pid = pcntl_fork();
        
        if ($pid == -1) {
            throw ProcessException::fromMessage('Failed to fork process');
        }
        
        if ($pid) {
            // Parent process
            // Close unused pipe ends
            fclose($stdinPipes[0]);  // Close reading end of stdin
            fclose($stdoutPipes[1]); // Close writing end of stdout
            fclose($stderrPipes[1]); // Close writing end of stderr
            
            $pipes = [
                $stdinPipes[1],  // Writing end for stdin
                $stdoutPipes[0], // Reading end for stdout
                $stderrPipes[0]  // Reading end for stderr
            ];
            
            // Create a process resource for compatibility with the interface
            $process = proc_open(
                'echo', // Dummy command that does nothing
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ],
                $dummyPipes
            );
            
            return [$process, $pipes];
        } else {
            // Child process
            try {
                // Close unused pipe ends
                fclose($stdinPipes[1]);  // Close writing end of stdin
                fclose($stdoutPipes[0]); // Close reading end of stdout
                fclose($stderrPipes[0]); // Close reading end for stderr
                
                // Redirect stdin, stdout, stderr
                fclose(STDIN);
                fclose(STDOUT);
                fclose(STDERR);
                
                define('STDIN', $stdinPipes[0]);
                define('STDOUT', $stdoutPipes[1]);
                define('STDERR', $stderrPipes[1]);
                
                // Execute worker script
                require $this->workerScript;
                
                exit(0);
            } catch (\Throwable $e) {
                // Write error to stderr
                fwrite($stderrPipes[1], $e->getMessage());
                exit(1);
            }
        }
    }

    /**
     * Check if PCNTL extension is available
     * 
     * @return bool
     */
    protected function hasPcntl(): bool
    {
        return function_exists('pcntl_fork') && function_exists('posix_mkfifo');
    }
}
