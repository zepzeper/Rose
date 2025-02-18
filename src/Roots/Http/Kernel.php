<?php

namespace Rose\Roots\Http;

use Carbon\Carbon;
use Rose\Contracts\Http\Kernel as KernelContract;
use Rose\Roots\Application;
use Rose\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Kernel implements KernelContract
{
    protected $requestStartTime;
    protected Application $app;
    protected Router $router;

    /**
     * @var string[]
     */
    protected array $bootstrappers = [
        \Rose\Roots\Bootstrap\LoadEnviromentVariables::class,
        \Rose\Roots\Bootstrap\LoadConfiguration::class,
        \Rose\Roots\Bootstrap\RegisterProviders::class,
        \Rose\Roots\Bootstrap\BootProvider::class,
    ];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;

        $this->bootstrap();
    }

    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    /**
     * @return string[]
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }


    public function handle(Request $request): Response
    {

        $this->requestStartTime = Carbon::now();


        $response = $this->forwardToRouter($request);

        return $response;

    }
    /**
     * @return void
     */
    protected function forwardToRouter(Request $response): void
    {

        $this->app->getInstance();


    }

    /*public function emit(Request $request): void*/
    /*{*/
    /**/
    /*}*/
    /**/

}
