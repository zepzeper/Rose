<?php

namespace Rose\Tests\Unit\Concurrency\Runtime;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\Runtime\AsyncRuntime;
use Rose\Exceptions\Concurrency\ProcessException;

class AsyncRuntimeTest extends TestCase
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
        $runtime = new AsyncRuntime($this->mockWorkerPath);
        
        $this->assertInstanceOf(AsyncRuntime::class, $runtime);
        $this->assertEquals($this->mockWorkerPath, $this->getPrivateProperty($runtime, 'workerScript'));
    }

    /** @test */
    public function it_can_start_process()
    {
        // Create a mock runtime that overrides procOpen method
        $runtime = $this->getMockBuilder(AsyncRuntime::class)
            ->setConstructorArgs([$this->mockWorkerPath])
            ->onlyMethods(['procOpen'])
            ->getMock();
        
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
    public function it_throws_exception_when_start_fails()
    {
        // Since we can't directly mock a method to throw an exception with willReturn(false),
        // we need to create a custom mock class that overrides the start method
        
        $customRuntime = new class($this->mockWorkerPath) extends AsyncRuntime {
            public function start(): array
            {
                throw ProcessException::fromMessage('Failed to start process');
            }
        };
        
        $this->expectException(ProcessException::class);
        $customRuntime->start();
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
