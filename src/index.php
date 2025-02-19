<?php

namespace Rose;

use Rose\Roots\Application;
use Rose\Routing\Router;
use Rose\Routing\RouterParameters;
use Symfony\Component\HttpFoundation\Request;

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$app = Application::configure()
    ->create();

$router = $app->get(Router::class);

$router->add('/test', '', '', function () {
    echo 'Does this work?';
    die;
});

dd($router->getMiddleware());

$response = $app->get(\Rose\Roots\Http\Kernel::class)->handle(new Request());


dd($response);
