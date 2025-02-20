<?php

namespace Rose\Routing;

use Rose\Support\ServiceProvider;
use Rose\Routing\Router;


class RouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerRouter();
    }
    
    protected function registerRouter()
    {
        $this->app->singleton('router', function($app) {
            return (new Router($app['events'], new RouteCollection, $app));
        });
    }
}
