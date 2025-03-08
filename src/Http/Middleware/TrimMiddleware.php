<?php

namespace Rose\Http\Middleware;

use Rose\Contracts\Routing\Middleware\Middleware as MiddlewareContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TrimMiddleware implements MiddlewareContract
{
    public function handle(Request $request, \Closure $next): Response
    {
        $data = $request->getPayload();

        if (is_array($data)) {
            $data = array_map(function ($value) {
                return is_string($value) ? trim($value) : $value;
            }, $data);
        }

        $response = $next($data);

        return $response;
    }
}
