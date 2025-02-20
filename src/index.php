<?php

namespace Rose;

use Rose\Roots\Application;
use Rose\Routing\Router;

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$app = Application::configure()
    ->withKernels()
    ->withProviders()
    ->create();

$kernel = $app->get(\Rose\Roots\Http\Kernel::class);


$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();


$router = $app->get(Router::class);

// Add routes within groups
$router->group(['prefix' => 'api', 'middleware' => ['auth']], function(Router $router) {
    $router->get('/users', 'UserController', 'index')
        ->name('users.index');
        
    $router->group(['prefix' => 'admin'], function(Router $router) {
        $router->get('/stats', 'AdminController', 'stats')
            ->name('stats');
    });
});

$router->get('/', 'TestController', 'aboeba', function (Router $router) {
    echo 'Werk dit ook?';
});


$response = $kernel->handle($request);

$session = $app->get('session');

dd($session);

$kernel->emit($response);


