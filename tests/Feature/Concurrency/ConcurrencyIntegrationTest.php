<?php

namespace Rose\Tests\Feature\Concurrency;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\Pool;
use Rose\Concurrency\Events\ProcessSucceeded;
use Rose\Concurrency\Events\ProcessFailed;

class ConcurrencyIntegrationTest extends TestCase
{
    /** @test */
    public function it_can_run_multiple_tasks_in_async_pool()
    {
        $results = [];
        $expectedResults = [2, 4, 6, 8, 10];
        
        $pool = Pool::async(3);
        
        // Add some tasks that will succeed
        foreach ([1, 2, 3, 4, 5] as $num) {
            $pool->add(function() use ($num) {
                // Simulate work
                usleep(100000);
                return $num * 2;
            });
        }
        
        // Register success callback
        $pool->whenTaskSucceeded(function(ProcessSucceeded $event) use (&$results) {
            $results[] = $event->output;
        });
        
        // Run tasks and wait for them to complete
        $pool->run()->wait();
        
        // Sort results for consistent comparison
        sort($results);
        
        // Assert all tasks were executed
        $this->assertEquals($expectedResults, $results);
        $this->assertTrue($pool->status()->isFinished());
        $this->assertTrue($pool->status()->isSuccessful());
    }

    /** @test */
    public function it_handles_failed_tasks_properly()
    {
        $successResults = [];
        $failureResults = [];
        
        $pool = Pool::async(2);
        
        // Add a task that will succeed
        $pool->add(function() {
            return 'success';
        });
        
        // Add a task that will fail
        $pool->add(function() {
            throw new \Exception('Task failed');
            return 'never reached';
        });
        
        // Register callbacks
        $pool->whenTaskSucceeded(function(ProcessSucceeded $event) use (&$successResults) {
            $successResults[] = $event->output;
        });
        
        $pool->whenTaskFailed(function(ProcessFailed $event) use (&$failureResults) {
            $failureResults[] = $event->exception->getMessage();
        });
        
        // Run tasks and wait for them to complete
        $pool->run()->wait();
        
        // Assert results
        $this->assertEquals(['success'], $successResults);
        $this->assertEquals(['Task failed'], $failureResults);
        $this->assertTrue($pool->status()->isFinished());
        $this->assertFalse($pool->status()->isSuccessful());
    }

    /** @test */
    public function it_respects_concurrency_limit()
    {
        $startTimes = [];
        $endTimes = [];
        
        // Use a small concurrency value
        $pool = Pool::async(2);
        
        // Add more tasks than the concurrency limit
        for ($i = 0; $i < 6; $i++) {
            $pool->add(function() use ($i, &$startTimes, &$endTimes) {
                $startTimes[$i] = microtime(true);
                // Each task takes some time
                usleep(200000);
                $endTimes[$i] = microtime(true);
                return $i;
            });
        }
        
        // Run and wait
        $pool->run()->wait();
        
        // Check if tasks were executed in batches based on concurrency
        $this->assertCount(6, $startTimes);
        $this->assertCount(6, $endTimes);
        
        // Sort times by task start time
        asort($startTimes);
        $taskOrder = array_keys($startTimes);
        
        // The first two tasks should start close together
        $this->assertLessThan(0.1, $startTimes[$taskOrder[1]] - $startTimes[$taskOrder[0]]);
        
        // The third task should start after at least one of the first two has finished
        $this->assertGreaterThan(
            min($endTimes[$taskOrder[0]], $endTimes[$taskOrder[1]]),
            $startTimes[$taskOrder[2]]
        );
    }

    /** @test */
    public function it_can_process_large_data_in_parallel()
    {
        $pool = Pool::async(4);
        $data = range(1, 1000);
        $results = [];
        
        // Split data into chunks
        $chunks = array_chunk($data, 250);
        
        foreach ($chunks as $chunk) {
            $pool->add(function() use ($chunk) {
                return array_sum($chunk);
            });
        }
        
        // Collect results
        $pool->whenTaskSucceeded(function(ProcessSucceeded $event) use (&$results) {
            $results[] = $event->output;
        });
        
        // Run and wait
        $pool->run()->wait();
        
        // Total should match sum of all numbers from 1 to 1000
        $this->assertEquals(500500, array_sum($results));
    }
}
