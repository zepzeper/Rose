<?php

namespace Rose\Contracts\Routing;

use Closure;
use Rose\Routing\Route;

interface Router
{
    // Core routing methods
    public function add(string|array $methods, string $uri, string $controller, string $action, ?Closure $callback = null): Route;
    public function get(string $uri, string $controller, string $action): Route;
    public function post(string $uri, string $controller, string $action): Route;
    public function put(string $uri, string $controller, string $action): Route;
    public function patch(string $uri, string $controller, string $action): Route;
    public function delete(string $uri, string $controller, string $action): Route;
    
    // Route organization
    public function group(array $attributes, Closure $callback): self;
    
    /**
    * Route handling
    *
    * @param string $uri
    * @param string $method
    * @return mixed
    * @throws \InvalidArgumentException|\RouteNotFoundException
    */
    public function dispatch(string $uri, string $method);
}
