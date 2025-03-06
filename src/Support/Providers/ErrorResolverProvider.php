<?php

namespace Rose\Support\Providers;

use Rose\Support\ServiceProvider;
use Rose\View\ErrorViewResolver;

class ErrorResolverProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ErrorViewResolver::class, function ($app) {
            return new ErrorViewResolver($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
    }
}
