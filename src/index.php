<?php

namespace Rose;

use Rose\Contracts\Session\Storage as SessionContract;
use Rose\Roots\Application;

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$app = Application::configure()
    ->create();

$app->make(\Rose\Roots\Http\Kernel::class);

$session = $app->get(SessionContract::class);

$session->set('preferences', ['theme' => 'dark']);
$session->set('preferences', ['theme' => 'dark']);
