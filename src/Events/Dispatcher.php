<?php

namespace Rose\Events;

use InvalidArgumentException;
use Closure;
use Rose\Contracts\Container\Container as ContainerContract;
use Rose\Contracts\Events\Dispatcher as DispatcherContract;
use Rose\Support\Album\Arr;
use ReflectionClass;
use ReflectionMethod;
use Rose\Support\Str;

class Dispatcher implements DispatcherContract
{
    protected ContainerContract $container;

    /**
     * Registered event listeners
     * Structure: ['eventName' => [listener1, listener2]]
     */
    protected array $listeners = [];

    /**
     * Wildcard event listeners
     * Structure: ['event.*' => [listener1, listener2]]
     */
    protected array $wildcards = [];

    /**
     * Sorted event listeners by priority
     */
    protected array $sorted = [];

    public function __construct(?ContainerContract $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch an event to its registered listeners
     * @param  string|object $event
     * @param  mixed         $payload
     * @param  bool          $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        // Check for method name typo (dispath -> dispatch)
        [$isObject, $eventName, $payload] = $this->parseEventPayload($event, $payload);

        $responses = [];

        foreach ($this->getListeners($eventName) as $listener) {
            $response = $this->callListener($listener, $event, $payload);

            // If halt is requested and response is not null, return first response
            if ($halt && !is_null($response)) {
                return $response;
            }

            // If response is false, stop propagation
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    public function flush($event)
    {
        $this->dispatch($event.'_pushed');
    }

    /**
     * Register an event listener
     */
    public function listen($events, $listener = null)
    {
        if ($events instanceof Closure) {
            // Register closure as wildcard listener
            $this->listen('*', $events);
        }

        foreach ((array) $events as $event) {
            // Separate wildcards from normal events
            if (str_contains($event, '*')) {
                $this->wildcards[$event][] = $listener;
            } else {
                $this->listeners[$event][] = $listener;
                unset($this->sorted[$event]);
            }
        }
    }

    /**
     * Check if any listeners exist for event
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]) || isset($this->wildcards[$eventName]);
    }

    /**
     * Register an event subscriber
     */
    public function subscribe($subscriber)
    {
        $subscriber = $this->resolveSubscriber($subscriber);

        $methods = (new ReflectionClass($subscriber))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->isStatic() || $method->isAbstract()) {
                continue;
            }

            $this->listen(
                $this->getEventNameFromMethod($subscriber, $method),
                [$subscriber, $method->getName()]
            );
        }
    }

    /**
     * Remove all listeners for an event
     */
    public function forget($event)
    {
        if (str_contains($event, '*')) {
            unset($this->wildcards[$event]);
        } else {
            unset($this->listeners[$event]);
        }
    }

    private function parseEventPayload($event, $payload): array
    {
        if (is_object($event)) {
            return [true, get_class($event), [$event]];
        }

        return [false, $event, Arr::wrap($payload)];
    }

    /**
     * Get all listeners for an event, including wildcards
     */
    private function getListeners(string $eventName): array
    {
        $listeners = $this->listeners[$eventName] ?? [];

        foreach ($this->wildcards as $wildcard => $wildcardListeners) {
            if (Str::is($wildcard, $eventName)) {
                $listeners = array_merge($listeners, $wildcardListeners);
            }
        }

        return $this->sortListeners($eventName, $listeners);
    }

    /**
     * Sort listeners by priority
     */
    private function sortListeners(string $event, array $listeners): array
    {
        if (!isset($this->sorted[$event])) {
            usort($listeners, function ($a, $b) {
                return $this->getListenerPriority($a) <=> $this->getListenerPriority($b);
            });

            $this->sorted[$event] = $listeners;
        }

        return $this->sorted[$event];
    }

    /**
     * Call a single listener with proper dependency injection
     */
    private function callListener($listener, $event, $payload)
    {
        // If listener is a closure, call it directly
        if ($listener instanceof Closure) {
            return $this->container->call($listener, compact('event', 'payload'));
        }

        // If listener is an array [class, method]
        if (is_array($listener)) {
            [$class, $method] = $listener;
            $instance = $this->container->make($class);

            return $this->container->call([$instance, $method], compact('event', 'payload'));
        }

        // If listener is a string class name
        if (is_string($listener)) {
            $instance = $this->container->make($listener);
            return $this->container->call($instance, compact('event', 'payload'));
        }

        throw new InvalidArgumentException('Invalid listener type');
    }

    /**
     * Resolve subscriber from container if needed
     */
    private function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }

        return $subscriber;
    }

    /**
     * Get event name from subscriber method
     * Conventions:
     * - onOrderCreated listens for OrderCreated
     * - handleOrderUpdated listens for OrderUpdated
     */
    private function getEventNameFromMethod($subscriber, ReflectionMethod $method): string
    {
        $methodName = $method->getName();

        // Remove "on" or "handle" prefix
        $event = preg_replace('/^(on|handle)/', '', $methodName);

        // Convert PascalCase to dotted notation (optional)
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $event));
    }

    /**
     * Get listener priority from docblock
     */
    private function getListenerPriority($listener): int
    {
        // Default priority if not specified
        return 0;
    }
}
