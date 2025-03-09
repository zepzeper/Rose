<?php

namespace Rose\Tests\Unit\Roots\System;

use PHPUnit\Framework\TestCase;
use Rose\Roots\System\PackageManifest;
use Rose\System\FileSystem;

class PackageManifestTest extends TestCase
{
    protected $files;
    protected $basePath;
    protected $manifestPath;
    protected $manifest;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->files = $this->createMock(FileSystem::class);
        $this->basePath = '/base/path';
        $this->manifestPath = '/path/to/manifest.php';
        $this->manifest = new PackageManifest($this->files, $this->basePath, $this->manifestPath);
    }
    
    public function test_it_returns_cached_manifest_when_available()
    {
        $cachedManifest = ['package1' => ['providers' => ['Provider1']]];
        
        $this->files->expects($this->once())
            ->method('exists')
            ->with($this->manifestPath)
            ->willReturn(true);
            
        // Set up the require to return cached data
        $this->files->expects($this->never())
            ->method('json');
            
        // Use reflection to replace require with a mock
        $manifestProperty = new \ReflectionProperty(PackageManifest::class, 'manifest');
        $manifestProperty->setAccessible(true);
        $manifestProperty->setValue($this->manifest, $cachedManifest);
            
        $result = $this->manifest->getManifest();
        
        $this->assertEquals($cachedManifest, $result);
    }
    
    public function test_it_builds_manifest_from_scratch()
    {
        $packageData = [
            [
                'name' => 'vendor/package1',
                'extra' => [
                    'rose' => [
                        'providers' => ['Package1ServiceProvider'],
                        'aliases' => ['P1' => 'Package1Facade']
                    ]
                ]
            ]
        ];
        
        $this->files->expects($this->at(0))
            ->method('exists')
            ->with($this->manifestPath)
            ->willReturn(false);
            
        $this->files->expects($this->at(1))
            ->method('exists')
            ->with($this->basePath . '/vendor/composer/installed.json')
            ->willReturn(true);
            
        $this->files->expects($this->once())
            ->method('json')
            ->with($this->basePath . '/vendor/composer/installed.json', JSON_THROW_ON_ERROR)
            ->willReturn($packageData);
            
        // Mock directory creation check
        $this->files->expects($this->once())
            ->method('exists')
            ->with(dirname($this->manifestPath))
            ->willReturn(true);
            
        // We can't easily mock file_put_contents, but we can verify other behaviors
        
        $result = $this->manifest->getManifest();
        
        $this->assertArrayHasKey('vendor/package1', $result);
        $this->assertEquals(['Package1ServiceProvider'], $result['vendor/package1']['providers']);
    }
    
    public function test_it_detects_when_recompilation_is_needed()
    {
        $lockFile = $this->basePath . '/composer.lock';
        
        $this->files->expects($this->at(0))
            ->method('exists')
            ->with($lockFile)
            ->willReturn(true);
            
        $this->files->expects($this->at(1))
            ->method('exists')
            ->with($this->manifestPath)
            ->willReturn(true);
            
        // Use a function monkey patch for filemtime
        $GLOBALS['_test_filemtime'] = function($path) use ($lockFile) {
            if ($path === $lockFile) {
                return 200;
            }
            if ($path === $this->manifestPath) {
                return 100;
            }
            return 0;
        };
        
        $result = $this->manifest->shouldRecompile();
        
        $this->assertTrue($result);
        
        // Clean up
        unset($GLOBALS['_test_filemtime']);
    }
}
