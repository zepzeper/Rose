<?php

namespace Rose\Contracts\Routing;

use Closure;
use Rose\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

interface Router
{
    // Core routing methods
    public function add(string|array $methods, string $uri, string $controller, string $action, ?Closure $callback = null): Route;
    public function get(string $uri, string $controller, string $action, ?Closure $callback = null): Route;
    public function post(string $uri, string $controller, string $action, ?Closure $callback = null): Route;
    public function put(string $uri, string $controller, string $action, ?Closure $callback = null): Route;
    public function patch(string $uri, string $controller, string $action, ?Closure $callback = null): Route;
    public function delete(string $uri, string $controller, string $action, ?Closure $callback = null): Route;
    
    // Route organization
    public function group(array $attributes, Closure $callback): self;
    
    /**
    * Route handling
    *
    * @param Request $request
    * @return mixed
    * @throws \InvalidArgumentException|\RouteNotFoundException
    */
    public function dispatch(Request $request);
}
