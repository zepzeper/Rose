<?php

use Carbon\Carbon;
use Rose\Contracts\Http\Kernel as KernelContract;
use Rose\Roots\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Kernel implements KernelContract
{

    protected $requestStartTime;
    protected $app;
    protected $router;

    public function __construct(Application $app, Router $router) {
        $this->app = $app;
        $this->router = $router;
    }

    public function bootstrap()
    {
        
    }

    public function handle(Request $request): Response
    {

        $this->requestStartTime = Carbon::now();

        $response = $this->forwardToRouter($request);

        return $response;
        
    }

    protected function forwardToRouter(Request $response): Response 
    {
    
        $this->app->getInstance();


    }

    public function emit(Request $request): Response
    {
        
    }


}
