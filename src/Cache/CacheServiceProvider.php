<?php

namespace Rose\Cache;

use Rose\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });
    }

    public function boot()
    {}

}
