<?php

namespace Rose\Roots\Http;

use Carbon\Carbon;
use Rose\Contracts\Http\Kernel as KernelContract;
use Rose\Roots\Application;
use Rose\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The HTTP Kernel serves as the central point of your application's request handling process.
 * It acts as a bridge between the web server and your framework, orchestrating the entire
 * request-response lifecycle. Key responsibilities include:
 * 
 * 1. Bootstrapping the application
 * 2. Processing incoming HTTP requests
 * 3. Managing middleware execution
 * 4. Coordinating the routing process
 * 5. Handling errors and exceptions
 * 6. Returning appropriate HTTP responses
 * 
 * This design follows the Front Controller pattern, providing a single entry point
 * for all HTTP requests to your application.
 */
class Kernel implements KernelContract
{
    /**
     * Tracks when the request processing began.
     * Used for performance monitoring and debugging.
     * 
     * @var Carbon|null
     */
    protected $requestStartTime;

    /**
     * The application container instance.
     * Provides access to the service container and core framework services.
     */
    protected Application $app;

    /**
     * The router instance.
     * Handles URL matching and request dispatching to appropriate controllers.
     */
    protected Router $router;

    /**
     * The bootstrap classes that prepare your application to handle requests.
     * These classes are executed in order and handle crucial setup tasks:
     * 
     * 1. LoadEnviromentVariables - Loads .env file configuration
     * 2. LoadConfiguration - Processes configuration files
     * 3. RegisterProviders - Registers service providers
     * 4. BootProvider - Bootstraps service providers
     *
     * @var string[]
     */
    protected array $bootstrappers = [
        \Rose\Roots\Bootstrap\LoadEnviromentVariables::class,
        \Rose\Roots\Bootstrap\LoadConfiguration::class,
        \Rose\Roots\Bootstrap\RegisterProviders::class,
        \Rose\Roots\Bootstrap\BootProvider::class,
    ];

    /**
     * Global middleware applied to all routes.
     * Middleware provides a convenient mechanism for filtering HTTP requests
     * entering your application.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * Named groups of middleware.
     * Allows you to assign multiple middleware to a group and apply them
     * together to routes or route groups.
     * 
     * Example:
     * [
     *     'web' => ['session', 'csrf', 'auth'],
     *     'api' => ['throttle', 'jwt']
     * ]
     *
     * @var array
     */
    protected array $middlewareGroups = [];

    /**
     * Create a new HTTP kernel instance.
     * 
     * @param Application $app    The application container instance
     * @param Router     $router The router instance
     */
    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    /**
     * Bootstrap the application.
     * This process prepares the application to handle requests by:
     * 1. Loading configuration
     * 2. Setting up error handling
     * 3. Loading service providers
     * 4. And other crucial initialization tasks
     */
    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    /**
     * Handle an incoming HTTP request.
     * This is the main entry point for request processing and coordinates:
     * 1. Application bootstrapping
     * 2. Request handling
     * 3. Response generation
     * 4. Error handling
     * 
     * The method follows a try-catch pattern to ensure all errors are caught
     * and converted to appropriate HTTP responses.
     * 
     * @param Request $request The incoming HTTP request
     * @return Response The generated HTTP response
     */
    public function handle(Request $request): Response
    {
        try {
            // Start timing the request for performance monitoring
            $this->requestStartTime = Carbon::now();

            // Prepare the application
            $this->bootstrap();

            // Make the request available in the service container
            $this->app->instance('request', $request);

            // Process the request through the router
            $response = $this->forwardToRouter($request);

            // Add security and debugging headers
            $this->addGlobalheaders($response);
        } catch (Throwable $e) {
            // Convert any errors to HTTP responses
            $response = $this->handleException($request, $e);
        }


        return $response;
    }

    /**
     * Get the global middleware stack.
     * 
     * @return array List of middleware classes
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get the middleware groups configuration.
     * 
     * @return array Middleware groups and their assigned middleware
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * Send the response to the client.
     * This method finalizes the response and sends it to the web server.
     * 
     * @param Response $response
     * @return Response
     */
    public function emit(Response $response): Response
    {
        return $response->send();
    }

    /*
     * Perform any last action for the lifecycle of the request
     *
     * @param Request $request The HTTP request to process
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        // TODO: Do some termination here or something...
    }

    /**
     * Forward the request to the router for processing.
     * This method:
     * 1. Configures the router with middleware
     * 2. Sets up middleware groups
     * 3. Dispatches the request to matching routes
     * 
     * @param Request $request The HTTP request to process
     * @return Response The generated response
     */
    protected function forwardToRouter(Request $request)
    {
        // Configure global middleware
        //$this->router->middleware($this->middleware);

        // Set up middleware groups
        /*foreach ($this->middlewareGroups as $group => $middleware) {*/
        /*    $this->router->middlewareGroup($group, $middleware);*/
        /*}*/

        return $this->router->dispatch($request->getPathInfo(), $request->getMethod());
    }

    /**
     * Add global security and debug headers to the response.
     * These headers enhance security and provide debugging information:
     * 
     * - X-Frame-Options: Prevents clickjacking attacks
     * - X-XXS-Protection: Enables XSS filtering
     * - X-Content-Type-Options: Prevents MIME-type sniffing
     * - X-Request-Time: Shows request processing duration
     * 
     * @param Response $response The response to modify
     */
    protected function addGlobalheaders(Response $response): void
    {
        // Security headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XXS-PROTECTION', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Performance monitoring header
        if ($this->requestStartTime) {
            $duration = Carbon::now()->diffInMilliseconds($this->requestStartTime);
            $response->headers->set('X-Request-Time', $duration);
        }
    }

    /**
     * Convert exceptions into HTTP responses.
     * This provides a last-resort handler for uncaught exceptions,
     * ensuring that errors always result in proper HTTP responses.
     * 
     * @param Request $request The incoming request
     * @param Throwable $e The caught exception
     * @return Response An error response
     */
    protected function handleException(Request $request, Throwable $e)
    {
        return new Response(
            "An error occured {$e->getMessage()}",
            500,
            ['Content-Type'=>'text/plain']
        );
    }

    /**
     * Get the bootstrap classes for the application.
     * 
     * @return string[] Array of bootstrapper class names
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }
}
