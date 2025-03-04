<?php

namespace Rose\Roots\Http\Helpers;

use Rose\Roots\Application;
use Rose\Http\Middleware\CsrfMiddleware;

/**
 * Helper functions for working with HTMX and CSRF protection
 */
class HtmxHelper
{
    /**
     * The application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * Create a new HTMX helper instance
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the CSRF middleware instance
     *
     * @return CsrfMiddleware
     */
    protected function getCsrfMiddleware(): CsrfMiddleware
    {
        return $this->app->make('middleware.csrf');
    }

    /**
     * Get the current CSRF token
     *
     * @return string
     */
    public function getCsrfToken(): string
    {
        return $this->getCsrfMiddleware()->getToken();
    }

    /**
     * Generate the HTMX CSRF meta tag
     * 
     * This will automatically configure HTMX to send the CSRF token
     * with every request in the X-HX-CSRF-Token header.
     *
     * @return string
     */
    public function csrfMetaTag(): string
    {
        $token = $this->getCsrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Generate JavaScript to configure HTMX for CSRF protection
     * 
     * This JS will:
     * 1. Add the CSRF token to all HTMX requests
     * 2. Handle CSRF token refreshing when needed
     *
     * @return string
     */
    public function csrfScript(): string
    {
        $token = $this->getCsrfToken();
        
        return <<<HTML
<script>
    // Configure HTMX to send CSRF token with all requests
    document.addEventListener('DOMContentLoaded', function() {
        // Set CSRF token in HTMX global config
        htmx.config.headers['X-HX-CSRF-Token'] = '$token';
        
        // Listen for CSRF token refresh headers
        htmx.on('htmx:afterOnLoad', function(event) {
            var xhr = event.detail.xhr;
            var csrfToken = xhr.getResponseHeader('X-CSRF-Token');
            if (csrfToken) {
                htmx.config.headers['X-HX-CSRF-Token'] = csrfToken;
                
                // Also update any meta tags
                var metaTags = document.querySelectorAll('meta[name="csrf-token"]');
                metaTags.forEach(function(tag) {
                    tag.setAttribute('content', csrfToken);
                });
            }
        });
    });
</script>
HTML;
    }

    /**
     * Generate a full HTMX setup with CSRF protection
     * 
     * Includes the meta tag and configuration script
     *
     * @return string
     */
    public function setup(): string
    {
        return $this->csrfMetaTag() . "\n" . $this->csrfScript();
    }

    /**
     * Generate an HTML hidden input with the CSRF token
     *
     * @return string
     */
    public function csrfField(): string
    {
        $token = $this->getCsrfToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
