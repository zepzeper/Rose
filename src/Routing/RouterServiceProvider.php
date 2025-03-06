<?php

namespace Rose\Routing;

use Rose\Support\ServiceProvider;
use Rose\Routing\Router;
use Rose\View\ErrorViewResolver;


class RouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerRouter();
    }
    
    protected function registerRouter()
    {
        $this->app->singleton('router', function($app) {
            return (new Router($app['events'], new RouteCollection, new ErrorViewResolver($app), $app));
        });
    }
}
