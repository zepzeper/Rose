<?php

namespace Rose\Support\Providers;

use Rose\Routing\Router;
use Rose\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    static $alwaysLoadRoutesUsing;
    
    public function register()
    {
        $this->registerRouter();
    }

    protected function registerRouter()
    {
        $this->booted(function () {
            $this->loadRoutes();

            $this->app->singleton('router', function ($app) {
                $this->app['router']->getRoutes();
            });
        });
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param  \Closure|null  $routesCallback
     * @return void
     */
    public static function loadRoutesUsing(?Closure $routesCallback)
    {
        self::$alwaysLoadRoutesUsing = $routesCallback;
    }

    protected function loadRoutes()
    {
        if (! is_null(self::$alwaysLoadRoutesUsing))
        {
            $this->app->call(self::$alwaysLoadRoutesUsing);
        } else if (method_exists($this, 'map'))
        {
            $this->app->call([$this, 'map']);
        }
    }
}
