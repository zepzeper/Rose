<?php

namespace Rose\Tests\Unit\Concurrency;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\ProcessManager;
use Rose\Concurrency\Pool;
use Rose\Contracts\Container\Container;

class ProcessManagerTest extends TestCase
{
    public function test_it_can_be_created_with_container()
    {
        $container = $this->createMock(Container::class);
        $manager = new ProcessManager($container);
        
        $this->assertInstanceOf(ProcessManager::class, $manager);
    }

    public function test_it_can_create_default_pool()
    {
        $container = $this->createMock(Container::class);
        $manager = new ProcessManager($container);
        
        $pool = $manager->pool();
        
        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals(5, $this->getPrivateProperty($pool, 'concurrency'));
    }

    public function test_it_can_create_pool_with_custom_concurrency()
    {
        $container = $this->createMock(Container::class);
        $manager = new ProcessManager($container);
        
        $pool = $manager->pool(10);
        
        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals(10, $this->getPrivateProperty($pool, 'concurrency'));
    }

    public function test_it_can_create_async_pool()
    {
        $container = $this->createMock(Container::class);
        $manager = new ProcessManager($container);
        
        $pool = $manager->async(8);
        
        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals(8, $this->getPrivateProperty($pool, 'concurrency'));
        $this->assertEquals('async', $this->getPrivateProperty($pool, 'runtime'));
    }

    public function test_it_can_create_parallel_pool()
    {
        $container = $this->createMock(Container::class);
        $manager = new ProcessManager($container);
        
        $pool = $manager->parallel(3);
        
        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals(3, $this->getPrivateProperty($pool, 'concurrency'));
        $this->assertEquals('parallel', $this->getPrivateProperty($pool, 'runtime'));
    }

    /**
     * Helper to get private property value
     */
    private function getPrivateProperty($object, $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
