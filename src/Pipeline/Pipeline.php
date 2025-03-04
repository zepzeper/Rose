<?php

namespace Rose\Pipeline;

use Closure;
use Rose\Container\Container;
use Rose\Contracts\Pipeline\Pipeline as PipelineContact;
use Rose\Contracts\Routing\Middleware\Middleware as MiddlewareContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Pipeline implements PipelineContact
{
    /**
     * The container instance for resolving middleware.
     */
    protected ?Container $container;

    /**
     * The array of middleware to execute.
     */
    protected array $middleware = [];

    /**
     * Create a new middleware pipeline instance.
     *
     * @param Container|null $container Optional container for resolving middleware
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the middleware to be executed.
     *
     * @param array|string $middleware Array of middleware or single middleware class/object
     * @return $this
     */
    public function through($middleware): self
    {
        $this->middleware = is_array($middleware) ? $middleware : [$middleware];
        return $this;
    }

    /**
     * Add middleware to the pipeline.
     *
     * @param array|string $middleware Array of middleware or single middleware class/object
     * @return $this
     */
    public function pipe(array|string $middleware): self
    {
        $this->middleware = array_merge(
            $this->middleware,
            is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    /**
     * Execute the pipeline with a final destination callback.
     *
     * @param Request $request The request to process
     * @param Closure $destination The final callback to execute after all middleware
     * @return Response
     */
    public function then(Request $request, Closure $destination): Response
    {
        // Create the pipeline by nesting each middleware inside the next
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            $this->carry(),
            $destination
        );

        // Execute the pipeline with the request
        return $pipeline($request);
    }

    /**
     * Get a Closure that creates the middleware pipeline.
     *
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($request) use ($stack, $pipe) {
                // Resolve the middleware instance
                $middleware = $this->resolveMiddleware($pipe);

                if ($middleware instanceof MiddlewareContract) {
                    // Using interface
                    return $middleware->handle($request, $stack);
                } 

                // Support for invokable class or closure
                return $middleware($request, $stack);
            };
        };
    }

    /**
     * Resolve a middleware instance from the container or create it directly.
     *
     * @param mixed $middleware The middleware to resolve
     * @return object The resolved middleware instance
     */
    protected function resolveMiddleware($middleware)
    {
        // If it's already an object, return it
        if (is_object($middleware)) {
            return $middleware;
        }

        // If we have a container, try to resolve from it
        if ($this->container && is_string($middleware)) {
            return $this->container->make($middleware);
        }

        // Otherwise create the instance directly
        return new $middleware();
    }    
}
