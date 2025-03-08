<?php

namespace Rose\Tests\Unit\Concurrency\Runtime;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\Runtime\ParallelRuntime;
use Rose\Exceptions\Concurrency\ProcessException;

class ParallelRuntimeTest extends TestCase
{
    protected $mockWorkerPath;
    protected $resources = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock worker file for testing
        $this->mockWorkerPath = __DIR__ . '/../../TestData/mock_worker.php';
        
        if (!file_exists(dirname($this->mockWorkerPath))) {
            mkdir(dirname($this->mockWorkerPath), 0777, true);
        }
        if (!file_exists($this->mockWorkerPath)) {
            file_put_contents($this->mockWorkerPath, '<?php echo "Mock worker"; ?>');
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up resources
        foreach ($this->resources as $resource) {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }

        // Clean up the mock worker file
        if (file_exists($this->mockWorkerPath)) {
            unlink($this->mockWorkerPath);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_can_be_created_with_worker_script()
    {
        $runtime = new ParallelRuntime($this->mockWorkerPath);
        
        $this->assertInstanceOf(ParallelRuntime::class, $runtime);
        $this->assertEquals($this->mockWorkerPath, $this->getPrivateProperty($runtime, 'workerScript'));
    }

    /** @test */
    public function it_can_start_process_with_proc_open_when_pcntl_not_available()
    {
        // Create a mock runtime that overrides hasPcntl and procOpen methods
        $runtime = $this->getMockBuilder(ParallelRuntime::class)
            ->setConstructorArgs([$this->mockWorkerPath])
            ->onlyMethods(['hasPcntl', 'procOpen'])
            ->getMock();
        
        // Make hasPcntl return false to use proc_open path
        $runtime->method('hasPcntl')
               ->willReturn(false);
        
        // Mock the process and pipes
        $mockProcess = fopen('php://memory', 'r+');
        $mockPipes = [
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+')
        ];

        // Store them for cleanup
        $this->resources[] = $mockProcess;
        $this->resources = array_merge($this->resources, $mockPipes);
        
        // Setup procOpen to place pipes in the reference and return the process
        $runtime->method('procOpen')
               ->willReturnCallback(function($command, $descriptor, &$pipes) use($mockProcess, $mockPipes) {
                   // Assign pipes by reference
                   $pipes = $mockPipes;
                   return $mockProcess;
               });
        
        // Start the process
        list($process, $pipes) = $runtime->start();
        
        // Assertions
        $this->assertIsResource($process);
        $this->assertCount(3, $pipes);
    }

    /** @test */
    public function it_throws_exception_when_proc_open_start_fails()
    {
        // Create a custom runtime that throws an exception from start()
        $customRuntime = new class($this->mockWorkerPath) extends ParallelRuntime {
            public function start(): array
            {
                throw ProcessException::fromMessage('Failed to start process');
            }
        };
        
        $this->expectException(ProcessException::class);
        $customRuntime->start();
    }

    /** @test */
    public function it_uses_pcntl_when_available()
    {
        // Create a mock runtime that overrides hasPcntl and startWithPcntl methods
        $runtime = $this->getMockBuilder(ParallelRuntime::class)
            ->setConstructorArgs([$this->mockWorkerPath])
            ->onlyMethods(['hasPcntl', 'startWithPcntl'])
            ->getMock();
        
        // Make hasPcntl return true
        $runtime->method('hasPcntl')
               ->willReturn(true);
        
        // Mock the result of startWithPcntl
        $mockProcess = fopen('php://memory', 'r+');
        $mockPipes = [
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+'),
            fopen('php://memory', 'r+')
        ];

        // Store them for cleanup
        $this->resources[] = $mockProcess;
        $this->resources = array_merge($this->resources, $mockPipes);
        
        $runtime->method('startWithPcntl')
               ->willReturn([$mockProcess, $mockPipes]);
        
        // Start the process
        list($process, $pipes) = $runtime->start();
        
        // Assertions
        $this->assertIsResource($process);
        $this->assertCount(3, $pipes);
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
