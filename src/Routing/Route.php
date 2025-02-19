<?php

namespace Rose\Routing;

use Rose\Contracts\Routing\Route as RouteContract;

/**
 * The Route class represents a single route in the application.
 * It encapsulates all the information needed to match and handle an HTTP request:
 * - The HTTP method(s) it responds to
 * - The URI pattern to match
 * - The controller and action that handle the request
 * - Additional configurations like middleware, name, domain, etc.
 */
class Route implements RouteContract
{
    /**
     * Configuration properties for the route.
     * These control how the route behaves and is identified in the system.
     */
    private array $middleware = [];     // Middleware to be executed before/after route handling
    private array $where = [];         // Patterns for route parameter constraints
    private ?string $name = null;      // Optional name for route identification
    private ?string $domain = null;    // Optional domain constraint
    private string $prefix = '';       // URL prefix, often set by route groups
    private array $parameters = [];    // Captured route parameters from URI
    private ?RouteCollection $collection = null;  // Reference to parent collection

    /**
     * Create a new Route instance.
     * 
     * The constructor accepts the core attributes that define a route.
     * The methods parameter can be either a string for a single method
     * or an array for multiple methods.
     * 
     * @param string|array $methods    HTTP method(s) this route handles
     * @param string       $uri        URI pattern to match
     * @param string       $controller Controller class name
     * @param string       $action     Controller method to call
     */
    public function __construct(
        private string|array $methods,
        private string $uri,
        private string $controller,
        private string $action
    ) {
        // Convert methods to array for consistent handling
        $this->methods = (array) $methods;
    }

    /**
     * Assign middleware to the route.
     * Middleware provide a mechanism to filter HTTP requests entering your application.
     * 
     * @param string|array $middleware Single middleware or array of middleware
     * @return self For method chaining
     */
    public function middleware($middleware): self
    {
        // Merge new middleware with existing ones
        $this->middleware = array_merge(
            $this->middleware,
            is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    /**
     * Assign a name to the route.
     * Named routes allow you to perform URL generation or redirects
     * without being coupled to specific URLs.
     * 
     * @param string $name The name to assign to this route
     * @return self For method chaining
     */
    public function name(string $name): self
    {
        $this->name = $name;
        
        // Generate a unique identifier for route tracking
        $identifier = md5(implode('|', $this->methods) . $this->uri);
        
        // If route is in a collection, update the named routes registry
        if (isset($this->collection)) {
            $this->collection->updateNamedRoute($identifier, $this);
        }
        
        return $this;
    }

    /**
     * Set a domain constraint for the route.
     * This allows you to handle subdomains or specific domains differently.
     * 
     * @param string $domain Domain pattern to match
     * @return self For method chaining
     */
    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Add a regular expression constraint for a route parameter.
     * This allows you to ensure parameters match specific patterns.
     * 
     * @param string $parameter Name of the parameter to constrain
     * @param string $pattern   Regular expression pattern to match
     * @return self For method chaining
     */
    public function where(string $parameter, string $pattern): self
    {
        $this->where[$parameter] = $pattern;
        return $this;
    }

    /**
     * Set a URL prefix for the route.
     * This is commonly used when routes are part of a group.
     * 
     * @param string $prefix The URL prefix to add
     * @return self For method chaining
     */
    public function prefix(string $prefix)
    {
        $this->prefix = trim($prefix, '/');
        return $this;
    }

    /**
     * Associate this route with a RouteCollection.
     * This enables the route to update the collection when its attributes change.
     * 
     * @param RouteCollection $collection The collection this route belongs to
     * @return self For method chaining
     */
    public function setCollection(RouteCollection $collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Getter methods for accessing private properties.
     * These provide controlled access to the route's internal state.
     */
    
    /**
     * Get the HTTP methods this route responds to.
     * @return array Array of HTTP methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the route's URI pattern.
     * @return string The URI pattern
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the controller class that handles this route.
     * @return string The controller class name
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Get the controller action method.
     * @return string The action method name
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the route's name if one has been assigned.
     * @return string|null The route name or null if unnamed
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get any parameters captured from the URI.
     * @return array Array of captured parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
