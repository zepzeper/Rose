<?php

namespace Rose\Contracts\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface Kernel
{
    /**
     * Bootstrap the application for Http requests.
     *
     * @return void;
     */
    public function bootstrap();

    /**
     * Handle incoming requets.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response;

    /**
     * Latest actions before sending the request to the client.
     *
     * @param Response $response
     * @return Response
     */
    public function emit(Response $response): Response;

    /*
     * Perform any last action for the lifecycle of the request
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void;
}
