<?php

namespace Rose\Contracts\Pipeline;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface Pipeline
{
    /**
     * Set the array of pipes.
     *
     * @param  array|string  $pipes
     * @return $this
     */
    public function through($pipes);

   /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return Response
     */
    public function then(Request $request, \Closure $destination): Response;
}
