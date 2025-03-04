<?php

namespace Rose\Contracts\Routing\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request The incoming request
     * @param Closure $next The next middleware/handler in the pipeline
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
