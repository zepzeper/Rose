<?php

namespace Rose\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TemplateEngine
{
    protected $twig;
    
    public function __construct(string $templatesPath)
    {
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
    }
    
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
}
