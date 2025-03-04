<?php

namespace Rose\Pipeline;

use Rose\Http\Middleware\CorsMiddleware;
use Rose\Support\ServiceProvider;

class PipelineServiceProvider extends ServiceProvider
{
    /**
     * Register middleware services in the container
     *
     * @return void
     */
    public function register(): void
    {
        // Register the middleware pipeline
        $this->app->singleton('middleware.pipeline', function ($app) {
            return new Pipeline($app);
        });
        
        // Register CORS middleware with default configuration
        $this->app->singleton('middleware.cors', function ($app) {
            // Default configuration which can be overridden in config files
            return new CorsMiddleware([
                'allowedOrigins' => $app['config']->get('cors.allowed_origins', ['*']),
                'allowedMethods' => $app['config']->get('cors.allowed_methods', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']),
                'allowedHeaders' => $app['config']->get('cors.allowed_headers', ['Content-Type', 'X-Requested-With', 'Authorization']),
                'exposedHeaders' => $app['config']->get('cors.exposed_headers', []),
                'maxAge' => $app['config']->get('cors.max_age', 86400),
                'supportsCredentials' => $app['config']->get('cors.supports_credentials', false),
            ]);
        });
    }
    
    /**
     * Bootstrap middleware services
     *
     * @return void
     */
    public function boot(): void
    {
        // Register middleware in the router
        $router = $this->app['router'];
        
        // Register middleware aliases
        $router->aliasMiddleware('cors', CorsMiddleware::class);
        
        // Set up middleware groups if not already configured in Kernel
        if (method_exists($router, 'middlewareGroup')) {
            $router->middlewareGroup('api', ['cors']);
        }
    }
}
