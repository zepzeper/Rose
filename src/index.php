<?php

namespace Rose;

use Rose\Roots\Application;
use Rose\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

$app = Application::configure()
    ->create();

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

dd($router);

$response = $app->get(\Rose\Roots\Http\Kernel::class)->handle(new Request());
