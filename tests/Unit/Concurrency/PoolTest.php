<?php

namespace Rose\Tests\Unit\Concurrency;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\Pool;
use Rose\Concurrency\PoolStatus;
use Rose\Concurrency\Process;
use Rose\Exceptions\Concurrency\PoolOverflowException;

class PoolTest extends TestCase
{
    public function test_it_can_be_created_with_default_options()
    {
        $pool = Pool::create();
        
        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals(5, $this->getPrivateProperty($pool, 'concurrency'));
        $this->assertEquals('async', $this->getPrivateProperty($pool, 'runtime'));
    }

    public function test_it_can_be_created_with_async_runtime()
    {
        $pool = Pool::async(10);
        
        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals(10, $this->getPrivateProperty($pool, 'concurrency'));
        $this->assertEquals('async', $this->getPrivateProperty($pool, 'runtime'));
    }

    public function test_it_can_be_created_with_parallel_runtime()
    {
        $pool = Pool::parallel(3);
        
        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals(3, $this->getPrivateProperty($pool, 'concurrency'));
        $this->assertEquals('parallel', $this->getPrivateProperty($pool, 'runtime'));
    }

    public function test_it_can_add_tasks_to_queue()
    {
        $pool = Pool::create();
        $task = function() { return 'Hello World'; };
        
        $pool->add($task);
        
        $queue = $this->getPrivateProperty($pool, 'queue');
        $this->assertCount(1, $queue);
        $this->assertSame($task, $queue[0]);
    }

    public function test_it_can_set_concurrency()
    {
        $pool = Pool::create();
        $pool->concurrency(10);
        
        $this->assertEquals(10, $this->getPrivateProperty($pool, 'concurrency'));
    }

    public function test_it_can_set_callback_for_when_task_succeeds()
    {
        $pool = Pool::create();
        $callback = function() {};
        
        $pool->whenTaskSucceeded($callback);
        
        $this->assertSame($callback, $this->getPrivateProperty($pool, 'successCallback'));
    }

    public function test_it_can_set_callback_for_when_task_fails()
    {
        $pool = Pool::create();
        $callback = function() {};
        
        $pool->whenTaskFailed($callback);
        
        $this->assertSame($callback, $this->getPrivateProperty($pool, 'failedCallback'));
    }

    public function test_it_can_set_callback_for_before_task()
    {
        $pool = Pool::create();
        $callback = function() {};
        
        $pool->beforeTask($callback);
        
        $this->assertSame($callback, $this->getPrivateProperty($pool, 'beforeTask'));
    }

    public function test_it_can_get_pool_status()
    {
        $pool = Pool::create();
        
        // Add two tasks to the queue
        $pool->add(function() {});
        $pool->add(function() {});
        
        // Set running processes, successful and failed counts for testing
        $this->setPrivateProperty($pool, 'runningProcesses', [new \stdClass(), new \stdClass()]);
        $this->setPrivateProperty($pool, 'successful', [new \stdClass()]);
        $this->setPrivateProperty($pool, 'failed', [new \stdClass(), new \stdClass(), new \stdClass()]);
        
        $status = $pool->status();
        
        $this->assertInstanceOf(PoolStatus::class, $status);
        $this->assertEquals(2, $status->getPending());
        $this->assertEquals(2, $status->getRunning());
        $this->assertEquals(1, $status->getSuccessful());
        $this->assertEquals(3, $status->getFailed());
        $this->assertEquals(8, $status->getTotal());
    }

    public function test_it_throws_exception_when_creating_process_with_overflow()
    {
        $pool = Pool::create()->concurrency(2);
        $this->setPrivateProperty($pool, 'runningProcesses', [new \stdClass(), new \stdClass()]);
        
        $this->expectException(PoolOverflowException::class);
        
        $method = new \ReflectionMethod(Pool::class, 'createProcess');
        $method->setAccessible(true);
        $method->invoke($pool, function() {});
    }

    public function test_it_can_run_tasks()
    {
        $pool = $this->getMockBuilder(Pool::class)
            ->onlyMethods(['createProcess'])
            ->disableOriginalConstructor()
            ->getMock();

        // Initialize required properties
        $this->setPrivateProperty($pool, 'queue', []);
        $this->setPrivateProperty($pool, 'runningProcesses', []);
        $this->setPrivateProperty($pool, 'concurrency', 5);
        $this->setPrivateProperty($pool, 'beforeTask', null);

        // Mock process that will be returned
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('start')
            ->willReturnSelf();

        // Mock createProcess to return our mock process
        $pool->expects($this->once())
            ->method('createProcess')
            ->willReturn($process);

        // Add a task and run
        $pool->add(function() { return 'test'; });
        $pool->run();

        // Check if process was added to running processes
        $runningProcesses = $this->getPrivateProperty($pool, 'runningProcesses');
        $this->assertCount(1, $runningProcesses);
        $this->assertSame($process, $runningProcesses[0]);

        // Check if task was removed from queue
        $queue = $this->getPrivateProperty($pool, 'queue');
        $this->assertCount(0, $queue);
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
    
    /**
     * Helper to set private property value
     */
    private function setPrivateProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
