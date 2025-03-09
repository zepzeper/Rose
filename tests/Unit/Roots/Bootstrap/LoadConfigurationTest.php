<?php

namespace Rose\Tests\Unit\Roots;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;
use Rose\Roots\Bootstrap\LoadConfiguration;
use Rose\Config\Repository;

class LoadConfigurationTest extends TestCase
{
    protected $app;
    protected $configPath;
    protected $basePath;
    protected $config; // Add this property to store the config repository
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test directories
        $this->basePath = __DIR__ . '/fixtures';
        $this->configPath = $this->basePath . '/config';
        
        if (!is_dir($this->configPath)) {
            mkdir($this->configPath, 0777, true);
        }
        
        // Create a real config repository that the bootstrapper can modify
        $this->config = new Repository([]);
        
        // Create a mock Application rather than the real one
        $this->app = $this->createMock(Application::class);
        
        // Configure the mock to return our test paths
        $this->app->method('configPath')
             ->willReturnCallback(function($path = '') {
                 return $this->configPath . ($path ? DIRECTORY_SEPARATOR . $path : '');
             });
        
        $this->app->method('basePath')
             ->willReturnCallback(function($path = '') {
                 return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
             });
        
        // Configure instance() to capture when the config is set
        $this->app->method('instance')
             ->willReturnCallback(function($key, $instance) {
                 if ($key === 'config') {
                     $this->config = $instance;
                 }
                 return $this->app;
             });
        
        // Configure bound() to return true for 'config' after bootstrap
        $this->app->method('bound')
             ->willReturnCallback(function($key) {
                 return $key === 'config';
             });
             
        // Configure offsetGet() to return our config
        $this->app->method('offsetGet')
             ->willReturnCallback(function($key) {
                 if ($key === 'config') {
                     return $this->config;
                 }
                 return null;
             });
    }
    
    protected function tearDown(): void
    {
        $this->cleanupDirectory($this->configPath);
        parent::tearDown();
    }
    
    /**
     * Helper to recursively clean up directories
     */
    private function cleanupDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = glob($dir . '/*');
        foreach($files as $file) {
            if (is_dir($file)) {
                $this->cleanupDirectory($file);
            } else {
                unlink($file);
            }
        }
        
        rmdir($dir);
    }
    
    public function test_it_loads_config_files()
    {
        // Create test config file
        file_put_contents(
            $this->configPath . '/app.php',
            '<?php return ["name" => "Rose Framework", "env" => "testing"];'
        );
        
        // Create a partial mock of LoadConfiguration to control its use of Finder
        $bootstrapper = $this->createPartialMock(LoadConfiguration::class, ['getConfigurationFiles']);
        $bootstrapper->method('getConfigurationFiles')
            ->willReturn(['app' => $this->configPath . '/app.php']);
            
        // Bootstrap with our mocked objects
        $bootstrapper->bootstrap($this->app);
        
        // Verify the config was properly loaded - use $this->config
        $this->assertEquals('Rose Framework', $this->config->get('app.name'));
        $this->assertEquals('testing', $this->config->get('app.env'));
    }
    
    public function test_it_handles_nested_config_directories()
    {
        // Create nested config directory
        $nestedPath = $this->configPath . '/services';
        if (!is_dir($nestedPath)) {
            mkdir($nestedPath, 0777, true);
        }
        
        // Create test config files
        file_put_contents(
            $nestedPath . '/cache.php',
            '<?php return ["driver" => "file", "ttl" => 3600];'
        );
        
        // Create a partial mock of LoadConfiguration
        $bootstrapper = $this->createPartialMock(LoadConfiguration::class, ['getConfigurationFiles']);
        $bootstrapper->method('getConfigurationFiles')
            ->willReturn(['services.cache' => $nestedPath . '/cache.php']);
            
        // Bootstrap with our mocked objects
        $bootstrapper->bootstrap($this->app);
        
        // Verify the config was properly loaded - use $this->config
        $this->assertEquals('file', $this->config->get('services.cache.driver'));
        $this->assertEquals(3600, $this->config->get('services.cache.ttl'));
    }
}
