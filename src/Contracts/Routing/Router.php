<?php

namespace Rose\Contracts\Routing;

use Closure;
use Rose\Routing\RouterParameters;

interface Router
{
    public function add(string $uri, string $controller, string $action, ?Closure $callback = null): self;
    public function get(string $uri, string $controller, string $action): self;
    public function post(string $uri, string $controller, string $action): self;
    public function put(string $uri, string $controller, string $action): self;
    public function patch(string $uri, string $controller, string $action): self;
    public function delete(string $uri, string $controller, string $action): self;
    public function name(string $name): self;
    public function middleware(array|string $middleware): self;
    public function middlewareGroup(string $name, array $middleware): self;
    public function pattern(string $key, string $pattern): self;
    public function domain(string $domain): self;
    public function group(array $attributes, Closure $callback): self;
    public function namespace(string $namespace, Closure $callback): self;
    public function resource(string $name, string $controller): self;
    public function fallback(string $controller, string $action): self;
    public function generateUrl(string $name, array $parameters = []): string;
    public function dispatch(string $uri, string $method): mixed;
}
