<?php

namespace Rose\Tests\Roots;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;

class ApplicationTest extends TestCase
{
    /**
     * Helper method to access protected properties using reflection
     */
    protected function getProtectedProperty($object, $property)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        
        return $reflectionProperty->getValue($object);
    }
    
    /**
     * Helper method to invoke protected methods using reflection
     */
    protected function invokeProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
    
    public function test_it_has_correct_version_constant()
    {
        $this->assertEquals('0.1.alpha', Application::VERSION);
    }
    
    public function test_it_resolves_paths_correctly()
    {
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerBaseBindings', 'registerBaseServiceProviders', 'registerCoreContainerAliases'])
            ->getMock();
            
        $basePath = '/test/path';
        $this->invokeProtectedMethod($app, 'setBasePath', [$basePath]);
        
        $this->assertEquals($basePath, $app->basePath());
        $this->assertEquals($basePath . '/config', $app->configPath());
        $this->assertEquals($basePath . '/storage', $app->storagePath());
        $this->assertEquals($basePath . '/database', $app->databasePath());
    }
    
    public function test_it_resolves_paths_with_subdirectory()
    {
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerBaseBindings', 'registerBaseServiceProviders', 'registerCoreContainerAliases'])
            ->getMock();
            
        $basePath = '/test/path';
        $this->invokeProtectedMethod($app, 'setBasePath', [$basePath]);
        
        $this->assertEquals($basePath . '/config/app.php', $app->configPath('app.php'));
        $this->assertEquals($basePath . '/storage/logs', $app->storagePath('logs'));
    }
    
    public function test_it_can_boot_application()
    {
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerBaseBindings', 'registerBaseServiceProviders', 'registerCoreContainerAliases'])
            ->getMock();
            
        // Mock the initial booted state
        $reflection = new \ReflectionProperty(Application::class, 'booted');
        $reflection->setAccessible(true);
        $reflection->setValue($app, false);
        
        $this->assertFalse($app->isBooted());
        
        $app->boot();
        
        $this->assertTrue($app->isBooted());
        
        // Test calling boot again doesn't cause issues
        $app->boot();
        $this->assertTrue($app->isBooted());
    }
    
    public function test_it_can_register_service_provider()
    {
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerBaseBindings', 'registerBaseServiceProviders', 'registerCoreContainerAliases'])
            ->getMock();
            
        $mockProvider = $this->createMock(\Rose\Support\ServiceProvider::class);
        $mockProvider->expects($this->once())->method('register');
        
        $app->register($mockProvider);
        
        $providers = $this->getProtectedProperty($app, 'serviceProviders');
        $this->assertContains($mockProvider, $providers);
    }
    
    public function test_it_executes_booting_callbacks()
    {
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerBaseBindings', 'registerBaseServiceProviders', 'registerCoreContainerAliases'])
            ->getMock();
            
        $called = false;
        
        $app->booting(function ($appInstance) use (&$called, $app) {
            $called = true;
            $this->assertSame($app, $appInstance);
        });
        
        // Set initial state
        $reflection = new \ReflectionProperty(Application::class, 'booted');
        $reflection->setAccessible(true);
        $reflection->setValue($app, false);
        
        $app->boot();
        
        $this->assertTrue($called);
    }
    
    public function test_it_executes_booted_callbacks()
    {
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerBaseBindings', 'registerBaseServiceProviders', 'registerCoreContainerAliases'])
            ->getMock();
            
        $called = false;
        
        // Set initial state
        $reflection = new \ReflectionProperty(Application::class, 'booted');
        $reflection->setAccessible(true);
        $reflection->setValue($app, false);
        
        $app->boot();
        
        $app->booted(function ($appInstance) use (&$called, $app) {
            $called = true;
            $this->assertSame($app, $appInstance);
        });
        
        $this->assertTrue($called);
    }
}
