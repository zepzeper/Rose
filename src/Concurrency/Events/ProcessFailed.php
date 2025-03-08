<?php

namespace Rose\Concurrency\Events;

use Rose\Concurrency\Process;

class ProcessFailed
{
    public function __construct(public Process $process, public \Throwable $exception) {
    }
}
