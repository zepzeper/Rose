<?php

namespace Rose\Exceptions\Concurrency;

class PoolOverflowException extends \Exception
{
    /**
     * Create a new pool overflow exception.
     *
     * @param int $maxProcesses
     * @return static
     */
    public static function create(int $maxProcesses): self
    {
        return new static("The pool has reached its maximum size of {$maxProcesses} processes.");
    }
}
