<?php

namespace Rose\Session\Manager;

use Closure;
use Rose\Encryption\Encryption;
use Rose\Roots\Application;
use InvalidArgumentException;
use Rose\Session\Storage\NativeSessionHandler as RoseNativeSessionHandler;

class SessionManager
{
    protected array $handlers = [];

    public function __construct(protected Application $app)
    {
        $this->registerDefaultHandlers();
    }

    protected function registerDefaultHandlers(): void
    {
        // Update the closure to receive just the $app parameter
        $this->extend('native', function (Application $app) {
            $config = $app->make('config')->get('session');
            $encryptor = $app->make(Encryption::class);
            return new RoseNativeSessionHandler($app, $encryptor, $config);
        });
    }

    public function extend(string $driver, \Closure $callback): void
    {
        $this->handlers[$driver] = $callback;
    }

    public function driver(?string $driver = null)
    {
        $driver = $driver ?? $this->getDefaultDriver();

        if (!isset($this->handlers[$driver])) {
            throw new InvalidArgumentException("Session driver [$driver] not supported.");
        }

        // Pass only the app instance to the handler closure
        return $this->handlers[$driver]($this->app);
    }

    protected function getDefaultDriver(): string
    {
        return $this->app->make('config')->get('session.driver', 'native');
    }
}
