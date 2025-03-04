<?php

namespace Rose\Http\Middleware;

use Closure;
use Rose\Contracts\Routing\Middleware\Middleware as MiddlewareContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware implements MiddlewareContract
{
    /**
     * Default CORS configuration
     */
    protected array $config = [
        'allowedOrigins' => ['*'],
        'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowedHeaders' => ['Content-Type', 'X-Requested-With', 'Authorization'],
        'exposedHeaders' => [],
        'maxAge' => 0,
        'supportsCredentials' => false,
    ];

    /**
     * Create a new CORS middleware instance
     *
     * @param array $config Optional custom configuration to override defaults
     */   
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Handle the incoming request
     *
     * @param Request $request The incoming request
     * @param Closure $next The next middleware/handler in the pipeline
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() == 'OPTIONS') 
        {
            $response = new Response('', 204);
            $this->addPreflightHeaders($response, $request);
            return $response;
        }

        $response = $next($request);
        $this->addCorsHeaders($response, $request);

        return $response;
    }

    /**
     * Add preflight headers to the response
     *
     * @param Response $response.
     * @param Request $request.
     * @return void.
     */
    protected function addPreflightHeaders(Response $response, Request $request)
    {
        $origin = $request->headers->get('origin');

        if ($this->isAllowedOrigin($origin))
        {
            $response->headers->set('Access-Control-Allow-Origin', $origin);

            if ($this->config['supportsCredentials'])
            {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }

            $response->headers->set('Access-Control-Allow-Methods', 
                implode(', ', $this->config['allowedMethods']));

            $response->headers->set('Access-Control-Allow-Headers', 
                implode(', ', $this->config['allowedHeaders']));

            if ($this->config['maxAge'] > 0) {
                $response->headers->set('Access-Control-Max-Age', (string) $this->config['maxAge']);
            }
        }
    }

    /**
     * Add CORS headers to normal response
     *
     * @param Response $response The response to modify
     * @param Request $request The incoming request
     */
    protected function addCorsHeaders(Response $response, Request $request): void
    {
        $origin = $request->headers->get('Origin');

        if ($this->isAllowedOrigin($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);

            if ($this->config['supportsCredentials']) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }

            if (!empty($this->config['exposedHeaders'])) {
                $response->headers->set('Access-Control-Expose-Headers', 
                    implode(', ', $this->config['exposedHeaders']));
            }
        }
    }

    /**
     * Check if the given origin is allowed
     *
     * @param string|null $origin The origin to check
     * @return bool
     */
    protected function isAllowedOrigin(?string $origin): bool
    {
        if (!$origin) {
            return false;
        }

        // Allow all origins
        if (in_array('*', $this->config['allowedOrigins'])) {
            return true;
        }

        // Check specific origins
        return in_array($origin, $this->config['allowedOrigins']);
    }

}
