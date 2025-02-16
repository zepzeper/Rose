<?php

namespace Rose\Contracts\Routing;

use Closure;

interface Register {

    /**
    * @param string $route
    * @param array $params
    * @param Closure|string|null $callback
    *
    * @return void
    */
    public function add($route, $params = [], $callback = null);

    public function dispatch(string $url);

    public function getParams(): array;

    public function getRoutes(): array;
}
