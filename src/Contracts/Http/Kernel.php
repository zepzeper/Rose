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
     * @param Request $request
     * @return Response
     */
    /*public function emit(Request $request): Response;*/



}
