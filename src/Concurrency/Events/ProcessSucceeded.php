<?php

namespace Rose\Concurrency\Events;

use Rose\Concurrency\Process;

class ProcessSucceeded
{
    public function __construct(public Process $process, public mixed $output) {
    }
}
