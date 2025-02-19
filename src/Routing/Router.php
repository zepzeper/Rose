<?php

namespace Rose\Routing;

use Closure;
use InvalidArgumentException;
use Rose\Contracts\Routing\Router as RouterContract;

class Router implements RouterContract
{
    /** @var array Route collection */
    protected array $routes = [];
    
    /** @var array Middleware groups */
    protected array $middlewareGroups = [];
    
    /** @var array Global patterns for route parameters */
    protected array $patterns = [];
    
    /** @var array Current group stack */
    protected array $groupStack = [];
    
    /** @var array Named routes */
    protected array $namedRoutes = [];
    
    /** @var string Current route name */
    protected ?string $currentRouteName = null;

    /** @var array Current route middleware */
    protected array $currentMiddleware = [];

    /** @var string|null Current domain */
    protected ?string $currentDomain = null;

    public function add(string $uri, string $controller, string $action, ?Closure $callback = null): self
    {
        if (! is_null($callback))
        {
            return $callback();
        }
        
        return $this;
    }

    public function get(string $uri, string $controller, string $action): self
    {
        return $this->addRoute('GET', $uri, $controller, $action);
    }

    public function post(string $uri, string $controller, string $action): self
    {
        return $this->addRoute('POST', $uri, $controller, $action);
    }

    public function put(string $uri, string $controller, string $action): self
    {
        return $this->addRoute('PUT', $uri, $controller, $action);
    }

    public function patch(string $uri, string $controller, string $action): self
    {
        return $this->addRoute('PATCH', $uri, $controller, $action);
    }

    public function delete(string $uri, string $controller, string $action): self
    {
        return $this->addRoute('DELETE', $uri, $controller, $action);
    }

    public function name(string $name): self
    {
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        return $this;
    }

    public function middlewareGroup(string $name, array $middleware): self
    {
        return $this;
    }

    public function pattern(string $key, string $pattern): self
    {
        return $this;
    }

    public function domain(string $domain): self
    {
        return $this;
    }

    public function group(array $attributes, Closure $callback): self
    {
        return $this;
    }

    public function namespace(string $namespace, Closure $callback): self
    {
        return $this;
    }

    public function resource(string $name, string $controller): self
    {
        return $this;
    }

    public function fallback(string $controller, string $action): self
    {
        return $this;
    }

    public function generateUrl(string $name, array $parameters = []): string
    {
        return '';
    }

    public function dispatch(string $uri, string $method): mixed
    {
        return null;
    }

    protected function addRoute(string $method, string $uri, string $controller, string $action): self
    {
        return $this;
    }

    protected function getCurrentGroup(): array
    {
        return [];
    }

    protected function mergeWithLastGroup(array $new): array
    {
        return [];
    }

    protected function processMiddlewareGroups(array $middleware): array
    {
        return [];
    }

    protected function buildRoute(string $method, string $uri, string $controller, string $action): array
    {
        return [];
    }
}
