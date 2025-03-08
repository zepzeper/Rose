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
}
