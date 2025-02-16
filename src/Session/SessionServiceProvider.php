<?php

namespace Rose\Session;

use Rose\Roots\Application;
use Rose\Session\Storage\NativeSessionStorage;
use Rose\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{

    public function __construct(protected Application $app) {
    }

    public function register()
    {
        $this->registerSessionManager();
        $this->registerSessionDriver();
    }

    protected function registerSessionManager()
    {
        $this->app->singleton('session', function (Application $app) {
            return new NativeSessionStorage($app);
        });
    }

    protected function registerSessionDriver()
    {
        $this->app->singleton('session.store', function (Application $app) {
            return $app->make('session');
        });
    }
}
