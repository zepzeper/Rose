<?php

namespace Rose\Tests\Unit\Roots\Bootstrap;

use PHPUnit\Framework\TestCase;
use Rose\Roots\Application;
use Rose\Roots\Bootstrap\BootProvider;

class BootProviderTest extends TestCase
{
    public function test_it_calls_application_boot()
    {
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['boot'])
            ->getMock();
            
        $app->expects($this->once())
            ->method('boot');
            
        $bootstrapper = new BootProvider();
        $bootstrapper->bootstrap($app);
    }
}
