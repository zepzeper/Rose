<?php

namespace Rose\Routing;

use BadMethodCallException;

abstract class Controllers
{

    public function callAction($method, $parameters)
    {
        return $this->{$method}(...array_values($parameters));
    }

    public function __call($method, $parameters)
    {
        throw new BadMethodCallException("Method {$method} does not exist");
    }

}
