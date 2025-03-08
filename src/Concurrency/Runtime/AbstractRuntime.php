<?php

namespace Rose\Concurrency\Runtime;

abstract class AbstractRuntime
{
    protected const DESCRIPTOR = [
        0 => ['pipe', 'r'], // stdin
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w'] // stderr
    ];
    
    public function start(): array
    {
        return [];
    }

    protected function hasPcntl(): bool
    {
        return function_exists('pcntl_fork');
    }

    protected function procOpen($command, $descriptorspec, &$pipes, $cwd = null, $env = null)
    {
        return proc_open($command, $descriptorspec, $pipes, $cwd, $env);
    }

}
