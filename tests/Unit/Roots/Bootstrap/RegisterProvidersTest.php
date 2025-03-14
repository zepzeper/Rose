<?php

namespace Rose\Tests\Unit\Roots\Bootstrap;

use PHPUnit\Framework\TestCase;
use Rose\Config\Repository;
use Rose\Roots\Application;
use Rose\Roots\Bootstrap\RegisterProviders;

class RegisterProvidersTest extends TestCase
{
    protected $app;
    protected $config;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock config repository
        $this->config = new Repository([]);
        
        // Create a mock builder for Application
        $this->app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        // Mock make() with callback
        $this->app->method('make')
            ->willReturnCallback(function($abstract) {
                if ($abstract === 'config') {
                    return $this->config;
                }
                return null;
            });
            
        // Mock bound() with callback
        $this->app->method('bound')
            ->willReturnCallback(function($key) {
                return $key === 'config' || $key === 'cached_config_loaded';
            });
            
        // Mock instance() with callback
        $this->app->method('instance')
            ->willReturnCallback(function($key, $instance = null) {
                if ($key === 'config' && $instance !== null) {
                    $this->config = $instance;
                }
                return $this->app;
            });
            
        // Mock array access for config
        $this->app->method('offsetGet')
            ->willReturnCallback(function($key) {
                if ($key === 'config') {
                    return $this->config;
                }
                return null;
            });
            
        $this->app->method('offsetExists')
            ->willReturnCallback(function($key) {
                return $key === 'config';
            });
    }
    
    protected function tearDown(): void
    {
        RegisterProviders::flushState();
        parent::tearDown();
    }
    
    public function test_it_registers_configured_providers()
    {
        // Use expects() instead of manually tracking the calls
        $this->app->expects($this->once())
            ->method('registerConfiguredProviders');
            
        $bootstrapper = new RegisterProviders();
        $bootstrapper->bootstrap($this->app);
        
        // No need to manually assert since the expectation above will fail if the method isn't called
    }
    
    public function test_it_merges_providers_into_config()
    {
        // Reset static state
        RegisterProviders::flushState();
        
        // Add test providers
        RegisterProviders::merge([
            'TestProvider1',
            'TestProvider2'
        ]);
        
        $bootstrapper = new RegisterProviders();
        $bootstrapper->bootstrap($this->app);
        
        // Verify providers were added to config
        $providers = $this->config->get('app.providers');
        $this->assertContains('TestProvider1', $providers);
        $this->assertContains('TestProvider2', $providers);
    }
}
