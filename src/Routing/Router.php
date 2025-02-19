<?php

namespace Rose\Routing;

use Closure;
use Rose\Contracts\Routing\Router as RouterContract;
use Rose\Exceptions\Routing\RouteNotFoundException;

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

    /**
     * Initialize a new Router instance.
     * 
     * @param RouteCollection $routes Collection to store and manage routes
     */
    public function __construct(protected RouteCollection $routes)
    {
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
    public function get(string $uri, string $controller, string $action): Route
    {
        return $this->add('GET', $uri, $controller, $action);
    }

    /**
     * Register a POST route.
     * Shorthand method for registering routes that respond to POST requests.
     */
    public function post(string $uri, string $controller, string $action): Route
    {
        return $this->add('POST', $uri, $controller, $action);
    }

    /**
     * Register a PUT route.
     * Shorthand method for registering routes that respond to PUT requests.
     */
    public function put(string $uri, string $controller, string $action): Route
    {
        return $this->add('PUT', $uri, $controller, $action);
    }

    /**
     * Register a PATCH route.
     * Shorthand method for registering routes that respond to PATCH requests.
     */
    public function patch(string $uri, string $controller, string $action): Route
    {
        return $this->add('PATCH', $uri, $controller, $action);
    }

    /**
     * Register a DELETE route.
     * Shorthand method for registering routes that respond to DELETE requests.
     */
    public function delete(string $uri, string $controller, string $action): Route
    {
        return $this->add('DELETE', $uri, $controller, $action);
    }

    /**
     * Create a route group with shared attributes.
     * Groups allow routes to share common characteristics like:
     * - URL prefixes
     * - Middleware
     * - Namespace prefixes
     * - Name prefixes
     * 
     * @param  array    $attributes Shared attributes for all routes in group
     * @param  Closure  $callback   Function that defines the group's routes
     * @return self     For method chaining
     */
    public function group(array $attributes, Closure $callback): self
    {
        $this->routes->group($attributes, function() use ($callback) {
            $callback($this);
        });
        return $this;
    }

    /**
     * Match and dispatch an incoming request to its handler.
     * This method:
     * 1. Validates the HTTP method
     * 2. Finds a matching route
     * 3. Creates the controller
     * 4. Calls the appropriate action
     * 
     * @param  string $uri     The request URI to match
     * @param  string $method  The HTTP method of the request
     * @return mixed          The response from the route handler
     * @throws RouteNotFoundException If no matching route is found
     * @throws \InvalidArgumentException If the HTTP method is invalid
     */
    public function dispatch(string $uri, string $method)
    {
        // Convert method to uppercase for consistency
        $method = strtoupper($method);

        // Ensure the HTTP method is valid
        if (!in_array($method, self::VALID_METHODS)) {
            throw new \InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        // Find a route matching the URI and method
        $route = $this->routes->match($uri, $method);

        // If no route matches, throw an exception
        if (!$route) {
            throw new RouteNotFoundException("No route found for {$method} {$uri}");
        }

        // Get the controller and action from the matching route
        $controller = $route->getController();
        $action = $route->getAction();

        // Instantiate controller and call the action
        $instance = new $controller();
        return $instance->$action();
    }
}
