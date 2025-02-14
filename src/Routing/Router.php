<?php

namespace Rose\Routing;

class Router
{

    protected $routes;
    protected $events;

    public function __construct(Dispatcher $events, ?Container $container = null)
    {
        $this->events = $events;
        $this->routes = new RouteCollection;
    }

    /**
     * Register a new GET route with the router.
     *
     * @param  string $uri
     * @param  array|string|callable|null $action
     * @return \Illuminate\Routing\Route
     */
    public function get($uri, $action = null)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route with the router.
     *
     * @param  string                     $uri
     * @param  array|string|callable|null $action
     * @return \Illuminate\Routing\Route
     */
    public function post($uri, $action = null)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param  string                     $uri
     * @param  array|string|callable|null $action
     * @return \Illuminate\Routing\Route
     */
    public function put($uri, $action = null)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string                     $uri
     * @param  array|string|callable|null $action
     * @return \Illuminate\Routing\Route
     */
    public function patch($uri, $action = null)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param  string                     $uri
     * @param  array|string|callable|null $action
     * @return \Illuminate\Routing\Route
     */
    public function delete($uri, $action = null)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  array|string|callable|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function addRoute($methods, $uri, $action)
    {
        return $this->routes->add($this->createRoute($methods, $uri, $action));
    }

     /**
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  array|string|callable|null  $action
     */
    protected function createRoute($methods, $uri, $action) {

    }
    
}
