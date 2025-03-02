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

        // Register the cache.store interface
        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });
    }

    public function boot()
    {
        // Set up default cache configuration if not exists
        if (!$this->app['config']->has('cache')) {
            $this->app['config']->set('cache', [
                'default' => 'file',
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => $this->app->bootstrapPath('cache'),
                    ],
                    'array' => [
                        'driver' => 'array'
                    ]
                ]
            ]);
        }
    }

}
