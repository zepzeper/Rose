<?php

namespace Rose\Contracts\Events;

use Closure;

interface Dispatcher
{
    /**
     * @param  Closure|string|array      $events
     * @param  Closure|string|array|null $listener
     * @return void
     */
    public function listen($events, $listener = null);

    /**
     * @param string $eventName
     * @return void
     */
    public function hasListeners($eventName);

    /**
     * @param object|string $subscriber
     * @return void
     */
    public function subscribe($subscriber);

    /**
     * @param  string|object $event
     * @param  mixed         $payload
     * @param  bool          $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt =false);

    /**
     * @param  string $event
     * @return void
     */
    public function flush($event);

    /**
     * @param  string $event
     * @return void
     */
    public function forget($event);
}
