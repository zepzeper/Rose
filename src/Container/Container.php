<?php

namespace Rose\Container;

use ArrayAccess;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use Rose\Contracts\Container\Container as ContainerContract;
use RuntimeException;
use stdClass;

/**
 * Inversion of Control (IoC) Container implementation for dependency management.
 * Supports binding, singleton, contextual binding, method injection, and event hooks.
 */
class Container implements ContainerContract, ArrayAccess
{
    // Holds the global container instance if set
    protected static ?ContainerContract $instance = null;

    // Stores registered abstract bindings and their configurations
    protected array $bindings = [];

    // Maps aliases to their abstract names
    protected array $aliases = [];

    // Stores shared instances (singletons)
    protected array $instances = [];

    // Extenders to modify resolved instances
    protected array $extenders = [];

    // Callbacks to run before resolving dependencies
    protected array $beforeResolving = [];

    // Callbacks to run after resolving dependencies
    protected array $afterResolving = [];

    // Method-specific bindings
    protected array $methodBindings = [];

    // Contextual bindings for specific classes
    protected array $contextual = [];

    /**
     * Retrieve an entry from the container by its identifier.
     *
     * @param  string $id Identifier of the entry to look for.
     * @return mixed The resolved entry.
     */
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * Register an alias for an abstract name.
     *
     * @param string $abstract Abstract name to alias.
     * @param string $alias    Alias name.
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Register a binding in the container.
     *
     * @param string              $abstract Abstract name to bind.
     * @param Closure|string|null $callback Closure that returns the concrete implementation.
     * @param bool                $shared   Whether to share the instance (singleton).
     */
    public function bind(string $abstract, Closure|string|null $callback = null, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $callback ?? $abstract,
            'shared' => $shared
        ];
    }

    /**
     * Bind a method to a specific implementation.
     *
     * @param array|string        $method   Method identifier (either string or [class, method] array).
     * @param Closure|string|null $callback Closure to execute when method is called.
     */
    public function bindMethod(array|string $method, Closure|string|null $callback = null): void
    {
        if (is_array($method)) {
            [$class, $method] = $method;
            $this->methodBindings[$class][$method] = $callback;
        } else {
            $this->methodBindings[$method] = $callback;
        }
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param string              $abstract Abstract name to bind.
     * @param Closure|string|null $callback Closure that returns the concrete implementation.
     * @param bool                $shared   Whether to share the instance.
     */
    public function bindIf(string $abstract, Closure|string|null $callback = null, bool $shared = false): void
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $callback, $shared);
        }
    }

    /**
     * Register a shared binding (singleton).
     *
     * @param string              $abstract Abstract name to bind.
     * @param Closure|string|null $callback Closure that returns the concrete implementation.
     */
    public function singleton(string $abstract, Closure|string|null $callback = null): void
    {
        $this->bind($abstract, $callback, true);
    }

    /**
     * Register a shared binding if it hasn't been registered.
     *
     * @param string              $abstract Abstract name to bind.
     * @param Closure|string|null $callback Closure that returns the concrete implementation.
     */
    public function singletonIf(string $abstract, Closure|string|null $callback = null): void
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $callback);
        }
    }

    /**
     * Alias for singleton registration.
     *
     * @param string              $abstract Abstract name to bind.
     * @param Closure|string|null $callback Closure that returns the concrete implementation.
     */
    public function scoped(string $abstract, Closure|string|null $callback = null): void
    {
        $this->bind($abstract, $callback, true);
    }

    /**
     * Register a scoped binding if it hasn't been registered.
     *
     * @param string              $abstract Abstract name to bind.
     * @param Closure|string|null $callback Closure that returns the concrete implementation.
     */
    public function scopedIf(string $abstract, Closure|string|null $callback = null): void
    {
        if (!$this->bound($abstract)) {
            $this->scoped($abstract, $callback);
        }
    }

    /**
     * Extend an abstract's resolution with additional functionality.
     *
     * @param string              $abstract Abstract name to extend.
     * @param Closure|string|null $callback Closure that modifies the resolved instance.
     */
    public function extend(string $abstract, Closure|string|null $callback = null): void
    {
        $this->extenders[$abstract][] = $callback;
    }

    /**
     * Register an existing instance as a shared instance.
     *
     * @param string $abstract Abstract name to bind.
     * @param mixed  $instance The instance to share.
     */
    public function instance($abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Begin contextual binding for specific classes.
     *
     * @param  string|array $abstract Abstract(s) to bind contextually.
     * @return ContextualBindingBuilder Builder for contextual bindings.
     */
    public function when(string|array $abstract): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, (array) $abstract);
    }

    /**
     * Create a factory function for deferred instantiation.
     *
     * @param  string|stdClass $abstract Abstract to create factory for.
     * @return Closure Factory function.
     */
    public function factory(string|stdClass $abstract): Closure
    {
        return function () use ($abstract) {
            return $this->build($abstract);
        };
    }

    /**
     * Clear all container bindings and instances.
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->extenders = [];
        $this->methodBindings = [];
        $this->contextual = [];
    }

    /**
     * Resolve and return an instance from the container.
     *
     * @param  string $abstract   Abstract to resolve.
     * @param  array  $parameters Optional parameters for resolution.
     * @return mixed Resolved instance.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Call a method with dependency injection.
     *
     * @param  Closure $callback   Method to call.
     * @param  array   $parameters Parameters to use.
     * @param  mixed   $default    Fallback value if resolution fails.
     * @return mixed Call result or default.
     */
    public function call(Closure $callback, array $parameters = [], mixed $default = null): mixed
    {
        try {
            return $callback(...$this->resolveMethodDependencies($callback, $parameters));
        } catch (\Throwable $e) {
            return $default instanceof Closure ? $default() : $default;
        }
    }

    /**
     * Check if an abstract has been resolved/bound.
     *
     * @param  string $abstract Abstract to check.
     * @return bool True if resolved/bound.
     */
    public function resolved(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || $this->isShared($abstract);
    }

    /**
     * Register a before-resolve callback.
     *
     * @param string       $abstract Abstract to watch.
     * @param Closure|null $callback Callback to execute.
     */
    public function beforeResolve(string $abstract, ?Closure $callback = null): void
    {
        $this->beforeResolving[$abstract][] = $callback;
    }

    /**
     * Resolve an abstract and execute callback.
     *
     * @param  string       $abstract Abstract to resolve.
     * @param  Closure|null $callback Callback to execute.
     * @return mixed Resolved instance.
     */
    public function resolving(string $abstract, ?Closure $callback = null): mixed
    {
        $object = $this->resolve($abstract);

        if ($callback) {
            $callback($object);
        }

        return $object;
    }

    /**
     * Register an after-resolve callback.
     *
     * @param string       $abstract Abstract to watch.
     * @param Closure|null $callback Callback to execute.
     */
    public function afterResolve(string $abstract, ?Closure $callback = null): void
    {
        $this->afterResolving[$abstract][] = $callback;
    }

    /**
     * Core resolution logic for the container.
     *
     * @param  string $abstract   Abstract to resolve.
     * @param  array  $parameters Optional parameters.
     * @return mixed Resolved instance.
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        // Return existing instance if available and no parameters
        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        // Get context-specific concrete implementation
        $concrete = $this->getContextualConcrete($abstract);

        // Fall back to registered concrete or abstract itself
        if (is_null($concrete)) {
            $concrete = $this->getConcrete($abstract);
        }

        // Build the concrete implementation
        if ($concrete === $abstract || $concrete instanceof Closure) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->resolve($concrete, $parameters);
        }

        // Apply extenders
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // Store shared instances
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        // Trigger after-resolve callbacks
        foreach ($this->afterResolving[$abstract] ?? [] as $afterCallback) {
            $afterCallback($object, $this);
        }

        return $object;
    }

    /**
     * Get contextual concrete implementation for abstract.
     *
     * @param  string $abstract Abstract to check.
     * @return mixed Contextual concrete or null.
     */
    protected function getContextualConcrete(string $abstract): mixed
    {
        return $this->contextual[$abstract] ?? null;
    }

    /**
     * Get registered concrete implementation for abstract.
     *
     * @param  string $abstract Abstract to check.
     * @return mixed Concrete implementation or abstract.
     */
    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

         return $abstract;
    }

    /**
     * Build a concrete instance.
     *
     * @param  mixed $concrete   Concrete to build (class name or Closure).
     * @param  array $parameters Construction parameters.
     * @return mixed Built instance.
     * @throws RuntimeException If not instantiable.
     */
    protected function build(mixed $concrete, array $parameters = []): mixed
    {
        // If concrete is a Closure, execute it
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        // No constructor - simple instantiation
        if (is_null($constructor)) {
            return new $concrete();
        }

        // Resolve constructor dependencies
        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve class constructor dependencies.
     *
     * @param  array $dependencies Parameter reflections.
     * @param  array $parameters   Provided parameters.
     * @return array Resolved dependencies.
     * @throws RuntimeException If dependency cannot be resolved.
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // Use provided parameter if available
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }

            $type = $dependency->getType();

            // Resolve class type-hinted dependencies
            if ($type && !$type->isBuiltin()) {
                $results[] = $this->resolve($type->getName());
            } elseif ($dependency->isDefaultValueAvailable()) {
                // Use default value if available
                $results[] = $dependency->getDefaultValue();
            } else {
                throw new RuntimeException("Unresolvable dependency: {$dependency->name}");
            }
        }

        return $results;
    }

    /**
     * Resolve method dependencies for a callback.
     *
     * @param  Closure $callback   Callback to analyze.
     * @param  array   $parameters Provided parameters.
     * @return array Resolved parameters.
     * @throws RuntimeException If parameter cannot be resolved.
     */
    protected function resolveMethodDependencies(Closure $callback, array $parameters): array
    {
        $reflector = new ReflectionFunction($callback);
        $dependencies = [];

        foreach ($reflector->getParameters() as $parameter) {
            if (array_key_exists($parameter->name, $parameters)) {
                $dependencies[] = $parameters[$parameter->name];
                continue;
            }

            $type = $parameter->getType();

            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->resolve($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException("Unresolvable method parameter: {$parameter->name}");
            }
        }

        return $dependencies;
    }

    /**
     * Check if an abstract is registered as shared.
     *
     * @param  string $abstract Abstract to check.
     * @return bool True if shared.
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'];
    }

    /**
     * Check if an abstract is bound in the container.
     *
     * @param  string $abstract Abstract to check.
     * @return bool True if bound.
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            isset($this->aliases[$abstract]);
    }

    /**
     * Get the extender callbacks for a given type.
     *
     * @param  string $abstract
     * @return array
     */
    protected function getExtenders($abstract)
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Get the aliased name for an abstract.
     *
     * @param  string $abstract Abstract to check.
     * @return string Aliased name or original.
     */
    public function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Set the global container instance.
     *
     * @param ContainerContract|null $container Container instance to set.
     */
    public static function setInstance(?ContainerContract $container = null): void
    {
        static::$instance = $container;
    }

    /**
     * Get the global container instance (creates new if not set).
     *
     * @return ContainerContract Container instance.
     */
    public static function getInstance(): ContainerContract
    {
        return static::$instance ?? new static();
    }

    /**
     *
     * @param  string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->bound($offset);
    }

    /**
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->make($offset);
    }

    /**
     *
     * @param  string        $offset
     * @param  Closure|mixed $offset
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->bind($offset, $value instanceof Closure ? $value : fn() => $value);
    }

    /**
     *
     * @param  string $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset(
            $this->bindings[$offset],
            $this->instances[$offset],
            $this->resolved[$offset]
        );
    }
}
