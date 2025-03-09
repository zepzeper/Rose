<?php

namespace Rose\Tests\Unit\Roots\Http;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;
use Rose\Roots\Http\Helpers\HtmxHelper;
use Rose\Http\Middleware\CsrfMiddleware;

class HtmxHelperTest extends TestCase
{
    protected $app;
    protected $csrfMiddleware;
    protected $helper;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createMock(Application::class);
        $this->csrfMiddleware = $this->createMock(CsrfMiddleware::class);
        
        $this->app->expects($this->any())
            ->method('make')
            ->with('middleware.csrf')
            ->willReturn($this->csrfMiddleware);
            
        $this->helper = new HtmxHelper($this->app);
    }
    
    public function test_it_gets_csrf_token()
    {
        $this->csrfMiddleware->expects($this->once())
            ->method('getToken')
            ->willReturn('test-token-123');
            
        $token = $this->helper->getCsrfToken();
        
        $this->assertEquals('test-token-123', $token);
    }
    
    public function test_it_generates_csrf_meta_tag()
    {
        $this->csrfMiddleware->expects($this->once())
            ->method('getToken')
            ->willReturn('test-token-123');
            
        $metaTag = $this->helper->csrfMetaTag();
        
        $this->assertStringContainsString('<meta name="csrf-token"', $metaTag);
        $this->assertStringContainsString('content="test-token-123"', $metaTag);
    }
    
    public function test_it_creates_csrf_hidden_field()
    {
        $this->csrfMiddleware->expects($this->once())
            ->method('getToken')
            ->willReturn('test-token-123');
            
        $field = $this->helper->csrfField();
        
        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="_token"', $field);
        $this->assertStringContainsString('value="test-token-123"', $field);
    }
    
    public function test_it_provides_complete_htmx_setup()
    {
        $this->csrfMiddleware->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn('test-token-123');
            
        $setup = $this->helper->setup();
        
        $this->assertStringContainsString('<meta name="csrf-token"', $setup);
        $this->assertStringContainsString('<script>', $setup);
        $this->assertStringContainsString("htmx.config.headers['X-HX-CSRF-Token'] = 'test-token-123'", $setup);
    }
}
