<?php

namespace Rose\Tests\Unit\Roots\Bootstrap;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;
use Rose\Roots\Bootstrap\LoadEnviromentVariables;

class LoadEnvironmentVariablesTest extends TestCase
{
    protected $app;
    protected $basePath;
    protected $originalEnvVars;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Store original environment variables to restore later
        $this->originalEnvVars = $_ENV;
        
        // Set up test directories
        $this->basePath = __DIR__ . '/fixtures';
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
        
        // Create a mock Application instead of the real one
        $this->app = $this->createMock(Application::class);
        
        // Configure mock methods
        $this->app->method('configurationIsCached')
            ->willReturn(false);
            
        $this->app->method('environmentPath')
            ->willReturn($this->basePath);
            
        $this->app->method('environmentFile')
            ->willReturn('.env');
            
        $this->app->method('basePath')
            ->willReturn($this->basePath);
    }
    
    protected function tearDown(): void
    {
        // Restore original environment variables
        $_ENV = $this->originalEnvVars;
        
        // Clean up .env file if it exists
        if (file_exists($this->basePath . '/.env')) {
            unlink($this->basePath . '/.env');
        }
        
        // Clean up fixtures directory if it exists
        if (is_dir($this->basePath)) {
            rmdir($this->basePath);
        }
        
        parent::tearDown();
    }
    
    public function test_it_loads_environment_variables()
    {
        // Create a test .env file
        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_DEBUG=true\nAPP_KEY=base64:test123"
        );
        
        // Track instance() calls
        $instances = [];
        $this->app->method('instance')
            ->willReturnCallback(function($key, $value) use (&$instances) {
                $instances[$key] = $value;
                return $this->app;
            });
            
        $this->app->method('bound')
            ->willReturnCallback(function($key) use (&$instances) {
                return isset($instances[$key]);
            });
            
        $this->app->method('make')
            ->willReturnCallback(function($key) use (&$instances) {
                return $instances[$key] ?? null;
            });
        
        // Create and bootstrap the environment loader
        $bootstrapper = new LoadEnviromentVariables();
        $bootstrapper->bootstrap($this->app);
        
        // Verify environment variables were loaded
        $this->assertEquals('testing', $_ENV['APP_ENV']);
        $this->assertEquals('true', $_ENV['APP_DEBUG']);
        $this->assertEquals('base64:test123', $_ENV['APP_KEY']);
        
        // Verify container bindings
        $this->assertTrue(isset($instances['env.vars']));
        $this->assertTrue(isset($instances['env.repository']));
    }
    
    public function test_it_skips_loading_when_configuration_is_cached()
    {
        // Create a new mock that returns true for configurationIsCached
        $app = $this->createMock(Application::class);
        $app->method('configurationIsCached')
            ->willReturn(true);
            
        // Track instance() calls
        $instances = [];
        $app->method('instance')
            ->willReturnCallback(function($key, $value) use (&$instances) {
                $instances[$key] = $value;
                return $this;
            });
            
        $app->method('bound')
            ->willReturnCallback(function($key) use (&$instances) {
                return isset($instances[$key]);
            });
        
        $bootstrapper = new LoadEnviromentVariables();
        $bootstrapper->bootstrap($app);
        
        // Verify env.vars is not set in container
        $this->assertFalse(isset($instances['env.vars']));
    }
}
