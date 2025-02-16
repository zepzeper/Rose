<?php

namespace Rose\Routing;

use Closure;
use Rose\Contracts\Routing\Register;

class Router implements Register
{

    protected array $routes = [];
    protected array $params = [];

    protected string $controllerSuffix = 'Controller';
    private string $actionSuffix = 'Action';

    protected string $namespace = 'Rose\Http\Controller\\';

    /**
    *
    * @param string $route
    * @param array $params
    * @param Closure|string|null $callback
    *
    * @return void
    */
    public function add($route, $params = [], $callback = null)
    {
        if ($callback !== null) {
            return $callback($params);
        }

        // Convert the route to a regular expression: escape forward slashes
        $route = preg_replace('/\//', '\\/', $route);
        // Convert variables e.g. {controller}
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        // Convert variables with custom regular expressions e.g. {id:\d+}
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        // Add start and end delimiters, and case insensitive flag
        $route = '/^' . $route . '$/i';

        $this->routes[$route] = $params;
    }

    public function dispatch(string $url) 
    {
            
    }

    public function getParams(): array 
    {
        return $this->params;
    }

    public function getRoutes(): array 
    {
        return $this->routes;
    }
}
