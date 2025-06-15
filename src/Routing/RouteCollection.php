<?php

namespace Rose\Routing;

use Closure;
use Rose\Routing\Route;

/**
 * The RouteCollection class serves as a central registry for all routes in the application.
 * It manages route storage, organization, and lookup while also handling route groups
 * and their nested relationships. This class is fundamental to the routing system as it:
 * 
 * 1. Stores and indexes routes for quick access
 * 2. Manages route names and prevents naming conflicts
 * 3. Handles route group hierarchies and attribute inheritance
 * 4. Provides route matching capabilities for the dispatcher
 */
class RouteCollection
{
    /**
     * Primary storage for all registered routes in the application.
     * Routes are indexed by a unique identifier generated from their
     * HTTP methods and URI pattern. This ensures that each route
     * can be uniquely identified and quickly accessed.
     * 
     * Example identifier: md5('GET|POST' . '/users/{id}')
     */
    protected array $routes = [];

    /**
     * Secondary index of routes by their names.
     * This allows quick lookup of routes by their friendly names,
     * which is essential for URL generation and route referencing
     * throughout the application.
     * 
     * Example: ['users.show' => Route, 'users.edit' => Route]
     */
    protected array $namedRoutes = [];

    /**
     * Maintains the stack of active route groups.
     * As groups are nested, their configurations are pushed onto
     * this stack, allowing for proper attribute inheritance and
     * group hierarchy management.
     * 
     * Example: [
     *     ['prefix' => 'api', 'middleware' => ['auth']],
     *     ['prefix' => 'v1', 'middleware' => ['throttle']]
     * ]
     */
    protected array $groups = [];

    /**
     * Holds the attributes of the currently active group.
     * When routes are added, they inherit these attributes.
     * This is null when not within any group context.
     * 
     * Example: [
     *     'prefix' => 'api/v1',
     *     'middleware' => ['auth', 'throttle'],
     *     'namespace' => 'App\Http\Controllers\Api'
     * ]
     */
    protected ?array $currentGroup = null;

    /**
     * Register a new route in the collection.
     * This method serves as the entry point for adding routes and handles:
     * 1. Collection association
     * 2. Unique identifier generation
     * 3. Group attribute application
     * 4. Route storage and indexing
     * 
     * @param  Route $route The route instance to register
     * @return self For method chaining
     */
    public function add(Route $route): self
    {
        // Associate the route with this collection for later updates
        $route->setCollection($this);

        // Create a unique identifier for the route based on its properties
        $identifier = $this->generateRouteIdentifier($route);

        // If we're inside a group, apply its settings to the route
        if ($this->currentGroup) {
            $this->applyGroupSettingsToRoute($route);
        }

        // Store the route in our primary index
        $this->routes[$identifier] = $route;

        // If the route has a name, index it in our named routes array
        if ($route->getName()) {
            $this->addNamedRoute($route);
        }

        return $this;
    }

    /**
     * Register a named route in the secondary index.
     * This method ensures that route names remain unique across the application
     * and provides fast lookups for named route operations.
     * 
     * @param  Route $route The route to register by name
     * @throws \RuntimeException When attempting to register a duplicate route name
     */
    protected function addNamedRoute(Route $route): void
    {
        $name = $route->getName();
        
        // Prevent naming conflicts by checking for existing routes
        if (isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route name '{$name}' has already been taken");
        }

        $this->namedRoutes[$name] = $route;
    }

    /**
     * Update a route's entry in the named routes index.
     * This method is called when a route's name changes, ensuring that
     * the named routes index stays synchronized with route updates.
     * 
     * @param string $identifier The route's unique identifier
     * @param Route  $route      The route being updated
     */
    public function updateNamedRoute(string $identifier, Route $route)
    {
        $name = $route->getName();

        if ($name) {
            // Remove any existing named references to this route
            foreach ($this->namedRoutes as $existingName => $existingRoute) {
                if ($this->generateRouteIdentifier($existingRoute) === $identifier) {
                    unset($this->namedRoutes[$existingName]);
                }
            }

            // Register the route with its new name
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Create and manage a route group with shared attributes.
     * Route groups allow multiple routes to share common characteristics like:
     * - URI prefixes (e.g., all routes starting with 'api/')
     * - Middleware (e.g., authentication requirements)
     * - Naming prefixes (e.g., all routes prefixed with 'api.')
     * - Domain constraints (e.g., routes only responding to specific subdomains)
     * 
     * Groups can be nested, with each level inheriting and potentially
     * extending the parent group's attributes.
     * 
     * @param  array   $attributes Settings to apply to all routes in the group
     * @param  Closure $callback   Function containing route definitions
     * @return self For method chaining
     */
    public function group(array $attributes, Closure $callback): self
    {
        // Store current group settings to restore them after processing
        $previousGroup = $this->currentGroup;

        // Create new group settings by merging with parent group if it exists
        $this->currentGroup = $previousGroup ? 
            $this->mergeGroups($previousGroup, $attributes) : 
            $attributes;

        // Track group hierarchy by adding current group to the stack
        $this->groups[] = $this->currentGroup;

        // Define routes within this group context
        $callback();

        // Restore group hierarchy
        array_pop($this->groups);
        $this->currentGroup = $previousGroup;

        return $this;
    }

    /**
     * Find a route that matches a given URI and HTTP method.
     * This method is crucial for the dispatch process, determining
     * which route should handle an incoming request.
     * 
     * The matching process considers:
     * 1. The HTTP method (GET, POST, etc.)
     * 2. The exact URI pattern
     * 3. Any parameter constraints
     * 4. Domain restrictions
     * 
     * @param  string $uri    The request URI to match against routes
     * @param  string $method The HTTP method of the request
     * @return Route|null The matching route or null if none found
     */
    public function match(string $uri, string $method): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->matches($uri, $method)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Retrieve a route by its name.
     * Named routes are useful for generating URLs and redirects
     * without hardcoding paths throughout your application.
     * 
     * @param  string $name The route's assigned name
     * @return Route|null The named route or null if not found
     */
    public function getByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Generate a unique identifier for a route.
     * This identifier is used as the key in the routes array and
     * helps prevent duplicate routes. It combines the route's
     * HTTP methods and URI pattern to create a unique hash.
     * 
     * Example:
     * - Methods: ['GET', 'POST']
     * - URI: '/users/{id}'
     * - Result: md5('GET|POST/users/{id}')
     * 
     * @param  Route $route The route to generate an identifier for
     * @return string MD5 hash serving as the unique identifier
     */
    protected function generateRouteIdentifier(Route $route): string
    {
        return md5(implode('|', $route->getMethods()) . $route->getUri() . $route->getController());
    }

    /**
     * Merge attributes from two route groups.
     * This method handles the complexities of combining group attributes
     * when groups are nested. It provides special handling for:
     * - URI prefixes (ensuring proper slash handling)
     * - Middleware (preventing duplicates)
     * - Other attributes that need special merging logic
     * 
     * @param  array $previous Parent group's attributes
     * @param  array $new      Current group's attributes
     * @return array Combined attributes
     */
    protected function mergeGroups(array $previous, array $new): array
    {
        $merged = array_merge_recursive($previous, $new);

        // Special handling for URI prefixes
        if (isset($merged['prefix'])) {
            // Ensure proper slash formatting when joining prefixes
            $merged['prefix'] = trim(
                implode('/', (array) $previous['prefix'] ?? []) . '/' . 
                implode('/', (array) $new['prefix'] ?? []),
                '/'
            );
        }

        // Special handling for middleware to prevent duplicates
        if (isset($merged['middleware'])) {
            $merged['middleware'] = array_unique(
                array_merge(
                    (array) ($previous['middleware'] ?? []),
                    (array) ($new['middleware'] ?? [])
                )
            );
        }

        return $merged;
    }

    /**
     * Apply group settings to a route.
     * When a route is defined within a group, it inherits the group's
     * attributes. This method handles the application of those
     * inherited attributes to the route.
     * 
     * @param Route $route The route to modify with group settings
     */
    protected function applyGroupSettingsToRoute(Route $route): void
    {
        // Apply middleware from group
        if (isset($this->currentGroup['middleware'])) {
            $route->middleware($this->currentGroup['middleware']);
        }

        // Apply domain constraints from group
        if (isset($this->currentGroup['domain'])) {
            $route->domain($this->currentGroup['domain']);
        }

        // Apply URI prefix from group
        if (isset($this->currentGroup['prefix'])) {
            $route->prefix($this->currentGroup['prefix']);
        }

        // Apply name prefix from group (if route is named)
        if (isset($this->currentGroup['as'])) {
            $routeName = $route->getName();
            if ($routeName) {
                $route->name($this->currentGroup['as'] . $routeName);
            }
        }
    }

    /**
     * Get all registered routes.
     * Provides access to all routes in the collection,
     * primarily used for debugging and route listing.
     * 
     * @return array Array of all registered Route instances
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get all named routes.
     * Provides access to the named routes index,
     * useful for route generation and debugging.
     * 
     * @return array Array of named Route instances
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }
}
