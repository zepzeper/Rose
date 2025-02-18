<?php

namespace Rose\Contracts\Container;

use Closure;
use Rose\Container\ContextualBindingBuilder;
use stdClass;

interface Container
{
    public function get(string $id): mixed;
    
    public function alias(string $abstract, string $alias): void;
    
    public function bind(string $abstract, Closure|string|null $callback = null, bool $shared = false): void;
    
    public function bindMethod(array|string $method, Closure|string|null $callback = null): void;
    
    public function bindIf(string $abstract, Closure|string|null $callback = null, bool $shared = false): void;
    
    public function singleton(string $abstract, Closure|string|null $callback = null): void;
    
    public function singletonIf(string $abstract, Closure|string|null $callback = null): void;
    
    public function scoped(string $abstract, Closure|string|null $callback = null): void;
    
    public function scopedIf(string $abstract, Closure|string|null $callback = null): void;
    
    public function extend(string $abstract, Closure|string|null $callback = null): void;
    
    public function instance($abstract, mixed $instance): void;
    
    public function when(string|array $abstract): ContextualBindingBuilder;
    
    public function factory(string|stdClass $abstract): Closure;
    
    public function flush(): void;
    
    public function make(string $abstract, array $parameters = []): mixed;
    
    public function call(Closure $callback, array $parameters = [], mixed $default = null): mixed;
    
    public function resolved(string $abstract): bool;
    
    public function beforeResolve(string $abstract, ?Closure $callback = null): void;
    
    public function resolving(string $abstract, ?Closure $callback = null): mixed;
    
    public function afterResolve(string $abstract, ?Closure $callback = null): void;
}
