<?php

namespace Rose\Contracts\Routing;

interface Route
{
    public function middleware(array|string $middleware): self;
    public function name(string $name): self;
    public function domain(string $domain): self;
    public function where(string $parameter, string $pattern): self;
}
