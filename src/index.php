<?php

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

use Rose\Roots\Application;
use Rose\Roots\Bootstrap\BootProvider as Bootstrap;

$app = new Application();

(new Bootstrap())->Bootstrap($app);
