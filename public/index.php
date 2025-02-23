<?php

ob_start();

use Symfony\Component\HttpFoundation\Request;

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->handleRequest(Request::createFromGlobals());

$cache = $app->get('cache')->store();
$config = $app->get('config');
$cache->put('app-config', $config);

dd($app);
