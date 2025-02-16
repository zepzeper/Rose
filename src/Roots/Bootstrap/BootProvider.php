<?php

namespace Rose\Roots\Bootstrap;

use Rose\Roots\Application;

class BootProvider
{
    public function bootstrap(Application $app)
    {
        $app->boot();
    }
}
