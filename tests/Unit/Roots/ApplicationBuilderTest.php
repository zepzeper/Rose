<?php

namespace Rose\Tests\Unit\Roots;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;
use Rose\Roots\Configuration\ApplicationBuilder;
use Rose\Support\Providers\RouteServiceProvider;

class ApplicationBuilderTest extends TestCase
{
    protected $app;
    protected $builder;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createMock(Application::class);
        $this->builder = new ApplicationBuilder($this->app);
    }
    
    public function test_it_registers_kernels()
    {
        $this->app->expects($this->once())
            ->method('singleton')
            ->with(
                \Rose\Contracts\Http\Kernel::class,
                \Rose\Roots\Http\Kernel::class
            );
            
        $result = $this->builder->withKernels();
        
        $this->assertSame($this->builder, $result);
    }
    
    public function test_it_registers_providers()
    {
        // Since RegisterProviders is static, we'll test behavior not implementation
        $result = $this->builder->withProviders(['TestProvider']);
        
        $this->assertSame($this->builder, $result);
    }
    
    public function test_it_sets_up_routing()
    {
        // Mock the static loadRoutesUsing method to avoid the type issue
        $routeServiceProviderMock = $this->createMock(RouteServiceProvider::class);
        
        // Save original class
        $originalClass = null;
        if (class_exists(RouteServiceProvider::class)) {
            $originalClass = RouteServiceProvider::class;
        }
        
        // Create a class_alias for our mock
        class_alias(get_class($routeServiceProviderMock), RouteServiceProvider::class);
        
        try {
            // Set up app booting method expectation
            $this->app->expects($this->once())
                ->method('booting')
                ->with($this->isType('callable'));
            
            // Execute the method
            $result = $this->builder->withRouting(function($router) {
                // routing callback
            });
            
            // Verify the result
            $this->assertSame($this->builder, $result);
        } finally {
            // Restore original class if needed
            if ($originalClass) {
                class_alias($originalClass, RouteServiceProvider::class);
            }
        }
    }
    
    public function test_it_returns_application_instance()
    {
        $result = $this->builder->create();
        
        $this->assertSame($this->app, $result);
    }
}
