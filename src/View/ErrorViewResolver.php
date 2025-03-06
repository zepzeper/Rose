<?php

namespace Rose\View;

use Rose\Roots\Application;
use Symfony\Component\HttpFoundation\Response;

class ErrorViewResolver
{
    /**
     * The application instance
     */
    protected Application $app;
    
    /**
     * Default error view paths, can be overridden
     */
    protected $viewPaths = [
        '404' => 'errors/404',
        '500' => 'errors/500',
    ];
    
    /**
     * Create a new error view resolver
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        
        // Load configuration if available
        $this->loadConfiguration();
    }
    
    /**
     * Load error view configuration from the application
     */
    protected function loadConfiguration(): void
    {
        // Check if error configuration exists
        if ($errorViews = $this->app['config']->get('errors.views')) {
            
            // Apply custom view paths from configuration
            foreach ($errorViews as $type => $path) {
                $this->setErrorView($type, $path);
            }
        }
    }
    
    /**
     * Override a specific error view path
     */
    public function setErrorView(string $errorType, string $viewPath): self
    {
        $this->viewPaths[$errorType] = $viewPath;
        return $this;
    }
    
    /**
     * Get the configured view path for an error type
     */
    public function getErrorView(string $errorType): string
    {
        // First check if custom path is set
        if (isset($this->viewPaths[$errorType])) {
            return $this->viewPaths[$errorType];
        }
        
        // Then check app views directory
        $appViewPath = $this->app->viewPath("errors/{$errorType}.twig");
        if (file_exists($appViewPath)) {
            return "errors/{$errorType}";
        }

        // Fallback to framework default
        return "framework::errors/{$errorType}";
    }
    
    /**
     * Resolve and render a 404 Not Found response
     */
    public function resolveNotFound(string $uri): Response
    {
        // Check if we should use a custom handler for this request
        if ($this->shouldUseCustomHandler($uri, '404')) {
            return $this->resolveWithCustomHandler($uri, '404');
        }
        
        // Get template engine instance
        $templateEngine = $this->app->make(TemplateEngine::class);
        
        // Render the configured 404 view
        $content = $templateEngine->render($this->getErrorView('404'), [
            'title' => 'Page Not Found',
            'requestedUri' => $uri
        ]);
        
        // Return a Response object with 404 status
        return new Response($content, Response::HTTP_NOT_FOUND);
    }
    
    /**
     * Resolve and render a server error response
     */
    public function resolveServerError(?\Throwable $exception = null): Response
    {
        // Get current URI
        $uri = $this->app->request->getPathInfo();
        
        // Check if we should use a custom handler
        if ($this->shouldUseCustomHandler($uri, '500')) {
            return $this->resolveWithCustomHandler($uri, '500', $exception);
        }
        
        // Get template engine instance
        $templateEngine = $this->app->make(TemplateEngine::class);
        
        // Only include exception details in non-production environments
        $showDetails = !$this->app->isProduction();
        
        // Render the configured 500 view
        $content = $templateEngine->render($this->getErrorView('500'), [
            'title' => 'Server Error',
            'exception' => $showDetails ? $exception : null
        ]);
        
        // Return a Response object with 500 status
        return new Response($content, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    
    /**
     * Determine if a custom handler should be used for this request
     */
    protected function shouldUseCustomHandler(string $uri, string $errorType): bool
    {
        // Check for API requests
        if (str_starts_with($uri, '/api/') && 
            $this->app->get('config')->has('errors.handlers.api')) {
            return true;
        }
        
        // Check for specific error type handlers
        if ($this->app->get('config')->has("errors.handlers.{$errorType}")) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Resolve with a custom handler
     */
    protected function resolveWithCustomHandler(string $uri, string $errorType, ?\Throwable $exception = null): Response
    {
        // For API requests
        if (str_starts_with($uri, '/api/') && 
            $this->app->get('config')->has('errors.handlers.api')) {
            $handlerClass = $this->app->get('config')->get('errors.handlers.api');
            $handler = $this->app->make($handlerClass);
            return $handler->handle($errorType, $uri, $exception);
        }
        
        // For specific error types
        if ($this->app->get('config')->has("errors.handlers.{$errorType}")) {
            $handlerClass = $this->app->get('config')->get("errors.handlers.{$errorType}");
            $handler = $this->app->make($handlerClass);
            return $handler->handle($errorType, $uri, $exception);
        }
        
        // Should never reach here due to the check in shouldUseCustomHandler
        throw new \LogicException('No custom handler found but shouldUseCustomHandler returned true');
    }
}
