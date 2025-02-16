<?php

namespace Rose\Events;

use Rose\Support\ServiceProvider;
use Rose\Events\Dispatcher;

class EventServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(
            'events', function ($app) {
                return (new Dispatcher($app));
            }
        );
    }

}
