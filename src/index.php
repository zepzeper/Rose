<?php

namespace Rose;

use Rose\Roots\Application;

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$app = Application::configure()
    ->create();

$app->make(\Rose\Roots\Http\Kernel::class);

dd($app);
