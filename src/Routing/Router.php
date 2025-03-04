<?php

namespace Rose\Routing;

use Closure;
use Rose\Container\Container;
use Rose\Contracts\Routing\Router as RouterContract;
use Rose\Events\Dispatcher;
use Rose\Exceptions\Routing\RouteNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Router class handles HTTP route registration and request dispatching.
 * It serves as the main entry point for defining application routes and
 * matching incoming HTTP requests to their appropriate handlers.
 */
class Router implements RouterContract
{
    /**
     * List of valid HTTP methods supported by this router.
     * These methods align with the HTTP/1.1 specification.
     * 
     * @var array Valid HTTP methods 
     */
    protected const VALID_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
    protected ?Container $container;

    /**
     * All of middlewares.
     *
     * @var array
     */
    protected $middleware = [
    ];

    /**
     * All of the middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [],
        'api' => []
    ];
    
    /**
     * Middleware aliases for easier reference.
     *
     * @var array
     */
    protected array $middlewareAliases = [];

    /**
     * Initialize a new Router instance.
     * 
     * @param Dispatcher $events Dispatcher to resolve events
     * @param RouteCollection $routes Collection to store and manage routes
     * @param Collection $container Application container
     */
    public function __construct(protected Dispatcher $events, protected RouteCollection $routes, ?Container $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * Register a new route with the router.
     * This is the core method for adding routes to the application.
     * 
     * @param  string|array $methods    HTTP methods route responds to
     * @param  string       $uri        URI pattern to match
     * @param  string       $controller Controller class to handle the request
     * @param  string       $action     Method within controller to call
     * @param  Closure|null $callback   Optional configuration callback
     * @return Route                    The newly created route instance
     */
    public function add($methods, string $uri, string $controller, string $action, ?Closure $callback = null): Route
    {
        // Create new route instance with specified attributes
        $route = new Route($methods, $uri, $controller, $action);
        
        // Apply any custom configuration if callback provided
        if ($callback) {
            $callback($route);
        }
        
        // Register route with the collection and return it
        $this->routes->add($route);
        
        return $route;
    }

    /**
     * Register a GET route.
     * Shorthand method for registering routes that respond to GET requests.
     */
    public function get(string $uri, string $controller, string $action, ?Closure $callback = null): Route
    {
        $this->resolve($callback);
        return $this->add('GET', $uri, $controller, $action);
    }

    /**
     * Register a POST route.
     * Shorthand method for registering routes that respond to POST requests.
     */
    public function post(string $uri, string $controller, string $action, ?Closure $callback = null): Route
    {
        $this->resolve($callback);
        return $this->add('POST', $uri, $controller, $action);
    }

    /**
     * Register a PUT route.
     * Shorthand method for registering routes that respond to PUT requests.
     */
    public function put(string $uri, string $controller, string $action, ?Closure $callback = null): Route
    {
        $this->resolve($callback);
        return $this->add('PUT', $uri, $controller, $action);
    }

    /**
     * Register a PATCH route.
     * Shorthand method for registering routes that respond to PATCH requests.
     */
    public function patch(string $uri, string $controller, string $action, ?Closure $callback = null): Route
    {
        $this->resolve($callback);
        return $this->add('PATCH', $uri, $controller, $action);
    }

    /**
     * Register a DELETE route.
     * Shorthand method for registering routes that respond to DELETE requests.
     */
    public function delete(string $uri, string $controller, string $action, ?Closure $callback = null): Route
    {
        $this->resolve($callback);
        return $this->add('DELETE', $uri, $controller, $action);
    }

    protected function resolve(?Closure $callback): void
    {
        if (! is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Create a route group with shared attributes.
     * Groups allow routes to share common characteristics like:
     * - URL prefixes
     * - Middleware
     * - Namespace prefixes
     * - Name prefixes
     * 
     * @param  array   $attributes Shared attributes for all routes in group
     * @param  Closure $callback   Function that defines the group's routes
     * @return self     For method chaining
     */
    public function group(array $attributes, Closure $callback): self
    {
        $this->routes->group(
            $attributes, function () use ($callback) {
                $callback($this);
            }
        );

        return $this;
    }

    /**
     * Set the global middleware for the router.
     * 
     * @param array $middleware Array of middleware to apply globally
     * @return void
     */
    public function setMiddleware(array $middleware): void
    {
        $this->middleware = $middleware;
    }

    /**
     * Register a middleware group.
     * 
     * @param string $group Name of the middleware group
     * @param array $middleware Array of middleware in the group
     * @return void
     */
    public function setMiddlewareGroup($group, $middleware)
    {
        $this->middlewareGroups[$group] = $middleware;
    }

    /**
     * Register a middleware alias.
     * 
     * @param string $alias Short name for the middleware
     * @param string $middleware Full middleware class name
     * @return self
     */
    public function aliasMiddleware(string $alias, string $middleware)
    {
        $this->middlewareAliases[$alias] = $middleware;
        return $this;
    }

    /**
     * Get all registerd middleware aliases
     *
     * @return array.
     */
    public function getMiddlewareAliases(): array
    {
        return $this->middlewareAliases;
    }
    
    /**
     * Get all registerd middlewareGroups
     *
     * @return array.
     */
    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }

    /**
     * Match and dispatch an incoming request to its handler.
     * This method:
     * 1. Validates the HTTP method
     * 2. Finds a matching route
     * 3. Creates the controller
     * 4. Calls the appropriate action
     * 
     * @param  Request $uri    The request
     * @return mixed          The response from the route handler
     * @throws RouteNotFoundException If no matching route is found
     * @throws \InvalidArgumentException If the HTTP method is invalid
     */
    public function dispatch(Request $request)
    {
        $uri = $this->normalizeUri($request->getPathInfo());
        $method = $request->getMethod();

        // Find a matching route
        $route = $this->routes->match($uri, $method);

        if (!$route) {
            throw new RouteNotFoundException("No route found for {$method} {$uri}");
        }

        // Get the controller and action
        $controller = $route->getController();
        $action = $route->getAction();

        // Get any route parameters that were captured
        $parameters = $route->getParameters();

        $middleware = $this->gatherRouteMiddleware($route);

        $pipeline = $this->container->make('middleware.pipeline');

        // Process the request through the middleware pipeline
        return $pipeline->through($middleware)->then(
            $request,
            function ($request) use ($controller, $action, $parameters) {
                // Create controller instance
                $instance = $this->container->make($controller);
        
                // Call the action with parameters
                $response = $this->container->call(
                    [$instance, $action], 
                    array_merge($parameters, ['request' => $request])
                );
                
                // Convert string responses to Response objects
                if (is_string($response)) {
                    return new Response($response);
                }
                
                // If it's already a Response object, return it directly
                if ($response instanceof Response) {
                    return $response;
                }
                
                // Otherwise, wrap it in a Response
                return new Response($response);
            }
        );
    }

    /**
     * Gather all middleware for a route including global, group, and route-specific middleware.
     * 
     * @param Route $route The route to gather middleware for
     * @return array Array of middleware
     */
    protected function gatherRouteMiddleware(Route $route): array
    {
        $middleware = $this->middleware; // Start with global middleware
        
        // Add middleware from groups if applicable
        foreach ($route->getMiddlewareGroups() as $group) {
            if (isset($this->middlewareGroups[$group])) {
                $middleware = array_merge($middleware, $this->middlewareGroups[$group]);
            }
        }
        
        // Add route-specific middleware
        foreach ($route->getMiddleware() as $name) {
            // Check if we're using an alias
            if (isset($this->middlewareAliases[$name])) {
                $middleware[] = $this->middlewareAliases[$name];
            } else {
                $middleware[] = $name;
            }
        }
        
        return $middleware;
    }

    /**
     * Clean up the URI for consistent matching.
     */
    protected function normalizeUri(string $uri): string
    {
        return '/' . trim($uri, '/');
    }

    /**
    * @return RouteCollection
    */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}
