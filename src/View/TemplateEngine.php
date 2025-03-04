<?php

namespace Rose\View;

use Rose\Roots\Http\Helpers\HtmxHelper;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TemplateEngine
{
    protected $twig;
    protected $app;
    
    public function __construct(string $templatesPath, $app)
    {
        $this->app = $app;

        $loader = new FilesystemLoader($templatesPath);
        $this->twig = new Environment($loader, [
            'cache' => false, // Set to a path for cache in production
            'debug' => true,
        ]);
        
        // Add Vite extension
        $this->twig->addFunction(new \Twig\TwigFunction('vite_tags', function($entryPoints = 'resources/js/main.js') {
            return \Rose\View\Vite::tags($entryPoints);
        }, ['is_safe' => ['html']]));
        
        // Add HTMX extension
        $this->twig->addFunction(new \Twig\TwigFunction('htmx_attrs', function($attributes) {
            return \Rose\View\Htmx\HtmxHelper::attributes($attributes);
        }, ['is_safe' => ['html']]));

        // Register the CSRF functions using your HtmxHelper
        $this->registerCsrfFunctions();
    }

    /**
     * Register CSRF-related Twig functions
     */
    protected function registerCsrfFunctions(): void
    {
        // Add HTMX CSRF setup function
        $this->twig->addFunction(new TwigFunction('htmx_csrf_setup', function() {
            $helper = $this->app->make(HtmxHelper::class);
            return $helper->setup();
        }, ['is_safe' => ['html']]));
        
        // Add CSRF field function
        $this->twig->addFunction(new TwigFunction('csrf_field', function() {
            $helper = $this->app->make(HtmxHelper::class);
            return $helper->csrfField();
        }, ['is_safe' => ['html']]));
        
        // Add CSRF token function
        $this->twig->addFunction(new TwigFunction('csrf_token', function() {
            $helper = $this->app->make(HtmxHelper::class);
            return $helper->getCsrfToken();
        }));
    }
    
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
}
