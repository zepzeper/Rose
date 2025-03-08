<?php

namespace Rose\Tests\Unit\Concurrency;

use PHPUnit\Framework\TestCase;
use Rose\Concurrency\PoolStatus;

class PoolStatusTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_values()
    {
        $status = new PoolStatus(5, 2, 3, 1);
        
        $this->assertInstanceOf(PoolStatus::class, $status);
        $this->assertEquals(5, $status->getPending());
        $this->assertEquals(2, $status->getRunning());
        $this->assertEquals(3, $status->getSuccessful());
        $this->assertEquals(1, $status->getFailed());
    }

    /** @test */
    public function it_can_get_total_processes()
    {
        $status = new PoolStatus(5, 2, 3, 1);
        
        $this->assertEquals(11, $status->getTotal());
    }

    /** @test */
    public function it_can_check_if_all_processes_finished()
    {
        // Not finished: has pending tasks
        $status1 = new PoolStatus(5, 0, 3, 1);
        $this->assertFalse($status1->isFinished());
        
        // Not finished: has running tasks
        $status2 = new PoolStatus(0, 2, 3, 1);
        $this->assertFalse($status2->isFinished());
        
        // Finished: no pending or running tasks
        $status3 = new PoolStatus(0, 0, 3, 1);
        $this->assertTrue($status3->isFinished());
    }

    /** @test */
    public function it_can_check_if_all_processes_are_successful()
    {
        // Not successful: not finished
        $status1 = new PoolStatus(5, 0, 3, 1);
        $this->assertFalse($status1->isSuccessful());
        
        // Not successful: has failed tasks
        $status2 = new PoolStatus(0, 0, 3, 1);
        $this->assertFalse($status2->isSuccessful());
        
        // Successful: finished and no failures
        $status3 = new PoolStatus(0, 0, 3, 0);
        $this->assertTrue($status3->isSuccessful());
    }
}
