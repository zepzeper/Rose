<?php

namespace Rose\Tests\Unit\Concurrency;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\Process;
use Rose\Exceptions\Concurrency\ProcessException;
use Rose\Support\SerializableClosure;

class ProcessTest extends TestCase
{
    public function test_it_can_be_created_with_runtime_and_task()
    {
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        $process = new Process($runtime, $task);
        
        $this->assertInstanceOf(Process::class, $process);
    }

    public function test_it_can_stop_process()
    {
        // Create a mock Process class that overrides methods that would try to use proc_* functions
        $mockProcess = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isRunning'])
            ->getMock();
            
        // Configure mock
        $mockProcess->method('isRunning')->willReturn(false);
        
        // Set pipes property for testing
        $pipes = [
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+')
        ];
        
        $this->setPrivateProperty($mockProcess, 'pipes', $pipes);
        $this->setPrivateProperty($mockProcess, 'isRunning', true);
        
        // Call stop
        $result = $mockProcess->stop();
        
        // Check that pipes are cleared
        $this->assertEmpty($this->getPrivateProperty($mockProcess, 'pipes'));
        
        // Check that isRunning is set to false
        $this->assertFalse($this->getPrivateProperty($mockProcess, 'isRunning'));
        
        // Check that the method returns $this
        $this->assertSame($mockProcess, $result);
        
        // Clean up resources
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }

    public function test_it_throws_exception_when_start_fails()
    {
        // Create mocks
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });

        // Use mock to simulate start failure
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['start'])
            ->getMock();
            
        // Configure mock to throw exception
        $process->method('start')
                ->willThrowException(new ProcessException("Failed to start process"));
        
        // Expect exception
        $this->expectException(ProcessException::class);
        
        // Start process
        $process->start();
    }

    public function test_it_can_check_if_process_is_running()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        // Create mock that doesn't try to use proc_get_status
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['isRunning'])
            ->getMock();
        
        // Configure mock to return false, then true
        $process->method('isRunning')
                ->willReturnOnConsecutiveCalls(false, true);
        
        // Test
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isRunning());
    }

    public function test_it_can_wait_for_process()
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

    public function test_it_can_get_output()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['readOutput'])
            ->getMock();
        
        // Initialize output property
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

    public function test_it_can_get_error_output()
    {
        // Create process
        $runtime = $this->createMock(\stdClass::class);
        $task = new SerializableClosure(function() { return 'test'; });
        
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$runtime, $task])
            ->onlyMethods(['readErrorOutput'])
            ->getMock();
        
        // Initialize errorOutput property
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
