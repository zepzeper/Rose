<?php

namespace Rose\Support\Providers;

use Rose\Roots\Http\Helpers\HtmxHelper;
use Rose\Support\ServiceProvider;
use Rose\View\Htmx\HtmxTwigExtension;
use Rose\View\TemplateEngine;

class TemplateEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register dependencies first
        $this->app->singleton(HtmxTwigExtension::class);
        $this->app->singleton(HtmxHelper::class);
        
        // Register view configuration
        $this->app->singleton('view.config', function ($app) {
            // Merge defaults with user configuration if it exists
            $defaults = [
                'templates_path' => $app->basePath('resources/views'),
                'cache' => $app->environment('production') ? $app->storagePath('cache/views') : false,
                'debug' => !$app->environment('production'),
                'errors' => [
                    '404' => 'errors/404',
                    '500' => 'errors/500',
                ],
                'extensions' => [],
            ];

            return array_merge($defaults, $app->bound('view') ? $app['view'] : []);
        });
        
        // Register the template engine
        $this->app->singleton(TemplateEngine::class, function ($app) {
            $config = $app->get('view.config');
            
            return new TemplateEngine(
                $config['templates_path'],
                $app,
                $app->make(HtmxTwigExtension::class),
                $app->make(HtmxHelper::class)
            );
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
