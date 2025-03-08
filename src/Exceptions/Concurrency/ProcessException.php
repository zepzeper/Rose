<?php

namespace Rose\Exceptions\Concurrency;

class ProcessException extends \Exception
{
    /**
     * Create a new process exception.
     *
     * @param string $message
     * @return static
     */
    public static function fromMessage(string $message): self
    {
        return new static($message);
    }
}
