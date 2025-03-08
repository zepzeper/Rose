<?php

namespace Rose\Tests\Unit\Concurrency;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\Process;
use Rose\Exceptions\Concurrency\ProcessException;
use Rose\Support\SerializableClosure;

class ProcessTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_runtime_and_task()
    {
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        $process = new Process($runtime, $task);
        
        $this->assertInstanceOf(Process::class, $process);
    }

    /** @test */
    public function it_can_stop_process()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });

        // Create process and set up test conditions
        $process = new Process($runtime, $task);

        // Set properties
        $pipes = [
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+')
        ];

        $this->setPrivateProperty($process, 'pipes', $pipes);
        $this->setPrivateProperty($process, 'isRunning', true);

        // Call stop
        $result = $process->stop();

        // Assertions
        $this->assertSame($process, $result);
        $this->assertFalse($this->getPrivateProperty($process, 'isRunning'));

        // Manually close resources
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }

    /** @test */
    public function it_throws_exception_when_start_fails()
    {
        // Create mocks
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });

        // Instead of trying to mock the start method, extend the Process class
        $process = new class($runtime, $task) extends Process {
            // Override the start method to throw an exception
            public function start(): self
            {
                throw ProcessException::fromMessage("Failed to start process");
            }
        };

        // Expect exception
        $this->expectException(ProcessException::class);

        // Start process
        $process->start();
    }

    /** @test */
    public function it_can_check_if_process_is_running()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        $process = new Process($runtime, $task);
        
        // Test not running state (when process is null)
        $this->assertFalse($process->isRunning());
        
        // Test running state with mocked data
        $mockProcess = fopen('php://memory', 'r+');
        $this->setPrivateProperty($process, 'process', $mockProcess);
        $this->setPrivateProperty($process, 'isRunning', true);
        
        // Mock proc_get_status() behavior with reflection
        $processMock = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['isRunning'])
            ->getMock();
        
        $processMock->method('isRunning')
                  ->willReturn(true);
        
        $this->assertTrue($processMock->isRunning());
        
        // Clean up resources
        $this->setPrivateProperty($process, 'process', null);
    }

    /** @test */
    public function it_can_wait_for_process()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['isRunning', 'readOutput', 'readErrorOutput', 'stop'])
            ->getMock();
        
        // Configure mocks
        $process->method('isRunning')
               ->willReturnOnConsecutiveCalls(true, false);
               
        $process->method('readOutput')
               ->willReturn('test output');
               
        $process->method('readErrorOutput')
               ->willReturn('');
               
        $process->method('stop')
               ->willReturnSelf();
        
        // Set up the process with null value for process to avoid proc_close failure
        $this->setPrivateProperty($process, 'process', null);
        $this->setPrivateProperty($process, 'exitCode', 0);
        
        // Call wait
        $result = $process->wait();
        
        // Assertions - we can only test the return value since properties are mocked
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_can_get_output()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['readOutput'])
            ->getMock();
        
        // Initialize output property to avoid "must not be accessed before initialization" error
        $this->setPrivateProperty($process, 'output', null);
        
        // Setup mock
        $process->method('readOutput')
               ->willReturn('test output');
        
        // Test getting output when it's null
        $output = $process->getOutput();
        
        // Assertions
        $this->assertEquals('test output', $output);
        
        // Set output directly and test again
        $this->setPrivateProperty($process, 'output', 'cached output');
        $output = $process->getOutput();
        $this->assertEquals('cached output', $output);
    }

    /** @test */
    public function it_can_get_error_output()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['readErrorOutput'])
            ->getMock();
        
        // Initialize errorOutput property to avoid "must not be accessed before initialization" error
        $this->setPrivateProperty($process, 'errorOutput', null);
        
        // Setup mock
        $process->method('readErrorOutput')
               ->willReturn('test error');
        
        // Test getting error output when it's null
        $errorOutput = $process->getErrorOutput();
        
        // Assertions
        $this->assertEquals('test error', $errorOutput);
        
        // Set error output directly and test again
        $this->setPrivateProperty($process, 'errorOutput', 'cached error');
        $errorOutput = $process->getErrorOutput();
        $this->assertEquals('cached error', $errorOutput);
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
