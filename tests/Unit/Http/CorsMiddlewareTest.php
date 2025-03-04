<?php

namespace Tests\Unit\Http\Middleware;

use PHPUnit\Framework\TestCase;
use Rose\Http\Middleware\CorsMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddlewareTest extends TestCase
{
    /**
     * Test a standard CORS request with an allowed origin.
     */
    public function testHandleStandardRequestWithAllowedOrigin()
    {
        // Arrange
        $middleware = new CorsMiddleware([
            'allowedOrigins' => ['https://example.com'],
            'supportsCredentials' => true,
            'exposedHeaders' => ['X-Custom-Header'],
        ]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Origin', 'https://example.com');

        $wasCalled = false;
        $next = function ($req) use (&$wasCalled) {
            $wasCalled = true;
            return new Response('Test response');
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertTrue($wasCalled, 'Next middleware was not called');
        $this->assertEquals('https://example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertEquals('X-Custom-Header', $response->headers->get('Access-Control-Expose-Headers'));
    }

    /**
     * Test a standard CORS request with a disallowed origin.
     */
    public function testHandleStandardRequestWithDisallowedOrigin()
    {
        // Arrange
        $middleware = new CorsMiddleware([
            'allowedOrigins' => ['https://example.com'],
        ]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Origin', 'https://malicious-site.com');

        $wasCalled = false;
        $next = function ($req) use (&$wasCalled) {
            $wasCalled = true;
            return new Response('Test response');
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertTrue($wasCalled, 'Next middleware was not called');
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Test a preflight CORS request.
     */
    public function testHandlePreflightRequest()
    {
        // Arrange
        $middleware = new CorsMiddleware([
            'allowedOrigins' => ['https://example.com'],
            'allowedMethods' => ['GET', 'POST'],
            'allowedHeaders' => ['Content-Type', 'X-Custom-Header'],
            'maxAge' => 3600,
        ]);

        $request = Request::create('/api/users', 'OPTIONS');
        $request->headers->set('Origin', 'https://example.com');
        $request->headers->set('Access-Control-Request-Method', 'POST');
        $request->headers->set('Access-Control-Request-Headers', 'Content-Type');

        $wasCalled = false;
        $next = function ($req) use (&$wasCalled) {
            $wasCalled = true;
            return new Response('This should not be called');
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertFalse($wasCalled, 'Next middleware should not be called for preflight requests');
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('https://example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('Content-Type, X-Custom-Header', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    /**
     * Test the wildcard origin.
     */
    public function testHandleRequestWithWildcardOrigin()
    {
        // Arrange
        $middleware = new CorsMiddleware([
            'allowedOrigins' => ['*'],
        ]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Origin', 'https://some-random-site.com');

        $next = function ($req) {
            return new Response('Test response');
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertEquals('https://some-random-site.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Test a request without an origin header.
     */
    public function testHandleRequestWithoutOrigin()
    {
        // Arrange
        $middleware = new CorsMiddleware();

        $request = Request::create('/api/users', 'GET');
        // No Origin header set

        $next = function ($req) {
            return new Response('Test response');
        };

        // Act
        $response = $middleware->handle($request, $next);

        // Assert
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Test that configuration is being properly merged.
     */
    public function testConfigurationMerging()
    {
        // Arrange
        $defaultConfig = [
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'allowedHeaders' => ['Content-Type', 'X-Requested-With', 'Authorization'],
            'exposedHeaders' => [],
            'maxAge' => 0,
            'supportsCredentials' => false,
        ];

        $customConfig = [
            'allowedOrigins' => ['https://example.com'],
            'maxAge' => 3600,
        ];

        // Create a modified middleware test class that exposes the config
        $middlewareClass = new class extends CorsMiddleware {
            public function getConfig() {
                return $this->config;
            }
        };

        // Act
        $middleware = new $middlewareClass($customConfig);
        $config = $middleware->getConfig();

        // Assert
        $this->assertEquals(['https://example.com'], $config['allowedOrigins']);
        $this->assertEquals(3600, $config['maxAge']);
        
        // Ensure other default values remain
        $this->assertEquals(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $config['allowedMethods']);
        $this->assertEquals(['Content-Type', 'X-Requested-With', 'Authorization'], $config['allowedHeaders']);
    }
}
