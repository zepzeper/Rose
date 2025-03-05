<?php

namespace Rose\View;

use Rose\Roots\Http\Helpers\HtmxHelper;
use Rose\View\Htmx\HtmxTwigExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TemplateEngine
{
    protected Environment $twig;
    protected $app;
    protected HtmxTwigExtension $htmxExtension;
    protected HtmxHelper $htmxHelper;
    
    /**
     * Create a new template engine instance
     *
     * @param string $templatesPath Path to templates directory
     * @param object $app Application container
     * @param HtmxTwigExtension $htmxExtension HTMX Twig extension
     * @param HtmxHelper|null $htmxHelper HTMX helper for CSRF functions (optional)
     */
    public function __construct(
        string $templatesPath, 
        $app, 
        HtmxTwigExtension $htmxExtension,
        ?HtmxHelper $htmxHelper = null
    ) {
        $this->app = $app;
        $this->htmxExtension = $htmxExtension;
        $this->htmxHelper = $htmxHelper ?? $app->make(HtmxHelper::class);
        
        $loader = new FilesystemLoader($templatesPath);
        $this->twig = new Environment($loader, [
            'cache' => false, // Set to a path for cache in production
            'debug' => true,
        ]);
        
        // Register extensions
        $this->registerExtensions();
    }
    
    /**
     * Register all Twig extensions
     */
    protected function registerExtensions(): void
    {
        $this->registerViteExtension();
        $this->registerHtmxExtension();
        $this->registerCsrfFunctions();
    }
    
    /**
     * Register Vite extension for asset handling
     */
    protected function registerViteExtension(): void
    {
        $this->twig->addFunction(new TwigFunction('vite_tags', function($entryPoints = 'resources/js/main.js') {
            return \Rose\View\Vite::tags($entryPoints);
        }, ['is_safe' => ['html']]));
    }
    
    /**
     * Register HTMX extension
     */
    protected function registerHtmxExtension(): void
    {
        // Register the injected HTMX extension
        $this->twig->addExtension($this->htmxExtension);
    }
    
    /**
     * Register CSRF-related Twig functions
     */
    protected function registerCsrfFunctions(): void
    {
        // Add HTMX CSRF setup function
        $this->twig->addFunction(new TwigFunction('htmx_csrf_setup', function() {
            return $this->htmxHelper->setup();
        }, ['is_safe' => ['html']]));
        
        // Add CSRF field function
        $this->twig->addFunction(new TwigFunction('csrf_field', function() {
            return $this->htmxHelper->csrfField();
        }, ['is_safe' => ['html']]));
        
        // Add CSRF token function
        $this->twig->addFunction(new TwigFunction('csrf_token', function() {
            return $this->htmxHelper->getCsrfToken();
        }));
    }
    
    /**
     * Render a template with provided data
     *
     * @param string $template Template path
     * @param array $data Template variables
     * @return string Rendered output
     */
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
    
    /**
     * Get the Twig environment instance
     *
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
    
    /**
     * Add a custom Twig function
     *
     * @param string $name Function name
     * @param callable $callback Function callback
     * @param array $options Function options
     * @return void
     */
    public function addFunction(string $name, callable $callback, array $options = []): void
    {
        $this->twig->addFunction(new TwigFunction($name, $callback, $options));
    }
}
