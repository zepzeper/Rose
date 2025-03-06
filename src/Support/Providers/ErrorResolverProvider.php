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
        $this->app->singleton(ErrorViewResolver::class, function () {
            return new ErrorViewResolver();
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
