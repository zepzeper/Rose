<?php

use Rose\Controllers\AdminController;
use Rose\Controllers\Index;
use Rose\Roots\Application;
use Rose\Routing\Router;

return function (Router $router) {
    
    // Debug: Log route registration
    $router->get('/', Index::class, 'index');

    $router->get('/huts/fluts', Index::class, 'test');

    // Add routes within groups
    $router->group(['prefix' => 'api', 'middleware' => ['auth']], function(Router $router) {
        $router->get('/users', 'UserController', 'index')
            ->name('users.index');

        $router->group(['prefix' => 'admin'], function(Router $router) {
            $router->get('/stats', AdminController::class, 'index')
                ->name('stats');
        });
    });

    $router->get('/oehbahah', 'TestController', 'aboeba', function () {
        return 'Werk dit ook?';
    });
};
