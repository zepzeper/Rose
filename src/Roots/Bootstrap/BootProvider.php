<?php

namespace Rose\Roots\Bootstrap;

use Rose\Roots\Application;

class BootProvider
{
    public function Bootstrap(Application $app)
    {
        $app->boot();
    }
}
