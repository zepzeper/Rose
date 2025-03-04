<?php

namespace Rose\Support\Providers;

use Rose\Http\Middleware\CsrfMiddleware;
use Rose\Roots\Http\Helpers\HtmxHelper;
use Rose\Support\ServiceProvider;

class HtmxServiceProvider extends ServiceProvider
{
    /**
     * Register HTMX services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge HTMX and CSRF configurations
        $this->mergeHtmxAndCsrfConfig();
        
        // Register the CSRF middleware with HTMX-specific settings
        $this->app->singleton('middleware.csrf', function ($app) {
            // Get configuration from config file or use defaults
            $config = $app['config']->get('csrf', []);
            
            // Get HTMX-specific CSRF settings
            $htmxConfig = $app['config']->get('htmx.csrf', []);
            
            // Merge configurations with HTMX settings taking precedence
            $mergedConfig = array_merge($config, [
                'htmxHeaderName' => $htmxConfig['header_name'] ?? 'X-HX-CSRF-Token'
            ]);
            
            return new CsrfMiddleware($app['session'], $mergedConfig);
        });
        
        // Register the HTMX helper
        $this->app->singleton(HtmxHelper::class, function ($app) {
            return new HtmxHelper($app);
        });
    }

    /**
     * Bootstrap HTMX services.
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
    
    /**
     * Merge HTMX-specific CSRF config into the main CSRF config
     *
     * @return void
     */
    protected function mergeHtmxAndCsrfConfig(): void
    {
        if (!$this->app['config']->has('htmx')) {
            $this->app['config']->set('htmx', include __DIR__ . '/../config/htmx.php');
        }
        
        // Ensure CSRF config exists
        if (!$this->app['config']->has('csrf')) {
            $this->app['config']->set('csrf', []);
        }
        
        // Add HTMX-specific excluded paths if any
        if ($this->app['config']->has('htmx.no_boost')) {
            $excludedPaths = $this->app['config']->get('csrf.except', []);
            $noBoostPaths = $this->app['config']->get('htmx.no_boost', []);
            
            $this->app['config']->set('csrf.except', array_merge($excludedPaths, $noBoostPaths));
        }
    }
}
