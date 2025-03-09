<?php

namespace Rose\Tests\Unit\Roots;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;
use Rose\Roots\ProviderRepository;
use Rose\System\FileSystem;
use Rose\Support\ServiceProvider;

class ProviderRepositoryTest extends TestCase
{
    protected $app;
    protected $files;
    protected $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createMock(Application::class);
        $this->files = $this->createMock(FileSystem::class);
        $this->repository = new ProviderRepository($this->app, $this->files, '/path/to/manifest.php');
    }
    
    public function test_it_loads_multiple_providers()
    {
        $this->app->expects($this->exactly(2))
            ->method('register')
            ->withConsecutive(
                ['TestProvider1'],
                ['TestProvider2']
            );
            
        $this->repository->load(['TestProvider1', 'TestProvider2']);
    }
    
    public function test_it_creates_provider_instance()
    {
        // Create a test provider for this test
        $mockProvider = new class($this->app) extends ServiceProvider {
            public function register() {}
        };
        
        $providerClass = get_class($mockProvider);
        
        $provider = $this->repository->createProvider($providerClass);
        
        $this->assertInstanceOf($providerClass, $provider);
        $this->assertAttributeEquals($this->app, 'app', $provider);
    }
}
