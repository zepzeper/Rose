<?php

namespace Tests\Unit\Http\Middleware;

use PHPUnit\Framework\TestCase;
use Rose\Http\Middleware\CsrfMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CsrfMiddlewareTest extends TestCase
{
    /**
     * The mock session instance.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $session;

    /**
     * Set up the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock session
        $this->session = $this->createMock(\Rose\Contracts\Session\Session::class);
    }

    /**
     * Test that GET requests are allowed without a CSRF token.
     */
    public function testGetRequestsAreNotValidated()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session);
        $request = Request::create('/test', 'GET');
        $wasCalled = false;
        
        // The session should check if it has a token
        $this->session->expects($this->once())
            ->method('has')
            ->with('csrf_token')
            ->willReturn(true);
            
        // The session should get the token
        $this->session->expects($this->once())
            ->method('get')
            ->with('csrf_token')
            ->willReturn('token123');

        // Act
        $response = $middleware->handle($request, function ($req) use (&$wasCalled) {
            $wasCalled = true;
            return new Response('Test response');
        });

        // Assert
        $this->assertTrue($wasCalled, 'Next middleware was not called');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Set-Cookie'), 'CSRF cookie was not set');
    }

    /**
     * Test that POST requests require a valid CSRF token.
     */
    public function testPostRequestsRequireValidToken()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session);
        $request = Request::create('/test', 'POST', ['_token' => 'valid_token']);
        
        // The session should check if it has a token
        $this->session->expects($this->once())
            ->method('has')
            ->with('csrf_token')
            ->willReturn(true);
            
        // The session should get the token
        $this->session->expects($this->once())
            ->method('get')
            ->with('csrf_token')
            ->willReturn('valid_token');

        $wasCalled = false;

        // Act
        $response = $middleware->handle($request, function ($req) use (&$wasCalled) {
            $wasCalled = true;
            return new Response('Test response');
        });

        // Assert
        $this->assertTrue($wasCalled, 'Next middleware was not called');
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that POST requests with invalid CSRF token throw an exception.
     */
    public function testPostRequestsWithInvalidTokenThrowException()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session);
        $request = Request::create('/test', 'POST', ['_token' => 'invalid_token']);
        
        // The session should check if it has a token
        $this->session->expects($this->once())
            ->method('has')
            ->with('csrf_token')
            ->willReturn(true);
            
        // The session should get the token
        $this->session->expects($this->once())
            ->method('get')
            ->with('csrf_token')
            ->willReturn('valid_token');

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(419);
        
        // Act
        $middleware->handle($request, function ($req) {
            return new Response('This should not be called');
        });
    }

    /**
     * Test that excluded paths are not validated.
     */
    public function testExcludedPathsAreNotValidated()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session, [
            'except' => ['api/*']
        ]);
        
        $request = Request::create('/api/users', 'POST');
        
        // The session should check if it has a token
        $this->session->expects($this->once())
            ->method('has')
            ->with('csrf_token')
            ->willReturn(true);
            
        // The session should get the token
        $this->session->expects($this->once())
            ->method('get')
            ->with('csrf_token')
            ->willReturn('token123');

        $wasCalled = false;

        // Act
        $response = $middleware->handle($request, function ($req) use (&$wasCalled) {
            $wasCalled = true;
            return new Response('Test response');
        });

        // Assert
        $this->assertTrue($wasCalled, 'Next middleware was not called');
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that the middleware generates a new token if one doesn't exist.
     */
    public function testGeneratesNewTokenIfNotExists()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session);
        $request = Request::create('/test', 'GET');
        
        // The session should check if it has a token
        $this->session->expects($this->once())
            ->method('has')
            ->with('csrf_token')
            ->willReturn(false);
            
        // The session should store a new token
        $this->session->expects($this->once())
            ->method('put')
            ->with('csrf_token', $this->callback(function ($token) {
                return is_string($token) && strlen($token) >= 32;
            }));
            
        // The session should get the token
        $this->session->expects($this->once())
            ->method('get')
            ->with('csrf_token')
            ->willReturn('generated_token');

        // Act
        $response = $middleware->handle($request, function ($req) {
            return new Response('Test response');
        });

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that CSRF token can be passed via header.
     */
    public function testTokenCanBePassedViaHeader()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session);
        $request = Request::create('/test', 'POST');
        $request->headers->set('X-CSRF-TOKEN', 'valid_token');
        
        // The session should check if it has a token
        $this->session->expects($this->once())
            ->method('has')
            ->with('csrf_token')
            ->willReturn(true);
            
        // The session should get the token
        $this->session->expects($this->once())
            ->method('get')
            ->with('csrf_token')
            ->willReturn('valid_token');

        $wasCalled = false;

        // Act
        $response = $middleware->handle($request, function ($req) use (&$wasCalled) {
            $wasCalled = true;
            return new Response('Test response');
        });

        // Assert
        $this->assertTrue($wasCalled, 'Next middleware was not called');
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test the getToken method.
     */
    public function testGetToken()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session);
        
        // The session should check if it has a token
        $this->session->expects($this->once())
            ->method('has')
            ->with('csrf_token')
            ->willReturn(true);
            
        // The session should get the token
        $this->session->expects($this->once())
            ->method('get')
            ->with('csrf_token')
            ->willReturn('token123');

        // Act
        $token = $middleware->getToken();

        // Assert
        $this->assertEquals('token123', $token);
    }

    /**
     * Test the refreshToken method.
     */
    public function testRefreshToken()
    {
        // Arrange
        $middleware = new CsrfMiddleware($this->session);
        
        // The session should store a new token
        $this->session->expects($this->once())
            ->method('put')
            ->with('csrf_token', $this->callback(function ($token) {
                return is_string($token) && strlen($token) >= 32;
            }));

        // Act
        $token = $middleware->refreshToken();

        // Assert
        $this->assertIsString($token);
        $this->assertGreaterThanOrEqual(64, strlen($token)); // bin2hex doubles the length
    }
}
