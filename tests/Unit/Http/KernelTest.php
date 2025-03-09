<?php

namespace Rose\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;
use Rose\Roots\Http\Kernel;
use Rose\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KernelTest extends TestCase
{
    protected $app;
    protected $router;
    protected $kernel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createMock(Application::class);
        $this->router = $this->createMock(Router::class);
        $this->kernel = new Kernel($this->app, $this->router);
    }
    
    public function test_it_bootstraps_application()
    {
        $this->app->expects($this->once())
            ->method('hasBeenBootstrapped')
            ->willReturn(false);
            
        $this->app->expects($this->once())
            ->method('bootstrapWith')
            ->with($this->callback(function($bootstrappers) {
                return count($bootstrappers) === 4 &&
                       $bootstrappers[0] === \Rose\Roots\Bootstrap\LoadEnviromentVariables::class &&
                       $bootstrappers[3] === \Rose\Roots\Bootstrap\BootProvider::class;
            }));
            
        $this->kernel->bootstrap();
    }
    
    public function test_it_skips_bootstrapping_if_already_bootstrapped()
    {
        $this->app->expects($this->once())
            ->method('hasBeenBootstrapped')
            ->willReturn(true);
            
        $this->app->expects($this->never())
            ->method('bootstrapWith');
            
        $this->kernel->bootstrap();
    }
    
    public function test_it_handles_request_through_pipeline()
    {
        // Create pipeline mock
        $pipeline = $this->createMock(\Rose\Pipeline\Pipeline::class);
        $pipeline->method('through')->willReturnSelf();
        $pipeline->method('then')->willReturn(new Response('OK'));
        
        // Setup expectations
        $this->app->expects($this->once())->method('hasBeenBootstrapped')->willReturn(true);
        $this->app->expects($this->at(1))->method('instance')->with('request', $this->isInstanceOf(Request::class));
        $this->app->expects($this->at(2))->method('make')->with('middleware.pipeline')->willReturn($pipeline);
        
        $this->router->expects($this->once())->method('setMiddleware');
        
        // Execute
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        // Verify
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('OK', $response->getContent());
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }
    
    public function test_it_can_terminate_request()
    {
        $request = Request::create('/test', 'GET');
        $response = new Response('OK');
        
        // Just verifying this doesn't throw an exception
        $this->kernel->terminate($request, $response);
        $this->assertTrue(true);
    }
    
    public function test_it_provides_middleware_accessors()
    {
        $middleware = $this->kernel->getMiddleware();
        $this->assertIsArray($middleware);
        $this->assertContains(\Rose\Http\Middleware\CorsMiddleware::class, $middleware);
        
        $groups = $this->kernel->getMiddlewareGroups();
        $this->assertIsArray($groups);
        $this->assertArrayHasKey('web', $groups);
        $this->assertContains(\Rose\Http\Middleware\CsrfMiddleware::class, $groups['web']);
    }
}
