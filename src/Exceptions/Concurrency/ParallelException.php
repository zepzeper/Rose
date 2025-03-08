<?php

namespace Rose\Exceptions\Concurrency;

use Exception;

class ParallelException extends Exception
{
    protected array|string $exceptions = [];

    /**
     * @param array|string $exceptions 
     * @return static 
     */
    public function __construct(array|string $exceptions) {
        $exception = new static("Multiple exceptions happend.");
        $this->exceptions = $exceptions;
        
        return $exception;
    }

    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}
