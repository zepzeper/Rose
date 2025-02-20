<?php

namespace Rose\Support\Traits;

use BadMethodCallException;

trait ForwardCalls
{
    protected function forwardCallTo($object, $method, $parameters)
    {
        try {
            return $object->{$method}(...$parameters);
        } catch (BadMethodCallException $e)
        {
            throw $e;
        }
    }
}
