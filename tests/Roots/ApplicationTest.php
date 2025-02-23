<?php

namespace Rose\Tests\Roots;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Rose\Roots\Application;
use stdClass;

class ApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        restore_error_handler(); // Reset error handlers
        restore_exception_handler(); // Reset exception handlers
    }

    public function testLocaleConfig(): void
    {
        $app = new Application();

        $app['config'] = $config = m::mock(stdClass::class);
        
        $config->shouldReceive('set')->once()->with('app.locale', 'foo');

        $app->setLocale('foo');
    }

    public function testUseConfigPath(): void
    {
        $app = new Application;
        $app->useConfigPath(__DIR__.'/fixtures/config');
        $app->bootstrapWith([\Rose\Roots\Bootstrap\LoadConfiguration::class]);

        dd($app);
        // TODO: Test all path configurations.
        // App.php
        // database.php
        // cache.php
        // logging.php
        // session.php

        $this->assertSame('bar', $app->make('config')->get('app.foo'));
    }
}
