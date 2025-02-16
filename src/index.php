<?php

namespace Rose;

use Rose\Contracts\Http\Kernel;
use Rose\Roots\Application;

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$app = Application::configure()
    ->withProviders([
            \Rose\Session\SessionServiceProvider::class
    ])
    ->create();

$app->make(Kernel::class);

dd($app);
