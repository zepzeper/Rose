<?php

namespace Rose\Support\Providers;

use Rose\Http\Middleware\CsrfMiddleware;
use Rose\Http\Helpers\CsrfHelper;
use Rose\Support\ServiceProvider;

class CsrfServiceProvider extends ServiceProvider
{
    /**
     * Register CSRF services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the CSRF middleware
        $this->app->singleton('middleware.csrf', function ($app) {
            // Get configuration from config file or use defaults
            $config = $app['config']->get('csrf', []);
            
            return new CsrfMiddleware($app['session'], $config);
        });
        
        // Register the CSRF helper
        $this->app->singleton(CsrfHelper::class, function ($app) {
            return new CsrfHelper($app);
        });
    }

    /**
     * Bootstrap CSRF services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register middleware aliases if router supports it
        if ($this->app->bound('router')) {
            $router = $this->app['router'];
            
            if (method_exists($router, 'aliasMiddleware')) {
                $router->aliasMiddleware('csrf', CsrfMiddleware::class);
            }
        }
    }
}
