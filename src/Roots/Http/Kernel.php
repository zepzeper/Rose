<?php

namespace Rose\Roots\Http;

use Carbon\Carbon;
use Rose\Contracts\Http\Kernel as KernelContract;
use Rose\Roots\Application;
use Rose\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Kernel implements KernelContract
{
    /**
     * @var Carbon|null
     */
    protected $requestStartTime;

    /**
     * The application implementation.
     */
    protected Application $app;

    /**
     * The router instance.
     */
    protected Router $router;

    /**
     * The bootstrap classes for the application.
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
     * The application's middleware stack.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected array $middlewareGroups = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    public function handle(Request $request): Response
    {
        try{
            $this->requestStartTime = Carbon::now();

            $this->bootstrap();

            // Bind the request to the container
            $this->app->instance('request', $request);

            $response = $this->forwardToRouter($request);

            $this->addGlobalheaders($response);

        } catch (Throwable $e) {
            $response = $this->handleException($request, $e);
        }

        return $response;

    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    public function emit(Response $response)
    {
        return $response->send();
    }

    protected function forwardToRouter(Request $request)
    {

        $this->router->middleware($this->middleware);

        foreach ($this->middlewareGroups as $group => $middleware) {
            $this->router->middlewareGroup($group, $middleware);
        }

        return $this->router->dispatch($response);
    }

    protected function addGlobalheaders(Response $response): void
    {
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XXS-PROTECTION', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if ($this->requestStartTime) {
            $duration = Carbon::now()->diffInMilliseconds($this->requestStartTime);
            $response->headers->set('X-Request-Time', $duration);
        }
    }

    protected function handleException(Request $request, Throwable $e)
    {
        return new Response(
            "An error occured {$e->getMessage()}",
            500,
            ['Content-Type'=>'text/plain']
        );
    }


    /**
     * @return string[]
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }


}
